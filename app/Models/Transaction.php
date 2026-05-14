<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $table = "transaction";

    protected $fillable = [
        'user_id','subscription_id','total_paid',"date",'payment_id',"payment_type","payment_receipt","referral_code","status"
    ];

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }

    public function subscription()
    {
        return $this->hasOne("App\Models\Subscription", "id", "subscription_id");
    }
}
