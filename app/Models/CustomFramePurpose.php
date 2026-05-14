<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomFramePurpose extends Model
{
    use HasFactory;

    protected $table = 'custom_frame_purposes';

    protected $fillable = [
        'name',
        'icon',
        'ai_prompt',
        'data_requirement',
        'status',
    ];

    public function frames()
    {
        return $this->hasMany(BusinessCustomFrame::class, 'custom_frame_purpose_id');
    }
}
