<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;

class AdLiveBusinessProfileService
{
    /**
     * Return only the Artera-owned profile data that AdLive needs to display
     * and scope a client's business. Advertising tokens, campaigns and billing
     * data are deliberately excluded because they remain AdLive-owned.
     *
     * @return array<string, mixed>
     */
    public function snapshot(User $user, Business $business): array
    {
        $business->loadMissing([
            'business_category:id,name',
            'sub_categories:id,name',
            'types:id,name',
            'products:id,name',
        ]);

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
                'email' => (string) ($business->email ?: ''),
                'mobile_no' => (string) ($business->mobile_no ?: ''),
                'website' => (string) ($business->website ?: ''),
                'address' => (string) ($business->address ?: ''),
                'location' => (string) ($business->address ?: ''),
                'logo' => (string) ($business->logo ?: ''),
                'brand_primary_color' => (string) ($business->brand_primary_color ?: ''),
                'brand_secondary_color' => (string) ($business->brand_secondary_color ?: ''),
                'extra_emails' => $this->stringItems($business->extra_emails),
                'extra_mobile_numbers' => $this->stringItems($business->extra_mobile_numbers),
                'extra_websites' => $this->stringItems($business->extra_websites),
                'extra_addresses' => $this->stringItems($business->extra_addresses),
                'category' => [
                    'id' => $business->business_category_id === null ? null : (string) $business->business_category_id,
                    'name' => (string) optional($business->business_category)->name,
                ],
                'sub_categories' => $this->taxonomyItems($business->sub_categories),
                'business_types' => $this->taxonomyItems($business->types),
                'products' => $this->taxonomyItems($business->products),
                'profile_version' => $this->profileVersion($business),
                'updated_at' => optional($business->updated_at)->toIso8601String(),
            ],
        ];
    }

    private function profileVersion(Business $business): string
    {
        $updatedAt = $business->updated_at ? $business->updated_at->format('U.u') : '0';

        return 'business:'.$business->id.':'.$updatedAt;
    }

    /** @param mixed $items */
    private function stringItems($items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $items), function (string $item): bool {
            return $item !== '';
        }));
    }

    /** @param iterable $items */
    private function taxonomyItems($items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = [
                'id' => (string) $item->id,
                'name' => (string) $item->name,
            ];
        }

        return $result;
    }
}
