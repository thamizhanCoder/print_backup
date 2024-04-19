<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskManager extends Model
{
    protected $table='task_manager';
    protected $primaryKey = 'task_manager_id';
    protected $fillable=[''];
    public $timestamps = false;
}