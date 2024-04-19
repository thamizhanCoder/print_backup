<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table='product_variant';
    protected $primaryKey = 'product_variant_id';
    protected $fillable=[''];
    public $timestamps = false;

}