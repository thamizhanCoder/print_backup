<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTaskHistory extends Model
{
    protected $table='employee_task_history';
    protected $primaryKey = 'employee_task_history_id';
    protected $fillable=[''];
    public $timestamps = false;
}