<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A safe, idempotency/audit record for a signed AdLive identity mutation.
 *
 * This table deliberately contains no request body, credentials, tokens, or
 * identity values. The fingerprint is an HMAC of the canonical request and is
 * useful only for detecting a conflicting re-use of a request ID.
 */
class AdLiveIdentityRequest extends Model
{
    protected $table = 'adlive_identity_requests';

    protected $fillable = [
        'request_id',
        'request_fingerprint',
        'operation',
        'source',
        'artera_user_id',
        'changed_fields',
        'occurred_at',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'occurred_at' => 'datetime',
    ];
}
