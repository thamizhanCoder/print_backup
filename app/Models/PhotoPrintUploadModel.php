<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoPrintUploadModel extends Model
{
    protected $table='order_photoprint_upload';
    protected $primaryKey = 'order_photoprint_upload_id';
    protected $fillable=[''];
    public $timestamps = false;

}