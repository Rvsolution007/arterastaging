<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiPushNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_type', 'target_id', 'title', 'body', 'status', 'scheduled_for', 'predicted_ctr'
    ];
}
