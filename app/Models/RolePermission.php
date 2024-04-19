<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table='acl_permission';
    protected $primaryKey = 'acl_permission_id';
    protected $fillable=['acl_role_id','acl_menu_id','branch_id'];
    public $timestamps = false;

}
