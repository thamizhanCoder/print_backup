<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    protected $table='passwordreset';
    protected $primaryKey = 'passwordreset_id';
    protected $fillable=['passwordreset_id','token','acl_user_id'];
    public $timestamps = false;

}