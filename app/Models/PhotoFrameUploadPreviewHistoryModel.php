<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoFrameUploadPreviewHistoryModel extends Model
{
    protected $table='order_photoframeupload_preview_history';
    protected $primaryKey = 'order_photoframeupload_preview_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}