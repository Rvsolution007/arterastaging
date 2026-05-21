<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitorWebsite extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'url', 'social_url', 'last_content_hash', 'last_social_stats', 'last_checked_at'];
}
