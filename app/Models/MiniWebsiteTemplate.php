<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniWebsiteTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'preview_image',
        'html_content',
        'status',
    ];
}
