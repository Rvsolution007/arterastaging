<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RegressionTestLog extends Model
{
    protected $fillable = ['trigger', 'total_frames_tested', 'passed', 'failed', 'results', 'status'];
    protected $casts = ['results' => 'array'];
}
