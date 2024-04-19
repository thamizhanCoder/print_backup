<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model
{
    protected $table = 'activity_type';
    protected $primaryKey = 'activity_type_id';
    protected $fillable = [''];
    public $timestamps = false;
}
