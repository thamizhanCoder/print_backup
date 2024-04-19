<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatUser extends Model
{
    protected $table='chat_user';
    protected $primaryKey = 'chat_user_id';
    protected $fillable=[''];
    public $timestamps = false;

}