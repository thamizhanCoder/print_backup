<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassportSizeUploadModel extends Model
{
    protected $table='order_passport_upload';
    protected $primaryKey = 'order_passport_upload_id';
    protected $fillable=[''];
    public $timestamps = false;

}