<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskManagerPreviewHistory extends Model
{
    protected $table='task_manager_preview_history';
    protected $primaryKey = 'task_manager_preview_history_id';
    protected $fillable=[''];
    public $timestamps = false;
}
