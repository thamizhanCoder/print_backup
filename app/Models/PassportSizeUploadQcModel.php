<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassportSizeUploadQcModel extends Model
{
    protected $table='order_passportupload_qc_history';
    protected $primaryKey = 'order_passportupload_qc_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}