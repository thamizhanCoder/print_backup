<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfieUploadQcModel extends Model
{
    protected $table='order_selfieupload_qc_history';
    protected $primaryKey = 'order_selfieupload_qc_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}