<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomFrame extends Model
{
    use HasFactory;
    protected $table = "custom_frame";

    protected $fillable = [
        'user_id','frame_image','status',"height","width","image_type","aspect_ratio"
    ];

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }
}
