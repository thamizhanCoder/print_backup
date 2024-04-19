<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\PasswordReset;
use App\Events\ForgetPassword;
use App\Events\Register;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Firebase;
use App\Models\UserModel;
use \Firebase\JWT\JWT;

class RegisterController extends Controller

{
    public function register(Request $request)
    {
        // $validator = Validator::make(
        //     $request->all(),
        //     [
        //         'customer_first_name' => 'required|string|max:255',
        //         'mobile_no' => 'required|numeric|digits:10|unique:customer|regex:/^[6-9]\d{9}$/',
        //         'email' => 'required|string | email | unique:customer|regex:/^\w+([\.-]?\w+)*@[a-z]+\.[a-z]{2,3}$/',
        //         'password' => 'required|min:8',
        //         'is_agree' => 'required'
        //     ],
        //     [
        //         'mobile_no.unique' => 'Entered mobile number already exist'
        //     ]
        // );
        // if ($validator->fails()) {
        //     return response()->json([
        //         "status"    => false,
        //         "response"  => null,
        //         "message"   => $validator->errors()->first(),
        //     ]);
        // } else {


        $email = Customer::where([
            ['email', '=', $request->email],
            ['status', '!=', 2]
        ])->first();

        if (empty($email)) {

            $mobile_no = Customer::where([
                ['mobile_no', '=', $request->mobile_no],
                ['status', '!=', 2]
            ])->first();

            if (empty($mobile_no)) {

                $customer = new Customer();
                //$customer->customer_id = $request->input('customer_id');
                $customer->customer_first_name = $request->input('customer_first_name');
                $customer->customer_last_name = $request->input('customer_last_name');
                $customer->email = $request->input('email');
                $customer->mobile_no = $request->input('mobile_no');
                $customer->state_id = $request->input('state');
                $customer->district_id = $request->input('district');
                $customer->password = md5($request->input('password'));

                $customer->is_agree = $request->input('is_agree');
                $customer->created_on = Server::getDateTime();


                if ($customer->save()) {
                    $customer_code = 'CUS_' . str_pad($customer->customer_id, 3, '0', STR_PAD_LEFT);
                    $update_customer = Customer::find($customer->customer_id);
                    $update_customer->customer_code = $customer_code;
                    if ($update_customer->customer_from == '') {
                        $update_customer->customer_from = "Web";
                    }
                    $update_customer->save();
                    $customer_data = Customer::where('customer.customer_id', $customer->customer_id)
                        ->select('customer.customer_id', 'customer.auth_provider', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.email', 'customer.mobile_no', 'customer.profile_image', 'customer_code')->first();

                    $user = Customer::where('customer_id', $customer->customer_id)->first();


                    //mail send
                    $mail_data = [];
                    $mail_data['customer_name'] = !empty($user->customer_last_name) ? $user->customer_first_name . ' ' . $user->customer_last_name : $user->customer_first_name;
                    $mail_data['email'] = $user->email;

                    if ($user->email != '') {
                        event(new Register($mail_data));
                    }
                    // $msg = "DEAR $customer_data->customer_first_name$customer_data->customer_last_name,WELCOME TO NR INFOTECH.WE HAVE ALL KINDS OF IT PRODUCTS FOR UR BUSINESS AND EDUCATION. THIS IS DIRECT SALES CENTER FOR ALL LEADING BRANDS. support@nrinfotechworld.com. FOR MORE DETAILS 04567220705,04567355015,9486360705";
                    // $isSmsSent = GlobalHelper::sendSms($customer_data->mobile_no, $msg);

                    // $emp_info = [
                    //     'first_name' => $customer_data->customer_first_name,
                    //     'last_name' => $customer_data->customer_last_name,
                    // ];

                    $title = "New user registered - $user->customer_code";
                    $body = "You have a new member registered in printapp";
                    $module = 'new_register';
                    $portal = 'admin';
                    $page = 'register';
                    $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                    $data = [
                        'customer_id' => $customer_data->customer_id,
                        'customer_code' => $customer_data->customer_code,
                        'random_id' => $random_id,
                        'page' => 'register'
                    ];

                    $token = UserModel::where('acl_user_id', 1)->where('token', '!=', NULL)->select('token')->first();

                    if (!empty($token)) {
                        $message = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data,
                            'portal' => $portal,
                            'module' => $module
                        ];
                        $admin_key = $token->token;
                        $receiver_id = $token->acl_user_id;
                        $push = Firebase::sendSingle($admin_key, $message);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 1, $customer->customer_id, 1, $module, $page, "admin", $data, $random_id);

                    $user_type = 'customer';
                    // generate JWT token        
                    $key = env('TOKEN_KEY');
                    $issue_time = strtotime(date('Y-m-d H:i:s'));
                    $expire_time = strtotime(date('Y-m-d H:i:s', strtotime("+12 hours", $issue_time)));

                    if ($customer_data->auth_provider == "facebook" || $customer_data->auth_provider == "google") {
                        $cus_image = $customer_data->profile_image;
                    } else {
                        $cus_image = ($customer_data->profile_image != '') ? env('APP_URL') . env('PROFILE_URL') . $customer_data->profile_image : env('APP_URL') . "avatar.jpg";
                    }
                    $payload = array(
                        "portal" => "userview",
                        "first_name" => $customer_data->customer_first_name,
                        "last_name" => $customer_data->customer_last_name,
                        "profile_image" => $cus_image,
                        "email" => $customer_data->email,
                        "user_type" => $user_type,
                        "customer_id" => $customer_data->customer_id,
                        "mobile_no" => $customer_data->mobile_no,
                        "iss" => env('TOKEN_ISSUER'),
                        "aud" => $customer_data->email,
                        "iat" => $issue_time,
                        "nbf" => $issue_time,
                        "exp" => $expire_time
                    );

                    $jwt = JWT::encode($payload, $key);

                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('Registered successfully'),
                        'data'        => [
                            'register_data' => $customer_data,
                            'token' => $jwt
                        ]

                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failure',
                        'message'      => __('Registration failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Mobile number already exist'),
                    'data'        => []
                ]);
            }
        } else {
            return response()->json([
                'keyword'      => 'failure',
                'message'      => __('Email already exist'),
                'data'        => []
            ]);
        }
    }
}
