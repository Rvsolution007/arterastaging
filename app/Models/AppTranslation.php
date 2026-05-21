<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppTranslation extends Model
{
    use HasFactory;

    protected $table = 'app_translations';

    protected $fillable = [
        'language_code',
        'title',
        'status',
        'translations',
    ];

    protected $casts = [
        'translations' => 'json',
    ];
}
