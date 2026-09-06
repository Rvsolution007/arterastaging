<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessAiPurpose;
use App\Models\BusinessAiPurposeScope;
use App\Models\BusinessSubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Manages Custom Post Type data from the Business Subcategory context.
 *
 * The URL contains the subcategory, so the parent category is deliberately
 * never accepted from the browser.  This keeps General Data such as
 * Healthcare > Eye Clinic isolated from every other subcategory.
 */
class BusinessSubCategoryAiDataController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessSubCategory');
    }

    public function index(BusinessSubCategory $businessSubCategory)
    {
        $this->loadSubCategoryContext($businessSubCategory);

        $scopes = BusinessAiPurposeScope::query()
            ->where('business_sub_category_id', $businessSubCategory->id)
            ->with([
                'purpose:id,title,description,status',
                'styles:id,name,description,status',
            ])
            ->withCount('styles')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(50);

        return view('business_ai.business_sub_categories.ai_data.index', compact('businessSubCategory', 'scopes'));
    }

    /**
     * A `purpose_id` query parameter is intentionally supported.  The form
     * can first ask the admin to choose one Custom Post Type, then reload with
     * its parent styles without ever asking for category/subcategory again.
     */
    public function create(Request $request, BusinessSubCategory $businessSubCategory)
    {
        $this->loadSubCategoryContext($businessSubCategory);
        $selectedPurposeId = $request->query('purpose_id');
        $purpose = filled($selectedPurposeId)
            ? $this->activePurposeOrFail((int) $selectedPurposeId)
            : null;

        return $this->formResponse(
            $businessSubCategory,
            new BusinessAiPurposeScope([
                'business_category_id' => $businessSubCategory->business_category_id,
                'business_sub_category_id' => $businessSubCategory->id,
                'status' => true,
                'sort_order' => 0,
                'general_data' => [''],
                'brief_fields' => [[
                    'key' => '',
                    'label' => '',
                    'hint' => '',
                    'required' => false,
                ]],
            ]),
            $purpose,
        );
    }

    public function store(Request $request, BusinessSubCategory $businessSubCategory)
    {
        $validated = $this->validateScope($request);

        DB::transaction(function () use ($request, $businessSubCategory, $validated) {
            // The lock serialises two admin requests that try to add the same
            // Type under this subcategory at exactly the same time.
            $subCategory = $this->lockedSubCategory($businessSubCategory);
            $purpose = $this->activePurposeOrFail((int) $validated['business_ai_purpose_id']);

            $this->ensureNoDuplicateScope($subCategory, $purpose);
            $this->ensureStylesBelongToPurpose($validated, $purpose, $request->boolean('status'));

            $scope = BusinessAiPurposeScope::create($this->payload($request, $subCategory, $validated, $purpose));
            $scope->styles()->sync($this->styleIds($validated));
            $this->syncSharedBriefTargets($scope, $validated, $purpose);
        });

        return redirect()
            ->route('business_sub_category.ai_data.index', $businessSubCategory)
            ->with('success', 'Custom Post Type data was added for this subcategory.');
    }

    public function edit(BusinessSubCategory $businessSubCategory, BusinessAiPurposeScope $businessAiPurposeScope)
    {
        $this->loadSubCategoryContext($businessSubCategory);
        $this->ensureScopeBelongsToSubCategory($businessSubCategory, $businessAiPurposeScope);

        $purpose = $businessAiPurposeScope->purpose;
        abort_unless($purpose, 404);

        return $this->formResponse($businessSubCategory, $businessAiPurposeScope, $purpose);
    }

    public function update(
        Request $request,
        BusinessSubCategory $businessSubCategory,
        BusinessAiPurposeScope $businessAiPurposeScope,
    ) {
        $this->ensureScopeBelongsToSubCategory($businessSubCategory, $businessAiPurposeScope);
        $validated = $this->validateScope($request);

        DB::transaction(function () use ($request, $businessSubCategory, $businessAiPurposeScope, $validated) {
            $subCategory = $this->lockedSubCategory($businessSubCategory);
            $currentScope = BusinessAiPurposeScope::query()->lockForUpdate()->findOrFail($businessAiPurposeScope->id);
            $this->ensureScopeBelongsToSubCategory($subCategory, $currentScope);

            $purpose = $this->editablePurposeOrFail(
                (int) $validated['business_ai_purpose_id'],
                $currentScope,
            );

            $this->ensureNoDuplicateScope($subCategory, $purpose, $currentScope);
            $this->ensureStylesBelongToPurpose($validated, $purpose, $request->boolean('status'));

            $isBriefLinkedToAnotherScope = filled($currentScope->brief_fields_source_scope_id);
            $payload = $this->payload($request, $subCategory, $validated, $purpose);
            // A linked target submits readonly source fields with the form.
            // Keep its original JSON as a safe backup for a future unlink.
            if ($isBriefLinkedToAnotherScope) {
                $payload['brief_fields'] = $currentScope->brief_fields;
            }

            $currentScope->update($payload);
            $currentScope->styles()->sync($this->styleIds($validated));
            if (!$isBriefLinkedToAnotherScope) {
                $this->syncSharedBriefTargets($currentScope, $validated, $purpose);
            }
        });

        return redirect()
            ->route('business_sub_category.ai_data.index', $businessSubCategory)
            ->with('success', 'Custom Post Type data was updated.');
    }

    public function destroy(BusinessSubCategory $businessSubCategory, BusinessAiPurposeScope $businessAiPurposeScope)
    {
        $this->ensureScopeBelongsToSubCategory($businessSubCategory, $businessAiPurposeScope);
        $businessAiPurposeScope->delete();

        return redirect()
            ->route('business_sub_category.ai_data.index', $businessSubCategory)
            ->with('success', 'Custom Post Type data was deleted from this subcategory.');
    }

    private function formResponse(
        BusinessSubCategory $businessSubCategory,
        BusinessAiPurposeScope $scope,
        ?BusinessAiPurpose $purpose = null,
    ) {
        $purpose ??= $scope->exists ? $scope->purpose : null;
        $purposeOptions = $this->purposeOptions($purpose?->id);
        $purpose = $purpose ? $purposeOptions->firstWhere('id', $purpose->id) ?? $purpose : null;
        $parentStyles = $purpose ? $purpose->styles : collect();
        $selectedStyleIds = $scope->exists
            ? $scope->styles()->pluck('business_ai_styles.id')->map(fn ($id) => (int) $id)->all()
            : [];
        $briefFieldsSource = $scope->exists && $scope->brief_fields_source_scope_id
            ? $scope->briefFieldsSource()->with([
                'category:id,name',
                'subCategory:id,name,business_category_id',
            ])->first()
            : null;
        $sharedBriefScopeIds = $scope->exists && !$briefFieldsSource
            ? BusinessAiPurposeScope::query()
                ->where('brief_fields_source_scope_id', $scope->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];
        $shareableBriefScopes = $purpose
            ? BusinessAiPurposeScope::query()
                ->where('business_ai_purpose_id', $purpose->id)
                ->whereNotNull('business_sub_category_id')
                ->when($scope->exists, fn ($query) => $query->where('id', '!=', $scope->id))
                ->with([
                    'category:id,name',
                    'subCategory:id,name,business_category_id',
                ])
                ->orderBy('business_category_id')
                ->orderBy('business_sub_category_id')
                ->get()
            : collect();

        return view('business_ai.business_sub_categories.ai_data.form', compact(
            'businessSubCategory',
            'scope',
            'purposeOptions',
            'purpose',
            'parentStyles',
            'selectedStyleIds',
            'briefFieldsSource',
            'sharedBriefScopeIds',
            'shareableBriefScopes',
        ));
    }

    /**
     * Validates only form-owned values.  The category and subcategory IDs are
     * intentionally absent: both are assigned from the nested route model.
     */
    private function validateScope(Request $request): array
    {
        $validated = $request->validate([
            'business_ai_purpose_id' => ['required', 'integer', Rule::exists('business_ai_purposes', 'id')],
            'general_data' => ['required', 'array', 'min:1', 'max:50'],
            'general_data.*' => ['nullable', 'string', 'max:1000'],
            'brief_fields' => ['required', 'array', 'min:1', 'max:20'],
            'brief_fields.*.key' => ['required', 'string', 'max:50', 'alpha_dash', 'distinct'],
            'brief_fields.*.label' => ['required', 'string', 'max:150'],
            'brief_fields.*.hint' => ['nullable', 'string', 'max:200'],
            'brief_fields.*.required' => ['nullable', 'boolean'],
            'share_brief_fields' => ['nullable', 'boolean'],
            'shared_brief_scope_ids' => ['nullable', 'array', 'max:50'],
            'shared_brief_scope_ids.*' => ['integer', 'distinct', Rule::exists('business_ai_purpose_scopes', 'id')],
            'style_ids' => ['nullable', 'array', 'max:20'],
            'style_ids.*' => ['integer', 'distinct'],
            'content_instruction' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $validated['share_brief_fields'] = $request->boolean('share_brief_fields');

        if ($this->normaliseGeneralData($validated['general_data']) === []) {
            throw ValidationException::withMessages([
                'general_data' => 'Add at least one approved General Data point.',
            ]);
        }

        $normalisedFieldKeys = collect($this->normaliseFields($validated['brief_fields']))->pluck('key');
        if ($normalisedFieldKeys->unique()->count() !== $normalisedFieldKeys->count()) {
            throw ValidationException::withMessages([
                'brief_fields' => 'Each Brief field key must remain unique after lower-case formatting.',
            ]);
        }

        if ($validated['share_brief_fields'] && empty($validated['shared_brief_scope_ids'] ?? [])) {
            throw ValidationException::withMessages([
                'shared_brief_scope_ids' => 'Choose at least one saved subcategory to share these User Brief Fields.',
            ]);
        }

        return $validated;
    }

    private function payload(
        Request $request,
        BusinessSubCategory $businessSubCategory,
        array $validated,
        BusinessAiPurpose $purpose,
    ): array {
        return [
            'business_ai_purpose_id' => $purpose->id,
            // Server-side derivation prevents forged category/subcategory data.
            'business_category_id' => $businessSubCategory->business_category_id,
            'business_sub_category_id' => $businessSubCategory->id,
            'general_data' => $this->normaliseGeneralData($validated['general_data']),
            'brief_fields' => $this->normaliseFields($validated['brief_fields']),
            'content_instruction' => blank($validated['content_instruction'] ?? null)
                ? null
                : trim($validated['content_instruction']),
            'status' => $request->boolean('status'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }

    private function purposeOptions(?int $includedPurposeId = null)
    {
        return BusinessAiPurpose::query()
            ->where(function ($query) use ($includedPurposeId) {
                $query->where('status', true);
                if ($includedPurposeId) {
                    // This project uses an older Laravel Builder that does
                    // not provide orWhereKey(). Keep an existing inactive
                    // Type visible while its scoped data is being edited.
                    $query->orWhere('id', $includedPurposeId);
                }
            })
            ->with(['styles' => fn ($query) => $query
                ->where('business_ai_styles.status', true)
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'description', 'status']);
    }

    private function activePurposeOrFail(int $purposeId): BusinessAiPurpose
    {
        return BusinessAiPurpose::query()
            ->where('id', $purposeId)
            ->where('status', true)
            ->firstOrFail();
    }

    /**
     * A row belongs to one Type for its whole life. The edit screen carries
     * the Type in a hidden field purely so the normal form contract remains
     * simple; it must not be possible to turn, for example, Treatment Process
     * data into Doctor Introduction data by changing that hidden value.
     *
     * An inactive Type may still keep its own existing row editable.
     */
    private function editablePurposeOrFail(int $purposeId, BusinessAiPurposeScope $currentScope): BusinessAiPurpose
    {
        if ($purposeId !== (int) $currentScope->business_ai_purpose_id) {
            throw ValidationException::withMessages([
                'business_ai_purpose_id' => 'Custom Post Type cannot be changed while editing. Add a separate AI Post Data row for the other Type.',
            ]);
        }

        $purpose = BusinessAiPurpose::query()->findOrFail($purposeId);
        return $purpose;
    }

    private function lockedSubCategory(BusinessSubCategory $businessSubCategory): BusinessSubCategory
    {
        return BusinessSubCategory::query()->lockForUpdate()->findOrFail($businessSubCategory->id);
    }

    private function ensureNoDuplicateScope(
        BusinessSubCategory $businessSubCategory,
        BusinessAiPurpose $purpose,
        ?BusinessAiPurposeScope $currentScope = null,
    ): void {
        $existing = BusinessAiPurposeScope::query()
            ->where('business_sub_category_id', $businessSubCategory->id)
            ->where('business_ai_purpose_id', $purpose->id)
            ->when($currentScope, fn ($query) => $query->where('id', '!=', $currentScope->id))
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'business_ai_purpose_id' => 'This Custom Post Type already has data for this subcategory.',
            ]);
        }
    }

    private function ensureStylesBelongToPurpose(array $validated, BusinessAiPurpose $purpose, bool $isActive): void
    {
        $styleIds = $this->styleIds($validated);
        $allowedStyleIds = $purpose->styles()
            ->where('business_ai_styles.status', true)
            ->pluck('business_ai_styles.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (array_diff($styleIds, $allowedStyleIds) !== []) {
            throw ValidationException::withMessages([
                'style_ids' => 'Select only styles linked to the chosen Custom Post Type.',
            ]);
        }
        if ($isActive && empty($allowedStyleIds)) {
            throw ValidationException::withMessages([
                'style_ids' => 'Link at least one active Custom Post Style in the Custom Post Type master setup before this subcategory data can be saved.',
            ]);
        }
    }

    /**
     * Links the current scope's User Brief Fields to selected *existing*
     * scopes of the same Type. No General Data, style, instruction, status,
     * or ordering value is copied across subcategories.
     */
    private function syncSharedBriefTargets(
        BusinessAiPurposeScope $sourceScope,
        array $validated,
        BusinessAiPurpose $purpose,
    ): void {
        $targetIds = ($validated['share_brief_fields'] ?? false)
            ? $this->sharedBriefScopeIds($validated)
            : [];

        $targets = BusinessAiPurposeScope::query()
            ->whereIn('id', $targetIds)
            ->where('business_ai_purpose_id', $purpose->id)
            ->whereNotNull('business_sub_category_id')
            ->where('id', '!=', $sourceScope->id)
            ->lockForUpdate()
            ->get();

        if ($targets->count() !== count($targetIds)) {
            throw ValidationException::withMessages([
                'shared_brief_scope_ids' => 'Choose only saved subcategories for this same Custom Post Type.',
            ]);
        }

        BusinessAiPurposeScope::query()
            ->where('brief_fields_source_scope_id', $sourceScope->id)
            ->when($targetIds !== [], fn ($query) => $query->whereNotIn('id', $targetIds))
            ->update(['brief_fields_source_scope_id' => null, 'updated_at' => now()]);

        foreach ($targets as $target) {
            $target->update([
                'brief_fields_source_scope_id' => $sourceScope->id,
            ]);
        }
    }

    private function sharedBriefScopeIds(array $validated): array
    {
        return array_values(array_unique(array_map(
            'intval',
            $validated['shared_brief_scope_ids'] ?? [],
        )));
    }

    private function ensureScopeBelongsToSubCategory(
        BusinessSubCategory $businessSubCategory,
        BusinessAiPurposeScope $businessAiPurposeScope,
    ): void {
        abort_unless(
            (int) $businessAiPurposeScope->business_sub_category_id === (int) $businessSubCategory->id,
            404,
        );
    }

    private function normaliseGeneralData(array $points): array
    {
        return collect($points)
            ->map(fn ($point) => is_string($point) ? trim($point) : '')
            ->filter()
            ->values()
            ->all();
    }

    private function normaliseFields(array $fields): array
    {
        return collect($fields)->map(fn (array $field) => [
            'key' => Str::lower(trim($field['key'])),
            'label' => trim($field['label']),
            'hint' => blank($field['hint'] ?? null) ? null : trim($field['hint']),
            'required' => (bool) ($field['required'] ?? false),
        ])->values()->all();
    }

    private function styleIds(array $validated): array
    {
        return array_values(array_unique(array_map('intval', $validated['style_ids'] ?? [])));
    }

    private function loadSubCategoryContext(BusinessSubCategory $businessSubCategory): void
    {
        $businessSubCategory->loadMissing('business_category:id,name');
        abort_unless($businessSubCategory->business_category, 404);
    }
}
