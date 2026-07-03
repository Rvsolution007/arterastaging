<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'date', 'category_id', 'category_type', 'total_views', 
        'total_downloads', 'template_count', 'demand_score'
    ];
}

