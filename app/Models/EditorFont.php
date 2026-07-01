<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EditorFont extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'family',
        'file_path',
        'format',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
