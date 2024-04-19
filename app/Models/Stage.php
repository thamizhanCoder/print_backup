<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    protected $table = 'stages';
    protected $primaryKey = 'stage_id';
    protected $fillable = ['stage_name', 'status', 'taskstage_id'];
    public $timestamps = false;
}
