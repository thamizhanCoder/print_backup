<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalizedUploadPreviewHistoryModel extends Model
{
    protected $table='order_personalizedupload_preview_history';
    protected $primaryKey = 'order_personalizedupload_preview_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}