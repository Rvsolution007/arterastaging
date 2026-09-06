<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessAiPurpose;
use App\Models\BusinessAiPurposeScope;

/**
 * Read-only Type-side overview of scoped AI Post Data.
 *
 * Creation and editing deliberately live under Business Subcategories. This
 * prevents an admin from selecting a mismatched category/subcategory while
 * adding General Data for a Custom Post Type.
 */
class BusinessAiPurposeScopeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessFrame');
    }

    public function index(BusinessAiPurpose $businessAiPurpose)
    {
        $scopes = $businessAiPurpose->scopes()
            ->with([
                'category:id,name',
                'subCategory:id,name,business_category_id',
            ])
            ->withCount('styles')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(50);

        return view('business_ai.purposes.scopes.index', compact('businessAiPurpose', 'scopes'));
    }

    public function create(BusinessAiPurpose $businessAiPurpose)
    {
        return $this->redirectToSubcategoryList();
    }

    /**
     * Kept only for old bookmarks or an already-open legacy form. New data
     * must always be created from the nested Business Subcategory page.
     */
    public function store(BusinessAiPurpose $businessAiPurpose)
    {
        return $this->redirectToSubcategoryList();
    }

    public function edit(BusinessAiPurpose $businessAiPurpose, BusinessAiPurposeScope $businessAiPurposeScope)
    {
        $this->ensureScopeBelongsToPurpose($businessAiPurpose, $businessAiPurposeScope);

        if ($businessAiPurposeScope->business_sub_category_id && $businessAiPurposeScope->subCategory) {
            return redirect()->route('business_sub_category.ai_data.edit', [
                $businessAiPurposeScope->subCategory,
                $businessAiPurposeScope,
            ]);
        }

        return redirect()
            ->route('custom_post_types.scopes.index', $businessAiPurpose)
            ->with('info', 'This is a legacy category-wide fallback. New AI Post Data is managed from a Business Subcategory.');
    }

    /**
     * Do not process stale requests from the retired category-selector form.
     * Redirecting avoids accidental creation or updates outside a fixed
     * subcategory context.
     */
    public function update(BusinessAiPurpose $businessAiPurpose, BusinessAiPurposeScope $businessAiPurposeScope)
    {
        $this->ensureScopeBelongsToPurpose($businessAiPurpose, $businessAiPurposeScope);

        return $this->edit($businessAiPurpose, $businessAiPurposeScope);
    }

    /**
     * Deletion is also handled from the nested page so its subcategory context
     * is visible to the admin before the destructive action is confirmed.
     */
    public function destroy(BusinessAiPurpose $businessAiPurpose, BusinessAiPurposeScope $businessAiPurposeScope)
    {
        $this->ensureScopeBelongsToPurpose($businessAiPurpose, $businessAiPurposeScope);

        return $this->edit($businessAiPurpose, $businessAiPurposeScope);
    }

    private function redirectToSubcategoryList()
    {
        return redirect()
            ->route('business-sub-category.index')
            ->with('info', 'Open the required Business Subcategory, then use AI Post Data. The category and subcategory will remain fixed there.');
    }

    private function ensureScopeBelongsToPurpose(
        BusinessAiPurpose $businessAiPurpose,
        BusinessAiPurposeScope $businessAiPurposeScope,
    ): void {
        abort_unless(
            (int) $businessAiPurposeScope->business_ai_purpose_id === (int) $businessAiPurpose->id,
            404,
        );
    }
}
