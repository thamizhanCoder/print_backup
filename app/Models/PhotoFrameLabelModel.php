<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoFrameLabelModel extends Model
{
    protected $table='order_photoframe_upload_label';
    protected $primaryKey = 'order_photoframe_upload_label_id';
    protected $fillable=[''];
    public $timestamps = false;

}