<?php

namespace App\Http\Controllers\API\V1\WP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Firebase\JWT\JWT;
use App\Helpers\Server;
use App\Helpers\GlobalHelper;
use App\Events\ForgetPasswordCustomer;
use App\Helpers\Firebase;
use App\Helpers\JwtHelper;
use App\Http\Requests\MobileLoginRequest;
use App\Http\Requests\OtpVerifyWebsiteRequest;
use App\Http\Requests\ResetRequest;
use App\Http\Requests\WebsiteLoginRequest;
use App\Models\Customer;
use App\Models\PasswordReset;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function login(WebsiteLoginRequest $request)
    {
        try {
            Log::channel("websitelogin")->error('** start the login method **');
            $user_type = 'customer';

            /// LOGIN WITH USERNAME & PASSWORD
            if (isset($request->password) && isset($request->username)) {

                $password = md5($request->password);
                $username = $request->username;

                $login_det = Customer::where([
                    ['email', '=', $username],
                    ['password', '=', $password],
                    ['status', '!=', 2]
                ])->first();


                if (empty($login_det)) {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => 'Email address or password invalid', 'data' => []
                    ]);
                }
            }

            if (!empty($login_det)) {
                if ($login_det->status == 1) {
                    $customer_id = isset($login_det->customer_id) ? $login_det->customer_id : 1;
                    $customer_first_name = isset($login_det->customer_first_name) ? $login_det->customer_first_name : "";
                    $customer_last_name = isset($login_det->customer_last_name) ? $login_det->customer_last_name : "";
                    $profile_image = isset($login_det->profile_image) ? $login_det->profile_image : "";
                    $email = isset($login_det->email) ? $login_det->email : "";
                    $customer_type = isset($login_det->customer_type) ? $login_det->customer_type : "";
                    $mobile_no = isset($login_det->mobile_no) ? $login_det->mobile_no : "";

                    // generate JWT token        
                    $key = env('TOKEN_KEY');
                    $issue_time = strtotime(date('Y-m-d H:i:s'));
                    $expire_time = strtotime(date('Y-m-d H:i:s', strtotime("+8766 hours", $issue_time)));

                    if ($login_det->auth_provider == "facebook" || $login_det->auth_provider == "google") {
                        $cus_image = $login_det->profile_image;
                    }
                    if ($login_det->auth_provider == "" || $login_det->auth_provider == "apple" || $login_det->check_profile_image == 1) {
                        $cus_image = ($login_det->profile_image != '') ? env('APP_URL') . env('PROFILE_URL') . $login_det->profile_image : env('APP_URL') . "avatar.jpg";
                    }

                    $payload = array(
                        "portal" => "userview",
                        "first_name" => $customer_first_name,
                        "last_name" => $customer_last_name,
                        "profile_image" => (!empty($cus_image)) ? $cus_image : '',
                        "user_type" => $user_type,
                        "email" => $email,
                        "customer_type" => $customer_type,
                        "mobile_no" => $mobile_no,
                        "customer_id" => $customer_id,
                        "iss" => env('TOKEN_ISSUER'),
                        "aud" => $login_det->email,
                        "iat" => $issue_time,
                        "nbf" => $issue_time,
                        "exp" => $expire_time
                    );

                    $jwt = JWT::encode($payload, $key);

                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'Logged in successfully',
                        'data' => [
                            'token' => $jwt,
                            'otp' => [],
                        ]
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => 'Account suspended', 'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {
            Log::channel("websitelogin")->error($exception);
            Log::channel("websitelogin")->error('** end the login method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function otp_login(MobileLoginRequest $request)
    {
        try {

            if ($request->input('mobile_no')) {
                $mobile_no = $request->input('mobile_no');

                // LOGIN WITH MOBILE
                if (isset($mobile_no)) {

                    $login_det = Customer::where([
                        ['mobile_no', '=', $mobile_no],
                        ['status', '!=', 2],
                    ])->first();
                }

                if (!empty($login_det)) {

                    // log start *********
                    Log::channel("websitelogin")->info("******* websiteloginController Mobile number Exist Method Start *******");
                    Log::channel("websitelogin")->info("websiteloginController start:: Request values :: $mobile_no");
                    // log start *********

                    if ($login_det->status == 1) {
                        $customer_id = isset($login_det->customer_id) ? $login_det->customer_id : 1;
                        $mobile_no = isset($login_det->mobile_no) ? $login_det->mobile_no : "";


                        $otp = '';
                        if (isset($mobile_no)) {
                            $otp = GlobalHelper::getOTP(4);
                            // $msg = $otp . " is your Print App verification code. Please enter the OTP to verify your mobile number. Please DO NOT share this OTP with anyone to ensure account's security";
                            $msg = "Dear Customer, Your OTP for login to Print App is $otp Valid for 30 mins. Please do not share this OTP.";
                            $isSmsSent = GlobalHelper::sendSMS($mobile_no, $msg);
                        }

                        $newDateTime = Carbon::now()->addSeconds(180);
                        $id = $login_det->customer_id;
                        $cus = Customer::find($id);
                        $cus->otp = $otp;
                        $cus->otp_verify_date = $newDateTime;
                        $cus->save();

                        if (isset($mobile_no)) {

                            // log end ***********
                            Log::channel("websitelogin")->info("websiteloginController end:: save values :: $login_det::::end");
                            Log::channel("websitelogin")->info("******* websiteloginController Method End *******");
                            Log::channel("websitelogin")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                            // log end ***********


                            return response()->json([
                                'keyword' => 'success',
                                'message' => 'OTP sent successfully',
                                'otp' => $otp

                            ]);
                        } else {
                            return response()->json([
                                'keyword' => 'success',
                                'message' => 'Logged in successfully',
                                'otp' => [],

                            ]);
                        }
                    } else {
                        return response()->json([
                            'keyword' => 'failed',
                            'message' => 'Account suspended', 'data' => []
                        ]);
                    }
                } else {

                    $customer = new Customer();
                    $customer->mobile_no = $request->input('mobile_no');
                    $customer->created_on = Server::getDateTime();

                    // log start *********
                    Log::channel("websitelogin")->info("******* websiteloginController Register Method Start *******");
                    Log::channel("websitelogin")->info("websiteloginController start:: Request values :: $customer");
                    // log start *********

                    if ($customer->save()) {
                        $customer_code = 'CUS_' . str_pad($customer->customer_id, 3, '0', STR_PAD_LEFT);
                        $cus = Customer::find($customer->customer_id);
                        $cus->customer_code = $customer_code;
                        if ($cus->customer_from == '') {
                            $cus->customer_from = "Web";
                        }
                        $cus->save();
                        $customers = Customer::where('customer_id', $customer->customer_id)
                            ->select('customer.*')
                            ->first();

                        $otp = '';
                        if (isset($customer->mobile_no)) {
                            $otp = GlobalHelper::getOTP(4);
                            // $msg = $otp . " is the OTP to login to the NRInfotech. Please enter the OTP to verify your mobile number.";
                            $msg = "Dear Customer, Your OTP for login to Print App is $otp Valid for 30 mins. Please do not share this OTP.";
                            $isSmsSent = GlobalHelper::sendSMS($customer->mobile_no, $msg);
                        }
                        $newDateTime = Carbon::now()->addSeconds(180);
                        $id = $customers->customer_id;
                        $cus = Customer::find($id);
                        $cus->otp = $otp;
                        $cus->otp_verify_date = $newDateTime;
                        $cus->save();

                        // log end ***********
                        Log::channel("websitelogin")->info("websiteloginController end:: save values :: $customer::::end");
                        Log::channel("websitelogin")->info("******* websiteloginController Method End *******");
                        Log::channel("websitelogin")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                        // log end ***********

                        return response()->json([
                            'keyword' => 'success',
                            'message' => 'OTP sent successfully',
                            'otp' => $otp
                        ]);
                    }
                }
            } else {
                return "Key Invalid";
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("websitelogin")->error("*******websiteloginController Method Error Start *******");
            Log::channel("websitelogin")->error($exception);
            Log::channel("websitelogin")->error("******* websiteloginController Method Error End *******");
            Log::channel("websitelogin")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function otpVerify(OtpVerifyWebsiteRequest $request)
    {
        try {

            // if ($request->input('mobile_no')) {
            $mobile_no = $request->input('mobile_no');
            $otp = $request->input('otp');

            // LOGIN WITH MOBILE
            //                if (isset($mobile_no) || isset($otp)) {
            if (!empty($mobile_no) || !empty($otp)) {

                $login_det = Customer::where([
                    ['mobile_no', '=', $mobile_no],
                    ['otp', '=', $otp],
                    ['status', '=', 1],
                ])->first();
                if (empty($login_det)) {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => 'Otp invalid', 'data' => []
                    ]);
                }
            }

            if (!empty($login_det)) {

                // log start *********
                Log::channel("websitelogin")->info("******* otpVerify Mobile number Exist Method Start *******");
                Log::channel("websitelogin")->info("otpVerify start:: Request values :: $mobile_no");
                // log start *********

                if ($login_det->status == 1) {

                    // // generate JWT token        
                    $key = env('TOKEN_KEY');
                    $issue_time = strtotime(date('Y-m-d H:i:s'));

                    $expire_time = strtotime(date('Y-m-d H:i:s', strtotime("+8766 hours", $issue_time)));

                    $payload = array(
                        "portal" => "website",
                        "customer_first_name" => $login_det->customer_first_name,
                        "customer_last_name" => $login_det->customer_last_name,
                        "customer_code" => $login_det->customer_code,
                        "mobile_no" => $login_det->mobile_no,
                        "email" => $login_det->email,
                        "customer_id" => $login_det->customer_id,
                        "address" => $login_det->address,
                        "profile_image" => ($login_det->profile_image != '') ? env('APP_URL') . env('PROFILE_URL') . $login_det->profile_image : env('APP_URL') . "avatar.jpg",
                        "auth_provider" => $login_det->auth_provider,
                        "auth_provider_token" => $login_det->auth_provider_token,
                        "user_type" => 'customer',
                        "iss" => env('TOKEN_ISSUER'),
                        "iat" => $issue_time,
                        "nbf" => $issue_time,
                        "exp" => $expire_time
                    );

                    $jwt = JWT::encode($payload, $key, 'HS256');

                    $today = date('Y-m-d H:i:s');
                    if ($login_det->otp_verify_date > $today) {

                        // log end ***********
                        Log::channel("websitelogin")->info("otpVerify end:: save values :: $login_det::::end");
                        Log::channel("websitelogin")->info("******* otpVerify Method End *******");
                        Log::channel("websitelogin")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                        // log end ***********


                        return response()->json([
                            'keyword' => 'success',
                            'message' => 'Logged in successfully',
                            'data' => [
                                'token' => $jwt
                            ]

                        ]);
                    } else {
                        return response()->json([
                            'keyword' => 'failed',
                            'message' => 'Otp expired',
                            'otp' => [],

                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => 'Account suspended', 'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("websitelogin")->error("*******otpVerify Method Error Start *******");
            Log::channel("websitelogin")->error($exception);
            Log::channel("websitelogin")->error("******* otpVerify Method Error End *******");
            Log::channel("websitelogin")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            Log::channel("websitelogin")->info('** started the websitelogin update method **');

            $id = JwtHelper::getSesUserId();
            $customer = Customer::find($id);
            $customer->customer_first_name = $request->customer_first_name;
            $customer->customer_last_name = $request->customer_last_name;
            $customer->district_id = $request->city_name;
            $customer->state_id = $request->state_name;
            $customer->updated_on = Server::getDateTime();
            $customer->updated_by = JwtHelper::getSesUserId();
            Log::channel("websitelogin")->info("request value :: $customer->customer");

            if ($customer->save()) {
                $customers = Customer::where('customer_id', $customer->customer_id)->select('customer_id', 'customer_first_name', 'customer_last_name', 'updated_on', 'updated_by')->first();
                $title = "PrintApp New Customer";
                $body = "You have a new member registered in printapp";
                $module = "New_Register";
                $data = [
                    'customer_id' => $customers->customer_id,
                    'customer_name' => $customers->customer_first_name."".$customers->customer_last_name,
                    'customer_code' => $customers->customer_code,
                ];
                $page = "Mobile Register";
                $portal = "Admin";
                $message = [
                    'title' => $title,
                    'body' => $body,
                    'page' => $page,
                    'data' => $data,
                    'portal' => $portal

                ];

                //Notification for Admin
                $admin_reciever_token = UserModel::where('acl_role_id',1)->where('token','!='," ")->get();
                $key = [];
                if (!empty($admin_reciever_token)) {

                    foreach ($admin_reciever_token as $recipient) {

                        //mail
                        // if ($recipient->email != '') {
                        //     $mail_data = [];
                        //     $mail_data['email'] = $recipient->email;
                        //     $mail_data['customer_code'] = $customers->customer_code;
                        //     $mail_data['customer_name'] = $customers->customer_name;
                        //     event(new CustomerRegistration($mail_data));
                        // }
                        $key[] = $recipient['token'];
                    }
                }
                $push = Firebase::sendMultiple($key, $message);
                $getdata = GlobalHelper::notification_create($title, $body, 2, $customers->customer_id, 1, $module, $page, $portal, $data);
                Log::channel("websitelogin")->info("save value :: $customers");
                Log::channel("websitelogin")->info('** end the websitelogin update method **');

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Profile updated successfully'),
                    'data'        => [$customers]
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'data'        => [],
                    'message'      => __('Profile update failed')
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("websitelogin")->error($exception);
            Log::channel("websitelogin")->error('** end the profile update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function forget(Request $request)
    {
        $email = $request->email;
        $validity_hours = 24;
        $insert_id = 0;

        $user = Customer::where('email', '=', $email)->where('status', 1)->first();

        if (!empty($user)) {
            if (isset($user->customer_id)) {
                $result = PasswordReset::where('customer_id', '=', $user->customer_id)->first();
                if (!empty($result)) {
                    PasswordReset::where('customer_id', '=', $user->customer_id)->delete();
                }

                $insert_id = PasswordReset::insertGetId([
                    'customer_id' => $user->customer_id,
                    'token' => md5(rand() . microtime() . time() . uniqid()),
                    'created_on' => date('Y-m-d H:i:s'),
                    'expired_time' => Carbon::now()->addSeconds(180),
                    'validity_hours' => $validity_hours,
                ]);
            }
            if ($insert_id > 0) {
                $insert_result = PasswordReset::where('id', '=', $insert_id)->first();
                $token = $insert_result->token;
                $name =  env('RESET_URL_WEBSITE') . '?reset=' . $token . "";

                $mail_data = [];
                $mail_data['link'] = $name;
                $mail_data['customer_name'] = !empty($user->customer_last_name) ? $user->customer_first_name . ' ' . $user->customer_last_name : $user->customer_first_name;
                $mail_data['email'] = $user->email;

                if ($user->email != '') {
                    event(new ForgetPasswordCustomer($mail_data));
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
            // $activitytype = Config('activitytype.admin login');
            // GlobalHelper::logActivity($desc, $activitytype, $user->customer_id, 1);

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

    public function update_profile(Request $request)
    {
        try {
            Log::channel("websitelogin")->info('** started the websitelogin update method **');

            $id = JwtHelper::getSesUserId();
            $customer = Customer::find($id);
            $customer->customer_first_name = $request->customer_first_name;
            $customer->customer_last_name = $request->customer_last_name;
            $customer->updated_on = Server::getDateTime();
            $customer->updated_by = JwtHelper::getSesUserId();
            Log::channel("websitelogin")->info("request value :: $customer->customer");

            if ($customer->save()) {
                $customers = Customer::where('customer_id', $customer->customer_id)->select('customer_id', 'customer_first_name', 'customer_last_name', 'updated_on', 'updated_by')->first();

                Log::channel("websitelogin")->info("save value :: $customers");
                Log::channel("websitelogin")->info('** end the websitelogin update method **');

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Profile updated successfully'),
                    'data'        => [$customers]
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'data'        => [],
                    'message'      => __('Profile update failed')
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("websitelogin")->error($exception);
            Log::channel("websitelogin")->error('** end the profile update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
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
                if ($user->customer_id > 0) {
                    $result = Customer::where('customer_id', $user->customer_id)
                        ->update(['password' => $password]);

                    PasswordReset::where('customer_id', '=', $user->customer_id)->delete();
                }

                $user_detail = Customer::where('customer_id', $user->customer_id)->first();

                // log activity
                // $desc = 'Reset password by ' . $user_detail->name . '-' . $user_detail->email;
                // $activitytype = Config('activitytype.admin login');
                // GlobalHelper::logActivity($desc, $activitytype, $user->customer_id, 1);

                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Password reset successfully', 'data' => []
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => 'Link expired', 'data' => []
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => 'Link expired', 'data' => []
            ]);
        }
    }

    public function changepassword(Request $request)
    {
        $old_password = $request->old_password;
        $old_pass = md5($old_password);
        $new_password = md5($request->new_password);
        $email = $request->email;

        $user = Customer::where([
            ['email', '=', $email]
        ])->first();

        if (!empty($user)) {
            if ($user->password == $old_pass) {
                $result = Customer::where('customer_id', $user->customer_id)->update(['password' => $new_password]);

                // log activity
                // $desc = 'Change password by ' . $user->name . '-' . $user->email;
                // $activitytype = Config('activitytype.admin login');
                // GlobalHelper::logActivity($desc, $activitytype, $user->customer_id, 1);

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


    public function view(Request $request)
    {
        try {
            Log::channel("websitelogin")->info('** started the websitelogin view method **');
            $id = JwtHelper::getSesUserId();
            $customer = Customer::where('customer_id', $id)->first();
            // Log::channel("websitelogin")->info("request value websitelogin_id:: $id");
            if (!empty($customer)) {
                $final = [];
                $ary = [];
                $ary['customer_id'] = $customer['customer_id'];
                $ary['customer_code'] = $customer['customer_code'];
                $ary['customer_first_name'] = $customer['customer_first_name'];
                $ary['customer_last_name'] = $customer['customer_last_name'];
                $ary['mobile_no'] = $customer['mobile_no'];
                $ary['email'] = $customer['email'];
                $ary['image'] = $customer['profile_image'];
                if ($customer->auth_provider == "facebook" || $customer->auth_provider == "google") {
                    $cus_image = $customer['profile_image'];
                }
                if ($customer->auth_provider == "" || $customer->auth_provider == "apple" || $customer->check_profile_image == 1) {
                    $cus_image = ($customer['profile_image'] != '') ? env('APP_URL') . env('PROFILE_URL') . $customer['profile_image'] : env('APP_URL') . "avatar.jpg";
                }
                $ary['profile_image'] = $cus_image;
                $ary['auth_provider'] = $customer['auth_provider'];
                $ary['created_on'] = $customer['created_on'];
                $ary['created_by'] = $customer['created_by'];
                $ary['updated_on'] = $customer['updated_on'];
                $ary['updated_by'] = $customer['updated_by'];
                $ary['status'] = $customer['status'];
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("websitelogin")->info("view value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Profile viewed successfully'),
                    'data' => $final,
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("websitelogin")->error($exception);
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function updateTokenForWebsite(Request $request)
    {
        try {
            Log::channel("websitelogin")->info('** started the mobile update token method **');
            $id = JwtHelper::getSesUserId();
            Log::channel("websitelogin")->info("request value :: $id");
            $update = Customer::where('customer_id', $id)->update(array(
                'token' => $request->token,
                'updated_on' => Server::getDateTime()
            ));
            Log::channel("websitelogin")->info("save value :: customer_id  => $id :: token => $request->token :: update token saved");
            Log::channel("websitelogin")->info('** end the mobile update token method **');
            return response()->json([
                'keyword' => 'success',
                'message' =>  'Token updated successfully',
                'data' => []
            ]);
        } catch (\Exception $exception) {
            Log::channel("websitelogin")->error($exception);
            Log::channel("websitelogin")->error('** end the mobile update token method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
