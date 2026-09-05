<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdLiveInternalRequestVerifier
{
    /**
     * Verify a signed server-to-server request and permanently reject a nonce
     * replay during the permitted clock window.
     */
    public function verify(Request $request): bool
    {
        $secret = (string) config('adlive.shared_secret');
        $timestamp = (string) $request->header('X-Artera-AdLive-Timestamp');
        $nonce = (string) $request->header('X-Artera-AdLive-Nonce');
        $signature = (string) $request->header('X-Artera-AdLive-Signature');
        $maxAge = max(30, (int) config('adlive.internal_request_max_age_seconds', 300));

        if ($secret === '' || ! ctype_digit($timestamp) || ! preg_match('/^[A-Za-z0-9-]{16,128}$/', $nonce) || ! preg_match('/^[a-f0-9]{64}$/', $signature)) {
            return false;
        }

        if (abs(now()->timestamp - (int) $timestamp) > $maxAge) {
            return false;
        }

        $payload = $request->json()->all();
        if (! is_array($payload)) {
            return false;
        }

        // getRequestUri retains an XAMPP subdirectory such as
        // /Artera/123456. Request::path() strips that base path, which makes
        // an otherwise valid signed server-to-server request fail locally.
        // Accepting the exact signed body also avoids JSON transport changing
        // empty arrays/objects before the verifier sees the request.
        $paths = array_unique([
            '/'.ltrim((string) (parse_url($request->getRequestUri(), PHP_URL_PATH) ?: '/'), '/'),
            '/'.ltrim($request->path(), '/'),
        ]);
        $signatureBodies = array_unique([
            $this->canonicalPayload($payload),
            $request->getContent(),
        ]);
        $signatureIsValid = collect($paths)->contains(function (string $path) use ($timestamp, $nonce, $request, $signatureBodies, $secret, $signature): bool {
            foreach ($signatureBodies as $body) {
                $expected = hash_hmac(
                    'sha256',
                    $this->signaturePayloadForBody($request->method(), $path, $timestamp, $nonce, $body),
                    $secret,
                );

                if (hash_equals($expected, $signature)) {
                    return true;
                }
            }

            return false;
        });
        if (! $signatureIsValid) {
            return false;
        }

        return Cache::add(
            'adlive:internal-request-nonce:'.hash('sha256', $nonce),
            true,
            // A future-dated request remains valid until timestamp + maxAge.
            // Retain the nonce across that entire window (including its edge).
            now()->addSeconds(2 * $maxAge + 1),
        );
    }

    public function hasSignatureHeaders(Request $request): bool
    {
        return $request->hasHeader('X-Artera-AdLive-Timestamp')
            || $request->hasHeader('X-Artera-AdLive-Nonce')
            || $request->hasHeader('X-Artera-AdLive-Signature');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function signaturePayload(string $method, string $path, string $timestamp, string $nonce, array $payload): string
    {
        return $this->signaturePayloadForBody($method, $path, $timestamp, $nonce, $this->canonicalPayload($payload));
    }

    public function signaturePayloadForBody(string $method, string $path, string $timestamp, string $nonce, string $body): string
    {
        return implode("\n", [
            $timestamp,
            $nonce,
            strtoupper($method),
            '/'.ltrim($path, '/'),
            $body,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function canonicalPayload(array $payload): string
    {
        return json_encode($this->canonicalize($payload), JSON_UNESCAPED_SLASHES);
    }

    private function canonicalize($value)
    {
        if (! is_array($value)) {
            return $value;
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = $this->canonicalize($item);
        }

        if (! $this->isList($normalized)) {
            ksort($normalized, SORT_STRING);
        }

        return $normalized;
    }

    private function isList(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }
}
