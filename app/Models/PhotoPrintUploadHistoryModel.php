<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoPrintUploadHistoryModel extends Model
{
    protected $table='order_photoprint_upload_history';
    protected $primaryKey = 'order_photoprint_upload_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}