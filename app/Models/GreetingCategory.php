<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GreetingCategory extends Model
{
    use HasFactory;
    protected $table = "greeting_category";

    protected $fillable = [
        'name','icon','status'
    ];
}
