<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpectedDays extends Model
{
    protected $table='expected_delivery_days';
    protected $primaryKey = 'expected_delivery_days_id';
    protected $fillable=['expected_delivery_days'];
    public $timestamps = false;
}
