<?php

namespace Tests\Feature;

use App\Http\Middleware\ProtectAdLiveProfileUpdates;
use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdLiveRegistrationBodyLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'adlive.shared_secret' => bin2hex(random_bytes(32))]);
    }

    public function test_registration_accepts_the_encoded_two_megabyte_logo_with_business_fields(): void
    {
        $request = $this->signedRequest('/api/internal/adlive/registrations', [
            'logo' => ['mime' => 'image/png', 'data' => base64_encode(str_repeat('x', 2 * 1024 * 1024))],
            'business_name' => 'Example Business',
        ]);

        $response = $this->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($request->attributes->get('adlive_profile_authenticated'));
    }

    public function test_registration_still_rejects_bodies_over_three_megabytes(): void
    {
        $request = $this->signedRequest('/api/internal/adlive/registrations', [
            'padding' => str_repeat('x', 3 * 1024 * 1024),
        ]);

        $this->assertSame(413, $this->handle($request)->getStatusCode());
    }

    public function test_other_internal_endpoints_keep_the_original_body_limit(): void
    {
        $request = $this->signedRequest('/api/internal/adlive/business-profile-updates', [
            'padding' => str_repeat('x', 131072),
        ]);

        $this->assertSame(413, $this->handle($request)->getStatusCode());
    }

    public function test_a_large_registration_without_a_signature_is_rejected(): void
    {
        $request = $this->signedRequest('/api/internal/adlive/registrations', [
            'padding' => str_repeat('x', 200000),
        ]);
        $request->headers->remove('X-Artera-AdLive-Signature');

        $this->assertSame(401, $this->handle($request)->getStatusCode());
    }

    public function test_a_large_registration_with_a_tampered_body_is_rejected(): void
    {
        $request = $this->signedRequest('/api/internal/adlive/registrations', [
            'padding' => str_repeat('x', 200000),
        ]);
        $request->headers->set('X-Artera-AdLive-Signature', str_repeat('0', 64));

        $this->assertSame(401, $this->handle($request)->getStatusCode());
    }

    public function test_a_large_registration_cannot_replay_its_signed_nonce(): void
    {
        $request = $this->signedRequest('/api/internal/adlive/registrations', [
            'padding' => str_repeat('x', 200000),
        ]);

        $this->assertSame(200, $this->handle($request)->getStatusCode());
        $this->assertSame(401, $this->handle($request)->getStatusCode());
    }

    private function signedRequest(string $path, array $payload): Request
    {
        $verifier = app(AdLiveInternalRequestVerifier::class);
        $body = $verifier->canonicalPayload($payload);
        $timestamp = (string) now()->timestamp;
        $nonce = (string) Str::uuid();
        $signature = hash_hmac('sha256', $verifier->signaturePayload('POST', $path, $timestamp, $nonce, $payload), (string) config('adlive.shared_secret'));

        return Request::create($path, 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ARTERA_ADLIVE_TIMESTAMP' => $timestamp,
            'HTTP_X_ARTERA_ADLIVE_NONCE' => $nonce,
            'HTTP_X_ARTERA_ADLIVE_SIGNATURE' => $signature,
        ], $body);
    }

    private function handle(Request $request)
    {
        return app(ProtectAdLiveProfileUpdates::class)->handle($request, static fn () => response()->json(['accepted' => true]));
    }
}
