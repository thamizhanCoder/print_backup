<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuModule extends Model
{
    protected $table='acl_menu_module';
    protected $primaryKey = 'acl_menu_module_id';
    protected $fillable=['name'];
    public $timestamps = false;

}   