<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessProduct;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use App\Models\User;
use App\Services\AdLiveBusinessProfileService;
use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdLiveBusinessProfileUpdateTest extends TestCase
{
    private User $user;
    private Business $business;
    private BusinessCategory $category;
    private BusinessSubCategory $subCategory;
    private BusinessType $businessType;
    private BusinessProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'adlive.shared_secret' => 'profile-sync-test-secret',
            'adlive.internal_request_max_age_seconds' => 300,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->app['url']->forceRootUrl('http://localhost');
        Cache::flush();
        $this->createSchema();
        $this->seedProfile();
    }

    public function test_valid_update_returns_the_complete_canonical_profile_and_a_non_sensitive_audit_record(): void
    {
        $payload = $this->payload([
            'identity' => [
                'artera_user_id' => (string) $this->user->id,
                'name' => 'Updated Owner',
                'email' => 'updated-owner@example.test',
                'phone' => '9876543210',
            ],
            'business' => [
                'id' => (string) $this->business->id,
                'name' => 'Updated Business',
                'category' => ['id' => (string) $this->category->id, 'name' => $this->category->name],
                'sub_categories' => [['id' => (string) $this->subCategory->id, 'name' => $this->subCategory->name]],
                'business_types' => [['id' => (string) $this->businessType->id, 'name' => $this->businessType->name]],
                'products' => [['id' => (string) $this->product->id, 'name' => $this->product->name]],
                'location' => 'Updated address',
            ],
        ]);

        $response = $this->signedPost($payload);

        $response->assertOk()
            ->assertJsonPath('profile.identity.artera_user_id', (string) $this->user->id)
            ->assertJsonPath('profile.identity.name', 'Updated Owner')
            ->assertJsonPath('profile.identity.email', 'updated-owner@example.test')
            ->assertJsonPath('profile.identity.phone', '9876543210')
            ->assertJsonPath('profile.business.id', (string) $this->business->id)
            ->assertJsonPath('profile.business.name', 'Updated Business')
            ->assertJsonPath('profile.business.category.id', (string) $this->category->id)
            ->assertJsonPath('profile.business.location', 'Updated address')
            ->assertJsonStructure([
                'profile' => [
                    'identity' => ['artera_user_id', 'name', 'email', 'phone'],
                    'business' => ['id', 'name', 'category', 'sub_categories', 'business_types', 'products', 'location', 'profile_version', 'updated_at'],
                ],
            ]);
        $this->assertNotEmpty($response->json('profile.business.profile_version'));
        $this->assertNotEmpty($response->json('profile.business.updated_at'));

        $audit = DB::table('adlive_business_profile_updates')->first();
        $this->assertSame('adlive', $audit->source);
        $this->assertSame($payload['request_id'], $audit->request_id);
        $this->assertSame((string) $this->user->id, (string) $audit->artera_user_id);
        $this->assertSame((string) $this->business->id, (string) $audit->artera_business_id);
        $this->assertStringNotContainsString('Updated Owner', $audit->changed_fields);
        $this->assertStringNotContainsString('updated-owner@example.test', $audit->changed_fields);
        $this->assertStringContainsString('identity.email', $audit->changed_fields);
    }

    public function test_invalid_signature_is_rejected_before_any_profile_or_audit_write(): void
    {
        $payload = $this->payload();

        $response = $this->signedPost($payload, str_repeat('0', 64));

        $response->assertUnauthorized()->assertExactJson(['message' => 'Unauthorized.']);
        $this->assertSame(0, DB::table('adlive_business_profile_updates')->count());
        $this->assertSame('Original Business', $this->business->fresh()->name);
    }

    public function test_browser_originated_requests_are_rejected_before_cors_or_profile_processing(): void
    {
        $response = $this->call('POST', '/api/internal/adlive/business-profile-updates', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ORIGIN' => 'https://untrusted-browser.example',
        ], '{}');

        $response->assertForbidden()
            ->assertExactJson(['message' => 'Server-to-server requests only.']);
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertSame(0, DB::table('adlive_business_profile_updates')->count());
    }

    public function test_replayed_nonce_is_rejected_even_when_the_signed_body_is_identical(): void
    {
        $payload = $this->payload();
        $nonce = 'replay-nonce-1111-2222-3333-444455556666';

        $this->signedPost($payload, null, $nonce)->assertOk();
        $this->signedPost($payload, null, $nonce)->assertUnauthorized();
        $this->assertSame(1, DB::table('adlive_business_profile_updates')->count());
    }

    public function test_user_cannot_update_a_business_they_do_not_own(): void
    {
        $other = User::create(['name' => 'Other', 'email' => 'other@example.test', 'mobile_no' => '8123456789', 'status' => 1]);
        $otherBusiness = Business::create(['user_id' => $other->id, 'name' => 'Other business', 'status' => 1, 'is_default' => 1]);
        $payload = $this->payload(['business' => ['id' => (string) $otherBusiness->id]]);

        $this->signedPost($payload)
            ->assertForbidden()
            ->assertExactJson(['message' => 'The Artera user does not own this business.']);
        $this->assertSame(0, DB::table('adlive_business_profile_updates')->count());
    }

    public function test_duplicate_request_id_is_idempotent_and_does_not_apply_or_audit_twice(): void
    {
        $payload = $this->payload([
            'identity' => ['artera_user_id' => (string) $this->user->id, 'name' => 'Only Once'],
            'business' => ['id' => (string) $this->business->id, 'name' => 'Only Once Business'],
        ]);

        $first = $this->signedPost($payload)->assertOk();
        $second = $this->signedPost($payload)->assertOk();

        $this->assertSame($first->json('profile.business.profile_version'), $second->json('profile.business.profile_version'));
        $this->assertSame(1, DB::table('adlive_business_profile_updates')->count());
        $this->assertSame('Only Once', $this->user->fresh()->name);
        $this->assertSame('Only Once Business', $this->business->fresh()->name);
    }

    public function test_stale_profile_version_returns_409_and_the_latest_canonical_snapshot_without_writing(): void
    {
        $latest = app(AdLiveBusinessProfileService::class)->sharedSnapshot($this->user, $this->business);
        $payload = $this->payload([
            'business' => [
                'id' => (string) $this->business->id,
                'name' => 'Should not be saved',
                'client_profile_version' => 'profile:v1:stale',
            ],
        ]);

        $this->signedPost($payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'profile_version_conflict')
            ->assertJsonPath('profile.business.profile_version', $latest['business']['profile_version'])
            ->assertJsonPath('profile.business.name', 'Original Business');
        $this->assertSame(0, DB::table('adlive_business_profile_updates')->count());
    }

    private function payload(array $replace = []): array
    {
        $payload = [
            'request_id' => (string) Str::uuid(),
            'occurred_at' => now()->utc()->toIso8601String(),
            'source' => 'adlive',
            'identity' => ['artera_user_id' => (string) $this->user->id],
            'business' => ['id' => (string) $this->business->id],
        ];

        foreach ($replace as $key => $values) {
            $payload[$key] = array_merge($payload[$key], $values);
        }

        return $payload;
    }

    private function signedPost(array $payload, ?string $signature = null, ?string $nonce = null)
    {
        $timestamp = (string) now()->timestamp;
        $nonce ??= 'nonce-'.str_replace('-', '', (string) Str::uuid());
        $verifier = app(AdLiveInternalRequestVerifier::class);
        $signature ??= hash_hmac(
            'sha256',
            $verifier->signaturePayload('POST', '/api/internal/adlive/business-profile-updates', $timestamp, $nonce, $payload),
            (string) config('adlive.shared_secret')
        );

        return $this->call('POST', '/api/internal/adlive/business-profile-updates', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
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
            $table->string('mobile_no')->nullable()->unique();
            $table->unsignedInteger('status')->default(1);
            $table->string('user_type')->nullable();
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
            $table->unsignedInteger('is_default')->default(1);
            $table->uuid('profile_version')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('business_category', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->unsignedInteger('status')->default(1); $table->timestamps();
        });
        Schema::create('business_sub_category', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_category_id'); $table->string('name'); $table->unsignedInteger('status')->default(1); $table->timestamps();
        });
        Schema::create('business_types', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_sub_category_id'); $table->string('name'); $table->unsignedInteger('status')->default(1); $table->timestamps();
        });
        Schema::create('business_products', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_category_id')->nullable(); $table->unsignedBigInteger('business_sub_category_id')->nullable(); $table->unsignedBigInteger('business_type_id')->nullable(); $table->string('name'); $table->unsignedInteger('status')->default(1); $table->timestamps();
        });
        foreach ([
            'business_sub_category_mappings' => 'business_sub_category_id',
            'business_type_mappings' => 'business_type_id',
            'business_product_mappings' => 'business_product_id',
        ] as $tableName => $relatedKey) {
            Schema::create($tableName, function (Blueprint $table) use ($relatedKey) {
                $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger($relatedKey); $table->timestamps();
                $table->unique(['business_id', $relatedKey]);
            });
        }
        Schema::create('adlive_business_profile_updates', function (Blueprint $table) {
            $table->id(); $table->uuid('request_id')->unique(); $table->char('request_fingerprint', 64); $table->string('source', 32);
            $table->unsignedBigInteger('artera_user_id'); $table->unsignedBigInteger('artera_business_id'); $table->json('changed_fields');
            $table->dateTime('occurred_at'); $table->string('resulting_profile_version', 128); $table->timestamps();
        });
    }

    private function seedProfile(): void
    {
        $this->user = User::create(['name' => 'Original Owner', 'email' => 'original@example.test', 'mobile_no' => '9123456789', 'status' => 1]);
        $this->category = BusinessCategory::create(['name' => 'Retail', 'status' => 1]);
        $this->subCategory = BusinessSubCategory::create(['business_category_id' => $this->category->id, 'name' => 'Bakery', 'status' => 1]);
        $this->businessType = BusinessType::create(['business_sub_category_id' => $this->subCategory->id, 'name' => 'Local', 'status' => 1]);
        $this->product = BusinessProduct::create(['business_category_id' => $this->category->id, 'business_sub_category_id' => $this->subCategory->id, 'business_type_id' => $this->businessType->id, 'name' => 'Bread', 'status' => 1]);
        $this->business = Business::create(['user_id' => $this->user->id, 'name' => 'Original Business', 'address' => 'Original address', 'business_category_id' => $this->category->id, 'business_type_id' => $this->businessType->id, 'status' => 1, 'is_default' => 1]);
        $this->business->sub_categories()->sync([$this->subCategory->id]);
        $this->business->types()->sync([$this->businessType->id]);
        $this->business->products()->sync([$this->product->id]);
        $this->business->refresh();
    }
}
