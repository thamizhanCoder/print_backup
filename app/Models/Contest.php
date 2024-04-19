<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contest extends Model
{
    use HasFactory;
    protected $table='contest';
    protected $primaryKey = 'contest_id';
    protected $fillable=[''];
    public $timestamps = false;

}