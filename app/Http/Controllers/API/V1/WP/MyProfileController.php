<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Server;
use App\Models\Customer;
use Firebase\JWT\JWT;
use App\Helpers\JwtHelper;
use App\Http\Requests\CustomerInfoRequest;
use App\Http\Requests\DeliveryAddressInfoRequest;
use App\Http\Requests\MyProfileRequest;
use App\Models\CouponCode;
use App\Models\Orders;
use App\Models\OtherDistrict;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MyProfileController extends Controller
{
    public function myprofile_update(MyProfileRequest $request)
    {
        try {
            Log::channel("websitemyprofile")->info('** started the websitemyprofile update method **');

            $id = JwtHelper::getSesUserId();
            $customer = Customer::find($id);
            $check = Customer::where('customer_id', $id)->first();
            $email = Customer::where([
                ['email', '=', $request->email],
                ['status', '!=', 2],
                ['customer_id', '!=', $id]
            ])->first();
            if (empty($email)) {

                $mobile = Customer::where([
                    ['mobile_no', '=', $request->mobile_no],
                    ['status', '!=', 2],
                    ['customer_id', '!=', $id]
                ])->first();

                if (empty($mobile)) {

                    // if ($request->profile_image != '') {
                    //     $Extension =  pathinfo($request->input('profile_image'), PATHINFO_EXTENSION);
                    //     $extension_ary = ['jpeg', 'png', 'jpg'];
                    //     if (in_array($Extension, $extension_ary)) {
                    //         $request->profile_image;
                    //     } else {
                    //         return response()->json([
                    //             'keyword'      => 'failed',
                    //             'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                    //             'data'        => []
                    //         ]);
                    //     }
                    // }
                    $customer->customer_first_name = $request->customer_first_name;
                    $customer->customer_last_name = $request->customer_last_name;
                    $customer->mobile_no = $request->mobile_no;
                    $customer->email = $request->email;
                    $customer->profile_image = $request->profile_image;
                    if ($check->profile_image != $request->profile_image) {
                        $customer->check_profile_image = 1;
                    }
                    $customer->updated_on = Server::getDateTime();
                    $customer->updated_by = JwtHelper::getSesUserId();
                    Log::channel("websitemyprofile")->info("request value :: $customer->customer");

                    if ($customer->save()) {
                        $customers = Customer::where('customer_id', $customer->customer_id)->select('customer_id', 'customer_first_name', 'customer_last_name', 'mobile_no', 'email', 'profile_image', 'check_profile_image', 'updated_on', 'updated_by')->first();

                        Log::channel("websitemyprofile")->info("save value :: $customers");
                        Log::channel("websitemyprofile")->info('** end the websitemyprofile update method **');

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
        } catch (\Exception $exception) {
            Log::channel("websitemyprofile")->error($exception);
            Log::channel("websitemyprofile")->error('** end the profile update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //customer info
    public function customerInfoUpdate(CustomerInfoRequest $request)
    {
        try {
            Log::channel("websitemyprofile")->info('** started the customerInfoUpdate update method **');

            $id = JwtHelper::getSesUserId();
            $customer = Customer::find($id);
            $email = Customer::where([
                ['email', '=', $request->email],
                ['status', '!=', 2],
                ['customer_id', '!=', $id]
            ])->first();
            if (empty($email)) {

                $mobile = Customer::where([
                    ['mobile_no', '=', $request->mobile_no],
                    ['status', '!=', 2],
                    ['customer_id', '!=', $id]
                ])->first();

                if (empty($mobile)) {

                    $customer->customer_first_name = $request->customer_first_name;
                    $customer->customer_last_name = $request->customer_last_name;
                    $customer->mobile_no = $request->mobile_no;
                    $customer->email = $request->email;
                    $customer->updated_on = Server::getDateTime();
                    $customer->updated_by = JwtHelper::getSesUserId();
                    Log::channel("websitemyprofile")->info("request value :: $customer->customer");

                    if ($customer->save()) {
                        $customers = Customer::where('customer_id', $customer->customer_id)->select('customer_id', 'customer_first_name', 'customer_last_name', 'mobile_no', 'email', 'updated_on', 'updated_by')->first();

                        Log::channel("websitemyprofile")->info("save value :: $customers");
                        Log::channel("websitemyprofile")->info('** end the customerInfoUpdate update method **');

                        return response()->json([
                            'keyword'      => 'success',
                            'message'      => __('Customer info updated successfully'),
                            'data'        => [$customers]
                        ]);
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'data'        => [],
                            'message'      => __('Customer info update failed')
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
        } catch (\Exception $exception) {
            Log::channel("websitemyprofile")->error($exception);
            Log::channel("websitemyprofile")->error('** end the customerInfoUpdate update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function deliveryAddressInfoUpdate(DeliveryAddressInfoRequest $request)
    {
        try {
            Log::channel("websitemyprofile")->info('** started the deliveryAddressInfoUpdate update method **');

            $mobilenoCheck = $request->billing_mobile_number == $request->billing_alt_mobile_number;
            if(empty($mobilenoCheck)){
            $id = JwtHelper::getSesUserId();
            $customer = Customer::find($id);

            $customer->billing_customer_first_name = $request->billing_customer_first_name;
            $customer->billing_customer_last_name = $request->billing_customer_last_name;
            $customer->billing_email = strtolower($request->billing_email);
            $customer->billing_mobile_number = $request->billing_mobile_number;
            $customer->billing_alt_mobile_number = $request->billing_alt_mobile_number;
            $customer->billing_gst_no = $request->billing_gst_no;
            $customer->billing_address_1 = $request->billing_address_1;
            $customer->billing_landmark = $request->billing_landmark;
            $customer->billing_state_id = $request->billing_state_id;
            $customer->billing_city_id = $request->billing_city_id;
            $customer->other_district = $request->other_district;
            $customer->billing_pincode = $request->billing_pincode;
            $customer->updated_on = Server::getDateTime();
            $customer->updated_by = JwtHelper::getSesUserId();
            Log::channel("websitemyprofile")->info("request value :: $customer->customer");

            if(!empty($request->other_district)){
                $otherDistrict = new OtherDistrict();
                $otherDistrict->district = $request->other_district;
                $otherDistrict->state_id = $request->billing_state_id;
                $otherDistrict->created_on = Server::getDateTime();
                $otherDistrict->created_by = JwtHelper::getSesUserId();
                $otherDistrict->save();
            }

            if ($customer->save()) {
                $customers = Customer::where('customer_id', $customer->customer_id)->select('customer_id', 'billing_customer_first_name', 'billing_customer_last_name', 'billing_email', 'billing_mobile_number', 'billing_alt_mobile_number', 'billing_gst_no', 'billing_address_1', 'billing_landmark', 'billing_state_id', 'billing_city_id', 'billing_pincode')->first();

                Log::channel("websitemyprofile")->info("save value :: $customers");
                Log::channel("websitemyprofile")->info('** end the deliveryAddressInfoUpdate update method **');

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Delivery Address Updated Sucessfully'),
                    'data'        => [$customers]
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'data'        => [],
                    'message'      => __('Delivery address update failed')
                ]);
            }
        }
        else{
            return response()->json([
                'keyword'      => 'failed',
                'data'        => [],
                'message'      => __('Alternative mobile number should not be same as mobile number')
            ]);
        }
        } catch (\Exception $exception) {
            Log::channel("websitemyprofile")->error($exception);
            Log::channel("websitemyprofile")->error('** end the deliveryAddressInfoUpdate update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function view(Request $request)
    {
        try {
            Log::channel("websitemyprofile")->info('** started the websitemyprofile view method **');
            $id = JwtHelper::getSesUserId();
            $customer = Customer::where('customer_id', $id)->first();
            // Log::channel("websitemyprofile")->info("request value websitemyprofile_id:: $id");
            if (!empty($customer)) {
                $final = [];
                $ary = [];
                $ary['customer_id'] = $customer['customer_id'];
                $ary['customer_code'] = $customer['customer_code'];
                $ary['customer_first_name'] = $customer['customer_first_name'];
                $ary['customer_last_name'] = $customer['customer_last_name'];
                $ary['mobile_no'] = $customer['mobile_no'];
                $ary['email'] = $customer['email'];
                $ary['profile_image'] = ($customer['profile_image'] != '') ? env('APP_URL') . env('PROFILE_URL') . $customer['profile_image'] : env('APP_URL') . "avatar.jpg";
                $ary['created_on'] = $customer['created_on'];
                $ary['created_by'] = $customer['created_by'];
                $ary['updated_on'] = $customer['updated_on'];
                $ary['updated_by'] = $customer['updated_by'];
                $ary['status'] = $customer['status'];
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("websitemyprofile")->info("view value :: $log");
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
            Log::channel("websitemyprofile")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    //addressview
    public function address_view(Request $request)
    {
        $id = JwtHelper::getSesUserId();
        $get_customer = Customer::where('customer.customer_id', $id)
            ->select(
                'customer.customer_id',
                'customer.customer_code',
                'customer.customer_first_name',
                'customer.customer_last_name',
                'customer.email',
                'customer.mobile_no',
                'customer.billing_customer_first_name',
                'customer.billing_customer_last_name',
                'customer.billing_email',
                'customer.billing_mobile_number',
                'customer.billing_alt_mobile_number',
                'customer.billing_gst_no',
                'customer.billing_address_1',
                'customer.billing_landmark',
                'customer.billing_state_id',
                'customer.billing_city_id',
                'customer.other_district',
                'customer.billing_pincode',
                'state.state_name as billing_state_name',
                'district.district_name as billing_city_name',
            )
            ->leftjoin('state', 'state.state_id', '=', 'customer.billing_state_id')
            ->leftjoin('district', 'district.district_id', '=', 'customer.billing_city_id')->first();
        if (!empty($get_customer)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Customer viewed successfully'),
                'data' => $get_customer
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => []
            ]);
        }
    }

    //coupon code list
    public function couponCodeList(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'coupon_code' => 'coupon_code.coupon_code',
            'description' => 'coupon_code.description',
            'percentage' => 'coupon_code.percentage',
            'set_min_amount' => 'coupon_code.set_min_amount'
        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "coupon_code_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('coupon_code.coupon_code', 'coupon_code.description', 'coupon_code.percentage', 'coupon_code.set_min_amount');

        // DB::enableQueryLog();
        // $view = Orders::where('orders.coupon_code', '!=', NULL)->leftjoin('coupon_code', 'coupon_code.coupon_code', '=', 'orders.coupon_code')->select('coupon_code.coupon_code_id', 'orders.coupon_code', DB::raw('COUNT(orders.coupon_code) as count'))->groupby('orders.coupon_code')->get();
        // $Queries = DB::getQueryLog();
        // $last_query = end($Queries);
        // print_r($last_query);
        // die;

        $todayDate = date('Y-m-d H:i:s');

        $newCustomer = Orders::where('customer_id', JwtHelper::getSesUserId())->count();

        if ($newCustomer == 0) {
            $couponcodes = CouponCode::select('coupon_code.*', 'couponcodecount_history.count')->where('coupon_code.status', 1)->where('coupon_code.start_date', '<=', $todayDate)->where('coupon_code.set_end_date', '>=', $todayDate)->leftjoin('couponcodecount_history', 'couponcodecount_history.coupon_code_id', '=', 'coupon_code.coupon_code_id');
        } else {
            $couponcodes = CouponCode::select('coupon_code.*', 'couponcodecount_history.count')->where('coupon_code.status', 1)->where('customer_eligibility', 2)->where('coupon_code.start_date', '<=', $todayDate)->where('coupon_code.set_end_date', '>=', $todayDate)->leftjoin('couponcodecount_history', 'couponcodecount_history.coupon_code_id', '=', 'coupon_code.coupon_code_id');
        }

        $couponcodes->where(function ($query) use ($searchval, $column_search, $couponcodes) {
            $i = 0;
            if ($searchval) {
                foreach ($column_search as $item) {
                    if ($i === 0) {
                        $query->where(($item), 'LIKE', "%{$searchval}%");
                    } else {
                        $query->orWhere(($item), 'LIKE', "%{$searchval}%");
                    }
                    $i++;
                }
            }
        });
        if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
            $couponcodes->orderBy($order_by_key[$sortByKey], $sortType);
        }
        $count = $couponcodes->count();

        if ($offset) {
            $offset = $offset * $limit;
            $couponcodes->offset($offset);
        }
        if ($limit) {
            $couponcodes->limit($limit);
        }
        $couponcodes->orderBy('coupon_code_id', 'desc');
        $couponcodes = $couponcodes->get();

        $couponcodes = $couponcodes->filter(function ($item) {
            // echo($item->count);exit;
            if ($item->total_usage_limit_no_of_discount > $item->count || $item->total_usage_limit_no_of_discount == null) {
                return $item;
            }
        })->values();

        if ($count > 0) {
            $final = [];
            foreach ($couponcodes as $value) {
                $ary = [];
                $ary['coupon_code_id'] = $value['coupon_code_id'];
                $ary['coupon_code'] = $value['coupon_code'];
                $ary['description'] = $value['description'];
                $ary['percentage'] = $value['percentage'];
                $ary['set_min_amount'] = $value['set_min_amount'];
                $ary['is_limit_for_discount'] = $value['is_limit_for_discount'];
                $ary['total_usage_limit_no_of_discount'] = $value['total_usage_limit_no_of_discount'];
                $ary['limit_to_use_per_customer'] = $value['limit_to_use_per_customer'];
                $ary['customer_eligibility'] = $value['customer_eligibility'];
                $ary['start_date'] = $value['start_date'];
                $ary['start_time'] = $value['start_time'];
                $ary['set_end_date'] = $value['set_end_date'];
                $ary['set_end_time'] = $value['set_end_time'];
                $ary['created_on'] = $value['created_on'];
                $ary['created_by'] = $value['created_by'];
                $ary['updated_on'] = $value['updated_on'];
                $ary['updated_by'] = $value['updated_by'];
                $ary['status'] = $value['status'];
                $final[] = $ary;
            }
        }
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Coupon code listed successfully'),
                'data' => $final,
                'count' => intval(json_encode(count($final)))
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => [],
                'count' => 0
            ]);
        }
    }

    //coupon code verify
    public function couponCodeApply(Request $request)
    {
        $todayDate = date('Y-m-d H:i:s');
        $checkcode = CouponCode::where([
            ['coupon_code', '=', $request->coupon_code],
            ['start_date', '<=', $todayDate],
            ['set_end_date', '>=', $todayDate],
            ['status', '=', 1]
        ])->first();
        if (!empty($checkcode)) {
            $newCustomer = Orders::where('customer_id', JwtHelper::getSesUserId())->count();

            //for new customer
            if ($checkcode->customer_eligibility == 1) {
                if ($newCustomer == 0 || $newCustomer > 0) {

                    //is_limit_for_discount = 1
                    if ($checkcode->is_limit_for_discount == 1) {
                        if ($checkcode->limit_to_use_per_customer == 2) {
                            $alreadyUseCode = Orders::where('customer_id', JwtHelper::getSesUserId())->where('coupon_code', $request->coupon_code)->first();
                            if (empty($alreadyUseCode)) {
                                return response()->json([
                                    'keyword'      => 'success',
                                    'message'      => __('Coupon code verified successfully'),
                                    'data'        => [$checkcode]
                                ]);
                            } else {
                                return response()->json([
                                    'keyword'      => 'failed',
                                    'message'      => __('You have already used this coupon code'),
                                    'data'        => []
                                ]);
                            }
                        }
                    }
                    
                    //is_limit_for_discount = 2
                    if ($checkcode->is_limit_for_discount == 2) {
                        if ($checkcode->limit_to_use_per_customer == 2) {
                            $alreadyUseCode = Orders::where('customer_id', JwtHelper::getSesUserId())->where('coupon_code', $request->coupon_code)->first();
                            if (empty($alreadyUseCode)) {
                                return response()->json([
                                    'keyword'      => 'success',
                                    'message'      => __('Coupon code verified successfully'),
                                    'data'        => [$checkcode]
                                ]);
                            } else {
                                return response()->json([
                                    'keyword'      => 'failed',
                                    'message'      => __('You have already used this Coupon code'),
                                    'data'        => []
                                ]);
                            }
                        }
                    $orderCouponCount = Orders::where('coupon_code', $request->coupon_code)->count();
                    if ($checkcode->total_usage_limit_no_of_discount > $orderCouponCount) {
                        // echo("hi");exit;
                        return response()->json([
                            'keyword'      => 'success',
                            'message'      => __('Coupon code verified successfully'),
                            'data'        => [$checkcode]
                        ]);
                    }
                }

                //Not Check
                if ($checkcode->is_limit_for_discount == 1 && $checkcode->limit_to_use_per_customer == 1) {
                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('Coupon code verified successfully'),
                        'data'        => [$checkcode]
                    ]);
                }
            }
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('This coupon reached the given no.of limits count'),
                    'data'        => []
                ]);
            }

            //for everyone
            if ($checkcode->customer_eligibility == 2) {
                if ($newCustomer == 0 || $newCustomer > 0) {

                    //is_limit_for_discount = 1
                    if ($checkcode->is_limit_for_discount == 1) {
                        if ($checkcode->limit_to_use_per_customer == 2) {
                            $alreadyUseCode = Orders::where('customer_id', JwtHelper::getSesUserId())->where('coupon_code', $request->coupon_code)->first();
                            if (empty($alreadyUseCode)) {
                                return response()->json([
                                    'keyword'      => 'success',
                                    'message'      => __('Coupon code verified successfully'),
                                    'data'        => [$checkcode]
                                ]);
                            } else {
                                return response()->json([
                                    'keyword'      => 'failed',
                                    'message'      => __('You have already used this coupon code'),
                                    'data'        => []
                                ]);
                            }
                        }
                    }
                    //is_limit_for_discount = 2
                    if ($checkcode->is_limit_for_discount == 2) {
                        if ($checkcode->limit_to_use_per_customer == 2) {
                            $alreadyUseCode = Orders::where('customer_id', JwtHelper::getSesUserId())->where('coupon_code', $request->coupon_code)->first();
                            if (empty($alreadyUseCode)) {
                                return response()->json([
                                    'keyword'      => 'success',
                                    'message'      => __('Coupon code verified successfully'),
                                    'data'        => [$checkcode]
                                ]);
                            } else {
                                return response()->json([
                                    'keyword'      => 'failed',
                                    'message'      => __('You have already used this coupon code'),
                                    'data'        => []
                                ]);
                            }
                        }
                        $orderCouponCount = Orders::where('coupon_code', $request->coupon_code)->count();
                        if ($checkcode->total_usage_limit_no_of_discount > $orderCouponCount) {
                            return response()->json([
                                'keyword'      => 'success',
                                'message'      => __('Coupon code verified successfully'),
                                'data'        => [$checkcode]
                            ]);
                        }
                    }

                    //Not Check
                    if ($checkcode->is_limit_for_discount == 1 && $checkcode->limit_to_use_per_customer == 1) {
                        return response()->json([
                            'keyword'      => 'success',
                            'message'      => __('Coupon code verified successfully'),
                            'data'        => [$checkcode]
                        ]);
                    }
                }
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('This coupon reached the given no.of limits count'),
                    'data'        => []
                ]);
            }
        } else {
            return response()->json([
                'keyword'      => 'failure',
                'message'      => __('Coupon code invalid'),
                'data'        => []
            ]);
        }
    }
}
