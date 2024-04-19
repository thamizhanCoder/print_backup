<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table='service';
    protected $primaryKey = 'service_id';
    protected $fillable=['service_name'];
    public $timestamps = false;
}
