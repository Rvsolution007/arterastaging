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
        $this->loadProfileRelations($business);

        return [
            'identity' => $this->identity($user),
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
                'profile_version' => $this->profileVersion($user, $business),
                'updated_at' => $this->updatedAt($user, $business),
            ],
        ];
    }

    /**
     * Exact canonical contract returned to AdLive after an inbound update.
     * Deliberately excludes credentials, advertising data and billing data.
     *
     * @return array<string, mixed>
     */
    public function sharedSnapshot(User $user, Business $business): array
    {
        $this->loadProfileRelations($business);

        return [
            'identity' => $this->sharedIdentity($user),
            'business' => array_merge($this->sharedBusiness($business), [
                'profile_version' => $this->profileVersion($user, $business),
                'updated_at' => $this->updatedAt($user, $business),
            ]),
        ];
    }

    /** @return array<string, mixed> */
    public function identity(User $user): array
    {
        return [
            'artera_user_id' => (string) $user->id,
            'name' => (string) $user->name,
            'email' => mb_strtolower(trim((string) $user->email)),
            'phone' => (string) ($user->mobile_no ?: ''),
            'email_verified' => (bool) $user->email_verified_at,
            'signup_source' => $user->registration_source === 'adlive' ? 'adlive' : 'artera_pixel',
        ];
    }

    /**
     * The stored UUID guarantees a new version for each accepted AdLive
     * update. The content fingerprint also detects profile edits made through
     * existing Pixel flows, including pivot-only taxonomy edits.
     */
    public function profileVersion(User $user, Business $business): string
    {
        $this->loadProfileRelations($business);

        $content = [
            'identity' => $this->sharedIdentity($user),
            'business' => $this->sharedBusiness($business),
        ];
        $fingerprint = hash('sha256', json_encode(
            $content,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
        $storedVersion = (string) ($business->profile_version ?: 'legacy');

        return 'profile:v1:'.$storedVersion.':'.substr($fingerprint, 0, 32);
    }

    /** @return array<string, string> */
    private function sharedIdentity(User $user): array
    {
        return [
            'artera_user_id' => (string) $user->id,
            'name' => (string) $user->name,
            'email' => mb_strtolower(trim((string) $user->email)),
            'phone' => (string) ($user->mobile_no ?: ''),
        ];
    }

    /** @return array<string, mixed> */
    private function sharedBusiness(Business $business): array
    {
        return [
            'id' => (string) $business->id,
            'name' => (string) $business->name,
            'category' => [
                'id' => $business->business_category_id === null ? null : (string) $business->business_category_id,
                'name' => (string) optional($business->business_category)->name,
            ],
            'sub_categories' => $this->taxonomyItems($business->sub_categories),
            'business_types' => $this->taxonomyItems($business->types),
            'products' => $this->taxonomyItems($business->products),
            'location' => (string) ($business->address ?: ''),
        ];
    }

    private function updatedAt(User $user, Business $business): ?string
    {
        $updatedAt = collect([$user->updated_at, $business->updated_at])
            ->filter()
            ->sortByDesc(fn ($timestamp) => $timestamp->getTimestamp())
            ->first();

        return $updatedAt ? $updatedAt->toIso8601String() : null;
    }

    private function loadProfileRelations(Business $business): void
    {
        $business->loadMissing([
            'business_category:id,name',
            'sub_categories:id,name',
            'types:id,name',
            'products:id,name',
        ]);
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

        usort($result, fn (array $left, array $right): int => strnatcmp($left['id'], $right['id']));

        return $result;
    }
}
