<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Events\Register;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use \Firebase\JWT\JWT;
use App\Helpers\JwtHelper;
use App\Http\Requests\SocialLoginRequest;
use App\Models\Customer;

class SocialLoginController extends Controller
{
    public function social(SocialLoginRequest $request)
    {
        if($request->input('auth_provider') == 'apple')
        {
            $details  = Customer::where('email', $request->input('email'))->where('apple_email', $request->input('apple_email_id'))->where('apple_auth_provider_id', $request->input('auth_provider_id'))->where('status', '!=', 2)->first();
        }
        else{
            $details  = Customer::where('email', $request->input('email'))->where('status',1)->first();
        }
        if ($details == '') {
            $customer_first_name = $request->input('customer_first_name');
            $customer_last_name = $request->input('customer_last_name');
            $email = $request->input('email');
            $password = md5($request->input('password'));
            $mobile_no = $request->input('mobile_no');
            $gender = $request->input('gender');
            $platform = $request->input('platform');
            $profile_image = $request->input('profile_image');
            $token = $request->input('auth_provider_token');
            $auth_provider = $request->input('auth_provider'); // fb, google
            $apple_email = $request->input('apple_email_id');
            $apple_auth_provider_id = $request->input('auth_provider_id'); 

            $data = new Customer();
            $data->customer_first_name = $customer_first_name;
            $data->customer_last_name = $customer_last_name;
            $data->email = $email;
            $data->gender = $gender;
            $data->profile_image = $profile_image;
            $data->password = $password;
            $data->mobile_no = $mobile_no;
            $data->platform = $platform;
            $data->created_on = Server::getDateTime();
            $data->status = 1;

            $data->auth_provider = $auth_provider;
            $data->auth_provider_token = $token;
            $data->apple_email = $apple_email;
            $data->apple_auth_provider_id = $apple_auth_provider_id;
            if ($data->save()) {
                $customer_code = 'CUS_' . str_pad($data->customer_id, 3, '0', STR_PAD_LEFT);
                $update_customerdetails = Customer::find($data->customer_id);
                $update_customerdetails->customer_code = $customer_code;
                if($update_customerdetails->customer_from == ''){
                    $update_customerdetails->customer_from = "Mobile";
                }
                $update_customerdetails->save();
                $details = Customer::where('customer_id', $data->customer_id)->where('status', 1)->where('email', $email)->first();
                $gtdata = $this->loginData($details);

                $response = array(
                    'token' => $gtdata['token'],
                );
                //mail send
                $mail_data = [];
                $mail_data['customer_name'] = !empty($customer_first_name) ? $customer_last_name . ' ' . $customer_first_name : $customer_last_name;
                $mail_data['email'] = $email;

                if ($email != '') {
                    event(new Register($mail_data));
                }
                return response()->json([
                    'keyword' => 'success',
                    'data' => $response,
                    'message' => __('User registered and logged in successfully')
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => 'No data Found',
                    'data' => []
                ]);
            }
        } else {
            // $email = $request->input('email');
            // $apple_auth_provider_id = $request->input('auth_provider_id');
            // $details = Customer::where('email', $email)->where('apple_auth_provider_id', $apple_auth_provider_id)->first();
            $email = $request->input('email');
            $apple_email_id = $request->input('apple_email_id');
            $apple_auth_provider_id = $request->input('auth_provider_id');

            if(!empty($apple_auth_provider_id)){
                $details = Customer::where('email', $email)->where('apple_email', $apple_email_id)->where('apple_auth_provider_id', $apple_auth_provider_id)->first();
            }
            else{
                $details = Customer::where('email', $email)->first();
            }

            if ($details->status == 1) {
                $gtdata = $this->loginData($details);


                $response = array(
                    'token' => $gtdata['token'],
                );
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Logged In successfully'),
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => 'Account suspended',
                    'data' => []
                ]);
            }
        }
    }

    public function loginData($details)
    {
        $user_type = 'customer';
        // generate JWT token        
        $key = env('TOKEN_KEY');
        $issue_time = strtotime(date('Y-m-d H:i:s'));
        $expire_time = strtotime(date('Y-m-d H:i:s', strtotime("+8766 hours", $issue_time)));
        if ($details->auth_provider == "facebook" || $details->auth_provider == "google") {
            $cus_image = $details->profile_image;
        }
        if ($details->auth_provider == "" || $details->auth_provider == "apple" || $details->check_profile_image == 1) {
            $cus_image = ($details->profile_image != '') ? env('APP_URL') . env('PROFILE_URL') . $details->profile_image : env('APP_URL') . "avatar.jpg";
        }

        $payload = array(
            "portal" => "customer",
            "customer_first_name" => $details->customer_first_name,
            "customer_last_name" => $details->customer_last_name,
            "profile_image" => (!empty($cus_image)) ? $cus_image : '',
            "customer_id" => $details->customer_id,
            "user_type" => $user_type,
            "email" => $details->email,
            "playstore_url" => env('PLAYSTORE_URL'),
            "appstore_url" => env('APPSTORE_URL'),
            "iss" => env('TOKEN_ISSUER'),
            "aud" => $details->email,
            "iat" => $issue_time,
            "nbf" => $issue_time,
            "exp" => $expire_time
        );
        $jwt = JWT::encode($payload, $key);

        //response for mobile
        $c_img = ($details->profile_image != '') ? env('APP_URL') . env('PRODUCT_URL') . $details->profile_image : '';
        $respay = array(
            'token' => $jwt,
            "first_name" =>  $details->customer_first_name,
            "last_name" => $details->customer_last_name,
            "profile_image" => (!empty($cus_image)) ? $cus_image : '',
            "user_type" => $user_type,
            "email" => $details->email,
            "customer_type" => $details->customer_type,
            "mobile_no" => $details->mobile_no,
            "customer_id" => $details->customer_id
        );
        return $respay;
    }


    public function CheckAppleEmailExists(Request $request)
    {
        $apple_email = $request->apple_email;
        $apple_auth_provider_id = $request->apple_auth_provider_id;
        $get_apple_email = Customer::where('apple_email',$apple_email)->where('apple_auth_provider_id',$apple_auth_provider_id)->select('email')->first();
        if(!empty($get_apple_email))
        {
            return response()->json([
                'keyword' => 'success',
                'data' => $get_apple_email
            ]);
        }
        
        else{
            return response()->json([
                'keyword' => 'failure',
                'message' => __('No data found'),
            ]);
        }
    }
}
