<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcTaskHistory extends Model
{
    protected $table='qc_task_history';
    protected $primaryKey = 'qc_task_history_id';
    protected $fillable=[''];
    public $timestamps = false;
}