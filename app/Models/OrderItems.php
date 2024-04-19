<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model
{
    protected $table='order_items';
    protected $primaryKey = 'order_items_id';
    protected $fillable=[''];
    public $timestamps = false;

}