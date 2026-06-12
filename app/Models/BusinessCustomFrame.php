<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCustomFrame extends Model
{
    use HasFactory;

    protected $table = 'business_custom_frames';

    protected $fillable = [
        'custom_frame_purpose_id',
        'custom_frame_image_type_id',
        'zip_file_path',
        'original_zip_name',
        'json_rules',
        'status',
        'show_on_landing',
    ];

    public function purpose()
    {
        return $this->belongsTo(CustomFramePurpose::class, 'custom_frame_purpose_id');
    }

    public function imageType()
    {
        return $this->belongsTo(CustomFrameImageType::class, 'custom_frame_image_type_id');
    }
}
