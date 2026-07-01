<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EditorTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'canvas_width',
        'canvas_height',
        'schema_json',
        'legacy_json',
        'thumbnail_path',
        'status',
        'is_premium',
        'author_id',
        'tags',
        'category',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'legacy_json' => 'array',
        'tags' => 'array',
        'is_premium' => 'boolean',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
