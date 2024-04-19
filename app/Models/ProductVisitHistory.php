<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVisitHistory extends Model
{
    protected $table='product_visit_history';
    protected $primaryKey = 'product_visit_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}