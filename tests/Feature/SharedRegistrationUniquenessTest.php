<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AdLiveRegistrationController;
use App\Http\Controllers\Api\AuthApi;
use App\Services\AdLiveBusinessProfileService;
use App\Services\AdLiveIdentitySyncService;
use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SharedRegistrationUniquenessTest extends TestCase
{
    private string $credential;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
        ]);
        DB::purge('sqlite');
        if (! app()->environment('testing') || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('An isolated test database is required.');
        }
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('mobile_no');
            $table->string('password');
            $table->string('registration_source');
            $table->softDeletes();
        });
        $this->credential = bin2hex(random_bytes(24)).'aA!';
    }

    public function test_both_registration_entrypoints_reject_an_existing_account_from_either_product(): void
    {
        foreach (['adlive', 'artera_pixel'] as $index => $source) {
            $email = "existing{$index}@example.test";
            $phone = "987654321{$index}";
            $hash = Hash::make($this->credential);
            $id = DB::table('users')->insertGetId([
                'email' => $email, 'mobile_no' => $phone,
                'password' => $hash, 'registration_source' => $source,
            ]);

            foreach (['pixel', 'adlive'] as $entrypoint) {
                foreach (['email', 'mobile_no'] as $field) {
                    $payload = [
                        'name' => 'Duplicate Customer',
                        'email' => $field === 'email' ? '  '.strtoupper($email).'  ' : 'new@example.test',
                        'mobile_no' => $field === 'mobile_no' ? "98765 4321{$index}" : '8000000000',
                        'password' => $this->credential,
                        'business_name' => 'Business',
                        'business_category_id' => 1,
                    ];
                    $request = Request::create('/', 'POST', $payload);
                    if ($entrypoint === 'pixel') {
                        $response = app(AuthApi::class)->registration($request, app(AdLiveIdentitySyncService::class));
                    } else {
                        $request->attributes->set('adlive_profile_authenticated', true);
                        $response = app(AdLiveRegistrationController::class)->register(
                            $request, app(AdLiveInternalRequestVerifier::class), app(AdLiveBusinessProfileService::class)
                        );
                    }
                    $this->assertSame(422, $response->getStatusCode());
                    $this->assertStringContainsString('already registered', $response->getData(true)['errors'][$field][0]);
                    $this->assertStringContainsString('Please log in', $response->getData(true)['errors'][$field][0]);
                    $this->assertSame($index + 1, DB::table('users')->count());
                    $this->assertSame($hash, DB::table('users')->where('id', $id)->value('password'));
                }
            }
        }
    }

    public function test_registration_race_cannot_replace_an_existing_password(): void
    {
        Schema::create('business_category', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('status');
        });
        DB::table('business_category')->insert(['id' => 1, 'name' => 'Retail', 'status' => 1]);
        $hash = Hash::make($this->credential);
        $inserted = false;
        // Another signup finishes immediately after the email uniqueness
        // query returned zero. The second check must preserve that account.
        DB::listen(function ($query) use (&$inserted, $hash): void {
            if (! $inserted && str_contains($query->sql, 'count(*)') && str_contains($query->sql, '"email"')) {
                $inserted = true;
                DB::table('users')->insert([
                    'email' => 'race@example.test', 'mobile_no' => '9000000000',
                    'password' => $hash, 'registration_source' => 'artera_pixel',
                ]);
            }
        });
        $request = Request::create('/', 'POST', [
            'name' => 'Other Signup', 'email' => 'race@example.test', 'mobile_no' => '8000000000',
            'password' => bin2hex(random_bytes(24)).'aA!',
            'business_name' => 'Other Business', 'business_category_id' => 1,
        ]);
        $request->attributes->set('adlive_profile_authenticated', true);
        try {
            app(AdLiveRegistrationController::class)->register(
                $request, app(AdLiveInternalRequestVerifier::class), app(AdLiveBusinessProfileService::class)
            );
            $this->fail('Concurrent registration must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('already registered', $exception->errors()['email'][0]);
        }
        $this->assertTrue($inserted);
        $this->assertSame(1, DB::table('users')->count());
        $this->assertSame($hash, DB::table('users')->value('password'));
        $this->assertSame('9000000000', DB::table('users')->value('mobile_no'));
    }
}
