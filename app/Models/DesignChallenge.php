<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesignChallenge extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'type', 'target_count', 'target_id', 'start_date', 'end_date', 'reward_points', 'is_active'];
}
