<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMiniWebsite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'mini_website_template_id',
        'slug',
        'views_count',
    ];
}
