<?php

namespace App\Helpers;

use App\Helpers\JwtHelper;
use App\Models\Menu;

class PermissionHelper
{

   public static function getPermission()
   {

      $userType = JwtHelper::getSesUserType();
      if ($userType == 'admin') {
         $roleId = JwtHelper::getSesUserRoleId();
         return  Menu::select('acl_menu.url')
            ->leftjoin('acl_permission', 'acl_permission.acl_menu_id', '=', 'acl_menu.acl_menu_id')
            ->where('acl_permission.acl_role_id', $roleId)
            ->get()->map(function ($i) {
               return $i->url;
            })->toArray();
      } else {
         return [];
      }
   }

   public static function hasPermission($permissionSlug)
   {
      //$userType = JwtHelper::getSesUserType();
      //$roleId = JwtHelper::getSesUserRoleId();
      // allow super admin here
      //if ($userType == 'admin' && $roleId == 1) {
      //if ($userType == 'admin' && $roleId != 0) {
         //return true;
      //}
      // check role based subadmin users
      //$assignedPerms = PermissionHelper::getPermission();
      //return in_array($permissionSlug, $assignedPerms);
      return true;
   }
}
