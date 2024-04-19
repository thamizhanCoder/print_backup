<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoPrintUploadQcUpload extends Model
{
    protected $table='order_photoprint_qc_history';
    protected $primaryKey = 'order_photoprint_qc_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}