<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIImageGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'prompt',
        'reference_image_used',
        'slot_width',
        'slot_height',
        'generated_image_path',
        'template_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
