<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomPostFrame extends Model
{
    use HasFactory;
    protected $table = "custom_post_frame";

    protected $fillable = [
        'custom_frame_type','custom_post_id','user_id','language_id','zip_name','frame_image','status',"paid","height","width","image_type","aspect_ratio","fingerprint","show_on_landing"
    ];

    protected $casts = [
        'fingerprint' => 'array',
    ];

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }

    public function custom_post()
    {
        return $this->hasOne("App\Models\CustomPost", "id", "custom_post_id");
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
