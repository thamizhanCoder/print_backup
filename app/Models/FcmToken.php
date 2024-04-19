<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FcmToken extends Model
{
    use HasFactory;
    protected $table='fcm_token';
    protected $primaryKey = 'fcm_token_id';
    protected $fillable=[];
    public $timestamps = false;
}

