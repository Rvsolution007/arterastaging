<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Greeting extends Model
{
    use HasFactory;
    protected $table = "greeting";

    protected $fillable = [
        'greeting_type','greeting_category_id','user_id','language_id','zip_name','frame_image','status',"paid","height","width","image_type","aspect_ratio"
    ];

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }

    public function greeting_category()
    {
        return $this->hasOne("App\Models\GreetingCategory", "id", "greeting_category_id");
    }

    public function language()
    {
        return $this->hasOne("App\Models\Language", "id", "language_id");
    }
}
