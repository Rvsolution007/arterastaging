<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBusinessAiGeneration;
use App\Models\AiImageModel;
use App\Models\BusinessAiEditableRequest;
use App\Models\BusinessAiGeneration;
use App\Models\BusinessAiPurpose;
use App\Models\BusinessAiStyle;
use App\Models\Language;
use App\Models\Product;
use App\Models\StorageSetting;
use App\Models\Subscription;
use App\Models\SubscriptionAiImageAccess;
use App\Models\User;
use App\Services\BusinessAiPromptCompiler;
use App\Services\FestivalAiBusinessContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/** API for the new Custom Post > Business Purpose Prompt journey. */
class BusinessAiGenerationController extends Controller
{
    public function __construct(
        private FestivalAiBusinessContextService $businessContext,
        private BusinessAiPromptCompiler $promptCompiler
    ) {
    }

    public function options(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $plan = $user->active_subscription;
        $accesses = $plan ? $this->activeAccesses($plan)->keyBy('ai_image_model_id') : collect();
        $models = AiImageModel::where('is_active', true)->orderByDesc('is_recommended')->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'quota' => [
                'limit' => (int) optional($plan)->ai_image_limit,
                'used' => (int) $user->ai_image_used,
                'remaining' => $user->getRemainingUsage('ai_image'),
            ],
            'generation_cost' => (int) config('business_ai_v1.generation_cost', 1),
            'business_purpose_label' => 'Custom Post Type',
            'max_product_references' => (int) config('business_ai_v1.max_product_references', 4),
            'purposes' => BusinessAiPurpose::query()
                ->where('status', true)
                ->whereHas('styles', fn ($query) => $query->where('business_ai_styles.status', true))
                ->whereHas('headerFooterStyle', fn ($query) => $query->where('status', true))
                ->with([
                    'styles' => fn ($query) => $query->where('status', true),
                    'headerFooterStyle:id,name,header_prompt,footer_prompt,overlay_enabled',
                ])
                ->orderBy('sort_order')->orderBy('title')->get()
                ->map(fn (BusinessAiPurpose $purpose) => [
                    'key' => $purpose->key,
                    'title' => $purpose->title,
                    'icon' => $purpose->icon,
                    'description' => $purpose->description,
                    'fields' => $purpose->brief_fields ?? [],
                    'allowed_size_keys' => $purpose->allowed_size_keys ?? [],
                    'product_upload_enabled' => $purpose->product_upload_enabled,
                    'product_required' => $purpose->product_required,
                    'max_product_references' => $purpose->max_product_references,
                    'change_instruction_limit' => $purpose->change_instruction_limit,
                    'header_footer_style_name' => optional($purpose->headerFooterStyle)->name,
                    'styles' => $purpose->styles->map(fn (BusinessAiStyle $style) => [
                        'key' => $style->key,
                        'name' => $style->name,
                        'description' => $style->description,
                        'colors' => $style->colors ?? [],
                        'preview_image_url' => $this->assetUrl($style->preview_image),
                    ])->values(),
                ])->values(),
            'models' => $models->map(fn (AiImageModel $model) => $this->modelPayload($model, $accesses->get($model->id)))->values(),
            'languages' => Language::where('status', true)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function create(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'purpose_key' => ['required', 'string', 'max:80'],
            'style_key' => ['required', 'string', 'max:80'],
            'model_id' => ['required', 'integer'],
            'quality' => ['required', 'string', 'max:30'],
            'size_key' => ['required', 'string', 'max:50'],
            'language_id' => ['nullable', 'integer', Rule::exists('language', 'id')->where('status', true)],
            'brief' => ['required', 'array', 'max:30'],
            'brief.*' => ['nullable', 'string', 'max:300'],
            'user_instruction' => ['nullable', 'string', 'max:1000'],
            'product_ids' => ['nullable', 'array', 'max:4'],
            'product_ids.*' => ['integer', 'distinct'],
        ]);
        $purposeRecord = BusinessAiPurpose::with('headerFooterStyle')->where('key', $validated['purpose_key'])->where('status', true)->first();
        $styleRecord = $purposeRecord
            ? $purposeRecord->styles()->where('business_ai_styles.key', $validated['style_key'])->where('business_ai_styles.status', true)->first()
            : null;
        if (!$purposeRecord || !$styleRecord) {
            return $this->error('Select a valid business purpose and post style.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $purpose = [
            'key' => $purposeRecord->key,
            'title' => $purposeRecord->title,
            'prompt' => $purposeRecord->base_prompt,
            'product_prompt' => $purposeRecord->product_prompt,
            'fields' => $purposeRecord->brief_fields ?? [],
        ];
        $style = [
            'key' => $styleRecord->key,
            'name' => $styleRecord->name,
            'description' => $styleRecord->description,
            'prompt_text' => $styleRecord->prompt_text,
            'colors' => $styleRecord->colors ?? [],
        ];
        $allowedSizeKeys = (array) ($purposeRecord->allowed_size_keys ?? []);
        if ($allowedSizeKeys !== [] && !in_array($validated['size_key'], $allowedSizeKeys, true)) {
            return $this->error('This size is not enabled for the selected Custom Post Type.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $instructionLimit = max(50, min(1000, (int) $purposeRecord->change_instruction_limit));
        if (mb_strlen((string) ($validated['user_instruction'] ?? '')) > $instructionLimit) {
            return $this->error("Change instruction can contain up to {$instructionLimit} characters for this Custom Post Type.", Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $productIds = array_values($validated['product_ids'] ?? []);
        if (!$purposeRecord->product_upload_enabled && $productIds !== []) {
            return $this->error('Product photos are not enabled for the selected Custom Post Type.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($purposeRecord->product_upload_enabled && $purposeRecord->product_required && $productIds === []) {
            return $this->error('Add at least one product photo for this Custom Post Type.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $products = Product::where('user_id', $user->id)->whereIn('id', $productIds)->get();
        if ($products->count() !== count($productIds) || $products->contains(fn (Product $product) => blank($product->image))) {
            return $this->error('Every selected product needs a valid photo.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        foreach ((array) ($purpose['fields'] ?? []) as $field) {
            if (($field['required'] ?? false) && blank($validated['brief'][$field['key']] ?? null)) {
                return $this->error(($field['label'] ?? Str::headline($field['key'])) . ' is required.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        try {
            $generation = DB::transaction(function () use ($user, $validated, $purpose, $style, $products, $purposeRecord, $instructionLimit) {
                $lockedUser = User::lockForUpdate()->findOrFail($user->id);
                $lockedUser->resetLimitsIfNeeded();
                $plan = $lockedUser->active_subscription;
                if (!$plan) throw new \DomainException('No active plan is available for AI image generation.');
                $access = $this->activeAccesses($plan)->firstWhere('ai_image_model_id', (int) $validated['model_id']);
                $model = $access?->imageModel;
                if (!$access || !$model || $model->provider !== 'openai') throw new \DomainException('This model is not included in your current plan.');
                if (!in_array($validated['quality'], (array) $access->allowed_qualities, true)) throw new \DomainException('This quality is not included in your current plan.');
                $size = collect((array) $model->size_options)->firstWhere('key', $validated['size_key']);
                if (!$size || !in_array($validated['size_key'], (array) $access->allowed_size_keys, true)) throw new \DomainException('This post size is not available for the selected model.');
                $maxReferences = min((int) $purposeRecord->max_product_references, (int) config('business_ai_v1.max_product_references', 4), (int) $access->max_reference_images, (int) $model->max_reference_images);
                if ($products->count() > $maxReferences) throw new \DomainException("This model and plan allow up to {$maxReferences} product photos.");
                if ($products->isNotEmpty() && (!(bool) $model->supports_reference_images || !(bool) $model->supports_edits)) throw new \DomainException('The selected model does not accept product reference photos.');
                if ((int) $lockedUser->ai_image_used >= (int) $plan->ai_image_limit) throw new \DomainException('Your AI image generation quota has been used. Please upgrade your plan.');

                $lockedUser->increment('ai_image_used');
                $snapshot = $products->map(fn (Product $product) => ['id' => $product->id, 'title' => $product->display_name, 'description' => $product->description, 'image' => $product->image])->values()->all();
                $business = $this->businessContext->snapshotForUser($lockedUser);
                $headerFooter = $this->headerFooterSnapshot($purposeRecord);
                $prompt = $this->promptCompiler->compile($purpose, $style, $validated['brief'], $snapshot, $business, $validated['user_instruction'] ?? null, (string) $size['size'], $headerFooter);
                $generation = BusinessAiGeneration::create([
                    'request_id' => (string) Str::uuid(), 'user_id' => $lockedUser->id, 'subscription_id' => $plan->id,
                    'purpose_key' => $validated['purpose_key'], 'purpose_title' => $purpose['title'],
                    'style_key' => $validated['style_key'], 'style_name' => $style['name'], 'language_id' => $validated['language_id'] ?? null,
                    'ai_image_model_id' => $model->id, 'provider' => $model->provider, 'provider_model_id' => $model->model_id,
                    'quality' => $validated['quality'], 'size_key' => $validated['size_key'], 'size_value' => $size['size'],
                    'brief' => $validated['brief'], 'user_instruction' => $validated['user_instruction'] ?? null, 'final_prompt' => $prompt,
                    'request_diagnostics' => [
                        'output_mode' => 'editable_v1',
                        'image_generation_count' => 1,
                        'credit_cost' => (int) config('business_ai_v1.generation_cost', 1),
                        'custom_post_type' => ['max_product_references' => $maxReferences, 'change_instruction_limit' => $instructionLimit],
                        'header_footer_style' => $headerFooter,
                    ],
                    'product_snapshot' => $snapshot, 'business_snapshot' => $business, 'status' => 'queued', 'quota_reserved_at' => now(),
                ]);
                BusinessAiEditableRequest::create(['public_id' => (string) Str::uuid(), 'business_ai_generation_id' => $generation->id, 'user_id' => $lockedUser->id, 'status' => 'queued']);
                return $generation;
            });
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        try {
            ProcessBusinessAiGeneration::dispatch($generation->id)->onConnection('festival-ai')->onQueue('festival-ai');
        } catch (\Throwable $exception) {
            $this->refundQueuedGeneration($generation->id, 'Custom Post AI queue is temporarily unavailable. Your credit was restored.');
            return $this->error('Custom Post AI queue is temporarily unavailable. Your credit was restored.', Response::HTTP_SERVICE_UNAVAILABLE);
        }
        return response()->json(['success' => true, 'message' => 'Your Custom Post is queued.', 'job' => $this->jobPayload($generation)], Response::HTTP_ACCEPTED);
    }

    public function show(Request $request, BusinessAiGeneration $businessAiGeneration)
    {
        abort_unless($businessAiGeneration->user_id === $this->authenticatedUser($request)->id, Response::HTTP_NOT_FOUND);
        return response()->json(['success' => true, 'job' => $this->jobPayload($businessAiGeneration)]);
    }

    public function history(Request $request)
    {
        $jobs = BusinessAiGeneration::where('user_id', $this->authenticatedUser($request)->id)
            ->with(['imageModel:id,display_name', 'editableRequest.document:id,public_id,status'])->latest()->limit(30)->get()
            ->map(fn (BusinessAiGeneration $item) => $this->jobPayload($item))->values();
        return response()->json(['success' => true, 'jobs' => $jobs]);
    }

    private function jobPayload(BusinessAiGeneration $generation): array
    {
        $editable = $generation->relationLoaded('editableRequest') ? $generation->editableRequest : $generation->editableRequest()->with('document:id,public_id,status')->first();
        return ['id' => $generation->id, 'request_id' => $generation->request_id, 'status' => $generation->status,
            'image_url' => $this->assetUrl($generation->generated_image_path), 'purpose_title' => $generation->purpose_title,
            'style_name' => $generation->style_name, 'model_name' => optional($generation->imageModel)->display_name,
            'error_message' => $generation->status === 'failed' ? $generation->error_message : null,
            'created_at' => optional($generation->created_at)->toIso8601String(), 'completed_at' => optional($generation->completed_at)->toIso8601String(),
            'editable_document' => $editable ? ['status' => $editable->status, 'document_id' => optional($editable->document)->public_id, 'error_message' => $editable->status === 'failed' ? $editable->error_message : null] : null,
        ];
    }

    private function activeAccesses(Subscription $plan)
    {
        return SubscriptionAiImageAccess::with('imageModel')->where('subscription_id', $plan->id)->where('status', true)->whereHas('imageModel', fn ($q) => $q->where('is_active', true))->orderBy('sort_order')->get();
    }

    private function modelPayload(AiImageModel $model, ?SubscriptionAiImageAccess $access): array
    {
        $allowed = $access ? (array) $access->allowed_qualities : [];
        return ['id' => $model->id, 'display_name' => $model->display_name, 'description' => $model->description, 'default_quality' => $model->default_quality,
            'quality_variants' => collect((array) $model->quality_options)->map(fn ($quality) => ['key' => $quality, 'display_name' => data_get($model->quality_display_names, $quality, ucfirst($quality)), 'is_available' => in_array($quality, $allowed, true)])->values(),
            'sizes' => collect((array) $model->size_options)->filter(fn ($size) => $access && in_array($size['key'] ?? null, (array) $access->allowed_size_keys, true))->values(),
            'max_product_images' => $access ? min((int) config('business_ai_v1.max_product_references', 4), (int) $access->max_reference_images, (int) $model->max_reference_images) : 0,
        ];
    }

    private function authenticatedUser(Request $request): User
    {
        $user = auth('sanctum')->user(); $token = $user?->currentAccessToken();
        abort_unless($user && $token && $token->can('mobile:access'), Response::HTTP_UNAUTHORIZED, 'Please sign in again to use Custom Post AI.');
        if ($token->expires_at && now()->greaterThanOrEqualTo($token->expires_at)) { $token->delete(); abort(Response::HTTP_UNAUTHORIZED, 'Your session has expired.'); }
        if ($request->filled('userId') && (int) $request->input('userId') !== $user->id) abort(Response::HTTP_FORBIDDEN);
        return $user;
    }

    private function refundQueuedGeneration(int $id, string $message): void
    {
        DB::transaction(function () use ($id, $message) {
            $generation = BusinessAiGeneration::lockForUpdate()->find($id);
            if (!$generation || $generation->quota_refunded_at) return;
            if ($user = User::lockForUpdate()->find($generation->user_id)) { $user->ai_image_used = max(0, (int) $user->ai_image_used - 1); $user->save(); }
            $generation->update(['status' => 'failed', 'error_code' => 'queue_unavailable', 'error_message' => $message, 'quota_refunded_at' => now(), 'completed_at' => now()]);
        });
    }

    private function assetUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') return Storage::disk('spaces')->url('uploads/' . ltrim($path, '/'));
        $request = request(); $origin = rtrim($request->getSchemeAndHttpHost() . str_replace('/index.php', '', $request->getBaseUrl()), '/');
        return $origin . '/uploads/' . ltrim($path, '/');
    }

    private function headerFooterSnapshot(BusinessAiPurpose $purpose): array
    {
        $style = $purpose->headerFooterStyle;
        if (!$style || !$style->status || !$style->overlay_enabled) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'name' => $style->name,
            'header_prompt' => $style->header_prompt,
            'footer_prompt' => $style->footer_prompt,
        ];
    }

    private function error(string $message, int $status) { return response()->json(['success' => false, 'message' => $message], $status); }
}
