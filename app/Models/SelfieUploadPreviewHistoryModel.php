<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfieUploadPreviewHistoryModel extends Model
{
    protected $table='order_selfieupload_preview_history';
    protected $primaryKey = 'order_selfieupload_preview_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}