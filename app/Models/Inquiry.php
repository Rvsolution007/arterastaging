<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use HasFactory;
    protected $table = "inquiry";

    protected $fillable = [
        'name','email','product_id','message',"mobile_no"
    ];

    public function product()
    {
        return $this->hasOne("App\Models\Product", "id", "product_id");
    }
}
