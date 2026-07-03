<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsoKeyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'keyword', 'current_rank', 'previous_rank', 'search_volume', 'difficulty'
    ];
}
