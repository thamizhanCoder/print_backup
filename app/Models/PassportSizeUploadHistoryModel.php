<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassportSizeUploadHistoryModel extends Model
{
    protected $table='order_passport_upload_history';
    protected $primaryKey = 'order_passport_upload_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}