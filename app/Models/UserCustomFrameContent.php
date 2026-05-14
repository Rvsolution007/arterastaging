<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCustomFrameContent extends Model
{
    use HasFactory;

    protected $table = 'user_custom_frame_contents';

    protected $fillable = [
        'user_id',
        'business_custom_frame_id',
        'product_id',
        'generated_content',
    ];

    protected $casts = [
        'generated_content' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customFrame()
    {
        return $this->belongsTo(BusinessCustomFrame::class, 'business_custom_frame_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
