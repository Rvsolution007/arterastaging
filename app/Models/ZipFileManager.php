<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZipFileManager extends Model
{
    use HasFactory;
    protected $table = "zip_file_managers";

    protected $fillable = [
        'file_name',
        'zip_file',
    ];

    public function posts()
    {
        return $this->hasMany("App\Models\GeneralPost", "zip_file_id", "id");
    }
}
