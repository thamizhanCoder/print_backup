<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomTaskQcHistory extends Model
{
    protected $table='custom_task_qc_history';
    protected $primaryKey = 'custom_task_qc_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}