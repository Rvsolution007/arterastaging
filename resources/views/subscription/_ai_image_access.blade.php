@php
    $oldAccesses = old('ai_accesses');
    $existingAccesses = isset($subscription) && $subscription
        ? $subscription->aiImageAccesses->keyBy('ai_image_model_id')
        : collect();
@endphp

<div class="row mb-4">
    <div class="col-12">
        <div class="feature-card p-3">
            <div class="feature-header">
                <h6 class="font-weight-bold text-dark mb-0">AI Model &amp; Quality Access</h6>
                <small class="text-muted">Enable the exact models, quality levels, post sizes, and product-image limit this plan can use.</small>
            </div>

            @forelse($aiImageModels as $model)
                @php
                    $savedAccess = $existingAccesses->get($model->id);
                    $formAccess = is_array($oldAccesses) && array_key_exists($model->id, $oldAccesses)
                        ? $oldAccesses[$model->id]
                        : null;
                    $isEnabled = $formAccess !== null ? !empty($formAccess['enabled']) : (bool) optional($savedAccess)->status;
                    $selectedQualities = $formAccess !== null
                        ? (array) ($formAccess['qualities'] ?? [])
                        : (array) optional($savedAccess)->allowed_qualities;
                    $selectedSizeKeys = $formAccess !== null
                        ? (array) ($formAccess['size_keys'] ?? [])
                        : (array) optional($savedAccess)->allowed_size_keys;
                    $maxReferenceImages = $formAccess !== null
                        ? (int) ($formAccess['max_reference_images'] ?? 0)
                        : (int) optional($savedAccess)->max_reference_images;
                    $allowRefinement = $formAccess !== null
                        ? !empty($formAccess['allow_refinement'])
                        : (bool) optional($savedAccess)->allow_refinement;
                    $sizeOptions = collect((array) $model->size_options)->keyBy('key');
                @endphp
                <div class="border rounded p-3 mb-3 {{ $model->is_active ? '' : 'bg-light' }}">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="font-weight-bold text-dark">{{ $model->display_name }}</div>
                            <small class="text-muted">{{ $model->provider }} · {{ $model->model_id }}</small>
                            @if($model->description)
                                <div><small class="text-muted">{{ $model->description }}</small></div>
                            @endif
                        </div>
                        <div class="text-right">
                            @if(!$model->is_active)
                                <span class="badge badge-secondary mb-2">Model inactive</span><br>
                            @endif
                            <label class="mb-0">
                                <input type="checkbox" name="ai_accesses[{{ $model->id }}][enabled]" value="1" {{ $isEnabled ? 'checked' : '' }}>
                                Include in this plan
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted font-weight-bold d-block mb-1">Allowed quality</label>
                        @foreach((array) $model->quality_options as $quality)
                            <label class="mr-3 mb-1">
                                <input type="checkbox" name="ai_accesses[{{ $model->id }}][qualities][]" value="{{ $quality }}" {{ in_array($quality, $selectedQualities, true) ? 'checked' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $quality)) }}
                            </label>
                        @endforeach
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted font-weight-bold d-block mb-1">Allowed post size</label>
                        @foreach($sizeOptions as $sizeKey => $size)
                            <label class="mr-3 mb-1">
                                <input type="checkbox" name="ai_accesses[{{ $model->id }}][size_keys][]" value="{{ $sizeKey }}" {{ in_array($sizeKey, $selectedSizeKeys, true) ? 'checked' : '' }}>
                                {{ $size['label'] ?? ucfirst($sizeKey) }}@if(!empty($size['ratio'])) ({{ $size['ratio'] }})@endif
                            </label>
                        @endforeach
                    </div>

                    <div class="row align-items-end">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <label class="small text-muted font-weight-bold mb-1">Max product/reference images</label>
                            @if($model->supports_reference_images)
                                <input type="number" class="form-control form-control-sm" name="ai_accesses[{{ $model->id }}][max_reference_images]" min="0" max="{{ $model->max_reference_images }}" value="{{ $maxReferenceImages }}">
                            @else
                                <input type="hidden" name="ai_accesses[{{ $model->id }}][max_reference_images]" value="0">
                                <div class="small text-muted">This model does not accept product images.</div>
                            @endif
                        </div>
                        <div class="col-md-8">
                            @if($model->supports_edits)
                                <label class="mb-0">
                                    <input type="checkbox" name="ai_accesses[{{ $model->id }}][allow_refinement]" value="1" {{ $allowRefinement ? 'checked' : '' }}>
                                    Allow follow-up refinement / edit requests
                                </label>
                            @else
                                <span class="small text-muted">Refinement is not supported by this model.</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-info mb-0">
                    Add an AI image model first from Settings → AI Image Models. It can then be assigned to subscription plans here.
                </div>
            @endforelse
        </div>
    </div>
</div>
