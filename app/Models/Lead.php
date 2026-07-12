<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;
    // Security fix: replaced $guarded = [] with explicit $fillable
    protected $fillable = [
        'name',
        'email',
        'industry',
        'source',
    ];
}
