<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoPrintUploadPreviewHistoryModel extends Model
{
    protected $table='order_photoprint_preview_history';
    protected $primaryKey = 'order_photoprint_preview_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}