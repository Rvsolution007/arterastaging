<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AdLiveSecurityEventService;
use App\Services\AdLiveUserProvisioningClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdLiveIdentityBridgeTest extends TestCase
{
    public function test_profile_sync_sends_only_the_safe_identity_snapshot(): void
    {
        config([
            'adlive.user_provision_url' => 'https://adlive.test/api/internal/artera/identities/provision',
            'adlive.shared_secret' => 'test-bridge-key',
        ]);
        Http::fake(['https://adlive.test/*' => Http::response([], 200)]);

        $user = new User([
            'name' => 'Shared Account',
            'email' => 'shared@example.test',
            'mobile_no' => '9999999999',
            'registration_source' => 'artera_pixel',
        ]);
        $user->id = 42;

        $this->assertTrue(app(AdLiveUserProvisioningClient::class)->sync($user, null));

        Http::assertSent(function (Request $request): bool {
            $identity = $request->data()['identity'];

            $this->assertSame('42', $identity['artera_user_id']);
            $this->assertSame('shared@example.test', $identity['email']);
            $this->assertSame('artera_pixel', $identity['signup_source']);
            $this->assertArrayNotHasKey('password', $identity);

            return true;
        });
    }

    public function test_session_revocation_contains_no_password(): void
    {
        config([
            'adlive.security_revoke_url' => 'https://adlive.test/api/internal/artera/security/revoke',
            'adlive.shared_secret' => 'test-bridge-key',
        ]);
        Http::fake(['https://adlive.test/*' => Http::response([], 204)]);

        $user = new User;
        $user->id = 42;

        $this->assertTrue(app(AdLiveSecurityEventService::class)->revokeLinkedSessions($user, 'password_changed'));

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            $this->assertSame('42', $payload['artera_user_id']);
            $this->assertSame('password_changed', $payload['reason']);
            $this->assertArrayNotHasKey('password', $payload);

            return true;
        });
    }
}
