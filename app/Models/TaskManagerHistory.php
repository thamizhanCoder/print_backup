<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskManagerHistory extends Model
{
    protected $table='task_manager_history';
    protected $primaryKey = 'task_manager_history_id';
    protected $fillable=[''];
    public $timestamps = false;
}