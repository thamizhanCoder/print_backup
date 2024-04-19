<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoFrameUploadHistoryModel extends Model
{
    protected $table='order_photoframe_upload_history';
    protected $primaryKey = 'order_photoframe_upload_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}