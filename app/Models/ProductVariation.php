<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id', 'combination', 'combination_key',
        'price', 'discount', 'custom_fields', 'description', 'status',
    ];

    protected $casts = [
        'combination' => 'array',
        'price' => 'integer',
        'discount' => 'decimal:2',
        'custom_fields' => 'array',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function getPriceInRupeesAttribute(): float { return $this->price / 100; }
    public function isActive(): bool { return $this->status === 'active'; }

    public static function generateKey(array $combination): string
    {
        $values = array_map(fn($v) => strtolower(trim($v)), array_values($combination));
        sort($values);
        return implode('|', $values);
    }
}
