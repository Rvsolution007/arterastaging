<?php

namespace App\Services;

use Illuminate\Support\Str;

/** Creates replay-protected HMAC headers for Artera-to-AdLive server calls. */
class ArteraInternalRequestSigner
{
    /** @param array<string, mixed> $payload
     *  @return array<string, string>
     */
    public function headers(string $method, string $url, array $payload, string $sharedSecret): array
    {
        $timestamp = (string) now()->timestamp;
        $nonce = Str::uuid()->toString();
        $path = '/'.ltrim((string) (parse_url($url, PHP_URL_PATH) ?: '/'), '/');
        $signature = hash_hmac(
            'sha256',
            implode("\n", [
                $timestamp,
                $nonce,
                strtoupper($method),
                $path,
                $this->encodePayload($payload),
            ]),
            $sharedSecret,
        );

        return [
            'X-Artera-AdLive-Timestamp' => $timestamp,
            'X-Artera-AdLive-Nonce' => $nonce,
            'X-Artera-AdLive-Signature' => $signature,
        ];
    }

    /**
     * JSON body used for both the signature and the outbound request. Keeping
     * it identical prevents HTTP-client serialization from invalidating the
     * signed payload.
     *
     * @param array<string, mixed> $payload
     */
    public function encodePayload(array $payload): string
    {
        return $this->canonicalPayload($payload);
    }

    /** @param array<string, mixed> $payload */
    private function canonicalPayload(array $payload): string
    {
        return json_encode($this->canonicalize($payload), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = $this->canonicalize($item);
        }

        if (! array_is_list($normalized)) {
            ksort($normalized, SORT_STRING);
        }

        return $normalized;
    }
}
