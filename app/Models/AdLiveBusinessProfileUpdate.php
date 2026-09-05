<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Non-sensitive audit and idempotency record for an AdLive profile update.
 */
class AdLiveBusinessProfileUpdate extends Model
{
    protected $table = 'adlive_business_profile_updates';

    protected $fillable = [
        'request_id',
        'request_fingerprint',
        'source',
        'artera_user_id',
        'artera_business_id',
        'changed_fields',
        'occurred_at',
        'resulting_profile_version',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'occurred_at' => 'datetime',
    ];
}
