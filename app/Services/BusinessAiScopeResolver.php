<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessAiPurpose;
use App\Models\BusinessAiPurposeScope;
use App\Models\BusinessAiStyle;
use App\Models\BusinessAiGeneration;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Resolves a category/subcategory Custom Post scope only against businesses
 * owned by the signed-in user. This is the boundary that prevents one
 * business's category setup or private snapshot leaking into another's flow.
 */
class BusinessAiScopeResolver
{
    public function __construct(private FestivalAiBusinessContextService $businessContext)
    {
    }

    /** @return Collection<int, Business> */
    public function activeBusinesses(User $user): Collection
    {
        $business = $this->activeBusiness($user);

        return $business ? new Collection([$business]) : new Collection();
    }

    /**
     * Custom Post has one deliberate business context: the business currently
     * marked active in My Business (`is_default`). It must never silently
     * fall back to a different business, because its category/subcategory
     * configuration controls which Custom Post Types may be used.
     */
    public function activeBusiness(User $user): ?Business
    {
        return Business::query()
            ->with([
                'business_category:id,name',
                'sub_categories:id,name,business_category_id',
                'products:id,name',
            ])
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->where('is_default', 1)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /** @return Collection<int, BusinessAiPurposeScope> */
    public function availableScopesFor(User $user): Collection
    {
        $businesses = $this->activeBusinesses($user);
        if ($businesses->isEmpty()) {
            return new Collection();
        }

        return BusinessAiPurposeScope::query()
            ->where('status', true)
            ->whereHas('purpose', fn ($query) => $query->where('status', true))
            ->with([
                'purpose:id,key,title,icon,description,brief_fields,allowed_size_keys,product_upload_enabled,product_required,max_product_references,change_instruction_limit,business_ai_header_footer_style_id,status,sort_order',
                'purpose.styles' => fn ($query) => $query->where('business_ai_styles.status', true),
                'purpose.headerFooterStyle:id,name,header_prompt,footer_prompt,overlay_enabled,status',
                'category:id,name',
                'subCategory:id,name,business_category_id',
                'briefFieldsSource:id,business_ai_purpose_id,brief_fields,brief_fields_source_scope_id',
                'styles' => fn ($query) => $query->where('business_ai_styles.status', true),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (BusinessAiPurposeScope $scope) => $businesses->contains(fn (Business $business) => $this->matchesBusiness($scope, $business)))
            ->values();
    }

    /**
     * @return array{scope: BusinessAiPurposeScope, purpose: BusinessAiPurpose, business: Business, business_snapshot: array, scope_snapshot: array, styles: Collection<int, BusinessAiStyle>}
     */
    public function resolve(User $user, int $scopeId, string $purposeKey, ?int $businessId = null): array
    {
        $scope = BusinessAiPurposeScope::query()
            ->whereKey($scopeId)
            ->where('status', true)
            ->whereHas('purpose', fn ($query) => $query->where('key', $purposeKey)->where('status', true))
            ->with([
                'purpose:id,key,title,icon,description,base_prompt,product_prompt,brief_fields,allowed_size_keys,product_upload_enabled,product_required,max_product_references,change_instruction_limit,business_ai_header_footer_style_id,status,sort_order',
                'purpose.styles' => fn ($query) => $query->where('business_ai_styles.status', true),
                'purpose.headerFooterStyle:id,name,header_prompt,footer_prompt,overlay_enabled,status',
                'category:id,name',
                'subCategory:id,name,business_category_id',
                'briefFieldsSource:id,business_ai_purpose_id,brief_fields,brief_fields_source_scope_id',
                'styles' => fn ($query) => $query->where('business_ai_styles.status', true),
            ])
            ->first();

        if (!$scope || !$scope->purpose) {
            throw new \DomainException('Select a valid Custom Post Type and category scope.');
        }

        $business = $this->activeBusiness($user);

        if (!$business) {
            throw new \DomainException('Choose an active business in My Business before creating a Custom Post.');
        }
        if ($businessId && (int) $businessId !== (int) $business->id) {
            throw new \DomainException('Custom Posts always use the business currently active in My Business.');
        }
        if (!$this->matchesBusiness($scope, $business)) {
            throw new \DomainException('This Custom Post Type is not available for your active business category.');
        }

        $purpose = $scope->purpose;
        $styles = $this->stylesFor($scope, $purpose);
        if ($styles->isEmpty()) {
            throw new \DomainException('This Custom Post Type does not have an active post style for the selected category.');
        }

        $businessSnapshot = $this->businessSnapshot($business);

        return [
            'scope' => $scope,
            'purpose' => $purpose,
            'business' => $business,
            'business_snapshot' => $businessSnapshot,
            'scope_snapshot' => $this->scopeSnapshot($scope, $purpose),
            'styles' => $styles,
        ];
    }

    public function matchesBusiness(BusinessAiPurposeScope $scope, Business $business): bool
    {
        if ((int) $scope->business_category_id !== (int) $business->business_category_id) {
            return false;
        }

        if (!$scope->business_sub_category_id) {
            return true;
        }

        // Treat a bad admin mapping as unavailable instead of exposing a
        // cross-category configuration to the mobile client.
        if ((int) optional($scope->subCategory)->business_category_id !== (int) $scope->business_category_id) {
            return false;
        }

        return in_array((int) $scope->business_sub_category_id, $this->subCategoryIds($business), true);
    }

    /** @return Collection<int, BusinessAiStyle> */
    public function stylesFor(BusinessAiPurposeScope $scope, BusinessAiPurpose $purpose): Collection
    {
        $parentStyles = $purpose->relationLoaded('styles')
            ? $purpose->styles
            : $purpose->styles()->where('business_ai_styles.status', true)->get();
        $parentStyles = $parentStyles->filter(fn (BusinessAiStyle $style) => (bool) $style->status)->values();

        $scopeStyles = $scope->relationLoaded('styles')
            ? $scope->styles
            : $scope->styles()->where('business_ai_styles.status', true)->get();
        $scopeStyles = $scopeStyles->filter(fn (BusinessAiStyle $style) => (bool) $style->status)->values();

        // No scope-style rows means the scope inherits every active style of
        // its parent. Rows present means it deliberately narrows the list.
        if ($scopeStyles->isEmpty()) {
            return $parentStyles;
        }

        $parentIds = $parentStyles->pluck('id')->map(fn ($id) => (int) $id)->all();
        return $scopeStyles
            ->filter(fn (BusinessAiStyle $style) => in_array((int) $style->id, $parentIds, true))
            ->values();
    }

    public function scopePayload(BusinessAiPurposeScope $scope, BusinessAiPurpose $purpose, array $matchingBusinessIds = []): array
    {
        return [
            'id' => $scope->id,
            'category' => [
                'id' => (int) $scope->business_category_id,
                'name' => optional($scope->category)->name,
            ],
            'subcategory' => $scope->business_sub_category_id ? [
                'id' => (int) $scope->business_sub_category_id,
                'name' => optional($scope->subCategory)->name,
            ] : null,
            'fields' => $scope->resolvedBriefFields(),
            'general_data' => $scope->resolvedGeneralData(),
            'content_instruction' => $this->cleanText($scope->content_instruction, 3000),
            'styles' => $this->stylesFor($scope, $purpose)->map(fn (BusinessAiStyle $style) => $this->stylePayload($style))->values(),
            'header_footer' => $this->headerFooterPayload($purpose),
            'matching_business_ids' => array_values(array_unique(array_map('intval', $matchingBusinessIds))),
        ];
    }

    public function businessPayload(Business $business): array
    {
        return [
            'id' => $business->id,
            'name' => $business->name,
            'is_default' => (bool) $business->is_default,
            'is_active_business' => (bool) $business->is_default,
            'brand_theme' => $this->brandThemePayload($business),
            'category' => [
                'id' => (int) $business->business_category_id,
                'name' => optional($business->business_category)->name,
            ],
            'subcategories' => collect($this->subCategoryIds($business))->map(function (int $id) use ($business) {
                $subCategory = $business->sub_categories->firstWhere('id', $id);
                return ['id' => $id, 'name' => optional($subCategory)->name];
            })->values(),
            'services' => $business->products->pluck('name')->filter()->values(),
        ];
    }

    public function businessSnapshot(Business $business): array
    {
        $snapshot = $this->businessContext->snapshotForBusiness($business);
        $snapshot['category'] = array_filter([
            'id' => (int) $business->business_category_id,
            'name' => optional($business->business_category)->name,
        ], static fn ($value) => $value !== null);
        $snapshot['subcategories'] = collect($this->subCategoryIds($business))
            ->map(function (int $id) use ($business) {
                $subCategory = $business->sub_categories->firstWhere('id', $id);
                return array_filter(['id' => $id, 'name' => optional($subCategory)->name], static fn ($value) => $value !== null);
            })
            ->values()
            ->all();
        $snapshot['services'] = $business->products
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->map(fn ($name) => trim($name))
            ->unique()
            ->take(20)
            ->values()
            ->all();
        // User-owned catalogue entries are a second, optional source of My
        // Business facts. Keep them compact; never expose another user's data.
        $snapshot['catalog_items'] = Product::query()
            ->where('user_id', $business->user_id)
            ->whereNotNull('title')
            ->orderByDesc('id')
            ->limit(12)
            ->pluck('title')
            ->filter(fn ($title) => is_string($title) && trim($title) !== '')
            ->map(fn ($title) => trim($title))
            ->unique()
            ->values()
            ->all();
        // These are only previews the same user already approved by pressing
        // Final for a completed Custom Post. They provide continuity without
        // ever reading another business user's history.
        $snapshot['old_confirmed_content'] = BusinessAiGeneration::query()
            ->where('user_id', $business->user_id)
            ->where('status', 'completed')
            ->whereNotNull('content_preview')
            ->latest('id')
            ->limit(6)
            ->get(['id', 'content_preview'])
            ->map(function (BusinessAiGeneration $generation) {
                return array_filter([
                    'headline' => data_get($generation->content_preview, 'headline'),
                    'content' => data_get($generation->content_preview, 'content'),
                    'cta' => data_get($generation->content_preview, 'cta'),
                ], static fn ($value) => is_string($value) && trim($value) !== '');
            })
            ->filter()
            ->values()
            ->all();

        return $snapshot;
    }

    public function scopeSnapshot(BusinessAiPurposeScope $scope, BusinessAiPurpose $purpose): array
    {
        return [
            'id' => $scope->id,
            'purpose_key' => $purpose->key,
            'category' => [
                'id' => (int) $scope->business_category_id,
                'name' => optional($scope->category)->name,
            ],
            'subcategory' => $scope->business_sub_category_id ? [
                'id' => (int) $scope->business_sub_category_id,
                'name' => optional($scope->subCategory)->name,
            ] : null,
            'brief_fields' => $scope->resolvedBriefFields(),
            'general_data' => $scope->resolvedGeneralData(),
            'content_instruction' => $this->cleanText($scope->content_instruction, 3000),
            'header_footer' => $this->headerFooterPayload($purpose, true),
        ];
    }

    private function subCategoryIds(Business $business): array
    {
        $mapped = $business->relationLoaded('sub_categories')
            ? $business->sub_categories->modelKeys()
            : $business->sub_categories()->pluck('business_sub_category.id')->all();

        return collect(array_merge((array) $business->business_sub_category_ids, $mapped))
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function stylePayload(BusinessAiStyle $style): array
    {
        return [
            'id' => $style->id,
            'key' => $style->key,
            'name' => $style->name,
            'description' => $style->description,
            'colors' => $style->colors ?? [],
            'preview_image_path' => $style->preview_image,
        ];
    }

    private function headerFooterPayload(BusinessAiPurpose $purpose, bool $includePrompts = false): ?array
    {
        $style = $purpose->headerFooterStyle;
        if (!$style || !$style->status) {
            return null;
        }

        $payload = [
            'name' => $style->name,
            'overlay_enabled' => (bool) $style->overlay_enabled,
        ];
        if ($includePrompts) {
            $payload['header_prompt'] = $style->header_prompt;
            $payload['footer_prompt'] = $style->footer_prompt;
        }

        return $payload;
    }

    private function cleanText(mixed $value, int $limit): ?string
    {
        $text = trim(strip_tags((string) $value));
        return $text === '' ? null : Str::limit($text, $limit, '');
    }

    private function brandThemePayload(Business $business): ?array
    {
        $colors = collect([$business->brand_primary_color, $business->brand_secondary_color])
            ->map(fn ($color) => strtoupper(trim((string) $color)))
            ->filter(fn ($color) => preg_match('/^#[A-F0-9]{6}$/', $color) === 1)
            ->values()
            ->all();

        return count($colors) === 2
            ? ['primary_color' => $colors[0], 'secondary_color' => $colors[1]]
            : null;
    }
}
