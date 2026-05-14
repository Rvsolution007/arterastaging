<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessFrame extends Model
{
    use HasFactory;
    protected $table = "business_frame";

    protected $fillable = [
        'business_category_id','business_sub_category_id','user_id','frame_image','status',"paid","height","width","image_type","aspect_ratio"
    ];

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }

    public function business_category()
    {
        return $this->hasOne("App\Models\BusinessCategory", "id", "business_category_id");
    }
    
    public function business_sub_category()
    {
        return $this->hasOne("App\Models\BusinessSubCategory", "id", "business_sub_category_id");
    }
}
