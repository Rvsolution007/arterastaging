<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostPurpose extends Model
{
    use HasFactory;

    protected $table = 'post_purposes';

    protected $fillable = [
        'name',
        'status',
    ];
}
