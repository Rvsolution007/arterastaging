<?php

namespace Tests\Feature;

use App\Models\AdLiveIdentityEvent;
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

class AdLiveIdentityControllerTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'adlive.shared_secret' => 'identity-api-test-secret',
            'adlive.security_revoke_url' => '',
            'queue.default' => 'sync',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->app['url']->forceRootUrl('http://localhost');
        Cache::flush();
        $this->createSchema();
        $this->user = User::create([
            'name' => 'Existing Customer', 'email' => 'existing@example.test',
            'mobile_no' => '9123456789', 'password' => Hash::make('Old#Password123'),
            'status' => 1, 'user_type' => 'User', 'registration_source' => 'artera_pixel',
        ]);
    }

    public function test_create_is_signed_idempotent_and_does_not_accept_a_role(): void
    {
        $payload = $this->envelope([
            'identity' => ['name' => 'New Client', 'email' => 'new@example.test', 'phone' => '9988776655'],
            'password' => 'New#Password123',
        ]);
        $first = $this->signedPost('identities/create', $payload)->assertCreated();
        $second = $this->signedPost('identities/create', $payload)->assertCreated();

        $this->assertSame($first->json('identity.artera_user_id'), $second->json('identity.artera_user_id'));
        $this->assertSame(1, User::where('email', 'new@example.test')->count());
        $this->assertSame(1, AdLiveIdentityEvent::where('event_type', 'identity.created')->count());
        $this->assertArrayNotHasKey('password', $first->json('identity'));
        $this->assertStringNotContainsString($payload['password'], $first->getContent());

        $invalid = $payload;
        $invalid['request_id'] = (string) Str::uuid();
        $invalid['identity']['role'] = 'admin';
        $this->signedPost('identities/create', $invalid)->assertUnprocessable();
    }

    public function test_duplicate_email_or_phone_is_rejected_without_identifying_the_collision(): void
    {
        foreach ([
            ['email' => $this->user->email, 'phone' => '8111111111'],
            ['email' => 'fresh@example.test', 'phone' => $this->user->mobile_no],
        ] as $identity) {
            $response = $this->signedPost('identities/create', $this->envelope([
                'identity' => ['name' => 'Collision', ...$identity],
                'password' => 'New#Password123',
            ]));
            $response->assertUnprocessable()->assertExactJson([
                'message' => 'The identity could not be saved with those details.',
            ]);
        }
    }

    public function test_update_returns_canonical_data_without_a_callback_loop_for_adlive_source(): void
    {
        $payload = $this->envelope([
            'identity' => ['artera_user_id' => $this->user->id, 'name' => 'Renamed Client'],
        ]);
        $this->signedPost('identities/update', $payload)
            ->assertOk()
            ->assertJsonPath('identity.name', 'Renamed Client')
            ->assertJsonPath('identity.businesses', []);

        $this->assertSame('Renamed Client', $this->user->fresh()->name);
        $this->assertSame(0, AdLiveIdentityEvent::count());
    }

    public function test_delete_is_a_soft_deactivation_that_revokes_pixel_tokens(): void
    {
        $this->user->createToken('mobile-app', ['mobile:access']);
        $payload = $this->envelope([
            'artera_user_id' => $this->user->id,
            'admin_authorized' => true,
        ]);
        $this->signedPost('identities/delete', $payload)
            ->assertOk()
            ->assertJsonPath('identity.status', 'deleted')
            ->assertJsonPath('identity.businesses', []);

        $deleted = User::withTrashed()->findOrFail($this->user->id);
        $this->assertSame(0, $deleted->status);
        $this->assertNotNull($deleted->deleted_at);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $deleted->id)->count());
        $this->assertSame('identity.deleted', AdLiveIdentityEvent::firstOrFail()->event_type);
    }

    public function test_password_change_revokes_stale_pixel_tokens_without_logging_passwords(): void
    {
        $this->user->createToken('mobile-app', ['mobile:access']);
        Log::spy();
        $newPassword = 'Changed#Password123';
        $this->signedPost('credentials/change', $this->envelope([
            'artera_user_id' => $this->user->id,
            'current_password' => 'Old#Password123',
            'new_password' => $newPassword,
        ]))->assertOk()->assertJsonMissing(['current_password', 'new_password']);

        $this->assertTrue(Hash::check($newPassword, (string) $this->user->fresh()->password));
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $this->user->id)->count());
        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    /** @param array<string, mixed> $replace */
    private function envelope(array $replace = []): array
    {
        return array_replace([
            'request_id' => (string) Str::uuid(),
            'occurred_at' => now()->utc()->toIso8601String(),
            'source' => 'adlive',
        ], $replace);
    }

    /** @param array<string, mixed> $payload */
    private function signedPost(string $endpoint, array $payload)
    {
        $timestamp = (string) now()->timestamp;
        $nonce = 'identity-'.str_replace('-', '', (string) Str::uuid());
        $path = '/api/internal/adlive/'.$endpoint;
        $signature = hash_hmac(
            'sha256',
            app(AdLiveInternalRequestVerifier::class)->signaturePayload('POST', $path, $timestamp, $nonce, $payload),
            (string) config('adlive.shared_secret'),
        );

        return $this->call('POST', $path, [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ARTERA_ADLIVE_TIMESTAMP' => $timestamp,
            'HTTP_X_ARTERA_ADLIVE_NONCE' => $nonce,
            'HTTP_X_ARTERA_ADLIVE_SIGNATURE' => $signature,
        ], json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->unique(); $table->string('password');
            $table->string('mobile_no')->nullable()->unique(); $table->unsignedInteger('status')->default(1);
            $table->string('user_type')->nullable(); $table->string('login_type')->nullable(); $table->string('registration_source')->nullable();
            $table->string('referral_code')->nullable(); $table->timestamp('email_verified_at')->nullable(); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('business', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('name')->nullable(); $table->string('website')->nullable();
            $table->text('address')->nullable(); $table->unsignedBigInteger('business_category_id')->nullable(); $table->json('business_sub_category_ids')->nullable();
            $table->unsignedBigInteger('business_type_id')->nullable(); $table->unsignedInteger('status')->default(1); $table->unsignedInteger('is_default')->default(0); $table->timestamps();
        });
        foreach (['business_category', 'business_sub_category', 'business_types', 'business_products'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) { $table->id(); $table->string('name')->nullable(); $table->timestamps(); });
        }
        foreach ([
            'business_sub_category_mappings' => 'business_sub_category_id', 'business_type_mappings' => 'business_type_id',
            'business_product_mappings' => 'business_product_id',
        ] as $tableName => $key) {
            Schema::create($tableName, function (Blueprint $table) use ($key) { $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger($key); $table->timestamps(); });
        }
        Schema::create('adlive_identity_requests', function (Blueprint $table) {
            $table->id(); $table->uuid('request_id')->unique(); $table->char('request_fingerprint', 64); $table->string('operation');
            $table->string('source'); $table->unsignedBigInteger('artera_user_id')->nullable(); $table->json('changed_fields')->nullable(); $table->timestamp('occurred_at'); $table->timestamps();
        });
        Schema::create('adlive_identity_events', function (Blueprint $table) {
            $table->id(); $table->uuid('event_id')->unique(); $table->string('event_type'); $table->unsignedBigInteger('artera_user_id');
            $table->unsignedBigInteger('artera_business_id')->nullable(); $table->timestamp('occurred_at'); $table->unsignedInteger('delivery_attempts')->default(0);
            $table->timestamp('processing_at')->nullable(); $table->timestamp('sent_at')->nullable(); $table->string('last_failure')->nullable(); $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); $table->string('tokenable_type'); $table->unsignedBigInteger('tokenable_id'); $table->string('name');
            $table->string('token', 64)->unique(); $table->text('abilities')->nullable(); $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
    }
}
