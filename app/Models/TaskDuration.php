<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskDuration extends Model
{
    protected $table = 'task_duration';
    protected $primaryKey = 'task_duration_id';
    protected $fillable = ['duration', 'revert_status'];
    public $timestamps = false;
}
