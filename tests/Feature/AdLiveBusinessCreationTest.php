<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\User;
use App\Services\AdLiveBusinessProfileService;
use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdLiveBusinessCreationTest extends TestCase
{
    private User $user;
    private BusinessCategory $category;
    private BusinessSubCategory $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'adlive.shared_secret' => 'business-create-test-secret',
            'adlive.internal_request_max_age_seconds' => 300,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->app['url']->forceRootUrl('http://localhost');
        Cache::flush();
        $this->createSchema();

        $this->user = User::create([
            'name' => 'Pixel Owner',
            'email' => 'owner@example.test',
            'mobile_no' => '9876543210',
            'status' => 1,
        ]);
        $this->category = BusinessCategory::create(['name' => 'Retail', 'status' => 1]);
        $this->subCategory = BusinessSubCategory::create([
            'business_category_id' => $this->category->id,
            'name' => 'Bakery',
            'status' => 1,
        ]);
    }

    public function test_valid_signed_creation_creates_only_a_canonical_owned_business(): void
    {
        $response = $this->signedPost($this->payload())->assertOk();
        $businessId = $response->json('profile.business.id');
        $business = Business::findOrFail($businessId);

        $this->assertSame((string) $this->user->id, (string) $business->user_id);
        $this->assertSame('AdLive Bakery', $business->name);
        $this->assertSame((string) $this->category->id, (string) $business->business_category_id);
        $this->assertSame([(int) $this->subCategory->id], $business->business_sub_category_ids);
        $this->assertSame('service', $business->adlive_business_type);
        $this->assertSame('https://bakery.example.test', $business->website);
        $this->assertSame('Mumbai', $business->address);
        $this->assertSame(1, (int) $business->is_default);
        $this->assertNotEmpty($business->profile_version);
        $this->assertSame(1, DB::table('business_product_requests')->where('business_id', $business->id)->count());
        $this->assertSame('Cakes', DB::table('business_product_requests')->where('business_id', $business->id)->value('requested_name'));
        $this->assertSame(
            app(AdLiveBusinessProfileService::class)->profileVersion($this->user, $business),
            $response->json('profile.business.profile_version')
        );
        $this->assertSame(1, DB::table('users')->count());
    }

    public function test_wrong_signature_is_rejected_before_a_business_is_created(): void
    {
        $this->signedPost($this->payload(), str_repeat('0', 64))
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthorized.']);

        $this->assertSame(0, Business::count());
    }

    public function test_expired_timestamp_is_rejected_before_a_business_is_created(): void
    {
        $this->signedPost($this->payload(), null, null, (string) now()->subSeconds(301)->timestamp)
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthorized.']);

        $this->assertSame(0, Business::count());
    }

    public function test_replayed_nonce_is_rejected_and_cannot_create_a_second_business(): void
    {
        $payload = $this->payload();
        $nonce = 'creation-replay-nonce-1111-2222-3333';

        $this->signedPost($payload, null, $nonce)->assertOk();
        $this->signedPost($payload, null, $nonce)->assertUnauthorized();

        $this->assertSame(1, Business::count());
    }

    public function test_inactive_or_wrong_category_taxonomy_returns_field_errors_without_a_business(): void
    {
        $otherCategory = BusinessCategory::create(['name' => 'Healthcare', 'status' => 1]);
        $wrongSubCategory = BusinessSubCategory::create([
            'business_category_id' => $otherCategory->id,
            'name' => 'Clinic',
            'status' => 1,
        ]);
        $payload = $this->payload([
            'business' => ['sub_category_ids' => [(string) $wrongSubCategory->id]],
        ]);

        $this->signedPost($payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['business.sub_category_ids']);

        $this->assertSame(0, Business::count());
    }

    public function test_unknown_artera_user_id_returns_a_field_error_without_a_business(): void
    {
        $payload = $this->payload(['artera_user_id' => '999999']);

        $this->signedPost($payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['artera_user_id']);

        $this->assertSame(0, Business::count());
    }

    public function test_success_response_matches_the_adlive_creation_contract_exactly(): void
    {
        $response = $this->signedPost($this->payload())->assertOk();
        $body = $response->json();

        $this->assertSame(['profile'], array_keys($body));
        $this->assertSame(['identity', 'business'], array_keys($body['profile']));
        $this->assertSame(['artera_user_id', 'name', 'email', 'phone'], array_keys($body['profile']['identity']));
        $this->assertSame([
            'id', 'name', 'category', 'sub_categories', 'business_types', 'products',
            'website', 'location', 'profile_version', 'updated_at',
        ], array_keys($body['profile']['business']));
        $this->assertSame((string) $this->user->id, $body['profile']['identity']['artera_user_id']);
        $this->assertSame('Pixel Owner', $body['profile']['identity']['name']);
        $this->assertSame('owner@example.test', $body['profile']['identity']['email']);
        $this->assertSame('9876543210', $body['profile']['identity']['phone']);
        $this->assertSame(['id' => (string) $this->category->id, 'name' => 'Retail'], $body['profile']['business']['category']);
        $this->assertSame([['id' => (string) $this->subCategory->id, 'name' => 'Bakery']], $body['profile']['business']['sub_categories']);
        $this->assertSame(['service'], $body['profile']['business']['business_types']);
        $this->assertSame(['Cakes'], $body['profile']['business']['products']);
        $this->assertSame('https://bakery.example.test', $body['profile']['business']['website']);
        $this->assertSame('Mumbai', $body['profile']['business']['location']);
        $this->assertNotEmpty($body['profile']['business']['id']);
        $this->assertNotEmpty($body['profile']['business']['profile_version']);
        $this->assertNotFalse(strtotime($body['profile']['business']['updated_at']));
    }

    /** @param array<string, mixed> $replace */
    private function payload(array $replace = []): array
    {
        $payload = [
            'artera_user_id' => (string) $this->user->id,
            'business' => [
                'name' => 'AdLive Bakery',
                'category_id' => (string) $this->category->id,
                'sub_category_ids' => [(string) $this->subCategory->id],
                'business_type' => 'service',
                'products' => ['Cakes'],
                'website' => 'https://bakery.example.test',
                'location' => 'Mumbai',
            ],
        ];

        foreach ($replace as $key => $value) {
            $payload[$key] = is_array($value) && is_array($payload[$key] ?? null)
                ? array_merge($payload[$key], $value)
                : $value;
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function signedPost(array $payload, ?string $signature = null, ?string $nonce = null, ?string $timestamp = null)
    {
        $timestamp ??= (string) now()->timestamp;
        $nonce ??= 'nonce-'.str_replace('-', '', (string) Str::uuid());
        $verifier = app(AdLiveInternalRequestVerifier::class);
        $signature ??= hash_hmac(
            'sha256',
            $verifier->signaturePayload('POST', '/api/internal/adlive/businesses', $timestamp, $nonce, $payload),
            (string) config('adlive.shared_secret')
        );

        return $this->call('POST', '/api/internal/adlive/businesses', [], [], [], [
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
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('business', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('business_category_id')->nullable();
            $table->json('business_sub_category_ids')->nullable();
            $table->unsignedBigInteger('business_type_id')->nullable();
            $table->string('adlive_business_type', 32)->nullable();
            $table->unsignedInteger('status')->default(1);
            $table->unsignedInteger('is_default')->default(0);
            $table->uuid('profile_version')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('business_category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('status')->default(1);
            $table->timestamps();
        });
        Schema::create('business_sub_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_category_id');
            $table->string('name');
            $table->unsignedInteger('status')->default(1);
            $table->timestamps();
        });
        Schema::create('business_sub_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_sub_category_id');
            $table->timestamps();
            $table->unique(['business_id', 'business_sub_category_id']);
        });
        Schema::create('business_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_sub_category_id')->nullable();
            $table->string('name')->nullable();
            $table->unsignedInteger('status')->default(1);
            $table->timestamps();
        });
        Schema::create('business_type_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_type_id');
            $table->timestamps();
            $table->unique(['business_id', 'business_type_id']);
        });
        Schema::create('business_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_category_id')->nullable();
            $table->unsignedBigInteger('business_sub_category_id')->nullable();
            $table->unsignedBigInteger('business_type_id')->nullable();
            $table->string('name');
            $table->unsignedInteger('status')->default(1);
            $table->timestamps();
        });
        Schema::create('business_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_product_id');
            $table->timestamps();
            $table->unique(['business_id', 'business_product_id']);
        });
        Schema::create('business_product_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_sub_category_id');
            $table->string('requested_name');
            $table->string('status', 16)->default('pending');
            $table->unsignedBigInteger('resolved_product_id')->nullable();
            $table->timestamps();
        });
    }
}
