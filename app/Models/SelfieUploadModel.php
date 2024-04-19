<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfieUploadModel extends Model
{
    protected $table='order_selfie_upload';
    protected $primaryKey = 'order_selfie_upload_id';
    protected $fillable=[''];
    public $timestamps = false;

}