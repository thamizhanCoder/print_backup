<?php

namespace App\Helpers;

use \Firebase\JWT\JWT;

class JwtHelper
{

    public static function getDecodedToken()
    {

        $key = env('TOKEN_KEY');
        $authToken = request()->header('Authorization');
        $token = str_replace("Bearer ", "", $authToken);

        if (trim($authToken) == '') {
            abort(401, 'Token must be present in header of the request');
        }

        try {
            return JWT::decode($token, $key, array('HS256'));
        } catch (\Throwable  $e) {
            abort(401, 'Invalid token');
        }
    }


    public static function getTokenValue($key)
    {
        $value = JwtHelper::getDecodedToken();
        return $value->$key;
    }


    public static function getSesUserId()
    {
        $value = JwtHelper::getDecodedToken();
        return $value->user_type == 'admin' ? $value->acl_user_id : $value->customer_id;
    }


    public static function getSesEmployeeId()
    {
        $value = JwtHelper::getDecodedToken();
        return $value->employee_id;
    }



    public static function getSesUserName()
    {
        $value = JwtHelper::getDecodedToken();
        return  $value->name;
    }

    public static function getSesUserNameWithType()
    {
        $value = JwtHelper::getDecodedToken();
        $id = JwtHelper::getSesUserRoleId();
        return $value->name . '(' . $value->email . ')';
    }

    public static function getSesUserRoleId()
    {
        $value = JwtHelper::getDecodedToken();
        return $value->user_type == 'admin' ? $value->acl_role_id : $value->customer_type;
    }


    public static function getSesUserType()
    {
        $value = JwtHelper::getDecodedToken();
        return $value->user_type;
    }

    public static function getCustomerType()
    {
        $value = JwtHelper::getDecodedToken();
        return $value->customer_type;
    }

    public static function getSesEmpUserId()
    {
        $value = JwtHelper::getDecodedToken();
        return $value->user_type == 'admin' ? $value->acl_user_id : $value->employee_id;
    }
}
