<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DynamicDiscountHistory extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'discount_code', 'ai_subject', 'ai_body'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
