<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;

/**
 * Creates the small, privacy-safe business snapshot used by Festival AI.
 * This deliberately mirrors the existing editor's "Hide in frame" values
 * without reading or rendering any editor/frame data.
 */
class FestivalAiBusinessContextService
{
    public function snapshotForUser(User $user): array
    {
        $business = Business::query()
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (!$business) {
            return [];
        }

        return array_filter([
            'business_id' => $business->id,
            'name' => $this->value($business->name),
            'logo_path' => $this->value($business->logo),
            'phones' => $this->visibleValues(
                $business->mobile_no,
                $business->extra_mobile_numbers,
                $business->hidden_frame_fields,
                'mobile_numbers'
            ),
            'emails' => $this->visibleValues(
                $business->email,
                $business->extra_emails,
                $business->hidden_frame_fields,
                'emails'
            ),
            'websites' => $this->visibleValues(
                $business->website,
                $business->extra_websites,
                $business->hidden_frame_fields,
                'websites'
            ),
            'addresses' => $this->visibleValues(
                $business->address,
                $business->extra_addresses,
                $business->hidden_frame_fields,
                'addresses'
            ),
        ], static fn ($value) => $value !== null && $value !== []);
    }

    private function visibleValues($primary, $extras, $hiddenFields, string $hiddenKey): array
    {
        $hidden = collect((array) $hiddenFields)
            ->get($hiddenKey, []);
        $hidden = collect((array) $hidden)
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
            ->all();

        return collect(array_merge([$primary], (array) $extras))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
            ->reject(fn (string $value) => in_array($value, $hidden, true))
            ->unique()
            ->values()
            ->all();
    }

    private function value($value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
