<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revertstatus extends Model
{
    use HasFactory;
    protected $table='revert_status';
    protected $primaryKey = 'revert_status_id';
    protected $fillable=[''];
    public $timestamps = false;
}
