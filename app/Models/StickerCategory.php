<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StickerCategory extends Model
{
    use HasFactory;
    protected $table = "sticker_category";

    protected $fillable = [
        'name','status'
    ];

    public function sticker()
    {
        return $this->hasMany(Sticker::class, 'sticker_category_id');
    }
}
