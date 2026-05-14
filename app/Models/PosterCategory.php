<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosterCategory extends Model
{
    use HasFactory;
    protected $table = "poster_category";

    protected $fillable = [
        'name','status'
    ];
}
