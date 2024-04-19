<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Communication extends Model
{
    use HasFactory;
    protected $table='communication';
    protected $primaryKey = 'communication_id';
    protected $fillable=[''];
    public $timestamps = false;

}