<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_date', 'content_type', 'target_id', 'target_name', 
        'suggested_templates', 'status', 'opportunity_score'
    ];
}
