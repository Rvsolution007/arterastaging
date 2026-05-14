<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosterMaker extends Model
{
    use HasFactory;

    protected $table = "poster_maker";

    protected $fillable = [
        'poster_category_id','template_type','zip_name','post_thumb',
        'theme', 'req_address', 'req_email', 'req_phone', 'req_website'
    ];

    public function poster_category()
    {
        return $this->hasOne("App\Models\PosterCategory", "id", "poster_category_id");
    }
}
