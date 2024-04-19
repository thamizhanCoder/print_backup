<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    protected $table='acl_user';
    protected $primaryKey = 'acl_user_id';
    protected $fillable=['acl_role_id','name','email','password','mobile_no','active'];
    public $timestamps = false;

}