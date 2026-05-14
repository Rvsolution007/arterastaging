<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Festivals extends Model
{
    use HasFactory;

    protected $table = "festivals";

    protected $fillable = [
        'title','image','festivals_date','activation_date','status'
    ];
}
