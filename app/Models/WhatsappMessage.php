<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;
    protected $table = "whatsapp_message";

    protected $fillable = [
        'message','type','btn1',"btn1_type",'btn1_value',"btn2","btn2_type","btn2_value","image","footer"
    ];
}
