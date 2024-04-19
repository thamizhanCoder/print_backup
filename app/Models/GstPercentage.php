<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GstPercentage extends Model
{
    use HasFactory;
    protected $table='gst_percentage';
    protected $primaryKey = 'gst_percentage_id';
    protected $fillable=['gst_percentage'];
    public $timestamps = false;
}