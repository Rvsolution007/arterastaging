<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrowthTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'date', 'module', 'priority', 'task_description', 
        'recommendation_reason', 'status'
    ];
}

