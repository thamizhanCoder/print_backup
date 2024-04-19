<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfieUploadHistoryModel extends Model
{
    protected $table='order_selfie_upload_history';
    protected $primaryKey = 'order_selfie_upload_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}