<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OtherDistrict extends Model
{
    use HasFactory;
    protected $table='other_district';
    protected $primaryKey = 'other_district_id';
    protected $fillable=['district_name','state_id','status'];
    public $timestamps = false;
}