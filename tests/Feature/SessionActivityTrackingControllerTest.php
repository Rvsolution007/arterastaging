<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserActivity;
use App\Services\MobileAccessTokenService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionActivityTrackingControllerTest extends TestCase
{
    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->app['url']->forceRootUrl('http://localhost');
        Cache::flush();
        $this->createSchema();

        $this->user = User::create([
            'name' => 'Tracked Customer',
            'email' => 'tracked@example.test',
            'password' => bcrypt('Safe#Password123'),
            'status' => 1,
        ]);
        $this->token = app(MobileAccessTokenService::class)->issue($this->user);
    }

    public function test_an_authenticated_session_event_is_saved_for_the_token_owner(): void
    {
        $eventId = (string) Str::uuid();
        $this->postTracking([
            'event_id' => $eventId,
            'action' => 'select_template',
            'item_type' => 'festival',
            'item_id' => '18',
            'platform' => 'Mobile',
        ])->assertOk()
            ->assertExactJson([
                'status' => 'success',
                'event_id' => $eventId,
                'duplicate' => false,
            ]);

        $activity = UserActivity::firstOrFail();
        $this->assertSame($this->user->id, $activity->user_id);
        $this->assertSame($eventId, $activity->client_event_id);
        $this->assertSame('select_template', $activity->action);
        $this->assertSame('18', $activity->payload['item_id']);
        $this->assertSame('Mobile', $activity->payload['platform']);
    }

    public function test_a_retried_client_event_is_acknowledged_without_a_duplicate_row(): void
    {
        $payload = [
            'event_id' => (string) Str::uuid(),
            'action' => 'home_visit',
            'platform' => 'Mobile',
        ];

        $this->postTracking($payload)
            ->assertOk()->assertJsonPath('duplicate', false);
        $this->postTracking($payload)
            ->assertOk()->assertJsonPath('duplicate', true);

        $this->assertSame(1, UserActivity::count());
    }

    public function test_an_unauthenticated_or_overposted_request_is_not_recorded(): void
    {
        $payload = [
            'event_id' => (string) Str::uuid(),
            'action' => 'home_visit',
            'platform' => 'Mobile',
        ];
        $this->postJson($this->trackingUri(), $payload)->assertUnauthorized();
        $this->postTracking([
            ...$payload,
            'user_id' => 999,
        ])->assertForbidden();

        $this->assertSame(0, UserActivity::count());
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedInteger('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('url')->nullable();
            $table->string('method')->nullable();
            $table->string('action')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_09_22_000001_add_client_event_id_to_user_activities_table.php'))->up();
    }

    private function postTracking(array $payload)
    {
        return $this->withToken($this->token)
            ->postJson($this->trackingUri(), $payload);
    }

    private function trackingUri(): string
    {
        return '/'.app('router')->getRoutes()->getByName('api.track-session-activity')->uri();
    }
}
