<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class District extends Model
{
    use HasFactory;
    protected $table='district';
    protected $primaryKey = 'district_id';
    protected $fillable=[];
    public $timestamps = false;
}

