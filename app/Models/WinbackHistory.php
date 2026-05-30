<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WinbackHistory extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'magic_link_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
