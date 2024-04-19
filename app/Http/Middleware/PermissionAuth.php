<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use App\Helpers\ControlHelper;
use App\Helpers\PermissionHelper;

class PermissionAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        // get controller method -> is the permission url slug in acl_menu table
        $methodAsUrlSlug = ControlHelper::getControllerMethod();

        //       $controllerName = ControlHelper::getControllerName();
        //       // ignore permission controller
        //       $ignoreClist = ControlHelper::getPermIgnoreController();

        //    // checking role permission allowed
        //   if(!in_array($controllerName, $ignoreClist)) {
        //   if(!PermissionHelper::hasPermission($methodAsUrlSlug)){
        //       abort(401, 'User is not allowed to access this route.');
        //   }
        // }

        // checking role permission allowed
        if (!PermissionHelper::hasPermission($methodAsUrlSlug)) {
            abort(401, 'User is not allowed to access this route.');
        }
        return $next($request);
    }
}
