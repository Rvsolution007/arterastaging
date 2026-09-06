<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdLiveCredentialVerificationTest extends TestCase
{
    private User $user;
    private Business $business;
    private string $password = 'Correct#Password123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'adlive.shared_secret' => 'credential-verify-test-secret',
            'adlive.internal_request_max_age_seconds' => 300,
            'adlive.identity_consent_version' => 'consent-v2',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->app['url']->forceRootUrl('http://localhost');
        Cache::flush();
        $this->createSchema();

        $this->user = User::create([
            'name' => 'Pixel Owner',
            'email' => 'pixel.owner@example.test',
            'password' => Hash::make($this->password),
            'mobile_no' => '9123456789',
            'status' => 1,
            'registration_source' => 'artera_pixel',
            'email_verified_at' => now(),
        ]);
        $category = BusinessCategory::create(['name' => 'Retail', 'status' => 1]);
        $this->business = Business::create([
            'user_id' => $this->user->id,
            'name' => 'Pixel Bakery',
            'address' => 'Mumbai',
            'business_category_id' => $category->id,
            'status' => 1,
            'is_default' => 1,
        ]);
    }

    public function test_valid_credentials_return_only_the_exact_canonical_identity_contract(): void
    {
        $response = $this->signedPost([
            // Signing canonicalizes this intentionally non-alphabetical body.
            'password' => $this->password,
            'email' => 'PIXEL.OWNER@EXAMPLE.TEST',
        ])->assertOk();

        $body = $response->json();
        $this->assertSame(['identity'], array_keys($body));
        $this->assertSame([
            'artera_user_id', 'name', 'email', 'phone', 'email_verified',
            'signup_source', 'consent_version', 'business',
        ], array_keys($body['identity']));
        $this->assertSame([
            'id', 'name', 'category', 'sub_categories', 'business_types',
            'products', 'location', 'profile_version', 'updated_at',
        ], array_keys($body['identity']['business']));
        $this->assertSame((string) $this->user->id, $body['identity']['artera_user_id']);
        $this->assertSame('pixel.owner@example.test', $body['identity']['email']);
        $this->assertTrue($body['identity']['email_verified']);
        $this->assertSame('artera_pixel', $body['identity']['signup_source']);
        $this->assertSame('consent-v2', $body['identity']['consent_version']);
        $this->assertSame((string) $this->business->id, $body['identity']['business']['id']);
        $this->assertNotEmpty($body['identity']['business']['profile_version']);
        $this->assertNotFalse(strtotime($body['identity']['business']['updated_at']));
        $this->assertArrayNotHasKey('password', $body['identity']);
        $this->assertArrayNotHasKey('password_hash', $body['identity']);
        $this->assertStringNotContainsString($this->password, $response->getContent());
    }

    public function test_invalid_credentials_and_missing_user_have_the_same_generic_response(): void
    {
        $wrongPassword = $this->signedPost([
            'email' => $this->user->email,
            'password' => 'Wrong#Password123',
        ]);
        $missingUser = $this->signedPost([
            'email' => 'missing@example.test',
            'password' => $this->password,
        ]);

        $wrongPassword->assertUnauthorized()->assertExactJson(['message' => 'Invalid credentials.']);
        $missingUser->assertUnauthorized()->assertExactJson(['message' => 'Invalid credentials.']);
    }

    public function test_wrong_signature_and_expired_timestamp_are_rejected_before_password_processing(): void
    {
        $payload = ['email' => $this->user->email, 'password' => $this->password];

        $this->signedPost($payload, str_repeat('0', 64))
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthorized.']);
        $this->signedPost($payload, null, null, (string) now()->subSeconds(301)->timestamp)
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthorized.']);
    }

    public function test_replayed_nonce_is_rejected(): void
    {
        $payload = ['email' => $this->user->email, 'password' => $this->password];
        $nonce = 'credential-replay-nonce-1111-2222-3333';

        $this->signedPost($payload, null, $nonce)->assertOk();
        $this->signedPost($payload, null, $nonce)
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthorized.']);
    }

    public function test_credential_route_requires_canonical_signature_and_is_not_browser_accessible(): void
    {
        $payload = ['password' => $this->password, 'email' => $this->user->email];
        $timestamp = (string) now()->timestamp;
        $nonce = 'raw-signature-nonce-1111-2222-3333';
        $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $rawSignature = hash_hmac(
            'sha256',
            app(AdLiveInternalRequestVerifier::class)->signaturePayloadForBody(
                'POST',
                '/api/internal/adlive/credentials/verify',
                $timestamp,
                $nonce,
                $rawBody,
            ),
            (string) config('adlive.shared_secret'),
        );

        $this->call('POST', '/api/internal/adlive/credentials/verify', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ARTERA_ADLIVE_TIMESTAMP' => $timestamp,
            'HTTP_X_ARTERA_ADLIVE_NONCE' => $nonce,
            'HTTP_X_ARTERA_ADLIVE_SIGNATURE' => $rawSignature,
        ], $rawBody)->assertUnauthorized();
        $this->call('POST', '/api/internal/adlive/credentials/verify', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_ORIGIN' => 'https://untrusted-browser.example',
        ], '{}')->assertForbidden()->assertExactJson(['message' => 'Server-to-server requests only.']);
    }

    public function test_credential_route_requires_an_explicit_json_accept_header(): void
    {
        $this->call('POST', '/api/internal/adlive/credentials/verify', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{}')->assertStatus(406)->assertExactJson(['message' => 'Accept must be application/json.']);
    }

    public function test_password_bearing_requests_do_not_emit_an_application_log_entry(): void
    {
        Log::spy();

        $this->signedPost([
            'email' => $this->user->email,
            'password' => 'Wrong#Password123',
        ])->assertUnauthorized();

        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    /** @param array<string, string> $payload */
    private function signedPost(array $payload, ?string $signature = null, ?string $nonce = null, ?string $timestamp = null)
    {
        $timestamp ??= (string) now()->timestamp;
        $nonce ??= 'nonce-'.str_replace('-', '', (string) Str::uuid());
        $verifier = app(AdLiveInternalRequestVerifier::class);
        $signature ??= hash_hmac(
            'sha256',
            $verifier->signaturePayload('POST', '/api/internal/adlive/credentials/verify', $timestamp, $nonce, $payload),
            (string) config('adlive.shared_secret'),
        );

        return $this->call('POST', '/api/internal/adlive/credentials/verify', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ARTERA_ADLIVE_TIMESTAMP' => $timestamp,
            'HTTP_X_ARTERA_ADLIVE_NONCE' => $nonce,
            'HTTP_X_ARTERA_ADLIVE_SIGNATURE' => $signature,
        ], json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('mobile_no')->nullable();
            $table->unsignedInteger('status')->default(1);
            $table->string('registration_source')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('business', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('business_category_id')->nullable();
            $table->json('business_sub_category_ids')->nullable();
            $table->unsignedBigInteger('business_type_id')->nullable();
            $table->unsignedInteger('status')->default(1);
            $table->unsignedInteger('is_default')->default(0);
            $table->uuid('profile_version')->nullable();
            $table->timestamps();
        });
        Schema::create('business_category', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->unsignedInteger('status')->default(1); $table->timestamps();
        });
        Schema::create('business_sub_category', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_category_id'); $table->string('name'); $table->unsignedInteger('status')->default(1); $table->timestamps();
        });
        Schema::create('business_types', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_sub_category_id')->nullable(); $table->string('name'); $table->unsignedInteger('status')->default(1); $table->timestamps();
        });
        Schema::create('business_products', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->unsignedInteger('status')->default(1); $table->timestamps();
        });
        foreach ([
            'business_sub_category_mappings' => 'business_sub_category_id',
            'business_type_mappings' => 'business_type_id',
            'business_product_mappings' => 'business_product_id',
        ] as $tableName => $relatedKey) {
            Schema::create($tableName, function (Blueprint $table) use ($relatedKey) {
                $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger($relatedKey); $table->timestamps();
            });
        }
    }
}
