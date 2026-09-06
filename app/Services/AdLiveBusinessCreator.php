<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessProductRequest;
use App\Models\BusinessSubCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdLiveBusinessCreator
{
    public function __construct(private AdLiveBusinessProfileService $profiles)
    {
    }

    /**
     * Create only Pixel-owned business/profile records. This intentionally
     * does not load or create any credential, campaign, billing or ad data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            // Select no credential columns. The lock serializes default-business
            // selection and guarantees ownership is the supplied Pixel user.
            $user = User::query()->whereKey($data['artera_user_id'])->lockForUpdate()
                ->first(['id', 'name', 'email', 'mobile_no', 'status', 'user_type', 'updated_at']);
            if (! $user) {
                throw ValidationException::withMessages(['artera_user_id' => ['The Artera user does not exist.']]);
            }
            if ((int) $user->status !== 1 || $user->user_type === 'Demo') {
                $this->reject(403, 'This Artera user is not available for business creation.');
            }

            $businessData = $data['business'];
            $taxonomy = $this->validateTaxonomy($businessData);

            // Lock the user's existing business range before choosing a single
            // default business. New AdLive businesses never replace it.
            $hasExistingBusiness = Business::query()->where('user_id', $user->id)
                ->lockForUpdate()->exists();

            $business = new Business();
            $business->name = $businessData['name'];
            $business->user_id = $user->id;
            $business->business_category_id = $taxonomy['category']->id;
            $business->business_sub_category_ids = $taxonomy['sub_category_ids'];
            $business->adlive_business_type = $businessData['business_type'];
            $business->website = $businessData['website'];
            $business->address = $businessData['location'];
            $business->status = 1;
            $business->is_default = $hasExistingBusiness ? 0 : 1;
            $business->profile_version = (string) Str::uuid();
            $business->save();

            $business->sub_categories()->sync($taxonomy['sub_category_ids']);

            // The API accepts user-owned free-text products/services, not
            // global Pixel product IDs. Persist them using Pixel's existing
            // custom-product request model; no global taxonomy is altered.
            $productRows = [];
            foreach ($businessData['products'] as $name) {
                $productRows[] = [
                    'business_id' => $business->id,
                    'business_sub_category_id' => $taxonomy['sub_category_ids'][0],
                    'requested_name' => $name,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($productRows !== []) {
                BusinessProductRequest::query()->insert($productRows);
            }

            $business->refresh();

            return $this->snapshot($user, $business, $taxonomy['category'], $taxonomy['sub_categories'], $businessData);
        }, 3);
    }

    /**
     * @param array<string, mixed> $businessData
     * @return array{category: BusinessCategory, sub_categories: array<int, BusinessSubCategory>, sub_category_ids: array<int, int>}
     */
    private function validateTaxonomy(array $businessData): array
    {
        $category = BusinessCategory::query()->whereKey($businessData['category_id'])
            ->where('status', 1)->sharedLock()->first();
        if (! $category) {
            throw ValidationException::withMessages(['business.category_id' => ['Choose an active Artera category.']]);
        }

        $subCategoryIds = array_map('intval', $businessData['sub_category_ids']);
        $subCategories = BusinessSubCategory::query()->whereIn('id', $subCategoryIds)
            ->where('status', 1)->where('business_category_id', $category->id)
            ->sharedLock()->get()->keyBy('id');
        if ($subCategories->count() !== count($subCategoryIds)) {
            throw ValidationException::withMessages([
                'business.sub_category_ids' => ['Choose active sub-categories belonging to the selected category.'],
            ]);
        }

        // Keep the caller's array ordering in both the durable JSON column and
        // canonical response, while the IDs themselves remain validated data.
        $orderedSubCategories = array_map(fn (int $id) => $subCategories->get($id), $subCategoryIds);

        return [
            'category' => $category,
            'sub_categories' => $orderedSubCategories,
            'sub_category_ids' => $subCategoryIds,
        ];
    }

    /**
     * @param array<int, BusinessSubCategory> $subCategories
     * @param array<string, mixed> $businessData
     * @return array<string, mixed>
     */
    private function snapshot(User $user, Business $business, BusinessCategory $category, array $subCategories, array $businessData): array
    {
        return [
            'identity' => [
                'artera_user_id' => (string) $user->id,
                'name' => (string) $user->name,
                'email' => mb_strtolower(trim((string) $user->email)),
                'phone' => (string) ($user->mobile_no ?: ''),
            ],
            'business' => [
                'id' => (string) $business->id,
                'name' => (string) $business->name,
                'category' => ['id' => (string) $category->id, 'name' => (string) $category->name],
                'sub_categories' => array_map(fn (BusinessSubCategory $subCategory) => [
                    'id' => (string) $subCategory->id,
                    'name' => (string) $subCategory->name,
                ], $subCategories),
                'business_types' => $this->businessTypes($businessData['business_type']),
                'products' => array_values($businessData['products']),
                'website' => (string) $business->website,
                'location' => (string) $business->address,
                // Use the same canonical revision returned by the existing
                // profile-update API. A creation response can therefore be
                // sent back as client_profile_version on the next sync.
                'profile_version' => $this->profiles->profileVersion($user, $business),
                'updated_at' => $business->updated_at?->toIso8601String(),
            ],
        ];
    }

    /** @return array<int, string> */
    private function businessTypes(string $businessType): array
    {
        return match ($businessType) {
            'product' => ['product'],
            'service' => ['service'],
            'product_and_service' => ['product', 'service'],
        };
    }

    private function reject(int $status, string $message): never
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json(['message' => $message], $status));
    }
}
