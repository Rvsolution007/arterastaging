<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;
    
    // Security fix: replaced $guarded = [] with explicit $fillable
    protected $fillable = [
        'user_id',
        'rating',
        'comment',
        'feature_name',
    ];
}
