<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerified extends Model 
{
	protected $table = "email_verify";
	protected $fillable = ['user_id', 'code', 'created_at'];
	public $timestamps = false;
}
