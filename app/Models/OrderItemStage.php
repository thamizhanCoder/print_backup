<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemStage extends Model
{
    protected $table='orderitem_stage';
    protected $primaryKey = 'orderitem_stage_id';
    protected $fillable=[''];
    public $timestamps = false;
}