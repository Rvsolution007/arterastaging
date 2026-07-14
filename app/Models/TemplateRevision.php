<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'frame_id',
        'revision_number',
        'file_path',
        'schema_json'
    ];
}
