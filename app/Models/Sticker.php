<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sticker extends Model
{
    use HasFactory;
    protected $table = "sticker";

    protected $fillable = [
        'sticker_category_id','image','status'
    ];

    public function sticker_category()
    {
        return $this->hasOne("App\Models\StickerCategory", "id", "sticker_category_id");
    }
}
