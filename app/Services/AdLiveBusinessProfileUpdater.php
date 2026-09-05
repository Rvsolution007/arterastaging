<?php

namespace App\Services;

use App\Models\AdLiveBusinessProfileUpdate;
use App\Models\Business;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdLiveBusinessProfileUpdater
{
    public function __construct(
        private AdLiveBusinessProfileService $profiles,
        private AdLiveProfileTaxonomy $taxonomy,
        private AdLiveInternalRequestVerifier $signatures,
    ) {
    }

    public function update(array $data): array
    {
        $fingerprint = hash('sha256', $this->signatures->canonicalPayload($data));

        return DB::transaction(function () use ($data, $fingerprint): array {
            // Select only profile/authorization columns; credentials are never
            // loaded. Lock identity first so edits to different businesses of
            // the same identity cannot race on its shared name/email/phone.
            $user = User::query()->whereKey($data['identity']['artera_user_id'])
                ->lockForUpdate()->first(['id', 'name', 'email', 'mobile_no', 'status', 'user_type', 'email_verified_at', 'updated_at']);
            $business = Business::query()->whereKey($data['business']['id'])
                ->lockForUpdate()->first();

            if (! $user || ! $business || (string) $business->user_id !== (string) $user->id) {
                $this->reject(403, ['message' => 'The Artera user does not own this business.']);
            }
            if ((int) $user->status !== 1 || (int) $business->status !== 1 || $user->user_type === 'Demo') {
                $this->reject(403, ['message' => 'This profile is not available for updates.']);
            }

            // A unique DB key arbitrates even when the same request_id is sent
            // simultaneously for different identities/businesses. Reservation,
            // profile writes and the completed audit commit or roll back together.
            $claimed = DB::table('adlive_business_profile_updates')->insertOrIgnore([
                'request_id' => $data['request_id'],
                'request_fingerprint' => $fingerprint,
                'source' => 'adlive',
                'artera_user_id' => $user->id,
                'artera_business_id' => $business->id,
                'changed_fields' => '[]',
                'occurred_at' => CarbonImmutable::parse($data['occurred_at'])->utc()->format('Y-m-d H:i:s'),
                'resulting_profile_version' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $audit = AdLiveBusinessProfileUpdate::query()->where('request_id', $data['request_id'])
                ->lockForUpdate()->firstOrFail();

            // Lock the pivot ranges before reading a canonical snapshot.
            foreach (['business_sub_category_mappings', 'business_type_mappings', 'business_product_mappings'] as $table) {
                DB::table($table)->where('business_id', $business->id)->lockForUpdate()->get();
            }
            $before = $this->profiles->sharedSnapshot($user, $business);

            if (! $claimed) {
                if (! hash_equals($audit->request_fingerprint, $fingerprint)
                    || (string) $audit->artera_user_id !== (string) $user->id
                    || (string) $audit->artera_business_id !== (string) $business->id) {
                    $this->reject(409, [
                        'code' => 'request_id_conflict',
                        'message' => 'The request_id has already been used for a different update.',
                        'profile' => $before,
                    ]);
                }

                // An exact retry acknowledges the original update without
                // reapplying it, and returns the latest canonical data.
                return $before;
            }

            $clientVersion = Arr::get($data, 'business.client_profile_version');
            if ($clientVersion !== null && ! hash_equals($before['business']['profile_version'], $clientVersion)) {
                $this->reject(409, [
                    'code' => 'profile_version_conflict',
                    'message' => 'The business profile has changed in Artera Pixel.',
                    'profile' => $before,
                ]);
            }

            $selection = $this->taxonomy->validate($data['business'], $business);
            $this->applyIdentity($data['identity'], $user);
            foreach (['name' => 'name', 'location' => 'address'] as $field => $column) {
                if (array_key_exists($field, $data['business'])) {
                    $business->{$column} = $data['business'][$field];
                }
            }
            if (array_key_exists('category', $data['business'])) {
                $business->business_category_id = $selection['category_id'];
            }
            if (array_key_exists('sub_categories', $data['business'])) {
                $business->sub_categories()->sync($selection['sub_categories']);
                $business->business_sub_category_ids = $selection['sub_categories'];
            }
            if (array_key_exists('business_types', $data['business'])) {
                $business->types()->sync($selection['business_types']);
                $business->business_type_id = $selection['business_types'][0] ?? null;
            }
            if (array_key_exists('products', $data['business'])) {
                $business->products()->sync($selection['products']);
            }

            // A new UUID prevents same-second and no-op updates from sharing a
            // revision. Existing Pixel writes are detected by the content hash.
            $business->profile_version = (string) Str::uuid();
            $business->updated_at = now();
            $business->save();
            $business->unsetRelations();
            $after = $this->profiles->sharedSnapshot($user, $business);

            $changed = [];
            foreach (['identity' => ['name', 'email', 'phone'], 'business' => ['name', 'category', 'sub_categories', 'business_types', 'products', 'location']] as $section => $fields) {
                foreach ($fields as $field) {
                    if ($before[$section][$field] !== $after[$section][$field]) {
                        $changed[] = $section.'.'.$field;
                    }
                }
            }
            sort($changed, SORT_STRING);
            $audit->update([
                'changed_fields' => $changed,
                'resulting_profile_version' => $after['business']['profile_version'],
            ]);

            return $after;
        }, 3);
    }

    private function applyIdentity(array $identity, User $user): void
    {
        foreach (['name' => 'name', 'email' => 'email', 'phone' => 'mobile_no'] as $field => $column) {
            if (array_key_exists($field, $identity)) {
                $value = $field === 'email' ? Str::lower(trim($identity[$field])) : $identity[$field];
                if (in_array($field, ['email', 'phone'], true) && $value !== '' && (string) $user->{$column} !== $value) {
                    $owner = User::withTrashed()->where($column, $value)->whereKeyNot($user->id)
                        ->lockForUpdate()->first(['id']);
                    if ($owner) {
                        throw ValidationException::withMessages(['identity.'.$field => ['This value is already used by another identity.']]);
                    }
                }
                $user->{$column} = $value === '' && $field === 'phone' ? null : $value;
            }
        }

        // This follows the existing mobile profile-update behavior for these
        // shared fields. Credential and verification flows stay unchanged.
        if ($user->isDirty()) {
            $user->save();
        }
    }

    private function reject(int $status, array $body): void
    {
        throw new HttpResponseException(response()->json($body, $status));
    }
}
