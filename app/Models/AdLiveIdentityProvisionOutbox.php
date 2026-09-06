<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdLiveIdentityProvisionOutbox extends Model
{
    protected $table = 'adlive_identity_provision_outbox';

    protected $fillable = [
        'artera_user_id',
        'artera_business_id',
        'sync_batch_id',
        'delivery_order',
        'signup_source',
        'delivery_attempts',
        'last_failure',
        'processing_at',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'processing_at' => 'datetime',
    ];
}
