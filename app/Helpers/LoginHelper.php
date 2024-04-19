<?php

namespace App\Helpers;

use App\Helpers\JwtHelper;
use App\Models\Customer;
use App\Models\UserModel;

class LoginHelper
{

    public static function isUserAllowed()
    {
        $userType = JwtHelper::getSesUserType();
        $userId = JwtHelper::getSesUserId();
        $user = ($userType == 'admin') ?
            UserModel::where('acl_user_id', $userId)->where('status', 1)->first()
            : Customer::where('customer_id', $userId)->where('status', 1)->first();
        return !empty($user);
    }
}
