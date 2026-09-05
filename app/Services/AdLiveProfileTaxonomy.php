<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessProduct;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use Illuminate\Validation\ValidationException;

class AdLiveProfileTaxonomy
{
    public function validate(array $data, Business $business): array
    {
        $selection = [
            'category_id' => $business->business_category_id === null ? null : (int) $business->business_category_id,
            'sub_categories' => $this->currentIds($business->sub_categories),
            'business_types' => $this->currentIds($business->types),
            'products' => $this->currentIds($business->products),
        ];
        if (! array_intersect(['category', 'sub_categories', 'business_types', 'products'], array_keys($data))) {
            return $selection;
        }

        if (isset($data['category'])) {
            $category = BusinessCategory::query()->where('status', 1);
            if (isset($data['category']['id'])) {
                $category->whereKey($data['category']['id']);
            } elseif (isset($data['category']['name'])) {
                $category->where('name', $data['category']['name']);
            } else {
                $this->invalid('category', 'Supply an Artera category ID or an unambiguous category name.');
            }
            $matches = $category->sharedLock()->get(['id']);
            if ($matches->count() !== 1) {
                $this->invalid('category', 'Choose one active Artera category.');
            }
            $selection['category_id'] = (int) $matches->first()->id;
        }

        foreach (['sub_categories', 'business_types', 'products'] as $field) {
            if (array_key_exists($field, $data)) {
                $selection[$field] = array_map(fn ($item) => (int) $item['id'], $data[$field]);
                sort($selection[$field], SORT_NUMERIC);
            }
        }

        // Validate the complete resulting graph, including retained selections.
        // A category change cannot leave old, incompatible children behind.
        $subCategories = BusinessSubCategory::query()->whereIn('id', $selection['sub_categories'])
            ->where('status', 1)->where('business_category_id', $selection['category_id'])
            ->sharedLock()->get(['id']);
        if ($subCategories->count() !== count($selection['sub_categories'])) {
            $this->invalid('sub_categories', 'Choose active sub-categories belonging to the selected category.');
        }
        $types = BusinessType::query()->whereIn('id', $selection['business_types'])
            ->where('status', 1)->whereIn('business_sub_category_id', $selection['sub_categories'])
            ->sharedLock()->get(['id']);
        if ($types->count() !== count($selection['business_types'])) {
            $this->invalid('business_types', 'Choose active business types belonging to the selected sub-categories.');
        }
        $products = BusinessProduct::query()->whereIn('id', $selection['products'])->where('status', 1)
            ->sharedLock()->get(['id', 'business_category_id', 'business_sub_category_id', 'business_type_id']);
        if ($products->count() !== count($selection['products']) || $products->contains(function ($product) use ($selection): bool {
            return ($product->business_category_id !== null && (int) $product->business_category_id !== $selection['category_id'])
                || ($product->business_sub_category_id !== null && ! in_array((int) $product->business_sub_category_id, $selection['sub_categories'], true))
                || ($product->business_type_id !== null && ! in_array((int) $product->business_type_id, $selection['business_types'], true));
        })) {
            $this->invalid('products', 'Choose active products belonging to the selected business taxonomy.');
        }

        // Client-supplied display names never rename global Pixel taxonomy.
        return $selection;
    }

    private function currentIds($items): array
    {
        return $items->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    private function invalid(string $field, string $message): void
    {
        throw ValidationException::withMessages(['business.'.$field => [$message]]);
    }
}
