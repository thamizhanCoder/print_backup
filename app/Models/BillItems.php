<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillItems extends Model
{
    use HasFactory;
    protected $table='bill_item';
    protected $primaryKey = 'bill_item_id';
    protected $fillable=[''];
    public $timestamps = false;

}