<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Events\CouponCodeMail;
use App\Helpers\Firebase;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\CouponCode;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Helpers\GlobalHelper;
use App\Http\Requests\CouponCodeRequest;
use App\Imports\SendCouponCodeImport;
use App\Jobs\SendCouponCodeEmail;
use App\Models\Customer;
use App\Models\JobSyncModel;
use App\Models\Orders;
use Carbon\Carbon;

class CouponCodeController extends Controller
{
    public function coupon_code_create(CouponCodeRequest $request)
    {
        try {
            Log::channel("couponcode")->info('** started the coupon code create method **');

            $couponcode = new CouponCode();
            $exist = CouponCode::where([['coupon_code', $request->input('coupon_code')], ['status', '!=', 2]])->first();

            if (empty($exist)) {
                $couponcode = new CouponCode();
                $couponcode->coupon_code = $request->input('coupon_code');
                $couponcode->description = $request->input('description');
                $couponcode->percentage = $request->input('percentage');
                $couponcode->set_min_amount = $request->input('set_min_amount');
                $couponcode->is_limit_for_discount = $request->input('is_limit_for_discount');
                $couponcode->total_usage_limit_no_of_discount = $request->input('total_usage_limit_no_of_discount');
                $couponcode->limit_to_use_per_customer = $request->input('limit_to_use_per_customer');
                $couponcode->customer_eligibility = $request->input('customer_eligibility');
                $couponcode->start_date = $request->input('start_date') . ' ' . $request->input('start_time');
                $couponcode->start_time = $request->input('start_time');
                $couponcode->set_end_date = $request->input('set_end_date') . ' ' . $request->input('set_end_time');
                $couponcode->set_end_time = $request->input('set_end_time');
                $couponcode->created_on = Server::getDateTime();
                $couponcode->created_by = JwtHelper::getSesUserId();
                Log::channel("couponcode")->info("request value :: $couponcode->coupon_code");

                if ($couponcode->save()) {
                    $couponcodes = CouponCode::where('coupon_code_id', $couponcode->coupon_code_id)->first();

                    //log activity
                    $desc =  'Coupon Code' . $couponcode->coupon_code . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Coupon Code');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                    Log::channel("couponcode")->info("save value :: $couponcodes->coupon_code");
                    Log::channel("couponcode")->info('** end the coupon code create method **');

                    //Send Push Notification For Customer
                    $pushNotification  = $this->sendNotificationForCustomerInCoupon($couponcodes->coupon_code_id);



                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('Coupon code created successfully'),
                        'data'        => [$couponcodes]
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Coupon code creation failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Coupon code already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("couponcode")->error($exception);
            Log::channel("couponcode")->error('** error occured in coupon code create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function getEmail($get_id)
    {
        $email = Customer::whereNotIn('customer_id', $get_id)->where('email', '!=', '')->select('email')->get();
        return $email;
    }

    public function sendNotificationForCustomerInCoupon($couponId)
    {


        $couponcodes = CouponCode::where('coupon_code_id', $couponId)->first();


        $title = "New Discount Added";
        $body = "New discount! " . $couponcodes->coupon_code . " is added by admin. Time to Buy!";

        $module = 'coupon';

        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);

        $page = 'new_coupon';

        $data = [
            'coupon_code_id' => $couponcodes->coupon_code_id,
            'coupon_code' => $couponcodes->coupon_code,
            'random_id' => $random_id,
            'page' => $page
        ];

        $portal1 = 'mobile';
        $portal2 = 'website';

       

        $token = Customer::where('token', '!=', NULL)->orwhere('mbl_token', '!=', NULL)->select('token', 'mbl_token', 'customer_id')->get();

        if (!empty($token)) {
            $tokens = [];
            foreach ($token as $tk) {
                $tokens[] = $tk['token'];
            }

            $mbl_tokens = [];
            foreach ($token as $tks) {
                $mbl_tokens[] = $tks['mbl_token'];
            }

            $customerId = [];
            foreach ($token as $tk) {
                $customerId[] = $tk['customer_id'];
            }
        }

        if (!empty($tokens)) {
            foreach (array_chunk($tokens, 500) as $tok) {
                $key = $tok;
                if (!empty($key)) {

                    $message = [
                        'title' => $title,
                        'body' => $body,
                        'page' => $page,
                        'data' => $data,
                        'portal' => $portal2
                    ];


                    if ($couponcodes->status == 1) {
                        $push = Firebase::sendMultiple($key, $message);
                    }
                }
            }

            if ($couponcodes->status == 1) {
                if (!empty($customerId)) {
                    $prod = array_chunk($customerId, 500);
                    // print_r($prod);exit;
                    if (!empty($prod)) {
                        for ($i = 0; $i < count($prod); $i++) {
                            $sizeOfArrayChunk = sizeof($prod[$i]);
                            for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                                $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $prod[$i][$j], $module, $page, "website", $data, $random_id);
                            }
                        }
                    }
                }
            }
        }

        //mobile app push
        if (!empty($mbl_tokens)) {
            foreach (array_chunk($mbl_tokens, 500) as $mbl_tok) {
                $key_mbl = $mbl_tok;
                if (!empty($key_mbl)) {
                    $message = [
                        'title' => $title,
                        'body' => $body,
                        'page' => $page,
                        'data' => $data,
                        'portal' => $portal1,
                        'module' => $module
                    ];


                    if ($couponcodes->status == 1) {
                        $push2 = Firebase::sendMultipleMbl($key_mbl, $message);
                    }
                }
            }

            if ($couponcodes->status == 1) {
                if (!empty($customerId)) {
                    $prod = array_chunk($customerId, 500);
                    // print_r($prod);exit;
                    if (!empty($prod)) {
                        for ($i = 0; $i < count($prod); $i++) {
                            $sizeOfArrayChunk = sizeof($prod[$i]);
                            for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                                $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $prod[$i][$j], $module, $page, "mobile", $data, $random_id);
                            }
                        }
                    }
                }
            }
        }


        
        if ($couponcodes->customer_eligibility == 1) {

            $cusdetails = Orders::where('created_by', '!=', '')->select('created_by')->get();


            foreach ($cusdetails as $cusdetail) {
                if (!empty($cusdetail)) {
                    $mailsDetails[] = $cusdetail['created_by'];
                }
            }

            if(!empty($mailsDetails))
            {
           
                  $mailsDetails = $this->getEmail($mailsDetails);
            }


            $mails = [];
            foreach ($mailsDetails as $maildetail) {
                if (!empty($maildetail)) {
                    $mails[] = $maildetail['email'];
                   
                }
            }

            // $prod = [];
            // if (!empty($maildetail)) {
            //     $ary = [];
            // foreach ($mailsDetails as $maildetail) {
            //         $ary['email'] = $maildetail['email'];
            //         $prod[] = $ary;
            //     }
            //     // return $prod;
            // }
            

            $prod = array_chunk($mails, 500);


            if (!empty($prod)) {
                for ($i = 0; $i < count($prod); $i++) {
                    $sizeOfArrayChunk = sizeof($prod[$i]);
                    for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                        $mail_data = [];
                        $email = $prod[$i][$j];
                        $mail_data['coupon_code'] = $couponcodes->coupon_code;
                        $mail_data['percentage'] = $couponcodes->percentage;
                        $date = date('d-m-Y', strtotime($couponcodes->set_end_date));
                        $mail_data['set_end_date'] = $date;
                        // if ($prod[$i] != '') {
                        //     event(new CouponCodeMail($mail_data));
                        // }
                        $import = (new SendCouponCodeEmail($email, $couponcodes));
                        dispatch($import)->delay(0);
                    }
                }
            }
        }

