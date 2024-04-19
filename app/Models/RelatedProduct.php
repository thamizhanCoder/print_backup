<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelatedProduct extends Model
{
    use HasFactory;
    protected $table='related_products';
    protected $primaryKey = 'related_products_id';
    protected $fillable=[];
    public $timestamps = false;
}

