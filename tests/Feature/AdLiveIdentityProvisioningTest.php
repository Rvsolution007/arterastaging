<?php

namespace Tests\Feature;

use App\Jobs\DeliverAdLiveIdentityEvent;
use App\Models\AdLiveIdentityEvent;
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
            'adlive.request_timeout_seconds' => 3,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->createSchema();

        $this->user = User::create([
            'name' => 'Pixel Owner', 'email' => 'owner@example.test',
            'mobile_no' => '9123456789', 'status' => 1,
            'registration_source' => 'artera_pixel', 'email_verified_at' => now(),
        ]);
        $category = BusinessCategory::create(['name' => 'Retail', 'status' => 1]);
        $this->selectedBusiness = Business::create([
            'user_id' => $this->user->id, 'name' => 'Selected Business',
            'address' => 'Pune', 'business_category_id' => $category->id,
            'status' => 1, 'is_default' => 1,
        ]);
        $this->otherBusiness = Business::create([
            'user_id' => $this->user->id, 'name' => 'Other Business',
            'address' => 'Delhi', 'business_category_id' => $category->id,
            'status' => 1, 'is_default' => 0,
        ]);
    }

    public function test_login_sync_queues_an_id_only_event_with_all_active_businesses(): void
    {
        Queue::fake();
        $eventIds = app(AdLiveIdentitySyncService::class)->queueForUser($this->user);

        $this->assertCount(1, $eventIds);
        $event = AdLiveIdentityEvent::findOrFail($eventIds[0]);
        $this->assertSame('identity.updated', $event->event_type);
        $this->assertNull($event->artera_business_id);
        Queue::assertPushed(DeliverAdLiveIdentityEvent::class, function (DeliverAdLiveIdentityEvent $job): bool {
            $serialized = serialize($job);
            $this->assertStringNotContainsString('owner@example.test', $serialized);
            $this->assertStringNotContainsString('outbox-test-shared-secret', $serialized);

            return true;
        });

        Http::fake(['https://arteraadlive.test/*' => Http::response([], 200)]);
        (new DeliverAdLiveIdentityEvent($event->id))->handle(app(AdLiveIdentityProvisioningClient::class));

        Http::assertSent(function (ClientRequest $request): bool {
            $payload = $request->data();
            $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $payload['event_id']);
            $this->assertSame('identity.updated', $payload['event_type']);
            $this->assertSame('artera_pixel', $payload['source']);
            $this->assertSame([
                (string) $this->selectedBusiness->id,
                (string) $this->otherBusiness->id,
            ], array_map(fn (array $business) => $business['id'], $payload['identity']['businesses']));
            $this->assertArrayNotHasKey('password', $payload['identity']);
            $this->assertTrue($request->hasHeader('X-Artera-AdLive-Timestamp'));
            $this->assertTrue($request->hasHeader('X-Artera-AdLive-Nonce'));
            $this->assertTrue($request->hasHeader('X-Artera-AdLive-Signature'));

            return true;
        });
        $this->assertNotNull($event->fresh()->sent_at);
    }

    public function test_business_event_sends_only_the_exact_saved_business(): void
    {
        Queue::fake();
        $eventId = app(AdLiveIdentitySyncService::class)
            ->queueBusiness($this->user, $this->otherBusiness, 'business.updated')[0];
        Http::fake(['https://arteraadlive.test/*' => Http::response([], 200)]);

        (new DeliverAdLiveIdentityEvent($eventId))->handle(app(AdLiveIdentityProvisioningClient::class));

        Http::assertSent(function (ClientRequest $request): bool {
            $identity = $request->data()['identity'];
            $this->assertSame('business.updated', $request->data()['event_type']);
            $this->assertSame((string) $this->otherBusiness->id, $identity['business']['id']);
            $this->assertSame([(string) $this->otherBusiness->id], array_column($identity['businesses'], 'id'));

            return true;
        });
    }

    public function test_callback_failure_is_retryable_and_logs_no_sensitive_body(): void
    {
        Queue::fake();
        $eventId = app(AdLiveIdentitySyncService::class)->queueForUser($this->user)[0];
        Http::fakeSequence()->push([], 503)->push([], 200);
        Log::spy();

        try {
            (new DeliverAdLiveIdentityEvent($eventId))->handle(app(AdLiveIdentityProvisioningClient::class));
            $this->fail('A callback failure must be retryable.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('AdLive identity event delivery failed.', $exception->getMessage());
        }
        $event = AdLiveIdentityEvent::findOrFail($eventId);
        $this->assertSame(1, $event->delivery_attempts);
        $this->assertSame('delivery_failed', $event->last_failure);
        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
            return $message === 'AdLive identity event delivery failed.'
                && ! str_contains(json_encode($context), 'password')
                && ! str_contains(json_encode($context), 'outbox-test-shared-secret');
        })->once();

        (new DeliverAdLiveIdentityEvent($eventId))->handle(app(AdLiveIdentityProvisioningClient::class));
        $this->assertNotNull($event->fresh()->sent_at);
    }

    public function test_no_scheduled_or_polling_adlive_identity_sync_is_registered(): void
    {
        $kernel = file_get_contents(app_path('Console/Kernel.php'));

        $this->assertStringNotContainsString('adlive:drain-identity-outbox', $kernel);
        $this->assertStringNotContainsString('sync-artera-users', $kernel);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->string('mobile_no')->nullable(); $table->string('password')->nullable();
            $table->unsignedInteger('status')->default(1); $table->string('user_type')->nullable();
            $table->string('registration_source')->nullable(); $table->timestamp('email_verified_at')->nullable();
            $table->softDeletes(); $table->timestamps();
        });
        Schema::create('business', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('name')->nullable();
            $table->string('website')->nullable(); $table->text('address')->nullable();
            $table->unsignedBigInteger('business_category_id')->nullable(); $table->json('business_sub_category_ids')->nullable();
            $table->unsignedBigInteger('business_type_id')->nullable(); $table->unsignedInteger('status')->default(1);
            $table->unsignedInteger('is_default')->default(0); $table->uuid('profile_version')->nullable(); $table->timestamps();
        });
        Schema::create('business_category', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->unsignedInteger('status')->default(1); $table->timestamps();
        });
        Schema::create('business_sub_category', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_category_id'); $table->string('name'); $table->timestamps();
        });
        Schema::create('business_types', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_sub_category_id')->nullable(); $table->string('name')->nullable(); $table->timestamps();
        });
        Schema::create('business_products', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps();
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
        Schema::create('adlive_identity_events', function (Blueprint $table) {
            $table->id(); $table->uuid('event_id')->unique(); $table->string('event_type', 32);
            $table->unsignedBigInteger('artera_user_id'); $table->unsignedBigInteger('artera_business_id')->nullable();
            $table->timestamp('occurred_at'); $table->unsignedInteger('delivery_attempts')->default(0);
            $table->timestamp('processing_at')->nullable(); $table->timestamp('sent_at')->nullable();
            $table->string('last_failure', 64)->nullable(); $table->timestamps();
        });
    }
}
