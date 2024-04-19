<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddToCart extends Model
{
    protected $table = 'add_to_cart';
    protected $primaryKey = 'add_to_cart_id';
    protected $fillable = [''];
    public $timestamps = false;
}
