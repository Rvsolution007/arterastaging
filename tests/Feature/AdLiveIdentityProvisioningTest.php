<?php

namespace Tests\Feature;

use App\Jobs\ProvisionAdLiveIdentity;
use App\Models\AdLiveIdentityProvisionOutbox;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use App\Services\AdLiveIdentityProvisioningClient;
use App\Services\AdLiveIdentitySyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdLiveIdentityProvisioningTest extends TestCase
{
    private User $user;
    private Business $selectedBusiness;
    private Business $otherBusiness;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'adlive.shared_secret' => 'outbox-test-shared-secret',
            'adlive.identity_provision_url' => 'https://arteraadlive.test/api/v1/internal/artera/users/provision',
            'adlive.identity_consent_version' => 'consent-v3',
            'adlive.request_timeout_seconds' => 3,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->createSchema();

        $this->user = User::create([
            'name' => 'Pixel Owner',
            'email' => 'owner@example.test',
            'mobile_no' => '9123456789',
            'status' => 1,
            'registration_source' => 'artera_pixel',
            'email_verified_at' => now(),
        ]);
        $category = BusinessCategory::create(['name' => 'Retail', 'status' => 1]);
        $this->selectedBusiness = Business::create([
            'user_id' => $this->user->id,
            'name' => 'Selected Business',
            'address' => 'Pune',
            'business_category_id' => $category->id,
            'status' => 1,
            'is_default' => 1,
        ]);
        $this->otherBusiness = Business::create([
            'user_id' => $this->user->id,
            'name' => 'Other Business',
            'address' => 'Delhi',
            'business_category_id' => $category->id,
            'status' => 1,
            'is_default' => 0,
        ]);
    }

    public function test_multi_business_sync_queues_ids_only_and_delivers_active_business_last(): void
    {
        Queue::fake();
        $outboxIds = app(AdLiveIdentitySyncService::class)->queueForUser($this->user);

        $this->assertCount(2, $outboxIds);
        $this->assertSame([
            (string) $this->otherBusiness->id,
            (string) $this->selectedBusiness->id,
        ], AdLiveIdentityProvisionOutbox::query()
            ->whereIn('id', $outboxIds)
            ->orderBy('id')
            ->pluck('artera_business_id')
            ->map(fn ($id) => (string) $id)
            ->all());
        Queue::assertPushed(ProvisionAdLiveIdentity::class, 2);
        Queue::assertPushed(ProvisionAdLiveIdentity::class, function (ProvisionAdLiveIdentity $job): bool {
            $serialized = serialize($job);

            $this->assertStringNotContainsString('owner@example.test', $serialized);
            $this->assertStringNotContainsString('outbox-test-shared-secret', $serialized);

            return in_array($job->outboxId, AdLiveIdentityProvisionOutbox::pluck('id')->all(), true);
        });

        Http::fake(['https://arteraadlive.test/*' => Http::response([], 200)]);
        foreach ($outboxIds as $outboxId) {
            (new ProvisionAdLiveIdentity($outboxId))->handle(app(AdLiveIdentityProvisioningClient::class));
        }

        $requests = Http::recorded()->all();
        $this->assertCount(2, $requests);
        $businessIds = array_map(
            fn (array $record) => (string) $record[0]->data()['identity']['business']['id'],
            $requests,
        );
        $nonces = array_map(
            fn (array $record) => (string) $record[0]->header('X-Artera-AdLive-Nonce')[0],
            $requests,
        );
        $this->assertSame([(string) $this->otherBusiness->id, (string) $this->selectedBusiness->id], $businessIds);
        $this->assertCount(2, array_unique($nonces));

        Http::assertSent(function (ClientRequest $request): bool {
            $identity = $request->data()['identity'];
            $body = json_encode($request->data(), JSON_UNESCAPED_SLASHES);

            $this->assertSame('artera_pixel', $identity['signup_source']);
            $this->assertSame('consent-v3', $identity['consent_version']);
            $this->assertArrayNotHasKey('password', $identity);
            $this->assertStringNotContainsString('outbox-test-shared-secret', $body);
            $this->assertTrue($request->hasHeader('X-Artera-AdLive-Timestamp'));
            $this->assertTrue($request->hasHeader('X-Artera-AdLive-Nonce'));
            $this->assertTrue($request->hasHeader('X-Artera-AdLive-Signature'));

            return true;
        });
        $this->assertSame(2, AdLiveIdentityProvisionOutbox::query()->whereNotNull('sent_at')->count());
    }

    public function test_callback_failure_is_retryable_and_its_log_context_never_contains_a_password(): void
    {
        $outbox = AdLiveIdentityProvisionOutbox::create([
            'artera_user_id' => $this->user->id,
            'artera_business_id' => $this->selectedBusiness->id,
            'sync_batch_id' => '11111111-1111-1111-1111-111111111111',
            'delivery_order' => 0,
            'signup_source' => 'artera_pixel',
        ]);
        Http::fakeSequence()
            ->push([], 503)
            ->push([], 200);
        Log::spy();

        try {
            (new ProvisionAdLiveIdentity($outbox->id))->handle(app(AdLiveIdentityProvisioningClient::class));
            $this->fail('The first transient callback failure should be retried.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('AdLive identity delivery attempt failed.', $exception->getMessage());
        }

        $outbox->refresh();
        $this->assertSame(1, $outbox->delivery_attempts);
        $this->assertSame('delivery_failed', $outbox->last_failure);
        $this->assertNull($outbox->sent_at);
        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
            return $message === 'AdLive identity provisioning delivery failed.'
                && ! str_contains(json_encode($context), 'password')
                && ! str_contains(json_encode($context), 'outbox-test-shared-secret');
        })->once();

        (new ProvisionAdLiveIdentity($outbox->id))->handle(app(AdLiveIdentityProvisioningClient::class));
        $outbox->refresh();
        $this->assertSame(2, $outbox->delivery_attempts);
        $this->assertNotNull($outbox->sent_at);
        $this->assertNull($outbox->last_failure);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
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
        Schema::create('adlive_identity_provision_outbox', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('artera_user_id');
            $table->unsignedBigInteger('artera_business_id');
            $table->uuid('sync_batch_id');
            $table->unsignedSmallInteger('delivery_order');
            $table->string('signup_source', 32);
            $table->unsignedInteger('delivery_attempts')->default(0);
            $table->string('last_failure', 64)->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }
}
