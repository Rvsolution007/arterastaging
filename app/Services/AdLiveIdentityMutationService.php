<?php

namespace App\Services;

use App\Models\AdLiveIdentityRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pixel's single mutation boundary for normal customer identities requested
 * by AdLive. It has no role, campaign, billing, wallet, Meta, or permission
 * inputs, and records only safe audit metadata.
 */
class AdLiveIdentityMutationService
{
    public function __construct(
        private AdLiveBusinessProfileService $profiles,
        private AdLiveIdentitySyncService $sync,
        private AdLiveSignedSecurityEventService $security,
        private MobileAccessTokenService $mobileTokens,
        private AdLiveInternalRequestVerifier $signatures,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): array
    {
        [$snapshot, $user, $publish] = DB::transaction(function () use ($data): array {
            [$audit, $replay] = $this->claim($data, 'create');
            if ($replay) {
                $user = $this->findAuditedUser($audit);

                return [$this->profiles->canonicalIdentitySnapshot($user), $user, false];
            }

            $identity = $data['identity'];
            $this->ensureIdentityAvailable($identity['email'], $identity['phone'] ?? null);
            $user = User::create([
                'name' => $identity['name'],
                'email' => Str::lower(trim($identity['email'])),
                'mobile_no' => ($identity['phone'] ?? '') ?: null,
                'password' => Hash::make($data['password']),
                'status' => 1,
                'login_type' => 'normal',
                'registration_source' => 'artera_pixel',
                'referral_code' => strtoupper(Str::random(10)),
            ]);
            // User type is a server-owned default, never a request field.
            $user->forceFill(['user_type' => 'User'])->save();

            $audit->update([
                'artera_user_id' => $user->id,
                'changed_fields' => ['identity.created'],
            ]);

            return [$this->profiles->canonicalIdentitySnapshot($user), $user, true];
        }, 3);

        // This callback is Pixel-originated and is queued only after commit.
        if ($publish) {
            $this->sync->queueForUser($user, 'identity.created');
        }

        return $snapshot;
    }

    /** @param array<string, mixed> $data */
    public function update(array $data): array
    {
        [$snapshot, $user, $publish] = DB::transaction(function () use ($data): array {
            $user = User::query()->whereKey($data['identity']['artera_user_id'])->lockForUpdate()->first();
            if (! $user || (int) $user->status !== 1 || $this->isProtectedIdentity($user)) {
                $this->reject(403, ['message' => 'This identity is not available for updates.']);
            }

            [$audit, $replay] = $this->claim($data, 'update', $user->id);
            if ($replay) {
                return [$this->profiles->canonicalIdentitySnapshot($user), $user, false];
            }

            if (isset($data['expected_updated_at'])
                && ! hash_equals((string) $user->updated_at?->toIso8601String(), $data['expected_updated_at'])) {
                $this->reject(409, [
                    'code' => 'identity_version_conflict',
                    'message' => 'The identity has changed in Artera Pixel.',
                    'identity' => $this->profiles->canonicalIdentitySnapshot($user),
                ]);
            }

            $identity = $data['identity'];
            $changed = [];
            if (array_key_exists('name', $identity) && $user->name !== $identity['name']) {
                $user->name = $identity['name'];
                $changed[] = 'identity.name';
            }
            if (array_key_exists('email', $identity)) {
                $email = Str::lower(trim($identity['email']));
                if ($email !== $user->email) {
                    $this->ensureIdentityAvailable($email, null, $user->id);
                    $user->email = $email;
                    // A changed address must be verified by Pixel again.
                    $user->email_verified_at = null;
                    $changed[] = 'identity.email';
                }
            }
            if (array_key_exists('phone', $identity)) {
                $phone = $identity['phone'] ?: null;
                if ((string) ($user->mobile_no ?: '') !== (string) ($phone ?: '')) {
                    $this->ensureIdentityAvailable(null, $phone, $user->id);
                    $user->mobile_no = $phone;
                    $changed[] = 'identity.phone';
                }
            }
            if ($user->isDirty()) {
                $user->save();
            }

            $audit->update(['changed_fields' => $changed]);

            return [$this->profiles->canonicalIdentitySnapshot($user->fresh()), $user->fresh(), $data['source'] === 'artera_pixel'];
        }, 3);

        if ($publish) {
            $this->sync->queueForUser($user, 'identity.updated');
        }

        return $snapshot;
    }

