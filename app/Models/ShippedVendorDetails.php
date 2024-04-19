<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippedVendorDetails extends Model
{
    use HasFactory;
    protected $table='shipped_vendor_details';
    protected $primaryKey = 'shipped_vendor_details_id';
    protected $fillable=[];
    public $timestamps = false;
}