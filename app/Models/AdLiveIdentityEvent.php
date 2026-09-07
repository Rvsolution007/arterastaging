<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Durable, ID-only Pixel-to-AdLive delivery record. */
class AdLiveIdentityEvent extends Model
{
    protected $table = 'adlive_identity_events';

    protected $fillable = [
        'event_id',
        'event_type',
        'artera_user_id',
        'artera_business_id',
        'occurred_at',
        'delivery_attempts',
        'processing_at',
        'sent_at',
        'last_failure',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'processing_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
