<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBusinessAiGeneration;
use App\Models\AiImageModel;
use App\Models\BusinessAiEditableRequest;
use App\Models\BusinessAiGeneration;
use App\Models\BusinessAiPurpose;
use App\Models\BusinessAiReferenceUpload;
use App\Models\BusinessAiStyle;
use App\Models\Language;
use App\Models\Product;
use App\Models\StorageSetting;
use App\Models\Subscription;
use App\Models\SubscriptionAiImageAccess;
use App\Models\User;
use App\Services\BusinessAiPromptCompiler;
use App\Services\BusinessAiContentPreviewService;
use App\Services\BusinessAiScopeResolver;
use App\Services\FestivalAiBusinessContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/** API for the new Custom Post > Business Purpose Prompt journey. */
class BusinessAiGenerationController extends Controller
{
    public function __construct(
        private FestivalAiBusinessContextService $businessContext,
        private BusinessAiPromptCompiler $promptCompiler,
        private BusinessAiScopeResolver $scopeResolver,
        private BusinessAiContentPreviewService $contentPreview
    ) {
    }

    public function options(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $plan = $user->active_subscription;
        $accesses = $plan ? $this->activeAccesses($plan)->keyBy('ai_image_model_id') : collect();
        $models = AiImageModel::where('is_active', true)->orderByDesc('is_recommended')->orderBy('sort_order')->get();
        $activeBusiness = $this->scopeResolver->activeBusiness($user);

        return response()->json([
            'success' => true,
            'quota' => [
                'limit' => (int) optional($plan)->ai_image_limit,
                'used' => (int) $user->ai_image_used,
                'remaining' => $user->getRemainingUsage('ai_image'),
            ],
            'generation_cost' => $this->generationCost($plan),
            'business_purpose_label' => 'Custom Post Type',
            'max_product_references' => (int) config('business_ai_v1.max_product_references', 4),
            // Custom has no business picker. Keep the one-item `businesses`
            // array only for older clients while new clients use this explicit
            // active-business payload.
            'active_business' => $activeBusiness
                ? $this->scopeResolver->businessPayload($activeBusiness)
                : null,
            'businesses' => $activeBusiness
                ? [$this->scopeResolver->businessPayload($activeBusiness)]
                : [],
            // A Custom Post Type is deliberately visible only when at least
            // one approved category/subcategory scope matches the one active
            // business profile.
            'purposes' => $this->scopedPurposePayloads($user),
            'custom_post_cards' => $this->scopedPostCards($user),
            'models' => $models->map(fn (AiImageModel $model) => $this->modelPayload($model, $accesses->get($model->id)))->values(),
            'languages' => Language::where('status', true)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    /**
     * Produces reviewable copy only. It never reserves an image credit, writes
     * a generation record, or queues an image job.
     */
    public function preview(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'purpose_key' => ['required', 'string', 'max:80'],
            'scope_id' => ['required', 'integer'],
            'business_id' => ['nullable', 'integer'],
            'style_key' => ['nullable', 'string', 'max:80'],
            'palette_mode' => ['nullable', Rule::in(['style_colors', 'business_theme'])],
            'language_id' => ['nullable', 'integer', Rule::exists('language', 'id')->where('status', true)],
            'brief' => ['required', 'array', 'max:30'],
            'brief.*' => ['nullable', 'string', 'max:300'],
            'user_instruction' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $resolved = $this->scopeResolver->resolve(
                $user,
                (int) $validated['scope_id'],
                $validated['purpose_key'],
                isset($validated['business_id']) ? (int) $validated['business_id'] : null,
            );
            /** @var BusinessAiPurpose $purpose */
            $purpose = $resolved['purpose'];
            $this->assertRequiredBriefFields($resolved['scope_snapshot']['brief_fields'] ?? [], $validated['brief']);
            $instructionLimit = max(50, min(1000, (int) $purpose->change_instruction_limit));
            if (mb_strlen((string) ($validated['user_instruction'] ?? '')) > $instructionLimit) {
                throw new \DomainException("Change instruction can contain up to {$instructionLimit} characters for this Custom Post Type.");
            }

            /** @var \Illuminate\Database\Eloquent\Collection $styles */
            $styles = $resolved['styles'];
            $style = filled($validated['style_key'] ?? null)
                ? $styles->firstWhere('key', $validated['style_key'])
                : $styles->first();
            if (!$style) {
                throw new \DomainException('Select a post style that is available for this business category.');
            }

            $stylePayload = $this->stylePayload($style);
            $stylePayload = $this->withEffectivePalette(
                $stylePayload,
                $validated['palette_mode'] ?? 'style_colors',
                $resolved['business'],
            );

            $language = !empty($validated['language_id'])
                ? Language::find($validated['language_id'])
                : null;
            $preview = $this->contentPreview->generate(
                $user,
                $purpose,
                $resolved['scope'],
                $validated['brief'],
                $resolved['business_snapshot'],
                $style,
                $validated['user_instruction'] ?? null,
                optional($language)->title,
            );
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'success' => true,
            'preview' => array_merge($preview, [
                'scope' => $this->scopeResolver->scopePayload($resolved['scope'], $purpose, [$resolved['business']->id]),
                'user_brief' => $validated['brief'],
                'my_business_data' => $resolved['business_snapshot'],
                'general_data' => $resolved['scope_snapshot']['general_data'] ?? [],
                'content_instruction' => $resolved['scope_snapshot']['content_instruction'] ?? null,
                'style' => $stylePayload,
                'header_footer' => $this->publicHeaderFooter($purpose),
            ]),
        ]);
    }

    public function create(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'purpose_key' => ['required', 'string', 'max:80'],
            'scope_id' => ['required', 'integer'],
            'business_id' => ['nullable', 'integer'],
            'style_key' => ['required', 'string', 'max:80'],
            'palette_mode' => ['nullable', Rule::in(['style_colors', 'business_theme'])],
            'model_id' => ['required', 'integer'],
            'quality' => ['required', 'string', 'max:30'],
            'size_key' => ['required', 'string', 'max:50'],
            'language_id' => ['nullable', 'integer', Rule::exists('language', 'id')->where('status', true)],
            'brief' => ['required', 'array', 'max:30'],
            'brief.*' => ['nullable', 'string', 'max:300'],
            'user_instruction' => ['nullable', 'string', 'max:1000'],
            'content_preview' => ['nullable', 'array'],
            'content_preview.headline' => ['nullable', 'string', 'max:80'],
            'content_preview.content' => ['nullable', 'string', 'max:360'],
            'content_preview.cta' => ['nullable', 'string', 'max:100'],
            'content_preview.content_lines' => ['nullable', 'array', 'max:4'],
            'content_preview.content_lines.*' => ['nullable', 'string', 'max:140'],
            'product_ids' => ['nullable', 'array', 'max:4'],
            'product_ids.*' => ['integer', 'distinct'],
            'reference_upload_ids' => ['nullable', 'array', 'max:4'],
            'reference_upload_ids.*' => ['integer', 'distinct'],
            'parent_generation_id' => ['nullable', 'integer'],
            'generation_kind' => ['nullable', Rule::in(['initial', 'another_version', 'brief_change', 'style_change'])],
        ]);
        $resolvedScope = null;
        try {
            $resolvedScope = $this->scopeResolver->resolve(
                $user,
                (int) $validated['scope_id'],
                $validated['purpose_key'],
                isset($validated['business_id']) ? (int) $validated['business_id'] : null,
            );
            $purposeRecord = $resolvedScope['purpose'];
            $styleRecord = $resolvedScope['styles']->firstWhere('key', $validated['style_key']);
            if (!$styleRecord) {
                throw new \DomainException('Select a post style that is available for this active business category.');
            }
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $purpose = [
            'key' => $purposeRecord->key,
            'title' => $purposeRecord->title,
            'prompt' => $purposeRecord->base_prompt,
            'product_prompt' => $purposeRecord->product_prompt,
            'fields' => $resolvedScope['scope_snapshot']['brief_fields'] ?? [],
        ];
        $style = [
            'key' => $styleRecord->key,
            'name' => $styleRecord->name,
            'description' => $styleRecord->description,
            'prompt_text' => $styleRecord->prompt_text,
            'colors' => $styleRecord->colors ?? [],
        ];
        try {
            $style = $this->withEffectivePalette(
                $style,
                $validated['palette_mode'] ?? 'style_colors',
                $resolvedScope['business'],
            );
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $allowedSizeKeys = (array) ($purposeRecord->allowed_size_keys ?? []);
        if ($allowedSizeKeys !== [] && !in_array($validated['size_key'], $allowedSizeKeys, true)) {
            return $this->error('This size is not enabled for the selected Custom Post Type.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $instructionLimit = max(50, min(1000, (int) $purposeRecord->change_instruction_limit));
        if (mb_strlen((string) ($validated['user_instruction'] ?? '')) > $instructionLimit) {
            return $this->error("Change instruction can contain up to {$instructionLimit} characters for this Custom Post Type.", Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $productIds = array_values($validated['product_ids'] ?? []);
        $referenceUploadIds = array_values($validated['reference_upload_ids'] ?? []);
        $referenceCount = count($productIds) + count($referenceUploadIds);
        if (!$purposeRecord->product_upload_enabled && $referenceCount !== 0) {
            return $this->error('Product photos are not enabled for the selected Custom Post Type.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($purposeRecord->product_upload_enabled && $purposeRecord->product_required && $referenceCount === 0) {
            return $this->error('Add at least one product photo for this Custom Post Type.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $products = Product::where('user_id', $user->id)->whereIn('id', $productIds)->get();
        if ($products->count() !== count($productIds) || $products->contains(fn (Product $product) => blank($product->image))) {
            return $this->error('Every selected product needs a valid photo.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $referenceUploads = BusinessAiReferenceUpload::where('user_id', $user->id)
            ->whereIn('id', $referenceUploadIds)
            ->get();
        if ($referenceUploads->count() !== count($referenceUploadIds)) {
            return $this->error('One or more uploaded reference images are not available for this account.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        try {
            $this->assertRequiredBriefFields((array) ($purpose['fields'] ?? []), $validated['brief']);
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $scopeSnapshot = $resolvedScope['scope_snapshot'] ?? [];
        $businessSnapshot = $resolvedScope['business_snapshot'] ?? null;
        $approvedPreview = null;
        if ($resolvedScope) {
            $language = !empty($validated['language_id']) ? Language::find($validated['language_id']) : null;
            $generatedPreview = $this->contentPreview->generate(
                $user,
                $purposeRecord,
                $resolvedScope['scope'],
                $validated['brief'],
                $resolvedScope['business_snapshot'],
                $styleRecord,
                $validated['user_instruction'] ?? null,
                optional($language)->title,
            );
            $approvedPreview = !empty($validated['content_preview'])
                ? $this->contentPreview->normaliseSubmitted($validated['content_preview'], $generatedPreview)
                : $generatedPreview;
        }

        try {
            $generation = DB::transaction(function () use ($user, $validated, $purpose, $style, $products, $referenceUploads, $purposeRecord, $instructionLimit, $resolvedScope, $scopeSnapshot, $businessSnapshot, $approvedPreview) {
                $lockedUser = User::lockForUpdate()->findOrFail($user->id);
                $generationKind = $validated['generation_kind'] ?? 'initial';
                $parentGeneration = null;
                if (!empty($validated['parent_generation_id'])) {
                    $parentGeneration = BusinessAiGeneration::query()
                        ->lockForUpdate()
                        ->whereKey((int) $validated['parent_generation_id'])
                        ->where('user_id', $lockedUser->id)
                        ->first();
                    if (!$parentGeneration || $parentGeneration->status !== 'completed') {
                        throw new \DomainException('Choose a completed Custom Post version before generating another one.');
                    }
                    if ((int) $parentGeneration->business_ai_purpose_scope_id !== (int) $resolvedScope['scope']->id) {
                        throw new \DomainException('A new version must stay inside the same Custom Post Type and subcategory.');
                    }
                    if ($generationKind === 'initial') {
                        throw new \DomainException('A linked Custom Post version needs a version action.');
                    }
                } elseif ($generationKind !== 'initial') {
                    throw new \DomainException('Choose a completed Custom Post before creating another version.');
                }
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
                $referenceCount = $products->count() + $referenceUploads->count();
                if ($referenceCount > $maxReferences) throw new \DomainException("This model and plan allow up to {$maxReferences} reference photos.");
                if ($referenceCount > 0 && (!(bool) $model->supports_reference_images || !(bool) $model->supports_edits)) throw new \DomainException('The selected model does not accept reference photos.');
                $creditCost = $this->generationCost($plan);
                if ((int) $lockedUser->ai_image_used + $creditCost > (int) $plan->ai_image_limit) throw new \DomainException('Your AI image generation quota has been used. Please upgrade your plan.');

                $lockedUser->increment('ai_image_used', $creditCost);
                $snapshot = $products->map(fn (Product $product) => [
                    'id' => $product->id,
                    'source' => 'catalogue_product',
                    'title' => $product->display_name,
                    'description' => $product->description,
                    'image' => $product->image,
                ])->values()->all();
                $snapshot = array_merge($snapshot, $referenceUploads->map(fn (BusinessAiReferenceUpload $upload) => [
                    'id' => $upload->id,
                    'source' => 'device_upload',
                    'title' => $upload->original_name,
                    'description' => 'User-uploaded reference image',
                    'image' => $upload->path,
                    'upload_public_id' => $upload->public_id,
                ])->values()->all());
                if ($referenceUploads->isNotEmpty()) {
                    BusinessAiReferenceUpload::whereIn('id', $referenceUploads->pluck('id'))->update(['last_used_at' => now()]);
                }
                $business = $businessSnapshot ?? $this->businessContext->snapshotForUser($lockedUser);
                $headerFooter = $this->headerFooterSnapshot($purposeRecord);
                $prompt = $this->promptCompiler->compile(
                    $purpose,
                    $style,
                    $validated['brief'],
                    $snapshot,
                    $business,
                    $validated['user_instruction'] ?? null,
                    (string) $size['size'],
                    $headerFooter,
                    $scopeSnapshot,
                    $approvedPreview ?? [],
                );
                $generation = BusinessAiGeneration::create([
                    'request_id' => (string) Str::uuid(), 'user_id' => $lockedUser->id, 'subscription_id' => $plan->id,
                    'root_generation_id' => $parentGeneration?->root_generation_id ?: $parentGeneration?->id,
                    'parent_generation_id' => $parentGeneration?->id,
                    'generation_kind' => $generationKind,
                    'credit_cost' => $creditCost,
                    'purpose_key' => $validated['purpose_key'], 'purpose_title' => $purpose['title'],
                    'business_ai_purpose_scope_id' => $resolvedScope['scope']->id ?? null,
                    'style_key' => $validated['style_key'], 'style_name' => $style['name'], 'language_id' => $validated['language_id'] ?? null,
                    'ai_image_model_id' => $model->id, 'provider' => $model->provider, 'provider_model_id' => $model->model_id,
                    'quality' => $validated['quality'], 'size_key' => $validated['size_key'], 'size_value' => $size['size'],
                    'brief' => $validated['brief'], 'user_instruction' => $validated['user_instruction'] ?? null, 'final_prompt' => $prompt,
                    'request_diagnostics' => [
                        'output_mode' => 'editable_v1',
                        'image_generation_count' => 1,
                        'credit_cost' => $creditCost,
                        'palette_mode' => $style['palette_mode'] ?? 'style_colors',
                        'effective_palette' => $style['effective_colors'] ?? $style['colors'] ?? [],
                        'custom_post_type' => ['max_product_references' => $maxReferences, 'change_instruction_limit' => $instructionLimit],
                        'header_footer_style' => $headerFooter,
                        'category_scope' => $scopeSnapshot ? [
                            'id' => $scopeSnapshot['id'] ?? null,
                            'category' => $scopeSnapshot['category'] ?? null,
                            'subcategory' => $scopeSnapshot['subcategory'] ?? null,
                            'general_data_count' => count((array) ($scopeSnapshot['general_data'] ?? [])),
                        ] : null,
                        'content_preview_approved' => $approvedPreview !== null,
                    ],
                    'product_snapshot' => $snapshot,
                    'business_snapshot' => $business,
                    'scope_snapshot' => $scopeSnapshot ?: null,
                    'content_preview' => $approvedPreview,
                    'palette_snapshot' => [
                        'mode' => $style['palette_mode'] ?? 'style_colors',
                        'style_colors' => $style['colors'] ?? [],
                        'effective_colors' => $style['effective_colors'] ?? $style['colors'] ?? [],
                    ],
                    'status' => 'queued',
                    'quota_reserved_at' => now(),
                ]);
                if (!$generation->root_generation_id) {
                    $generation->root_generation_id = $generation->id;
                    $generation->save();
                }
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

    /** Accept one safe, private device image for the signed-in user's Custom Post AI request. */
    public function uploadReference(Request $request)
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:12288'],
        ]);
        $image = $validated['image'];
        $mime = (string) $image->getMimeType();
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!array_key_exists($mime, $extensions)) {
            return $this->error('Upload a JPG, PNG, or WEBP image.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $relativePath = 'business_ai/references/' . $user->id . '/' . Str::uuid() . '.' . $extensions[$mime];
        $contents = file_get_contents($image->getRealPath());
        if ($contents === false) {
            return $this->error('The selected image could not be read.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            if (!Storage::disk('spaces')->put('uploads/' . $relativePath, $contents, 'public')) {
                return $this->error('The selected image could not be saved.', Response::HTTP_SERVICE_UNAVAILABLE);
            }
        } else {
            $directory = public_path('uploads/' . dirname($relativePath));
            File::ensureDirectoryExists($directory);
            if (file_put_contents($directory . DIRECTORY_SEPARATOR . basename($relativePath), $contents) === false) {
                return $this->error('The selected image could not be saved.', Response::HTTP_SERVICE_UNAVAILABLE);
            }
        }

        $upload = BusinessAiReferenceUpload::create([
            'user_id' => $user->id,
            'public_id' => (string) Str::uuid(),
            'original_name' => mb_substr(basename((string) $image->getClientOriginalName()), 0, 255),
            'mime_type' => $mime,
            'size' => (int) $image->getSize(),
            'path' => $relativePath,
        ]);

        return response()->json([
            'success' => true,
            'upload' => $this->referenceUploadPayload($upload),
        ], Response::HTTP_CREATED);
    }

    public function history(Request $request)
    {
        $jobs = BusinessAiGeneration::where('user_id', $this->authenticatedUser($request)->id)
            ->with(['imageModel:id,display_name', 'editableRequest.document:id,public_id,status'])->latest()->limit(30)->get()
            ->map(fn (BusinessAiGeneration $item) => $this->jobPayload($item))->values();
        return response()->json(['success' => true, 'jobs' => $jobs]);
    }

    /** My Designs contains explicitly saved Custom AI versions only. */
    public function drafts(Request $request)
    {
        $jobs = BusinessAiGeneration::where('user_id', $this->authenticatedUser($request)->id)
            ->where('is_saved_draft', true)
            ->where('status', 'completed')
            ->with(['imageModel:id,display_name', 'editableRequest.document:id,public_id,status'])
            ->orderByDesc('saved_as_draft_at')
            ->limit(100)
            ->get()
            ->map(fn (BusinessAiGeneration $item) => $this->jobPayload($item))
            ->values();

        return response()->json(['success' => true, 'drafts' => $jobs]);
    }

    public function saveDraft(Request $request, BusinessAiGeneration $businessAiGeneration)
    {
        abort_unless($businessAiGeneration->user_id === $this->authenticatedUser($request)->id, Response::HTTP_NOT_FOUND);
        $editable = $businessAiGeneration->editableRequest()->with('document:id,public_id,status')->first();
        if ($businessAiGeneration->status !== 'completed' || !$editable || $editable->status !== 'completed' || !$editable->document) {
            return $this->error('Wait for the generated design and editable text to be ready before saving a draft.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $businessAiGeneration->update([
            'is_saved_draft' => true,
            'saved_as_draft_at' => $businessAiGeneration->saved_as_draft_at ?? now(),
        ]);
        $businessAiGeneration->load(['imageModel:id,display_name', 'editableRequest.document:id,public_id,status']);

        return response()->json(['success' => true, 'draft' => $this->jobPayload($businessAiGeneration)]);
    }

    private function jobPayload(BusinessAiGeneration $generation): array
    {
        $editable = $generation->relationLoaded('editableRequest') ? $generation->editableRequest : $generation->editableRequest()->with('document:id,public_id,status')->first();
        return ['id' => $generation->id, 'request_id' => $generation->request_id, 'status' => $generation->status,
            'image_url' => $this->assetUrl($generation->generated_image_path), 'purpose_title' => $generation->purpose_title,
            'scope_id' => $generation->business_ai_purpose_scope_id,
            'root_generation_id' => $generation->root_generation_id,
            'parent_generation_id' => $generation->parent_generation_id,
            'generation_kind' => $generation->generation_kind,
            'credit_cost' => (int) ($generation->credit_cost ?: 1),
            'is_saved_draft' => (bool) $generation->is_saved_draft,
            'saved_as_draft_at' => optional($generation->saved_as_draft_at)->toIso8601String(),
            'content_preview' => $generation->content_preview,
            'style_name' => $generation->style_name, 'model_name' => optional($generation->imageModel)->display_name,
            'palette' => $generation->palette_snapshot,
            'error_message' => $generation->status === 'failed' ? $generation->error_message : null,
            'created_at' => optional($generation->created_at)->toIso8601String(), 'completed_at' => optional($generation->completed_at)->toIso8601String(),
            'editable_document' => $editable ? ['status' => $editable->status, 'document_id' => optional($editable->document)->public_id, 'error_message' => $editable->status === 'failed' ? $editable->error_message : null] : null,
        ];
    }

    private function activeAccesses(Subscription $plan)
    {
        return SubscriptionAiImageAccess::with('imageModel')->where('subscription_id', $plan->id)->where('status', true)->whereHas('imageModel', fn ($q) => $q->where('is_active', true))->orderBy('sort_order')->get();
    }

    /** Return only category/subcategory scopes that belong to this user's business profile. */
    private function scopedPurposePayloads(User $user)
    {
        $businesses = $this->scopeResolver->activeBusinesses($user);
        $scopes = $this->scopeResolver->availableScopesFor($user)
            ->filter(function ($scope) {
                $purpose = $scope->purpose;
                return $purpose
                    && $this->scopeResolver->stylesFor($scope, $purpose)->isNotEmpty()
                    && $this->publicHeaderFooter($purpose) !== null;
            })
            ->values();

        return $scopes
            ->groupBy('business_ai_purpose_id')
            ->sortBy(function ($purposeScopes) {
                $purpose = $purposeScopes->first()->purpose;
                return sprintf('%08d-%s', (int) $purpose->sort_order, Str::lower((string) $purpose->title));
            })
            ->map(function ($purposeScopes) use ($businesses) {
                /** @var BusinessAiPurpose $purpose */
                $purpose = $purposeScopes->first()->purpose;
                return [
                    'key' => $purpose->key,
                    'title' => $purpose->title,
                    'icon' => $purpose->icon,
                    'description' => $purpose->description,
                    // Brief fields belong to the selected subcategory scope.
                    // Keep this legacy parent-level key empty so a current
                    // client cannot show the old Type-level fallback fields.
                    'fields' => [],
                    'allowed_size_keys' => $purpose->allowed_size_keys ?? [],
                    'product_upload_enabled' => $purpose->product_upload_enabled,
                    'product_required' => $purpose->product_required,
                    'max_product_references' => $purpose->max_product_references,
                    'change_instruction_limit' => $purpose->change_instruction_limit,
                    'header_footer_style_name' => optional($purpose->headerFooterStyle)->name,
                    'styles' => $purpose->styles
                        ->map(fn (BusinessAiStyle $style) => $this->stylePayload($style))
                        ->values(),
                    'scopes' => $purposeScopes->map(function ($scope) use ($purpose, $businesses) {
                        $matchingBusinessIds = $businesses
                            ->filter(fn ($business) => $this->scopeResolver->matchesBusiness($scope, $business))
                            ->pluck('id')
                            ->map(fn ($id) => (int) $id)
                            ->all();
                        return $this->scopeResolver->scopePayload($scope, $purpose, $matchingBusinessIds);
                    })->values(),
                ];
            })
            ->values();
    }

    /**
     * Mobile Custom cards are scoped, rather than being a global Type list.
     * One Type used by two configured subcategories therefore becomes two
     * labelled cards when their data differs; the mobile flow never has to
     * guess which brief/style configuration the user intended.
     */
    private function scopedPostCards(User $user)
    {
        $business = $this->scopeResolver->activeBusiness($user);
        if (!$business) {
            return collect();
        }

        return $this->scopeResolver->availableScopesFor($user)
            ->filter(function ($scope) {
                $purpose = $scope->purpose;
                return $purpose
                    && $this->scopeResolver->stylesFor($scope, $purpose)->isNotEmpty()
                    && $this->publicHeaderFooter($purpose) !== null;
            })
            ->sortBy(function ($scope) {
                return sprintf('%08d-%s-%08d', (int) $scope->sort_order, Str::lower((string) $scope->purpose->title), (int) $scope->id);
            })
            ->map(function ($scope) use ($business) {
                /** @var BusinessAiPurpose $purpose */
                $purpose = $scope->purpose;
                $scopePayload = $this->scopeResolver->scopePayload($scope, $purpose, [(int) $business->id]);

                return [
                    'key' => $purpose->key,
                    'title' => $purpose->title,
                    'icon' => $purpose->icon,
                    'description' => $purpose->description,
                    'scope_label' => data_get($scopePayload, 'subcategory.name')
                        ?: data_get($scopePayload, 'category.name'),
                    'scope_id' => (int) $scope->id,
                    'allowed_size_keys' => $purpose->allowed_size_keys ?? [],
                    'product_upload_enabled' => (bool) $purpose->product_upload_enabled,
                    'product_required' => (bool) $purpose->product_required,
                    'max_product_references' => $purpose->max_product_references,
                    'change_instruction_limit' => $purpose->change_instruction_limit,
                    'header_footer_style_name' => optional($purpose->headerFooterStyle)->name,
                    'styles' => $this->scopeResolver->stylesFor($scope, $purpose)
                        ->map(fn (BusinessAiStyle $style) => $this->stylePayload($style))
                        ->values(),
                    'scopes' => [$scopePayload],
                ];
            })
            ->values();
    }

    private function assertRequiredBriefFields(array $fields, array $brief): void
    {
        foreach ($fields as $field) {
            $key = is_array($field) ? trim((string) ($field['key'] ?? '')) : '';
            if ($key !== '' && ($field['required'] ?? false) && blank($brief[$key] ?? null)) {
                throw new \DomainException(($field['label'] ?? Str::headline($key)) . ' is required.');
            }
        }
    }

    private function stylePayload(BusinessAiStyle $style): array
    {
        return [
            'id' => $style->id,
            'key' => $style->key,
            'name' => $style->name,
            'description' => $style->description,
            'colors' => $style->colors ?? [],
            'preview_image_url' => $this->assetUrl($style->preview_image),
        ];
    }

    /**
     * A style decides the visual art direction. Palette selection only decides
     * which approved two-colour brand palette is supplied with that style.
     */
    private function withEffectivePalette(array $style, string $paletteMode, $business): array
    {
        $styleColors = $this->normalisePalette((array) ($style['colors'] ?? []));
        if ($styleColors === []) {
            throw new \DomainException('The selected Custom Post Style needs a primary and secondary colour.');
        }

        $effectiveColors = $styleColors;
        if ($paletteMode === 'business_theme') {
            $businessColors = $this->normalisePalette([
                $business->brand_primary_color ?? null,
                $business->brand_secondary_color ?? null,
            ]);
            if (count($businessColors) < 2) {
                throw new \DomainException('Save a primary and secondary colour in My Business before using your business theme.');
            }
            $effectiveColors = $businessColors;
        }

        $style['palette_mode'] = $paletteMode;
        $style['effective_colors'] = $effectiveColors;

        return $style;
    }

    private function normalisePalette(array $colors): array
    {
        return collect($colors)
            ->map(fn ($color) => strtoupper(trim((string) $color)))
            ->filter(fn ($color) => preg_match('/^#[A-F0-9]{6}$/', $color) === 1)
            ->take(2)
            ->values()
            ->all();
    }

    private function publicHeaderFooter(BusinessAiPurpose $purpose): ?array
    {
        $style = $purpose->headerFooterStyle;
        if (!$style || !$style->status) {
            return null;
        }

        return [
            'name' => $style->name,
            'overlay_enabled' => (bool) $style->overlay_enabled,
        ];
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

    private function generationCost(?Subscription $plan): int
    {
        $configured = (int) ($plan?->business_ai_generation_credit_cost ?: config('business_ai_v1.generation_cost', 1));
        return max(1, min(1000, $configured));
    }

    private function referenceUploadPayload(BusinessAiReferenceUpload $upload): array
    {
        return [
            'id' => $upload->id,
            'public_id' => $upload->public_id,
            'name' => $upload->original_name,
            'image_url' => $this->assetUrl($upload->path),
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
            if ($user = User::lockForUpdate()->find($generation->user_id)) { $user->ai_image_used = max(0, (int) $user->ai_image_used - max(1, (int) $generation->credit_cost)); $user->save(); }
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
