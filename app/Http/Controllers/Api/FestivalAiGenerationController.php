<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessFestivalAiGeneration;
use App\Models\AiImageModel;
use App\Models\FestivalAiConfig;
use App\Models\FestivalAiGeneration;
use App\Models\Festivals;
use App\Models\Language;
use App\Models\Product;
use App\Models\StorageSetting;
use App\Models\Subscription;
use App\Models\SubscriptionAiImageAccess;
use App\Models\User;
use App\Services\FestivalAiBusinessContextService;
use App\Services\FestivalAiPromptCompiler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class FestivalAiGenerationController extends Controller
{
    public function __construct(
        private FestivalAiBusinessContextService $businessContext,
        private FestivalAiPromptCompiler $promptCompiler
    ) {
    }

    public function options(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $plan = $user->active_subscription;
        $accesses = $plan ? $this->activeAccesses($plan) : collect();
        $accessesByModel = $accesses->keyBy('ai_image_model_id');
        $models = AiImageModel::query()
            ->where('is_active', true)
            ->orderByDesc('is_recommended')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $languages = Language::query()
            ->where('status', true)
            ->orderBy('title')
            ->get(['id', 'title']);

        $festivals = Festivals::query()
            ->whereHas('aiConfig', fn ($query) => $query->where('is_enabled', true)->whereNotNull('base_prompt'))
            ->with(['aiConfig.styles' => fn ($query) => $query->where('status', true)->orderBy('sort_order')->orderBy('id')])
            ->where('status', 1)
            ->orderBy('festivals_date')
            ->get()
            ->map(function (Festivals $festival) {
                $config = $festival->aiConfig;

                return [
                    'id' => $festival->id,
                    'title' => $festival->title,
                    'image_url' => $this->assetUrl($festival->image),
                    'max_products' => $config->max_products,
                    'max_user_instruction_characters' => $config->max_user_instruction_characters,
                    'allow_product_upload' => (bool) $config->allow_product_upload,
                    'require_product_name_for_upload' => (bool) $config->require_product_name_for_upload,
                    'styles' => $config->styles->map(function ($style) use ($config) {
                        return [
                            'id' => $style->id,
                            'name' => $style->name,
                            'preview_images' => array_map(fn ($path) => $this->assetUrl($path), (array) $style->preview_images),
                            'allowed_size_keys' => $this->allowedSizeKeys($config, $style),
                            'product_required' => $style->product_required,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'quota' => [
                'limit' => (int) optional($plan)->ai_image_limit,
                'used' => (int) $user->ai_image_used,
                'remaining' => $user->getRemainingUsage('ai_image'),
            ],
            // Every enabled model is visible. The per-plan mapping only decides
            // whether a particular variant can be selected for generation.
            'models' => $models->map(fn (AiImageModel $model) => $this->modelPayload(
                $model,
                $accessesByModel->get($model->id)
            ))->values(),
            'languages' => $languages->map(fn (Language $language) => [
                'id' => $language->id,
                'title' => $language->title,
            ])->values(),
            'festivals' => $festivals,
        ]);
    }

    public function create(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'festival_id' => ['required', 'integer'],
            'style_id' => ['required', 'integer'],
            'model_id' => ['required', 'integer'],
            'quality' => ['required', 'string', 'max:30'],
            'size_key' => ['required', 'string', 'max:50'],
            'language_id' => [
                'required',
                'integer',
                Rule::exists('language', 'id')->where('status', true),
            ],
            'user_instruction' => ['nullable', 'string', 'max:1000'],
            'product_mode' => ['nullable', 'string', Rule::in(['choose', 'upload', 'none'])],
            'product_ids' => ['nullable', 'array', 'max:3'],
            'product_ids.*' => ['integer', 'distinct'],
            'uploaded_product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'uploaded_product_name' => ['nullable', 'string', 'max:150'],
        ]);

        $config = FestivalAiConfig::with([
            'styles' => fn ($query) => $query->where('status', true),
            'brandChromePreset',
        ])
            ->where('festival_id', $validated['festival_id'])
            ->where('is_enabled', true)
            ->whereNotNull('base_prompt')
            ->first();

        if (!$config) {
            return $this->error('This festival is not available for AI generation.', Response::HTTP_NOT_FOUND);
        }

        $style = $config->styles->firstWhere('id', (int) $validated['style_id']);
        if (!$style) {
            return $this->error('The selected festival style is unavailable.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $language = Language::query()
            ->where('status', true)
            ->find($validated['language_id']);
        if (!$language) {
            return $this->error('The selected text language is unavailable.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $productIds = array_values($validated['product_ids'] ?? []);
        $products = Product::where('user_id', $user->id)->whereIn('id', $productIds)->get();
        $uploadedProductImage = $request->file('uploaded_product_image');
        $productMode = $validated['product_mode'] ?? ($uploadedProductImage ? 'upload' : ($products->isNotEmpty() ? 'choose' : 'none'));
        if ($products->count() !== count($productIds)) {
            return $this->error('One or more selected products are unavailable.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($products->isNotEmpty() && $uploadedProductImage) {
            return $this->error('Choose an existing product or upload a product photo, not both.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($productMode === 'none' && ($products->isNotEmpty() || $uploadedProductImage)) {
            return $this->error('Remove the product selection or choose the matching product source.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($productMode === 'choose' && $uploadedProductImage) {
            return $this->error('Choose mode accepts saved products only.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($productMode === 'upload' && $products->isNotEmpty()) {
            return $this->error('Upload mode accepts one uploaded product photo only.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($productMode === 'upload' && !$uploadedProductImage) {
            return $this->error('Upload a product photo before generating your visual.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($productMode === 'upload' && !$config->allow_product_upload) {
            return $this->error('Product photo upload is not enabled for this festival.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (
            $productMode === 'upload'
            && $config->require_product_name_for_upload
            && blank($validated['uploaded_product_name'] ?? null)
        ) {
            return $this->error('Enter the uploaded product name before generating.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($productMode === 'choose' && $products->isEmpty()) {
            return $this->error('Choose at least one product before generating your visual.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($style->product_required && $products->isEmpty() && !$uploadedProductImage) {
            return $this->error('This style requires at least one product.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($products->contains(fn (Product $product) => blank($product->image))) {
            return $this->error('Each selected product needs an image before it can be used for Festival AI.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (mb_strlen((string) ($validated['user_instruction'] ?? '')) > $config->max_user_instruction_characters) {
            return $this->error('Your instruction is longer than this festival allows.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $storedUploadedProductPath = null;
        try {
            $generation = DB::transaction(function () use (
                $user,
                $validated,
                $config,
                $style,
                $language,
                $products,
                $uploadedProductImage,
                &$storedUploadedProductPath
            ) {
                $lockedUser = User::lockForUpdate()->findOrFail($user->id);
                $lockedUser->resetLimitsIfNeeded();
                $plan = $lockedUser->active_subscription;

                if (!$plan) {
                    throw new \DomainException('No active plan is available for AI image generation.');
                }

                $access = $this->activeAccesses($plan)
                    ->firstWhere('ai_image_model_id', (int) $validated['model_id']);

                if (!$access) {
                    throw new \DomainException('This model is not included in your current plan.');
                }

                $model = $access->imageModel;
                if (!$model || $model->provider !== 'openai') {
                    throw new \DomainException('This model is not available for Festival AI yet.');
                }

                if (!in_array($validated['quality'], (array) $access->allowed_qualities, true)) {
                    throw new \DomainException('This quality is not included in your current plan.');
                }

                $sizeOptions = collect((array) $model->size_options)->keyBy('key');
                $allowedSizes = array_values(array_intersect(
                    $this->allowedSizeKeys($config, $style),
                    (array) $access->allowed_size_keys,
                    $sizeOptions->keys()->all()
                ));
                if (!in_array($validated['size_key'], $allowedSizes, true)) {
                    throw new \DomainException('This size is not available for the selected festival, model, and plan.');
                }

                $maxProducts = min(
                    (int) $config->max_products,
                    (int) $access->max_reference_images,
                    (int) $model->max_reference_images
                );
                $referenceCount = $products->count() + ($uploadedProductImage ? 1 : 0);
                if ($referenceCount > $maxProducts) {
                    throw new \DomainException('This model and plan allow up to ' . $maxProducts . ' product images for this generation.');
                }
                if ($referenceCount > 0 && !$model->supports_reference_images) {
                    throw new \DomainException('The selected model does not accept product reference images.');
                }
                if ($referenceCount > 0 && !$model->supports_edits) {
                    throw new \DomainException('The selected model cannot edit product reference images.');
                }

                $limit = (int) $plan->ai_image_limit;
                if ((int) $lockedUser->ai_image_used >= $limit) {
                    throw new \DomainException('Your AI image generation quota has been used. Please upgrade your plan.');
                }

                $lockedUser->increment('ai_image_used');
                $size = $sizeOptions->get($validated['size_key']);
                $productSnapshot = $products->map(fn (Product $product) => [
                    'id' => $product->id,
                    'title' => $product->display_name,
                    'description' => $product->description,
                    'image' => $product->image,
                ])->values();

                if ($uploadedProductImage) {
                    $uploadedProductName = trim((string) ($validated['uploaded_product_name'] ?? ''));
                    if ($uploadedProductName === '') {
                        $uploadedProductName = trim((string) pathinfo(
                            $uploadedProductImage->getClientOriginalName(),
                            PATHINFO_FILENAME
                        )) ?: 'Uploaded product';
                    }
                    $storedUploadedProductPath = $this->storeUploadedProductImage($uploadedProductImage);
                    $productSnapshot->push([
                        'id' => 'uploaded-product',
                        'title' => $uploadedProductName,
                        'description' => 'User uploaded product reference image.',
                        'image' => $storedUploadedProductPath,
                    ]);
                }

                $businessSnapshot = $this->businessContext->snapshotForUser($lockedUser);
                $brandChromeSnapshot = $this->brandChromeSnapshot($config);
                $compiled = $this->promptCompiler->compile(
                    $config,
                    $style,
                    $language,
                    $productSnapshot,
                    $validated['user_instruction'] ?? null,
                    $businessSnapshot,
                    $brandChromeSnapshot,
                    [
                        'size_key' => $validated['size_key'],
                        'size_value' => $size['size'],
                    ]
                );
                $brandLogoExpected = (bool) ($brandChromeSnapshot['overlay_enabled'] ?? true)
                    && filled($businessSnapshot['logo_path'] ?? null);
                $expectedReferenceCount = $productSnapshot->count() + ($brandLogoExpected ? 1 : 0);
                $requestDiagnostics = array_merge($compiled['diagnostics'], [
                    'provider' => $model->provider,
                    'model' => $model->model_id,
                    'quality' => $validated['quality'],
                    'size' => $size['size'],
                    'planned_endpoint' => $expectedReferenceCount > 0
                        ? '/v1/images/edits'
                        : '/v1/images/generations',
                    'expected_reference_count' => $expectedReferenceCount,
                    'expected_product_reference_count' => $productSnapshot->count(),
                    'expected_brand_logo_reference' => $brandLogoExpected,
                    'attached_reference_count' => 0,
                ]);

                return FestivalAiGeneration::create([
                    'request_id' => (string) Str::uuid(),
                    'user_id' => $lockedUser->id,
                    'subscription_id' => $plan->id,
                    'festival_id' => $config->festival_id,
                    'language_id' => $language->id,
                    'festival_ai_style_id' => $style->id,
                    'ai_image_model_id' => $model->id,
                    'provider' => $model->provider,
                    'provider_model_id' => $model->model_id,
                    'quality' => $validated['quality'],
                    'size_key' => $validated['size_key'],
                    'size_value' => $size['size'],
                    'user_instruction' => $validated['user_instruction'] ?? null,
                    'final_prompt' => $compiled['prompt'],
                    'request_diagnostics' => $requestDiagnostics,
                    'product_snapshot' => $productSnapshot->all(),
                    'business_snapshot' => $businessSnapshot,
                    'brand_chrome_snapshot' => $brandChromeSnapshot,
                    'status' => 'queued',
                    'quota_reserved_at' => now(),
                ]);
            });
        } catch (\DomainException $exception) {
            $this->deleteUploadedProductImage($storedUploadedProductPath);
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\RuntimeException $exception) {
            if (!str_starts_with($exception->getMessage(), 'Uploaded Festival AI product image')) {
                throw $exception;
            }
            $this->deleteUploadedProductImage($storedUploadedProductPath);
            report($exception);
            return $this->error(
                'The product photo could not be stored. Please try again; no quota was used.',
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        } catch (\Throwable $exception) {
            $this->deleteUploadedProductImage($storedUploadedProductPath);
            throw $exception;
        }

        try {
            ProcessFestivalAiGeneration::dispatch($generation->id)
                ->onConnection('festival-ai')
                ->onQueue('festival-ai');
        } catch (\Throwable $exception) {
            // Do not leave a charged, permanently queued generation behind
            // when the queue service itself is unavailable.
            DB::transaction(function () use ($generation) {
                $lockedGeneration = FestivalAiGeneration::lockForUpdate()->find($generation->id);
                if (!$lockedGeneration || $lockedGeneration->quota_refunded_at) {
                    return;
                }

                $lockedUser = User::lockForUpdate()->find($lockedGeneration->user_id);
                if ($lockedUser) {
                    $lockedUser->ai_image_used = max(0, (int) $lockedUser->ai_image_used - 1);
                    $lockedUser->save();
                }

                $lockedGeneration->update([
                    'status' => 'failed',
                    'error_code' => 'queue_unavailable',
                    'error_message' => 'Festival AI queue is temporarily unavailable. Please try again. Your quota was restored.',
                    'quota_refunded_at' => now(),
                    'completed_at' => now(),
                ]);
            });

            report($exception);
            $this->deleteUploadedProductImage($storedUploadedProductPath);

            return $this->error('Festival AI queue is temporarily unavailable. Please try again. Your quota was restored.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your AI image is queued for generation.',
            'job' => $this->jobPayload($generation),
        ], Response::HTTP_ACCEPTED);
    }

    public function show(Request $request, FestivalAiGeneration $festivalAiGeneration)
    {
        $user = $this->authenticatedUser($request);
        if ($festivalAiGeneration->user_id !== $user->id) {
            return $this->error('This AI generation is unavailable.', Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'job' => $this->jobPayload($festivalAiGeneration),
        ]);
    }

    public function history(Request $request)
    {
        $user = $this->authenticatedUser($request);

        // Deliberately return only presentation-safe fields from the user's own
        // recent requests. Prompts, selected products and business snapshots
        // remain private server-side records.
        $jobs = FestivalAiGeneration::query()
            ->where('user_id', $user->id)
            ->with([
                'festival:id,title',
                'style:id,name',
                'imageModel:id,display_name',
            ])
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (FestivalAiGeneration $generation) => $this->jobPayload($generation))
            ->values();

        return response()->json([
            'success' => true,
            'jobs' => $jobs,
        ]);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = auth('sanctum')->user();
        $accessToken = $user ? $user->currentAccessToken() : null;

        if (!$user || !$accessToken || !$accessToken->can('mobile:access')) {
            abort(Response::HTTP_UNAUTHORIZED, 'Please sign in again to use Festival AI.');
        }

        if ($accessToken->expires_at && now()->greaterThanOrEqualTo($accessToken->expires_at)) {
            $accessToken->delete();
            abort(Response::HTTP_UNAUTHORIZED, 'Your session has expired. Please sign in again.');
        }

        if ($request->filled('userId') && (int) $request->input('userId') !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'The signed-in user does not match this request.');
        }

        return $user;
    }

    private function activeAccesses(Subscription $plan)
    {
        return SubscriptionAiImageAccess::with('imageModel')
            ->where('subscription_id', $plan->id)
            ->where('status', true)
            ->whereHas('imageModel', fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->get();
    }

    private function modelPayload(AiImageModel $model, ?SubscriptionAiImageAccess $access): array
    {
        $allowedQualities = $access ? array_values((array) $access->allowed_qualities) : [];
        $allowedSizeKeys = $access ? (array) $access->allowed_size_keys : [];

        return [
            'id' => $model->id,
            'display_name' => $model->display_name,
            'provider' => $model->provider,
            'model_id' => $model->model_id,
            'description' => $model->description,
            'default_quality' => $model->default_quality,
            'qualities' => array_values((array) $model->quality_options),
            'quality_variants' => collect((array) $model->quality_options)
                ->map(fn (string $quality) => [
                    'key' => $quality,
                    'display_name' => data_get($model->quality_display_names, $quality, $this->qualityLabel($quality)),
                    'is_available' => in_array($quality, $allowedQualities, true),
                ])
                ->values(),
            'sizes' => collect((array) $model->size_options)
                ->filter(fn ($size) => $access && in_array($size['key'] ?? null, $allowedSizeKeys, true))
                ->values(),
            'max_product_images' => $access ? min((int) $access->max_reference_images, (int) $model->max_reference_images) : 0,
            'allow_refinement' => $access && (bool) $access->allow_refinement && (bool) $model->supports_edits,
        ];
    }

    private function allowedSizeKeys(FestivalAiConfig $config, $style): array
    {
        $festivalSizes = array_values((array) $config->allowed_size_keys);
        $styleSizes = array_values((array) $style->allowed_size_keys);

        return empty($styleSizes)
            ? $festivalSizes
            : array_values(array_intersect($festivalSizes, $styleSizes));
    }

    private function qualityLabel(string $quality): string
    {
        return [
            'low' => 'Fast (Low)',
            'medium' => 'Standard (Medium)',
            'high' => 'High Quality',
            'auto' => 'Auto',
            'standard' => 'Standard',
            'hd' => 'HD',
        ][$quality] ?? ucfirst($quality);
    }

    private function brandChromeSnapshot(FestivalAiConfig $config): array
    {
        $preset = $config->brandChromePreset;
        if (!$preset || !$preset->status) {
            return [];
        }

        return [
            'preset_id' => $preset->id,
            'name' => $preset->name,
            'header_prompt' => trim((string) $preset->header_prompt),
            'footer_prompt' => trim((string) $preset->footer_prompt),
            'overlay_enabled' => (bool) $preset->overlay_enabled,
            'header_height_percent' => (int) $preset->header_height_percent,
            'footer_height_percent' => (int) $preset->footer_height_percent,
            'panel_style' => $preset->panel_style,
            'logo_position' => $preset->logo_position,
            'text_tone' => $preset->text_tone,
            'max_contact_items' => (int) $preset->max_contact_items,
        ];
    }

    private function storeUploadedProductImage($file): string
    {
        $fileName = Str::uuid() . '.' . $file->extension();
        $relativePath = 'festival_ai_uploads/' . $fileName;

        try {
            if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
                $contents = file_get_contents($file->getRealPath());
                $stored = $contents !== false
                    && Storage::disk('spaces')->put('uploads/' . $relativePath, $contents, 'public');
                if (!$stored) {
                    throw new \RuntimeException('Uploaded Festival AI product image could not be stored.');
                }
            } else {
                $directory = public_path('uploads/festival_ai_uploads');
                File::ensureDirectoryExists($directory);
                $file->move($directory, $fileName);
                if (!is_file($directory . DIRECTORY_SEPARATOR . $fileName)) {
                    throw new \RuntimeException('Uploaded Festival AI product image could not be stored.');
                }
            }
        } catch (\Throwable $exception) {
            if ($exception instanceof \RuntimeException
                && str_starts_with($exception->getMessage(), 'Uploaded Festival AI product image')) {
                throw $exception;
            }
            throw new \RuntimeException(
                'Uploaded Festival AI product image could not be stored.',
                0,
                $exception
            );
        }

        return $relativePath;
    }

    private function deleteUploadedProductImage(?string $relativePath): void
    {
        if (!$relativePath || !str_starts_with($relativePath, 'festival_ai_uploads/')) {
            return;
        }

        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            Storage::disk('spaces')->delete('uploads/' . $relativePath);
            return;
        }

        foreach ([public_path('uploads/' . $relativePath), base_path('uploads/' . $relativePath)] as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function jobPayload(FestivalAiGeneration $generation): array
    {
        return [
            'id' => $generation->id,
            'request_id' => $generation->request_id,
            'status' => $generation->status,
            // Build local URLs from the current API request host. This lets an
            // Android device on the LAN load the completed image instead of
            // receiving an unusable localhost URL from cached configuration.
            'image_url' => $this->assetUrl($generation->generated_image_path),
            'error_message' => $generation->status === 'failed' ? $generation->error_message : null,
            'created_at' => optional($generation->created_at)->toIso8601String(),
            'completed_at' => optional($generation->completed_at)->toIso8601String(),
            'festival_title' => optional($generation->festival)->title,
            'style_name' => optional($generation->style)->name,
            'model_name' => optional($generation->imageModel)->display_name,
        ];
    }

    private function assetUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            return Storage::disk('spaces')->url('uploads/' . ltrim($path, '/'));
        }

        // `asset()` may use the cached APP_URL (often localhost). Preview
        // images are consumed by phones, so use the same host that served this
        // API request while retaining the app's subdirectory installation.
        $request = request();
        $basePath = str_replace('/index.php', '', $request->getBaseUrl());
        $origin = rtrim($request->getSchemeAndHttpHost() . $basePath, '/');

        return $origin . '/uploads/' . ltrim($path, '/');
    }

    private function error(string $message, int $status)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
