<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCatalogue extends Model
{
    use HasFactory;
    protected $table='product';
    protected $primaryKey = 'product_id';
    protected $fillable=[''];
    public $timestamps = false;
}