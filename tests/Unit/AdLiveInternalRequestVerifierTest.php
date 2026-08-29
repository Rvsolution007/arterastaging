<?php

namespace Tests\Unit;

use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdLiveInternalRequestVerifierTest extends TestCase
{
    public function test_it_accepts_one_valid_signature_and_rejects_a_replay_or_tampering(): void
    {
        Cache::flush();
        config([
            'adlive.shared_secret' => 'test-shared-secret',
            'adlive.internal_request_max_age_seconds' => 300,
        ]);

        $verifier = new AdLiveInternalRequestVerifier;
        $this->assertSame('{"items":[]}', $verifier->canonicalPayload(['items' => []]));
        $payload = ['business_id' => 12, 'artera_user_id' => 7];
        $timestamp = (string) now()->timestamp;
        $nonce = 'e3f4e5f6-1111-2222-3333-444455556666';
        $signature = hash_hmac(
            'sha256',
            $verifier->signaturePayload('POST', '/api/internal/adlive/business-snapshot', $timestamp, $nonce, $payload),
            'test-shared-secret'
        );

        $request = Request::create('/api/internal/adlive/business-snapshot', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ARTERA_ADLIVE_TIMESTAMP' => $timestamp,
            'HTTP_X_ARTERA_ADLIVE_NONCE' => $nonce,
            'HTTP_X_ARTERA_ADLIVE_SIGNATURE' => $signature,
        ], json_encode($payload));

        $this->assertTrue($verifier->verify($request));
        $this->assertFalse($verifier->verify($request));

        $tamperedRequest = Request::create('/api/internal/adlive/business-snapshot', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ARTERA_ADLIVE_TIMESTAMP' => $timestamp,
            'HTTP_X_ARTERA_ADLIVE_NONCE' => 'f3f4e5f6-1111-2222-3333-444455556666',
            'HTTP_X_ARTERA_ADLIVE_SIGNATURE' => $signature,
        ], json_encode(['business_id' => 13, 'artera_user_id' => 7]));

        $this->assertFalse($verifier->verify($tamperedRequest));
    }
}
