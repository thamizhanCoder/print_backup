<?php

namespace App\Http\Controllers\API\V1\AP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Firebase\JWT\JWT;
use App\Helpers\GlobalHelper;
use App\Events\ForgetPassword;
use App\Models\UserModel;
use App\Models\MenuModule;
use App\Models\RolePermission;
use App\Models\PasswordReset;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use Illuminate\Support\Facades\DB;


class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function login(Request $request)
    {
        $user_type = 'admin';

        /// LOGIN WITH USERNAME & PASSWORD
        if (isset($request->password) && isset($request->username)) {

            $password = md5($request->password);
            $username = $request->username;

            $login_det = UserModel::where([
                ['email', '=', $username],
                ['password', '=', $password],
                ['status', '!=', 2],
            ])->first();
        }
        /// LOGIN WITH MOBILE
        elseif (isset($request->mobile)) {

            $mobile = $request->mobile;

            $login_det = UserModel::where([
                ['mobile_no', '=', $mobile],
                ['status', '!=', 2],
            ])->first();

            $user_type = 'admin';
            if (empty($login_det)) {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => 'We could not find mobile number', 'data' => []
                ]);
            }
        }

        if (!empty($login_det)) {
            if ($login_det->status == 1) {
                $acl_user_id = isset($login_det->acl_user_id) ? $login_det->acl_user_id : 1;
                $acl_role_id = isset($login_det->acl_role_id) ? $login_det->acl_role_id : 1;
                $name = isset($login_det->name) ? $login_det->name : "admin";
                $email = isset($login_det->email) ? $login_det->email : "";

                $otp = '';
                if (isset($request->mobile)) {
                    $otp = GlobalHelper::getOTP(4);
                    $user_own_name = $name;
                    // $msg = $otp . " is the OTP to login to The Buywoods. Please enter the OTP to verify your mobile number.";
                    $msg = ' Dear ' . $user_own_name . ', welcome to Spark Fitness, ' . $otp . ' is your OTP for your login. Do not share with anyone.';
                    $isSmsSent = GlobalHelper::sendSms($request->mobile, $msg);
                }

                // prepare role permission json
                $menu_group = DB::table('acl_menu_module')->where([
                    ['status', '=', 1],
                ])->get();

                $data = [];

                if (!empty($menu_group)) {
                    foreach ($menu_group as $row) {
                        $data[$row->name] = [];
                        $action_menu = DB::table('acl_permission AS p')
                            ->select('m.url')
                            ->leftJoin('acl_menu AS m', 'm.acl_menu_id', 'p.acl_menu_id')
                            ->where([
                                ['m.acl_menu_module_id', '=', $row->acl_menu_module_id],
                                ['p.acl_role_id', '=', $acl_role_id]
                            ])
                            ->get();
                        if (!empty($action_menu)) {
                            foreach ($action_menu as $menus) {
                                array_push($data[$row->name], $menus->url);
                            }
                        }
                    }
                }


                // generate JWT token        
                $key = env('TOKEN_KEY');
                $issue_time = strtotime(date('Y-m-d H:i:s'));
                $expire_time = strtotime(date('Y-m-d H:i:s', strtotime("+12 hours", $issue_time)));
                $payload = array(
                    "portal" => "admin",
                    "name" => $name,
                    "user_type" => $user_type,
                    "email" => $email,
                    "permission" => $data,
                    "acl_user_id" => $acl_user_id,
                    "acl_role_id" => $acl_role_id,
                    "iss" => env('TOKEN_ISSUER'),
                    "aud" => $login_det->email,
                    "iat" => $issue_time,
                    "nbf" => $issue_time,
                    "exp" => $expire_time
                );

                $jwt = JWT::encode($payload, $key, 'HS256');
                


                // log activity
                // $desc = 'Logged In ' . $name . '-' . $email;
                $desc = 'Login successfully by using email id '. $email.' at '.$login_det->updated_on;

                $activitytype = Config('activitytype.Admin Login');
                GlobalHelper::logActivity($desc, $activitytype, $acl_user_id, 1);

                if (isset($request->mobile)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'OTP sent successfully',
                        'data' => [
                            'token' => $jwt,
                            'otp' => $otp,
                        ]
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'Logged in successfully',
                        'data' => [
                            'token' => $jwt,
                            'otp' => $otp,
                        ]
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => 'Account suspended', 'data' => []
                ]);
            }
        } else {
            // log activity
            $desc = 'Login attempt failed using ' . $username;
            $activitytype = Config('activitytype.Admin Login');
            GlobalHelper::logActivity($desc, $activitytype, 0, 1);
            return response()->json([
                'keyword' => 'failed',
                'message' => 'Email address or password invalid', 'data' => []
            ]);
        }
    }

    public function forget(Request $request)
    {
        $email = $request->email;
        $validity_hours = 24;
        $insert_id = 0;

        $user = UserModel::where('email', '=', $email)->first();

        if (!empty($user)) {
            if (isset($user->acl_user_id)) {
                $result = PasswordReset::where('acl_user_id', '=', $user->acl_user_id)->first();
                if (!empty($result)) {
                    PasswordReset::where('acl_user_id', '=', $user->acl_user_id)->delete();
                }

                $insert_id = PasswordReset::insertGetId([
                    'acl_user_id' => $user->acl_user_id,
                    'token' => md5(rand() . microtime() . time() . uniqid()),
                    'created_on' => date('Y-m-d H:i:s'),
                    'validity_hours' => $validity_hours,
                ]);
            }
            if ($insert_id > 0) {
                $insert_result = PasswordReset::where('id', '=', $insert_id)->first();
                $token = $insert_result->token;
                $name =  env('RESET_URL') . $token . "/";

                $mail_data = [];
                $mail_data['name'] = $user->name;
                $mail_data['link'] = $name;
                $mail_data['email'] = $user->email;

                if ($user->email != '') {
                    event(new ForgetPassword($mail_data));
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => 'Email not found', 'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => 'Mail send failed', 'data' => []
                ]);
            }

            // log activity
            $desc = 'Forget password by ' . $user->name . '-' . $user->email;
            $activitytype = Config('activitytype.Admin Login');
            GlobalHelper::logActivity($desc, $activitytype, $user->acl_user_id, 1);

            return response()->json([
                'keyword' => 'success',
                'message' => 'Email has been sent successfully', 'data' => []
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => 'We could not find email address', 'data' => []
            ]);
        }
    }

    public function reset(Request $request)
    {
        $token = $request->token;
        $password = md5($request->password);

        $user = PasswordReset::where('token', '=', $request->token)->first();

        if (!empty($user)) {
            $created_time = $user->created_on;
            $validity_hours = $user->validity_hours;
            $expired_time = date('Y-m-d H:i:s', strtotime("+" . $validity_hours . " hours", strtotime($created_time)));
            $current_time = date('Y-m-d H:i:s');

            if ($current_time < $expired_time) {
                if ($user->acl_user_id > 0) {
                    $result = UserModel::where('acl_user_id', $user->acl_user_id)
                        ->update(['password' => $password]);

                    PasswordReset::where('acl_user_id', '=', $user->acl_user_id)->delete();
                }

                $user_detail = UserModel::where('acl_user_id', $user->acl_user_id)->first();

                // log activity
                $desc = 'Reset password by ' . $user_detail->name . '-' . $user_detail->email;
                $activitytype = Config('activitytype.Admin Login');
                GlobalHelper::logActivity($desc, $activitytype, $user->acl_user_id, 1);

                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Password reset successfully', 'data' => []
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => 'Link has been expired', 'data' => []
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => 'Link has been expired', 'data' => []
            ]);
        }
    }

    public function changepassword(Request $request)
    {
        $old_password = $request->old_password;
        $old_pass = md5($old_password);
        $new_password = md5($request->new_password);
        $email = $request->email;

        $user = UserModel::where([
            ['email', '=', $email]
        ])->first();

        if (!empty($user)) {
            if ($user->password == $old_pass) {
                $result = UserModel::where('acl_user_id', $user->acl_user_id)->update(['password' => $new_password]);

                // log activity
                $desc = 'Password was changed successfully by ' . $user->name . '-' . $user->email;
                $activitytype = Config('activitytype.Admin Login');
                GlobalHelper::logActivity($desc, $activitytype, $user->acl_user_id, 1);

                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Password updated successfully',
                    'data' => []
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failure',
                    'message' => 'Incorrect old password',
                    'data' => []
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failure',
                'message' => 'Email address or password invalid',
                'data' => []
            ]);
        }
    }

    public function user_viewprofile(Request $request, $id)
    {
        $key = env('TOKEN_KEY');
        $getToken = $request->header('Authorization');

        $token = str_replace("Bearer ", "", $getToken);

        try {
            $decoded = JWT::decode($token, $key, array('HS256'));

            if ($id != '' && $id > 0) {
                $data = [];

                $user = new UserModel();

                $data = UserModel::where('acl_user.acl_user_id', $id)
                    ->leftjoin('acl_role', 'acl_role.acl_role_id', '=', 'acl_user.acl_role_id')
                    ->select('acl_user.acl_user_id', 'acl_role.acl_role_id', 'acl_role.role_name', 'acl_user.name', 'acl_user.mobile_no', 'acl_user.email', 'acl_user.status', 'acl_user.created_on', 'acl_user.created_on', 'acl_user.updated_on', 'acl_user.updated_by')->get();


                if (!empty($data)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('User viewed successfully'),
                        'data' => $data
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failure',
                        'message' =>  __('No data found'), 'data' => $data
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' =>  __('No data found'), 'data' => []
                ]);
            }
        } catch (\Firebase\JWT\ExpiredException $e) {
            abort(401, 'Token not found');
        }
    }

    public function userupdateFcmToken(Request $request)
    {
        $id = JwtHelper::getSesUserId();
        $update = UserModel::where('acl_user_id', $id)->update(array(
            'token' => $request->token,
            'updated_on' => Server::getDateTime()
        ));
        return response()->json([
            'keyword' => 'success',
            'message' =>  'token upadated successfully',
            'data' => []
        ]);
    }

    public function logoutActivityLog(Request $request)
    {
        $user_id = $request->user_id;
        $update = UserModel::where('acl_user_id', $user_id)->update(array(
            'updated_on' => Server::getDateTime()
        ));
        $userDetails = UserModel::where('acl_user_id', $user_id)->first();
        $desc = 'Logout successfully by using email id '. $userDetails->email.' at '.$userDetails->updated_on;
        $activitytype = Config('activitytype.Logout');
        GlobalHelper::logActivity($desc, $activitytype, $user_id, 1);
        
        return response()->json([
            'keyword' => 'success',
            'message' =>  'Logout successfully',
            'data' => []
        ]);
    }
}
