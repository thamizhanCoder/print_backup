<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalizedUploadModel extends Model
{
    protected $table='order_personalized_upload';
    protected $primaryKey = 'order_personalized_upload_id';
    protected $fillable=[''];
    public $timestamps = false;

}