<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponCode extends Model
{
    use HasFactory;
    protected $table='coupon_code';
    protected $primaryKey = 'coupon_code_id';
    protected $fillable=['coupon_code','description','percentage'];
    public $timestamps = false;
}
