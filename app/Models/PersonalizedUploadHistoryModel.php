<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalizedUploadHistoryModel extends Model
{
    protected $table='order_personalized_upload_history';
    protected $primaryKey = 'order_personalized_upload_history_id';
    protected $fillable=[''];
    public $timestamps = false;

}