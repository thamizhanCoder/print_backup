<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoFrameUploadModel extends Model
{
    protected $table='order_photoframe_upload';
    protected $primaryKey = 'order_photoframe_upload_id';
    protected $fillable=[''];
    public $timestamps = false;

}