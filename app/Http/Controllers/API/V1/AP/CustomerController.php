<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function customer_list(Request $request)
    {
        try {
            Log::channel("customer_admin")->info('** started the customer_admin list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByDistrict = ($request->filterByDistrict) ? $request->filterByDistrict : '[]';
            $filterByState = ($request->filterByState) ? $request->filterByState : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';



            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'date' => DB::raw('DATE_FORMAT(customer.created_on, "%d-%m-%Y")'),
                'customer_code' => 'customer.customer_code',
                'customer_name' => DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'),
                'mobile_no' =>  DB::raw('CONCAT_WS(" ", customer.mobile_no, customer.billing_mobile_number)'),
                'address' =>  DB::raw('CONCAT_WS(" ", customer.billing_address_1, customer.billing_landmark)'),
                'district_name' => DB::raw('CONCAT_WS(" ", d.district_name, district.district_name)'),
                'state_name' => DB::raw('CONCAT_WS(" ", s.state_name, state.state_name)')
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "cutsomer.customer_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

            $column_search = array(DB::raw('DATE_FORMAT(customer.created_on, "%d-%m-%Y")'),'customer.customer_code', DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'), 'customer.mobile_no', 'customer.billing_mobile_number', DB::raw('CONCAT_WS(" ", customer.billing_address_1, customer.billing_landmark)'), 'district.district_name', 'd.district_name', 'state.state_name', 's.state_name');

            $customer = Customer::where('customer.status', '!=', '2')->leftjoin('district', 'district.district_id', '=', 'customer.billing_city_id')->leftjoin('state', 'state.state_id', '=', 'customer.billing_state_id')
                ->leftjoin('district as d', 'd.district_id', '=', 'customer.district_id')->leftjoin('state as s', 's.state_id', '=', 'customer.state_id')->select('customer.*', 'district.district_name', 'state.state_name', 'd.district_name as dist_name', 's.state_name as st_name');

            $customer->where(function ($query) use ($searchval, $column_search, $customer) {
                $i = 0;
                if ($searchval) {
                    foreach ($column_search as $item) {
                        if ($item == 'customer.mobile_no' || $item == 'customer.billing_mobile_number') {

                            $query->orWhere(function ($query) use ($searchval) {
                                $query->where(function ($query) use ($searchval) {
                                    $query->whereNotNull('customer.mobile_no')
                                        ->where('customer.mobile_no', 'LIKE', "%{$searchval}%");
                                })->orWhere(function ($query) use ($searchval) {
                                    $query->whereNotNull('customer.billing_mobile_number')
                                        ->where('customer.billing_mobile_number', 'LIKE', "%{$searchval}%")
                                        ->whereNull('customer.mobile_no');
                                });
                            });

                        } elseif ($item == 'district.district_name' ||  $item == 'd.district_name') {

                            $query->orWhere(function ($query) use ($searchval) {
                                $query->where(function ($query) use ($searchval) {
                                    $query->whereNotNull('d.district_name')
                                        ->where('d.district_name', 'LIKE', "%{$searchval}%");
                                })->orWhere(function ($query) use ($searchval) {
                                    $query->whereNotNull('district.district_name')
                                        ->where('district.district_name', 'LIKE', "%{$searchval}%")
                                        ->whereNull('d.district_name');
                                });
                            });

                        } elseif ($item == 'state.state_name' ||  $item == 's.state_name') {

                            $query->orWhere(function ($query) use ($searchval) {
                                $query->where(function ($query) use ($searchval) {
                                    $query->whereNotNull('s.state_name')
                                        ->where('s.state_name', 'LIKE', "%{$searchval}%");
                                })->orWhere(function ($query) use ($searchval) {
                                    $query->whereNotNull('state.state_name')
                                        ->where('state.state_name', 'LIKE', "%{$searchval}%")
                                        ->whereNull('s.state_name');
                                });
                            });

                        } else {
                            if ($i === 0) {
                                $query->where(($item), 'LIKE', "%{$searchval}%");
                            } else {
                                $query->orWhere(($item), 'LIKE', "%{$searchval}%");
                            }
                            $i++;
                        }
                    }
                }
            });
            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $customer->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $customer->where(function ($query) use ($from_date) {
                    $query->whereDate('customer.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $customer->where(function ($query) use ($to_date) {
                    $query->whereDate('customer.created_on', '<=', $to_date);
                });
            }

            if (!empty($filterByDistrict) && $filterByDistrict != '[]' && $filterByDistrict != 'all') {
                // $filterByDistrict = json_decode($filterByDistrict, true);
                // // $customer->whereIn('customer.billing_city_id', $filterByDistrict);
                // // $customer->orwhereIn('customer.district_id', $filterByDistrict);


                $filterByDistrict = json_decode($filterByDistrict, true);
                $customer->where(function ($query) use ($filterByDistrict) {
                    $query->where(function ($query) use ($filterByDistrict) {
                        $query->whereNull('customer.district_id')
                            ->whereNotNull('customer.billing_city_id')
                            ->whereIn('customer.billing_city_id', $filterByDistrict);
                    })->orWhere(function ($query) use ($filterByDistrict) {
                        $query->whereNotNull('customer.district_id')
                            ->whereIn('customer.district_id', $filterByDistrict);
                    });
                });
            }

            if (!empty($filterByState)) {
                // $customer->where('customer.billing_state_id', $filterByState);
                // $customer->orwhere('customer.state_id', $filterByState);

                $customer->where(function ($query) use ($filterByState) {
                    $query->where(function ($query) use ($filterByState) {
                        $query->whereNull('customer.state_id')
                            ->whereNotNull('customer.billing_state_id')
                            ->where('customer.billing_state_id', $filterByState);
                    })->orWhere(function ($query) use ($filterByState) {
                        $query->whereNotNull('customer.state_id')
                            ->where('customer.state_id', $filterByState);
                    });
                });
            }

            if (!empty($filterByStatus)) {
                if ($filterByStatus == "inactive") {
                    $customer->where('customer.status', 0);
                }

                if ($filterByStatus == "active") {
                    $customer->where('customer.status', 1);
                }
            }


            $count = $customer->count();

            if ($offset) {
                $offset = $offset * $limit;
                $customer->offset($offset);
            }
            if ($limit) {
                $customer->limit($limit);
            }
            $customer->orderBy('customer.customer_id', 'desc');
            $customer = $customer->get();
            $final = [];
            if ($count > 0) {
                foreach ($customer as $value) {
                    $ary = [];
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['customer_code'] = $value['customer_code'];
                    $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    $ary['mobile_no'] = !empty($value['mobile_no']) ? $value['mobile_no'] : $value['billing_mobile_number'];
                    $ary['billing_state_id'] = !empty($value['state_id']) ? $value['state_id'] : $value['billing_state_id'];
                    $ary['billing_city_id'] = !empty($value['district_id']) ? $value['district_id'] : $value['billing_city_id'];
                    $address = $value['billing_address_1'] . ' ' . $value['billing_landmark'];
                    $ary['address'] = ($address == " ") ? "-" : $address;
                    // if($value['billing_address_1'] != "" && $value['billing_landmark'] != ""){
                    // $ary['address'] = ($address == " ") ? "-" : $address;

                    // }
                    // else{
                    //     $ary['address'] ='-'; 
                    // }
                    if ($value['billing_city_id'] != '') {
                        $ary['district_name'] = !empty($value['dist_name']) ? $value['dist_name'] : $value['district_name'];
                    } else {
                        // $ary['district_name'] = $value['other_district'];
                        $ary['district_name'] = !empty($value['dist_name']) ? $value['dist_name'] : $value['district_name'];
                    }
                    // $ary['state_name'] = $value['state_name'];
                    $ary['state_name'] = !empty($value['st_name']) ? $value['st_name'] : $value['state_name'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }


            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("customer_admin")->info("list value :: $log");
                Log::channel("customer_admin")->info('** end the customer_admin list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Customers listed successfully'),
                    'data' => $final,
                    'count' => $count
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                    'count' => $count
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("customer_admin")->error($exception);
            Log::channel("customer_admin")->error('** end the customer_admin list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function customer_status(Request $request)
    {
        $id = $request->customer_id;
        $id = json_decode($id, true);

        if (!empty($id)) {
            $customerdetails = Customer::where('customer_id', $id)->first();

            $update = Customer::where('customer_id', $id)->update(array(
                'status' => $request->status,
                'updated_on' => Server::getDateTime(),
                'updated_by' => JwtHelper::getSesUserId()
            ));

            // log activity
            $activity_status = ($request->status) ? 'activated' : 'deactivated';
            // $implode = implode(",", $ids);
            $desc = $customerdetails->customer_first_name . ' ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . ' ';
            $activitytype = Config('activitytype.Customer');
            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


            if ($request->status == 0) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Customer inactivated successfully'),
                    'data' => []
                ]);
            } else if ($request->status == 1) {
                return response()->json([
                    'keyword' => 'success',
                    'message' =>  __('Customer activated successfully'),
                    'data' => []
                ]);
            }
        } else {
            return response()
                ->json([
                    'keyword' => 'failed',
                    'message' => __('failed'),
                    'data' => []
                ]);
        }
    }


    public function customer_delete(Request $request)
    {
        if (!empty($request)) {

            $id = $request->customer_id;

            if (!empty($id)) {
                $customerdetails = Customer::where('customer_id', $id)->first();
                $update = Customer::where('customer_id', $id)->update(array(
                    'status' => 2,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));

                // log activity
                // $implode = implode(",", $ids);
                $desc = $customerdetails->name . ' Customer' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Customer');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                return response()->json([
                    'keyword' => 'success',
                    'message' =>  __('Customer deleted successfully'),
                    'data' => []
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.failed'),
                    'data' => []
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('message.failed'),
                'data' => []
            ]);
        }
    }

    public function customer_view_info($id)
    {
        try {
            Log::channel("customer")->info('** started the customer view method **');
            $customer_view = Customer::where('customer_id', $id)
                ->leftjoin('district', 'district.district_id', '=', 'customer.billing_city_id')
                ->leftjoin('state', 'state.state_id', '=', 'customer.billing_state_id')
                ->leftjoin('district as d', 'd.district_id', '=', 'customer.district_id')->leftjoin('state as s', 's.state_id', '=', 'customer.state_id')
                ->select('customer.*', 'state.state_name', 'district.district_name', 'd.district_name as dist_name', 's.state_name as st_name')->first();

            Log::channel("customer")->info("request value customer_id:: $id");

            $final = [];

            if (!empty($customer_view)) {
                $ary = [];
                $ary['customer_id'] = $customer_view['customer_id'];
                $ary['customer_name'] = !empty($customer_view['customer_last_name']) ? $customer_view['customer_first_name'] . ' ' . $customer_view['customer_last_name'] : $customer_view['customer_first_name'];
                $ary['billing_email'] = $customer_view['billing_email'];
                $ary['image'] = $customer_view['profile_image'];
                $ary['profile_image'] = ($customer_view['profile_image'] != '') ? env('APP_URL') . env('PROFILE_URL') . $customer_view['profile_image'] : env('APP_URL') . "avatar.jpg";
                $ary['mobile_no'] = $customer_view['billing_mobile_number'];
                $ary['address'] = $customer_view['billing_address_1'];
                $ary['landmark'] = $customer_view['billing_landmark'];
                if ($customer_view['billing_city_id'] != '') {
                    $ary['district_name'] = $customer_view['district_name'];
                } else {
                    $ary['district_name'] = $customer_view['other_district'];
                }
                $ary['landmark'] = $customer_view['billing_landmark'];
                $ary['pincode'] = $customer_view['billing_pincode'];
                $ary['state_name'] = $customer_view['state_name'];
                $ary['name'] = !empty($customer_view['billing_customer_last_name']) ? $customer_view['billing_customer_first_name'] . ' ' . $customer_view['billing_customer_last_name'] : $customer_view['billing_customer_first_name'];
                $ary['register_email'] = $customer_view['email'];
                $ary['register_mobile_no'] = $customer_view['mobile_no'];
                $ary['register_state_id'] = $customer_view['state_id'];
                $ary['register_state_name'] = $customer_view['st_name'];
                $ary['register_district_id'] = $customer_view['district_id'];
                $ary['register_district_name'] = $customer_view['dist_name'];
                $ary['order_info'] = $this->order_info($customer_view['customer_id']);

                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("customer")->info("view value :: $log");
                Log::channel("customer")->info('** end the customer view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Customer viewed successfully'),
                    'data' => $final
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No Data Found'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("customer")->error($exception);
            Log::channel("customer")->info('** end the customer view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function order_info($cus_id)
    {
        try {
            $orderList = Orders::where('orders.customer_id', $cus_id)
                ->leftjoin('order_items', 'order_items.order_id', '=', 'orders.order_id')
                ->select('orders.*', 'order_items.order_status')
                ->groupby('order_items.order_id');
            $orderList = $orderList->get();

            if (!empty($orderList)) {
                $s = 1;
                $orderAry = [];
                foreach ($orderList as $value) {
                    $ary = [];
                    $ary['s_no'] = $s;
                    $ary['order_date'] = date('d-m-Y', strtotime($value['order_date']));
                    $ary['order_no'] = $value['order_code'];
                    $ary['quantity'] = $value['total_quantity'];
                    $ary['total_amount'] = $value['order_totalamount'];
                    $ary['payment_type'] = $value['payment_mode'];
                    if ($value['payment_status'] == 1) {
                        $ary['payment_status'] = "PAID";
                    }
                    if ($value['payment_status'] == 0) {
                        $ary['payment_status'] = "UNPAID";
                    }
                    if ($value['order_status'] == 1 || $value['order_status'] == 0) {
                        $ary['order_status'] = "Pending";
                    }
                    if ($value['order_status'] == 2 ||  $value['order_status'] == 9 ||  $value['order_status'] == 10) {
                        $ary['order_status'] = "Approved";
                    }
                    if ($value['order_status'] == 6 ||  $value['order_status'] == 8) {
                        $ary['order_status'] = "Disapproved";
                    }
                    if ($value['order_status'] == 3) {
                        $ary['order_status'] = "Dispatched";
                    }
                    if ($value['order_status'] == 4) {
                        $ary['order_status'] = "Cancelled";
                    }
                    if ($value['order_status'] == 5) {
                        $ary['order_status'] = "Delivered";
                    }
                    if ($value['order_status'] == 7) {
                        $ary['order_status'] = "Packed";
                    }

                    $s++;
                    $orderAry[] = $ary;
                }
            }
            return $orderAry;
        } catch (\Exception $exception) {

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
