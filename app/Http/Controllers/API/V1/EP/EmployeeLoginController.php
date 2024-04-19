<?php

namespace App\Http\Controllers\API\V1\EP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Firebase\JWT\JWT;
use App\Helpers\GlobalHelper;
use App\Events\ForgetPassword;
use App\Models\Employee;
use App\Models\PasswordReset;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Requests\EmployeeLoginRequest;
use App\Http\Requests\ResetRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeLoginController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function login_old(Request $request)
    {
        $user_type = 'employee';

        /// LOGIN WITH USERNAME & PASSWORD
        if (isset($request->password) && isset($request->username)) {

            $password = md5($request->password);
            $username = $request->username;

            $login_det = Employee::where([
                ['email', '=', $username],
                // ['mobile_no', '=', $username],
                ['password', '=', $password],
                ['status', '!=', 2],
            ])->first();
        }
        /// LOGIN WITH MOBILE
        // elseif (isset($request->mobile)) {

        //     $mobile = $request->mobile;

        //     $login_det = Employee::where([
        //         ['mobile_no', '=', $mobile],s
        //         ['status', '!=', 2],
        //     ])->first();

        //     if (empty($login_det)) {
        //         return response()->json([
        //             'keyword' => 'failed',
        //             'message' => 'We could not find mobile number', 'data' => []
        //         ]);
        //     }
        // }

        if (!empty($login_det)) {
            if ($login_det->status == 1) {

                // $otp = '';
                // if (isset($request->mobile)) {
                //     $otp = GlobalHelper::getOTP(4);
                //     $user_own_name = $name;
                //     // $msg = $otp . " is the OTP to login to The Buywoods. Please enter the OTP to verify your mobile number.";
                //     $msg = ' Dear ' . $user_own_name . ', welcome to Spark Fitness, ' . $otp . ' is your OTP for your login. Do not share with anyone.';
                //     $isSmsSent = GlobalHelper::sendSms($request->mobile, $msg);
                // }

                // generate JWT token        
                $key = env('TOKEN_KEY');
                $issue_time = strtotime(date('Y-m-d H:i:s'));
                $expire_time = strtotime(date('Y-m-d H:i:s', strtotime("+12 hours", $issue_time)));
                $payload = array(
                    "portal" => "employee",
                    "employee_name" => $login_det->employee_name,
                    "user_type" => $user_type,
                    "employee_type" => $login_det->employee_type,
                    "mobile_no" => $login_det->mobile_no,
                    "employee_image" => $login_det->employee_image,
                    "email" => $login_det->email,
                    "iss" => env('TOKEN_ISSUER'),
                    "aud" => $login_det->email,
                    "iat" => $issue_time,
                    "nbf" => $issue_time,
                    "exp" => $expire_time
                );

                $jwt = JWT::encode($payload, $key, 'HS256');



                // log activity
                // $desc = 'Logged In ' . $login_det->employee_name . '-' . $login_det->email;
                // $desc = 'Login successfully by using employee email ID '. $login_det->email .' ';
                // $activitytype = Config('activitytype.Admin Login');
                // GlobalHelper::logActivity($desc, $activitytype, $login_det->employee_id, 1);

                if (isset($request->mobile)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'OTP sent successfully',
                        'data' => [
                            'token' => $jwt
                        ]
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'Logged in successfully',
                        'data' => [
                            'token' => $jwt
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
            // $desc = 'Login attempt failed using ' . $username;
            // $activitytype = Config('activitytype.Admin Login');
            // GlobalHelper::logActivity($desc, $activitytype, 0, 1);
            return response()->json([
                'keyword' => 'failed',
                'message' => 'Email address or password invalid', 'data' => []
            ]);
        }
    }

    public function login(EmployeeLoginRequest $request)
    {
        try {

            Log::channel("employeelogin")->info("employeeloginController start:: Request values ::" . implode(' / ', $request->all()));

            $username = $request->input('username');
            $password = $request->input('password');

            $mobileregex = "/^[5-9][0-9]{9}$/";

            if (is_numeric($username) && !preg_match($mobileregex, $username)) {

                return response()->json([
                    'keyword' => 'failed',
                    'message' => 'Mobile number starts with 5,6,7,8,9 and must be 10 digits',
                    // 'data' => [],
                ]);
            }

            if (!is_numeric($username) && !filter_var($username, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => 'Enter valid email id',
                    // 'data' => [],
                ]);
            }

            if (is_numeric($username)) {
                $field = 'mobile_no';
            } else if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $field = 'email';
            }

            $login_det = Employee::where([
                ['password', '=', md5($password)],
                [$field, '=', $username],
                ['status', '!=', 2],
            ])->first();

            if (!empty($login_det)) {

                if ($login_det->status == 1) {

                    // log start *********
                    Log::channel("employeelogin")->info("******* employeeloginController Mobile number Exist Method Start *******");
                    Log::channel("employeelogin")->info("employeeloginController start:: Request values ::" . implode(' / ', $request->all()));
                    // log start *********

                    // $otp = '';
                    // if (isset($mobile_no)) {s
                    //     $otp = GlobalHelper::getOTP(6);
                    //     $msg = $otp . " is the OTP to login to the NRInfotech. Please enter the OTP to verify your mobile number.";
                    //     $isSmsSent = GlobalHelper::sendSms($mobile_no, $msg);
                    // }

                    // $newDateTime = Carbon::now()->addSeconds(120);
                    // $id = $login_det->customer_id;
                    // $cus = Customer::find($id);
                    // $cus->otp = $otp;
                    // $cus->otp_verify_date = $newDateTime;
                    // $cus->save();
                    // // generate JWT token
                    // // generate JWT token
                    $key = env('TOKEN_KEY');
                    $issue_time = strtotime(date('Y-m-d H:i:s'));

                    $expire_time = strtotime(date('Y-m-d H:i:s', strtotime("+12 hours", $issue_time)));

                    if ($login_det->auth_provider == "facebook" || $login_det->auth_provider == "google") {
                        $cus_image = $login_det->customer_profile_image;
                    }

                    $payload = array(
                        "portal" => "employee",
                        "employee_name" => $login_det->employee_name,
                        "employee_type" => $login_det->employee_type,
                        "employee_id" => $login_det->employee_id,
                        "mobile_no" => $login_det->mobile_no,
                        "email" => $login_det->email,
                        "profile_image" => ($login_det->employee_image != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $login_det->employee_image : env('APP_URL') . "avatar.jpg",
                        "user_type" => 'employee',
                        "iss" => env('TOKEN_ISSUER'),
                        "iat" => $issue_time,
                        "nbf" => $issue_time,
                        "exp" => $expire_time,
                    );
                    $jwt = JWT::encode($payload, $key, 'HS256');

                    // log activity
                // $desc = 'Logged In ' . $login_det->employee_name . '-' . $login_det->email;
                // $activitytype = Config('activitytype.Employee Login');
                // GlobalHelper::logActivity($desc, $activitytype, $login_det->employee_id, 2);

                    // $response = array(
                    //     'token' => $jwt,
                    //     'otp' => $otp
                    // );
                    if (isset($request->mobile_no)) {

                        // log end ***********
                        Log::channel("employeelogin")->info("employeeloginController end:: save values :: $login_det::::end");
                        Log::channel("employeelogin")->info("******* employeeloginController Method End *******");
                        Log::channel("employeelogin")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                        // log end ***********

                        return response()->json([
                            'keyword' => 'success',
                            'message' => 'OTP sent successfully',
                            'data' => [
                                'data' => $login_det,
                            ],

                        ]);
                    } else {
                        return response()->json([
                            'keyword' => 'success',
                            'message' => 'Logged in successfully',
                            'data' => [
                                'token' => $jwt,
                                'otp' => '',
                            ],

                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => 'Account suspended',
                        'data' => [],
                    ]);
                }
            } else {
                 // log activity
            // $desc = 'Login attempt failed using ' . $username;
            // $activitytype = Config('activitytype.Employee Login');
            // GlobalHelper::logActivity($desc, $activitytype, 0, 2);
                $errorMsg = (filter_var($username, FILTER_VALIDATE_EMAIL) ? "Email" : "Mobile ") . ' or password invalid';
                return response()->json([
                    'keyword' => 'failed',
                    'message' => $errorMsg,
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("employeelogin")->error("*******employeeloginController Method Error Start *******");
            Log::channel("employeelogin")->error($exception);
            Log::channel("employeelogin")->error("******* employeeloginController Method Error End *******");
            Log::channel("employeelogin")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function forget(Request $request)
    {
        $email = $request->email;
        $validity_hours = 24;
        $insert_id = 0;

        $user = Employee::where('email', '=', $email)->where('status', 1)->first();

        if (!empty($user)) {
            if (isset($user->employee_id)) {
                $result = PasswordReset::where('employee_id', '=', $user->employee_id)->first();
                if (!empty($result)) {
                    PasswordReset::where('employee_id', '=', $user->employee_id)->delete();
                }

                $insert_id = PasswordReset::insertGetId([
                    'employee_id' => $user->employee_id,
                    'token' => md5(rand() . microtime() . time() . uniqid()),
                    'created_on' => date('Y-m-d H:i:s'),
                    'expired_time' => Carbon::now()->addSeconds(1800),
                    'validity_hours' => $validity_hours,
                ]);
            }
            if ($insert_id > 0) {
                $insert_result = PasswordReset::where('id', '=', $insert_id)->first();
                $token = $insert_result->token;
                // $name =  env('EMPLOYEE_RESET_URL_WEBSITE') . '?reset=' . $token . "";
                $name =  env('EMPLOYEE_RESET_URL_WEBSITE') . $token . "";

                $mail_data = [];
                $mail_data['name'] = $user->employee_name;
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
            // $desc = 'Forget password by ' . $user->name . '-' . $user->email;
            // $activitytype = Config('activitytype.Employee Login');
            // GlobalHelper::logActivity($desc, $activitytype, $user->customer_id, 2);

            return response()->json([
                'keyword' => 'success',
                'message' => 'Reset password link has been sent to your email id!', 'data' => []
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => 'We could not find email address', 'data' => []
            ]);
        }
    }

    public function reset(ResetRequest $request)
    {
        $token = $request->token;
        $password = md5($request->password);

        $user = PasswordReset::where('token', '=', $request->token)->first();

        if (!empty($user)) {
            $created_time = $user->created_on;
            $expired_time = $user->expired_time;
            $validity_hours = $user->validity_hours;
            // $expired_time = date('Y-m-d H:i:s', strtotime("+" . $validity_hours . " hours", strtotime($created_time)));
            $current_time = date('Y-m-d H:i:s');
            if ($expired_time > $current_time) {
                if ($user->employee_id > 0) {
                    $result = Employee::where('employee_id', $user->employee_id)
                        ->update(['password' => $password]);

                    PasswordReset::where('employee_id', '=', $user->employee_id)->delete();
                }

                $user_detail = Employee::where('employee_id', $user->employee_id)->first();

                // log activity
                // $desc = 'Reset password by ' . $user_detail->employee_name . '-' . $user_detail->email;
                // $activitytype = Config('activitytype.Employee Login');
                // GlobalHelper::logActivity($desc, $activitytype, $user->employee_id, 2);

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

        $user = Employee::where([
            ['email', '=', $email]
        ])->first();

        if (!empty($user)) {
            if ($user->password == $old_pass) {
                $result = Employee::where('employee_id', $user->employee_id)->update(['password' => $new_password]);

                // log activity
                // $desc = 'Change password by ' . $user->name . '-' . $user->email;
                // $activitytype = Config('activitytype.Employee Login');
                // GlobalHelper::logActivity($desc, $activitytype, $user->employee_id, 2);

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

                $user = new Employee();

                $data = Employee::where('acl_user.acl_user_id', $id)
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

    public function employeeFcmTokenUpdate(Request $request)
    {
        $id = JwtHelper::getSesEmployeeId();
        $update = Employee::where('employee_id', $id)->update(array(
            'fcm_token' => $request->token,
            'updated_on' => Server::getDateTime()
        ));
        return response()->json([
            'keyword' => 'success',
            'message' =>  'token updated successfully',
            'data' => []
        ]);
    }
}
