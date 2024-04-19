<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassportSizeUploadPreviewHistoryModel extends Model
{
    protected $table='order_passportupload_preview_history';
    protected $primaryKey = 'order_passportupload_preview_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}