        if ($couponcodes->customer_eligibility == 2) {

            $cusdetails = Customer::where('email', '!=', '')->select('email')->get();


            $mails = [];
            foreach ($cusdetails as $cusdetail) {
                if (!empty($cusdetail)) {
                    $mails[] = $cusdetail['email'];
                    
                }
            }

            $prod = array_chunk($mails, 500);

            if (!empty($prod)) {
                for ($i = 0; $i < count($prod); $i++) {
                    $sizeOfArrayChunk = sizeof($prod[$i]);
                    for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                        $mail_data = [];
                        $email = $prod[$i][$j];
                        $mail_data['email'] = $prod[$i][$j];
                        $mail_data['coupon_code'] = $couponcodes->coupon_code;
                        $mail_data['percentage'] = $couponcodes->percentage;
                        $date = date('d-m-Y', strtotime($couponcodes->set_end_date));
                        $mail_data['set_end_date'] = $date;
                        // if ($prod[$i] != '') {
                        //     event(new CouponCodeMail($mail_data));
                        // }
                        $import = (new SendCouponCodeEmail($email, $couponcodes));
                        dispatch($import)->delay(0);
                    }
                }
            }
        }
    }


    public function coupon_code_update(CouponCodeRequest $request)
    {
        try {
            Log::channel("couponcode")->info('** started the coupon code update method **');

            $exist = CouponCode::where([['coupon_code', $request->coupon_code], ['status', '!=', 2], ['coupon_code_id', '!=', $request->coupon_code_id]])->first();

            if (empty($exist)) {
                $ids = $request->coupon_code_id;
                $couponcode = CouponCode::find($ids);
                $couponcode->coupon_code = $request->input('coupon_code');
                $couponcode->description = $request->input('description');
                $couponcode->percentage = $request->input('percentage');
                $couponcode->set_min_amount = $request->input('set_min_amount');
                $couponcode->is_limit_for_discount = $request->input('is_limit_for_discount');
                $couponcode->total_usage_limit_no_of_discount = $request->input('total_usage_limit_no_of_discount');
                $couponcode->limit_to_use_per_customer = $request->input('limit_to_use_per_customer');
                $couponcode->customer_eligibility = $request->input('customer_eligibility');
                $couponcode->start_date = $request->input('start_date') . ' ' . $request->input('start_time');
                $couponcode->start_time = $request->input('start_time');
                $couponcode->set_end_date = $request->input('set_end_date') . ' ' . $request->input('set_end_time');
                $couponcode->set_end_time = $request->input('set_end_time');
                $couponcode->updated_on = Server::getDateTime();
                $couponcode->updated_by = JwtHelper::getSesUserId();
                Log::channel("couponcode")->info("request value :: $couponcode->coupon_code");

                if ($couponcode->save()) {
                    $couponcodes = CouponCode::where('coupon_code_id', $couponcode->coupon_code_id)->first();

                    // log activity
                    $desc =   $couponcodes->coupon_code . ' Coupon Code' . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Coupon Code');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("couponcode")->info("save value :: $couponcodes->coupon_code");
                    Log::channel("couponcode")->info('** end the coupon code update method **');

                    return response()->json([
                        'keyword'      => 'success',
                        'data'        => [$couponcodes],
                        'message'      => __('Coupon code updated successfully')
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'data'        => [],
                        'message'      => __('Coupon code update failed')
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Coupon code already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("couponcode")->error($exception);
            Log::channel("couponcode")->error('** error occured in coupon code update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function coupon_code_list(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'coupon_code' => 'coupon_code',
            'description' => 'description',
            'percentage' => 'percentage',
        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "coupon_code_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('coupon_code', 'description', 'percentage');
        $couponcodes = CouponCode::where([
            ['status', '!=', '2']
        ]);

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
        $clone_coupons = clone $couponcodes;
        $count = $couponcodes->count();

        $clone_coupons = $clone_coupons->get();
        if ($offset) {
            $offset = $offset * $limit;
            $couponcodes->offset($offset);
        }
        if ($limit) {
            $couponcodes->limit($limit);
        }
        $couponcodes->orderBy('coupon_code_id', 'desc');
        $couponcodes = $couponcodes->get();
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
                'count' => count($clone_coupons)
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => [],
                'count' => $count
            ]);
        }
    }

    public function coupon_code_view($id)
    {
        if ($id != '' && $id > 0) {
            $couponcode = new CouponCode();
            $get_coupon_code = CouponCode::where('coupon_code_id', $id)->get();
            $count = $get_coupon_code->count();
            if ($count > 0) {
                $final = [];
                foreach ($get_coupon_code as $value) {

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
                    'message' => __('Coupon code viewed successfully'),
                    'data' => $final
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => []
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => []
            ]);
        }
    }

    public function coupon_code_status(Request $request)
    {
        try {
            if (!empty($request)) {

                $ids = $request->id;

                if (!empty($ids)) {
                    Log::channel("couponcode")->info('** started the coupon code status method **');
                    Log::channel("couponcode")->info("request value coupon_code_id:: $ids :: status :: $request->status");

                    $couponcode = CouponCode::where('coupon_code_id', $ids)->first();
                    $update = CouponCode::where('coupon_code_id', $ids)->update(array(
                        'status' => $request->status,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));

                    //   log activity
                    if ($request->status == 0) {
                        $activity_status = 'inactivated';
                    } else if ($request->status == 1) {
                        $activity_status = 'activated';
                    } else if ($request->status == 2) {
                        $activity_status = 'deleted';
                    }

                    $desc =  'Coupon Code '  . $couponcode->coupon_code  . ' is ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Coupon Code');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {
                        Log::channel("couponcode")->info("save value :: coupon_code_id :: $ids :: employee inactive successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Coupon code inactivated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 1) {
                        Log::channel("couponcode")->info("save value :: coupon_code_id :: $ids :: coupon code active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Coupon code activated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 2) {
                        Log::channel("couponcode")->info("save value :: coupon_code_id :: $ids :: coupon code deleted successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Coupon code deleted successfully'),
                            'data' => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.no_data'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("couponcode")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    // public function coupon_code_delete(Request $request)
    // {
    //     try {
    //         if (!empty($request)) {
    //             $ids = $request->id;

    //             if (empty($exist)) {
    //                 Log::channel("couponcode")->info('** started the coupon code delete method **');
    //                 Log::channel("couponcode")->info("request value coupon_code_id:: $ids :: ");
    //                 $couponcode = CouponCode::where('coupon_code_id', $ids)->first();
    //                 $update = CouponCode::where('coupon_code_id', $ids)->update(array(
    //                     'status' => 2,
    //                     'updated_on' => Server::getDateTime(),
    //                     'updated_by' => JwtHelper::getSesUserId()
    //                 ));

    //                 // log activity
    //                 // $implode = implode(",", $ids);
    //                 $desc =  ' Coupon Code '  . $couponcode->coupon_code . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
    //                 $activitytype = Config('activitytype.Coupon Code');
    //                 GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
    //                 Log::channel("couponcode")->info("save value :: coupon_code_id :: $ids :: coupon code deleted successfully");
    //                 Log::channel("couponcode")->info('** end the coupon code delete method **');
    //                 return response()->json([
    //                     'keyword' => 'success',
    //                     'message' =>  __('Coupon Code deleted successfully'),
    //                     'data' => []
    //                 ]);
    //             } else {
    //                 return response()->json([
    //                     'keyword' => 'failed',
    //                     'message' => __('message.failed'),
    //                     'data' => []
    //                 ]);
    //             }
    //         } else {
    //             return response()->json([
    //                 'keyword' => 'failed',
    //                 'message' => __('message.failed'),
    //                 'data' => []
    //             ]);
    //         }
    //     } catch (\Exception $exception) {
    //         Log::channel("couponcode")->error($exception);
    //         Log::channel("couponcode")->info('** end the coupon code delete method **');

    //         return response()->json([
    //             'error' => 'Internal server error.',
    //             'message' => $exception->getMessage()
    //         ], 500);
    //     }
    // }
}
