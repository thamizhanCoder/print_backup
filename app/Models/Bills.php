<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bills extends Model
{
    use HasFactory;
    protected $table='bill';
    protected $primaryKey = 'bill_id';
    protected $fillable=[''];
    public $timestamps = false;

}