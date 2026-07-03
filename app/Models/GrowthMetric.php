<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrowthMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'date', 'daily_installs', 'daily_active_users', 'daily_downloads', 
        'retention_day_1', 'retention_day_7', 'overall_score', 
        'top_opportunities', 'top_problems'
    ];

    protected $casts = [
        'top_opportunities' => 'array',
        'top_problems' => 'array',
    ];
}

