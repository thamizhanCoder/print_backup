<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taskstage extends Model
{
    protected $table='taskstage';
    protected $primaryKey = 'taskstage_id';
    protected $fillable=['department_id','servicename_id','no_of_stage','status'];
    public $timestamps = false;
}
