<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table='acl_menu';
    protected $primaryKey = 'acl_menu_id';
    protected $fillable=['acl_menu_module_id','menu_name','icon_type','icon','url_type','url','url_target','menu_class','active','parent','sort','accesskey','branch_id'];
    public $timestamps = false;

}
