<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessProductMapping extends Model
{
    use HasFactory;

    protected $table = 'business_product_mappings';

    protected $fillable = [
        'business_id',
        'business_product_id'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function product()
    {
        return $this->belongsTo(BusinessProduct::class, 'business_product_id');
    }
}
