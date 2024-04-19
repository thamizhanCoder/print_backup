<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskManagerQcHistory extends Model
{
    protected $table='task_manager_qc_history';
    protected $primaryKey = 'task_manager_qc_history_id';
    protected $fillable=[''];
    public $timestamps = false;
}
