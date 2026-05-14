<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    use HasFactory;
    protected $table = "entry";

    protected $fillable = [
        'name','email','subject_id','message','mobile_no'
    ];

    public function subject()
    {
        return $this->hasOne("App\Models\Subject", "id", "subject_id");
    }
}
