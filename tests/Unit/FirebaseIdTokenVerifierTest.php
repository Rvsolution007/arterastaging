<?php

namespace Tests\Unit;

use App\Services\FirebaseIdTokenVerifier;
use InvalidArgumentException;
use Tests\TestCase;

class FirebaseIdTokenVerifierTest extends TestCase
{
    public function test_it_rejects_a_malformed_token_before_any_identity_is_read(): void
    {
        config(['services.firebase.project_id' => 'test-project']);

        $this->expectException(InvalidArgumentException::class);

        app(FirebaseIdTokenVerifier::class)->verifyGoogleIdentity('not-a-jwt');
    }
}
