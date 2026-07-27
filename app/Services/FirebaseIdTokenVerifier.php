<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

/**
 * Verifies Firebase Authentication ID tokens without accepting any identity
 * fields directly from the mobile client.
 */
class FirebaseIdTokenVerifier
{
    private const CERTIFICATE_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    private const CERTIFICATE_CACHE_KEY = 'firebase-id-token-certificates';

    /**
     * @return array{uid: string, email: string, name: string, photo_url: string|null}
     */
    public function verifyGoogleIdentity(string $idToken): array
    {
        $projectId = trim((string) config('services.firebase.project_id'));

        if ($projectId === '') {
            throw new LogicException('Firebase project ID is not configured.');
        }

        $header = $this->decodeHeader($idToken);
        $keyId = $header['kid'] ?? null;

        if (($header['alg'] ?? null) !== 'RS256' || !is_string($keyId) || $keyId === '') {
            throw new InvalidArgumentException('Unsupported Firebase ID token header.');
        }

        $certificates = $this->certificates();
        $certificate = $certificates[$keyId] ?? null;

        // Google rotates signing keys. A cached set may not contain a new key,
        // so refresh once before rejecting an otherwise valid token.
        if (!is_string($certificate) || $certificate === '') {
            Cache::forget(self::CERTIFICATE_CACHE_KEY);
            $certificate = $this->certificates()[$keyId] ?? null;
        }

        if (!is_string($certificate) || $certificate === '') {
            throw new InvalidArgumentException('Unknown Firebase signing key.');
        }

        $claims = JWT::decode($idToken, new Key($certificate, 'RS256'));

        if (
            !isset($claims->aud, $claims->iss)
            || !hash_equals($projectId, (string) $claims->aud)
            || !hash_equals('https://securetoken.google.com/' . $projectId, (string) $claims->iss)
        ) {
            throw new InvalidArgumentException('Firebase token audience or issuer is invalid.');
        }

        $firebase = isset($claims->firebase) && is_object($claims->firebase) ? $claims->firebase : null;
        $provider = $firebase->sign_in_provider ?? null;

        if ($provider !== 'google.com') {
            throw new InvalidArgumentException('Firebase token is not a Google sign-in token.');
        }

        $uid = $claims->sub ?? null;
        $email = isset($claims->email) ? mb_strtolower(trim((string) $claims->email)) : '';
        $emailVerified = filter_var($claims->email_verified ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!is_string($uid) || $uid === '' || mb_strlen($uid) > 128 || !$emailVerified || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Firebase token is missing a verified Google identity.');
        }

        $name = isset($claims->name) ? trim((string) $claims->name) : '';
        $photoUrl = isset($claims->picture) ? trim((string) $claims->picture) : '';

        return [
            'uid' => $uid,
            'email' => $email,
            'name' => $name !== '' ? mb_substr($name, 0, 255) : Str::before($email, '@'),
            'photo_url' => filter_var($photoUrl, FILTER_VALIDATE_URL) ? $photoUrl : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function certificates(): array
    {
        return Cache::remember(self::CERTIFICATE_CACHE_KEY, now()->addMinutes(55), function (): array {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(8)
                ->get(self::CERTIFICATE_URL);

            if (!$response->successful() || !is_array($response->json())) {
                throw new RuntimeException('Unable to retrieve Firebase signing certificates.');
            }

            $certificates = array_filter(
                $response->json(),
                static fn ($certificate): bool => is_string($certificate) && str_starts_with($certificate, '-----BEGIN CERTIFICATE-----')
            );

            if ($certificates === []) {
                throw new RuntimeException('Firebase signing certificates response is invalid.');
            }

            return $certificates;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeHeader(string $idToken): array
    {
        if ($idToken === '' || strlen($idToken) > 4096) {
            throw new InvalidArgumentException('Firebase ID token is malformed.');
        }

        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Firebase ID token is malformed.');
        }

        $segment = strtr($parts[0], '-_', '+/');
        $decoded = base64_decode($segment . str_repeat('=', (4 - strlen($segment) % 4) % 4), true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Firebase ID token header is malformed.');
        }

        $header = json_decode($decoded, true);
        if (!is_array($header)) {
            throw new InvalidArgumentException('Firebase ID token header is malformed.');
        }

        return $header;
    }
}
