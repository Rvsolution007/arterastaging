<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;

    protected $table = 'product_types';

    protected $fillable = [
        'name',
        'slug',
        'status'
    ];

    public function products()
    {
        return $this->hasMany(BusinessProduct::class, 'product_type_id', 'id');
    }
}
