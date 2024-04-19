<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use App\Helpers\JwtHelper;
use App\Helpers\LoginHelper;

class RoleAuth
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

        //checking bearer token
        JwtHelper::getDecodedToken();
        // checking user has blocked or not
        if (!LoginHelper::isUserAllowed()) {
            abort(401, 'User has not blocked.');
        }


        return $next($request);
    }
}
