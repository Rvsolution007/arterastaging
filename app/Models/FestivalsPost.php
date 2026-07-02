<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FestivalsPost extends Model
{
    use HasFactory;

    protected $table = "festivals_post";

    protected $fillable = [
        'festivals_id',
        'user_id',
        'language_id',
        'frame_image',
        'status',
        "paid",
        "height",
        "width",
        "image_type",
        "aspect_ratio",
        "is_ai",
        "show_on_landing"
    ];

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }

    public function festivals()
    {
        return $this->hasOne("App\Models\Festivals", "id", "festivals_id");
    }

    public function language()
    {
        return $this->hasOne("App\Models\Language", "id", "language_id");
    }

    public function getSeoImageAttribute()
    {
        if (!$this->frame_image) return null;
        
        $watermarked = 'watermarked_' . $this->frame_image;
        $diskType = \App\Models\StorageSetting::getStorageSetting("storage");
        
        if ($diskType == 'DigitalOcean') {
            return asset('uploads/' . $watermarked);
        } else {
            if (file_exists(public_path('uploads/' . $watermarked))) {
                return asset('uploads/' . $watermarked);
            }
            return asset('uploads/' . $this->frame_image);
        }
    }
}
