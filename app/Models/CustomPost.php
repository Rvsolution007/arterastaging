<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomPost extends Model
{
    use HasFactory;
    protected $table = "custom_post";

    protected $fillable = [
        'name','icon','status'
    ];
}
