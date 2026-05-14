<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;
    protected $table = "business";

    protected $fillable = [
        'logo',
        'name',
        'email',
        'mobile_no',
        'website',
        'address',
        'user_id',
        'business_category_id',
        'business_sub_category_ids',
        'status',
        'is_default',
        'extra_emails',
        'extra_mobile_numbers',
        'extra_websites',
        'extra_addresses',
        'hidden_frame_fields'
    ];

    protected $casts = [
        'business_sub_category_ids' => 'array',
        'extra_emails' => 'array',
        'extra_mobile_numbers' => 'array',
        'extra_websites' => 'array',
        'extra_addresses' => 'array',
        'hidden_frame_fields' => 'array',
    ];

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }

    public function business_category()
    {
        return $this->hasOne("App\Models\BusinessCategory", "id", "business_category_id");
    }

    // Natively querying against JSON array: usage -> whereJsonContains('business_sub_category_ids', $id)
}