    /** @param array<string, mixed> $data */
    public function deactivate(array $data): array
    {
        [$snapshot, $user] = DB::transaction(function () use ($data): array {
            $user = User::query()->whereKey($data['artera_user_id'])->lockForUpdate()->first();
            if (! $user || $this->isProtectedIdentity($user)) {
                $this->reject(403, ['message' => 'This identity is not available for deletion.']);
            }
            if ((int) $user->status !== 1) {
                $this->reject(409, ['message' => 'This identity is already inactive.']);
            }

            [$audit, $replay] = $this->claim($data, 'delete', $user->id);
            if ($replay) {
                $deleted = User::withTrashed()->whereKey($user->id)->firstOrFail();

                return [$this->profiles->canonicalIdentitySnapshot($deleted, null, false), $deleted];
            }

            // AdLive acknowledges session revocation before the Pixel account
            // becomes inactive, so a failure leaves credentials unchanged.
            if (! $this->security->revokeLinkedSessions($user, 'identity_deleted')) {
                $this->reject(503, ['message' => 'The identity could not be secured for deletion.']);
            }

            $user->status = 0;
            $user->save();
            $this->mobileTokens->revokeAll($user);
            $user->delete();
            $deleted = User::withTrashed()->whereKey($user->id)->firstOrFail();
            $audit->update(['changed_fields' => ['identity.status']]);

            return [$this->profiles->canonicalIdentitySnapshot($deleted, null, false), $deleted];
        }, 3);

        $this->sync->queueDeletion($user);

        return $snapshot;
    }

    /** @param array<string, mixed> $data */
    public function changePassword(array $data, bool $adminReset = false): array
    {
        return DB::transaction(function () use ($data, $adminReset): array {
            $user = User::query()->whereKey($data['artera_user_id'])->lockForUpdate()->first(['id', 'name', 'email', 'mobile_no', 'password', 'status', 'user_type', 'registration_source', 'email_verified_at', 'created_at', 'updated_at']);
            if (! $user || (int) $user->status !== 1 || $this->isProtectedIdentity($user)) {
                $this->reject(403, ['message' => 'This identity is not available for credential changes.']);
            }

            [$audit, $replay] = $this->claim($data, $adminReset ? 'admin_reset' : 'credentials_change', $user->id);
            if ($replay) {
                return $this->profiles->canonicalIdentitySnapshot($user);
            }

            if (! $adminReset && ! Hash::check($data['current_password'], (string) $user->password)) {
                $this->reject(422, ['message' => 'The current password is invalid.']);
            }
            if (! $this->security->revokeLinkedSessions($user, $adminReset ? 'admin_password_reset' : 'password_changed')) {
                $this->reject(503, ['message' => 'The credential change could not be secured.']);
            }

            $user->password = Hash::make($data['new_password']);
            $user->save();
            $this->mobileTokens->revokeAll($user);
            $audit->update(['changed_fields' => ['credentials.password']]);

            return $this->profiles->canonicalIdentitySnapshot($user->fresh());
        }, 3);
    }

    /** @param array<string, mixed> $data */
    private function claim(array $data, string $operation, ?int $userId = null): array
    {
        $fingerprint = hash_hmac(
            'sha256',
            $this->signatures->canonicalPayload($data),
            (string) (config('adlive.shared_secret') ?: config('app.key')),
        );
        $audit = AdLiveIdentityRequest::query()->where('request_id', $data['request_id'])->lockForUpdate()->first();
        if ($audit) {
            if (! hash_equals($audit->request_fingerprint, $fingerprint)
                || $audit->operation !== $operation
                || $audit->source !== $data['source']) {
                $this->reject(409, ['code' => 'request_id_conflict', 'message' => 'The request_id has already been used.']);
            }

            return [$audit, true];
        }

        $audit = AdLiveIdentityRequest::create([
            'request_id' => strtolower($data['request_id']),
            'request_fingerprint' => $fingerprint,
            'operation' => $operation,
            'source' => $data['source'],
            'artera_user_id' => $userId,
            'changed_fields' => [],
            'occurred_at' => CarbonImmutable::parse($data['occurred_at'])->utc(),
        ]);

        return [$audit, false];
    }

    private function findAuditedUser(AdLiveIdentityRequest $audit): User
    {
        $user = User::withTrashed()->whereKey($audit->artera_user_id)->first();
        if (! $user) {
            $this->reject(409, ['message' => 'The original request did not complete.']);
        }

        return $user;
    }

    private function ensureIdentityAvailable(?string $email, ?string $phone, ?int $exceptId = null): void
    {
        $query = User::withTrashed()->when($exceptId, fn ($q) => $q->where('id', '<>', $exceptId));
        $emailTaken = $email !== null && (clone $query)->whereRaw('LOWER(email) = ?', [Str::lower(trim($email))])->exists();
        $phoneTaken = $phone !== null && $phone !== '' && (clone $query)->where('mobile_no', $phone)->exists();
        if ($emailTaken || $phoneTaken) {
            // Do not reveal which field belongs to an existing customer.
            $this->reject(422, ['message' => 'The identity could not be saved with those details.']);
        }
    }

    private function isProtectedIdentity(User $user): bool
    {
        if (preg_match('/(?:admin|staff|super)/i', (string) $user->user_type)) {
            return true;
        }
        try {
            return $user->getRoleNames()->contains(fn (string $role): bool => preg_match('/(?:admin|staff|super)/i', $role) === 1);
        } catch (\Throwable) {
            // Some focused tests intentionally omit the roles tables. In a
            // deployed application Spatie roles provide the additional guard.
            return false;
        }
    }

    private function reject(int $status, array $body): never
    {
        throw new HttpResponseException(response()->json($body, $status));
    }
}
