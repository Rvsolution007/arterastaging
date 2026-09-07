<?php

namespace Tests\Feature;

use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdLiveRegistrationOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'adlive.shared_secret' => 'registration-options-test-secret',
            'adlive.internal_request_max_age_seconds' => 300,
            'app.api_prefix' => 'api',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->app['url']->forceRootUrl('http://localhost');
        Cache::flush();

        Schema::create('business_category', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('status')->default(1);
            $table->timestamps();
        });
        Schema::create('business_sub_category', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('business_category_id');
            $table->string('name');
            $table->unsignedInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function test_signed_registration_options_request_is_served_once_through_the_internal_middleware(): void
    {
        $category = BusinessCategory::create(['name' => 'Retail', 'status' => 1]);
        $subCategory = BusinessSubCategory::create([
            'business_category_id' => $category->id,
            'name' => 'Bakery',
            'status' => 1,
        ]);
        BusinessCategory::create(['name' => 'Inactive', 'status' => 0]);

        $this->signedPost()->assertOk()->assertExactJson([
            'categories' => [[
                'id' => (string) $category->id,
                'name' => 'Retail',
                'sub_categories' => [[
                    'id' => (string) $subCategory->id,
                    'name' => 'Bakery',
                ]],
            ]],
        ]);
    }

    private function signedPost()
    {
        $timestamp = (string) now()->timestamp;
        $nonce = 'options-'.str_replace('-', '', (string) Str::uuid());
        $verifier = app(AdLiveInternalRequestVerifier::class);
        $signature = hash_hmac(
            'sha256',
            $verifier->signaturePayload('POST', '/api/internal/adlive/registration/options', $timestamp, $nonce, []),
            (string) config('adlive.shared_secret'),
        );

        return $this->call('POST', '/api/internal/adlive/registration/options', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_ARTERA_ADLIVE_TIMESTAMP' => $timestamp,
            'HTTP_X_ARTERA_ADLIVE_NONCE' => $nonce,
            'HTTP_X_ARTERA_ADLIVE_SIGNATURE' => $signature,
        ], '[]');
    }
}
