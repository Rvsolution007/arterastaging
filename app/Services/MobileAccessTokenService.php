<?php

namespace App\Services;

use App\Models\User;

class MobileAccessTokenService
{
    public const TOKEN_NAME = 'mobile-app';

    /**
     * The app uses one rotating bearer token per account. The plaintext value
     * is returned once; Sanctum persists only its SHA-256 hash.
     */
    public function issue(User $user): string
    {
        $user->tokens()->where('name', self::TOKEN_NAME)->delete();

        $newToken = $user->createToken(self::TOKEN_NAME, ['mobile:access']);
        $newToken->accessToken->forceFill([
            'expires_at' => now()->addDays((int) config('sanctum.mobile_token_expiration_days', 30)),
        ])->save();

        return $newToken->plainTextToken;
    }

    public function revokeAll(User $user): void
    {
        $user->tokens()->delete();
    }
}
