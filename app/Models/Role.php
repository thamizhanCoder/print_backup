<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table='acl_role';
    protected $primaryKey = 'acl_role_id';
    protected $fillable=['role_name'];
    public $timestamps = false;

}