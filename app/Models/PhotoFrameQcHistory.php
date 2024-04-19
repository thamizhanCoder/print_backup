<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoFrameQcHistory extends Model
{
    protected $table='order_photoframeupload_qc_history';
    protected $primaryKey = 'order_photoframeupload_qc_history_id';
    protected $fillable=[''];
    public $timestamps = false;
}
