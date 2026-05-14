<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralRegister extends Model
{
    use HasFactory;
    protected $table = "referral_register";

    protected $fillable = [
        'user_id','referral_code'
    ];

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }
}
