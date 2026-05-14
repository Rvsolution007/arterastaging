<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFavoriteFrame extends Model
{
    use HasFactory;

    protected $table = 'user_favorite_frames';

    protected $fillable = [
        'user_id',
        'frame_identifier'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
