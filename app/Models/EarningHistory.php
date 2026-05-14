<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EarningHistory extends Model
{
    use HasFactory;
    protected $table = "earning_history";

    protected $fillable = [
        'user_id','amount',"date","amount_type","refer_user"
    ];

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }

    public function referUser()
    {
        return $this->hasOne("App\Models\User", "id", "refer_user");
    }
}
