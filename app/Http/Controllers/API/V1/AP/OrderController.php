<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Events\DeliverySuccess;
use App\Events\SendApproved;
use App\Events\SendCodApproved;
use App\Events\SendCodDisapproved;
use App\Events\SendCodRevoke;
use App\Events\SendDisapproved;
use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Traits\OrderResponseTrait;
use App\Models\BillItems;
use App\Models\Bills;
use App\Models\ExpectedDays;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\TaskManager;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Writer\Pdf;
use App\Http\Requests\orderdispatchRequest;
use App\Models\CompanyInfo;
use App\Models\Customer;
use App\Models\Messages;
use App\Models\PgLinkHistory;
use App\Models\ProductVariant;
use App\Models\ShippedVendorDetails;
use File;
use Carbon\Carbon;

class OrderController extends Controller
{
    use OrderResponseTrait;
    public function waitingpayment_list(Request $request)
    {
        try {
            Log::channel("waitingpayment")->info('** started the waitingpayment list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            // $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'order_date' => 'orders.order_date',
                'order_id' => 'orders.order_id',
                'order_code' => 'orders.order_code',
                // 'customer_name' => 'customer.customer_first_name',
                'customer_name' => DB::raw('GROUP_CONCAT(CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name, bulk_order_enquiry.contact_person_name) SEPARATOR " ")'),
                // 'mobile_no' => 'customer.mobile_no',
                'mobile_no' => DB::raw('GROUP_CONCAT(CONCAT_WS(" ", customer.mobile_no, bulk_order_enquiry.mobile_no) SEPARATOR " ")'),
                'order_from' => 'orders.order_from',
                'order_amount' => 'orders.order_totalamount',
                'payment_transaction_date' => 'orders.payment_transaction_date',
                'payment_status' => 'orders.payment_status',
                'paid_amount' => 'orders.payment_amount'
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'orders.order_date', 'orders.order_id', DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'),
                'customer.mobile_no', 'orders.order_from', 'orders.order_totalamount', 'orders.order_code', 'orders.payment_transaction_date', 'orders.payment_status', 'orders.payment_amount', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no'
            );
            $waitingpayment = Orders::select(
                'orders.order_id',
                'orders.customer_id',
                'orders.bulk_order_enquiry_id',
                'orders.order_code',
                DB::raw('DATE_FORMAT(orders.order_date, "%d-%m-%Y") as
            order_date'),
                'customer.customer_first_name',
                'customer.customer_last_name',
                'customer.mobile_no',
                'bulk_order_enquiry.contact_person_name',
                'bulk_order_enquiry.mobile_no as bulk_order_mobile_no',
                'orders.order_from',
                'orders.shipping_cost',
                'orders.coupon_amount',
                'orders.payment_amount as paid_amount',
                'orders.payment_transcation_id',
                DB::raw('DATE_FORMAT(orders.payment_transaction_date, "%d-%m-%Y") as payment_transaction_date'),
                'orders.is_cod',
                'order_items.order_status',
                'order_items.cod_status',
                'orders.payment_status',
                'orders.payment_amount',
                'orders.payment_transaction_date',
                'pg_link_history.pg_link_history_id',
                'pg_link_history.expiry_date',
                'pg_link_history.created_on',
                'pg_link_history.short_url',
            )
                // ->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')
                ->leftJoin('customer', function ($leftJoin) {
                    $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                        ->where('orders.customer_id', '!=', NULL);
                })
                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                        ->where('orders.customer_id', '=', NULL);
                })
                ->leftJoin('pg_link_history', function ($leftJoin) {
                    $leftJoin->on('pg_link_history.order_id', '=', 'orders.order_id')
                        ->where('pg_link_history.payment_status', '!=', 1);
                })
                ->leftjoin('order_items', 'order_items.order_id', '=', 'orders.order_id')
                ->where('orders.is_cod', 2)->where('order_items.order_status', '!=', 4)
                ->groupBy('orders.order_id');
            // $waitingpayment->where(function ($query) {
            //     $query->whereIn('order_items.order_status', [1, 6, 7]);
            //     $query->orwhereIn('order_items.cod_status', [2, 4]);
            // });
            $waitingpayment->where(function ($query) use ($searchval, $column_search, $waitingpayment) {
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
                $waitingpayment->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $waitingpayment->where(function ($query) use ($from_date) {
                    $query->whereDate('orders.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $waitingpayment->where(function ($query) use ($to_date) {
                    $query->whereDate('orders.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $waitingpayment->where('orders.is_cod', $filterByStatus);
            }
            $count = count($waitingpayment->get());
            if ($offset) {
                $offset = $offset * $limit;
                $waitingpayment->offset($offset);
            }
            if ($limit) {
                $waitingpayment->limit($limit);
            }
            Log::channel("waitingpayment")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType:: $from_date:: $to_date::");
            $waitingpayment->orderBy('orders.order_id', 'desc');
            $waitingpayment = $waitingpayment->get();
            if ($count > 0) {
                $final = [];
                foreach ($waitingpayment as $value) {
                    $ary = [];
                    $ary['order_id'] = $value['order_id'];
                    $ary['order_code'] = $value['order_code'];
                    $ary['order_date'] = $value['order_date'];
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['bulk_order_enquiry_id'] = $value['bulk_order_enquiry_id'];
                    if (!empty($value['customer_id'] != '')) {
                        $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                        $ary['mobile_no'] = $value['mobile_no'];
                    } else {
                        $ary['customer_name'] = $value['contact_person_name'];
                        $ary['mobile_no'] = $value['bulk_order_mobile_no'];
                    }
                    $ary['order_from'] = $value['order_from'];
                    $itemCount = OrderItems::where('order_id', $value['order_id'])->count();
                    if ($itemCount == 1) {
                        $subtotal = OrderItems::where('order_id', $value->order_id)->sum('sub_total');
                        $ary['order_amount'] = $value['paid_amount'];
                    } else {
                        $subtotal = OrderItems::where('order_id', $value->order_id)->where('order_status', '!=', 4)->sum('sub_total');
                        $ary['order_amount'] = $value['paid_amount'];
                    }
                    $ary['order_status'] = $value['order_status'];
                    $ary['cod_status'] = $value['cod_status'];
                    $ary['is_cod'] = $value['is_cod'];
                    $ary['payment_transaction_date'] = $value['payment_transaction_date'];
                    $ary['payment_status'] = $value['payment_status'];
                    $ary['paid_amount'] = $value['payment_amount'];
                    $ary['pg_link_history_id'] = $value['pg_link_history_id'];
                    $ary['short_url'] = $value['short_url'];
                    $currentdate = Carbon::now();
                    if (!empty($value['expiry_date'])) {
                        $expiry_date = Carbon::createFromFormat('d/m/Y H:i:s', $value['expiry_date']);
                        if ($currentdate->gt($expiry_date) && $ary['payment_status'] != 1) {
                            $ary['short_url'] = null;
                        } else {
                            $ary['short_url'] = $value['short_url'];
                        }
                    }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("waitingpayment")->info("list value :: $log");
                Log::channel("waitingpayment")->info('** end the waitingpayment list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Waiting payments listed successfully'),
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
            Log::channel("waitingpayment")->error($exception);
            Log::channel("waitingpayment")->error('** end the waitingpayment list method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    // public function GetorderTotalamount($ord_id)
    // {
    //  $order_amount =  OrderItems::where('order_items.order_id',$ord_id)->where('order_items.order_status','!=',4)->select(DB::raw('SUM(order_items.sub_total) as total_amount'))->first();
    //     // $a = $order_amount->total_amount;
    //     print_r($order_amount);die;
    // //  $overll = [];
    // //  foreach ($order_amount as $photoframe) {
    // //      $ary = [];
    // //      $ary['total_amount'] = $photoframe['total_amount'];
    // //      $overll[] = $ary;
    // //  }
    // //  return $overll;
    // }

    public function waitingcod_list(Request $request)
    {
        try {
            Log::channel("waitingcod")->info('** started the waitingcod list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            // $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';


            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'

                'order_date' => 'orders.order_date',
                'order_id' => 'orders.order_id',
                'order_code' => 'orders.order_code',
                'customer_name' => 'customer.customer_first_name',
                'mobile_no' => 'customer.mobile_no',
                'order_from' => 'orders.order_from',
                'order_amount' => 'orders.order_totalamount',
                'payment_transaction_date' => 'orders.payment_transaction_date'

            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'orders.order_date', 'orders.order_id', DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'),
                'customer.mobile_no', 'orders.order_from', 'orders.order_totalamount', 'orders.order_code', 'orders.payment_transaction_date'
            );


            $waitingcod = Orders::select(
                'orders.order_id',
                'orders.order_code',
                DB::raw('DATE_FORMAT(orders.order_date, "%d-%m-%Y") as 
        order_date'),
                'customer.customer_first_name',
                'customer.customer_last_name',
                'customer.mobile_no',
                'orders.order_from',
                'orders.order_totalamount',
                'orders.payment_amount as paid_amount',
                'orders.payment_transcation_id',
                'orders.shipping_cost',
                'orders.coupon_amount',
                DB::raw('DATE_FORMAT(orders.payment_transaction_date, "%d-%m-%Y") as payment_transaction_date'),
                'orders.is_cod',
                'order_items.order_status',
                'order_items.cod_status'
            )
                ->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')
                ->leftjoin('order_items', 'order_items.order_id', '=', 'orders.order_id')
                // ->whereIn('order_items.cod_status', [1, 3])
                ->where('orders.is_cod', 1)->where('order_items.order_status', '!=', 4)
                ->groupBy('orders.order_id');

            $waitingcod->where(function ($query) use ($searchval, $column_search, $waitingcod) {
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
                $waitingcod->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $waitingcod->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $waitingcod->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $waitingcod->where('is_cod', $filterByStatus);
            }


            $count = count($waitingcod->get());

            if ($offset) {
                $offset = $offset * $limit;
                $waitingcod->offset($offset);
            }
            if ($limit) {
                $waitingcod->limit($limit);
            }
            Log::channel("waitingcod")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType:: $from_date:: $to_date::");
            $waitingcod->orderBy('order_id', 'desc');
            $waitingcod = $waitingcod->get();
            $final = [];

            // $count = $waitingcod->count();


            if ($count > 0) {
                foreach ($waitingcod as $value) {

                    $ary = [];
                    $ary['order_id'] = $value['order_id'];
                    $ary['order_code'] = $value['order_code'];
                    $ary['order_date'] = $value['order_date'];
                    $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    $ary['mobile_no'] = $value['mobile_no'];
                    $ary['order_from'] = $value['order_from'];
                    $itemCount = OrderItems::where('order_id', $value['order_id'])->count();
                    if ($itemCount == 1) {
                        $subtotal = OrderItems::where('order_id', $value->order_id)->sum('sub_total');
                        $ary['order_amount'] = $value['paid_amount'];
                    } else {
                        $subtotal = OrderItems::where('order_id', $value->order_id)->where('cod_status', '!=', 6)->sum('sub_total');
                        $ary['order_amount'] = $value['paid_amount'];
                    }
                    $ary['payment_transaction_date'] = $value['payment_transaction_date'];
                    $ary['order_status'] = $value['order_status'];
                    $ary['cod_status'] = $value['cod_status'];
                    $ary['is_cod'] = $value['is_cod'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("waitingcod")->info("list value :: $log");
                Log::channel("waitingcod")->info('** end the waitingcod list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Waiting COD listed successfully'),
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
            Log::channel("waitingcod")->error($exception);
            Log::channel("waitingcod")->error('** end the waitingcod list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function waitingdispatch_list(Request $request)
    {
        try {
            Log::channel("waitingdispatch")->info('** started the waitingdispatch list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';



            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'

                'order_date' => 'orders.order_date',
                'order_id' => 'orders.order_id',
                'order_code' => 'orders.order_code',
                // 'customer_name' => 'customer.customer_first_name',
                'customer_name' => DB::raw('GROUP_CONCAT(CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name, bulk_order_enquiry.contact_person_name) SEPARATOR " ")'),
                // 'mobile_no' => 'customer.mobile_no',
                'mobile_no' => DB::raw('GROUP_CONCAT(CONCAT_WS(" ", customer.mobile_no, bulk_order_enquiry.mobile_no) SEPARATOR " ")'),
                'order_from' => 'orders.order_from',
                'order_amount' => 'orders.order_totalamount',
                'payment_transaction_date' => 'orders.payment_transaction_date',
                'transaction_id' => 'orders.payment_transcation_id',
                'payment_mode' => 'orders.payment_mode'

            ];

            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'orders.order_date', 'orders.order_id', DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'),
                'customer.mobile_no', 'orders.order_from', 'orders.order_totalamount', 'orders.order_code', 'orders.payment_transaction_date', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no'
            );

            $waitingcod = Orders::select(
                'orders.order_id',
                'orders.order_code',
                'orders.customer_id',
                'orders.bulk_order_enquiry_id',
                DB::raw('DATE_FORMAT(orders.order_date, "%d-%m-%Y") as 
                order_date'),
                'customer.customer_first_name',
                'customer.customer_last_name',
                'customer.mobile_no',
                'bulk_order_enquiry.contact_person_name',
                'bulk_order_enquiry.mobile_no as bulk_order_mobile_no',
                'orders.order_from',
                'orders.payment_mode',
                'orders.order_totalamount',
                'orders.payment_amount as paid_amount',
                'orders.payment_transcation_id',
                'orders.shipping_cost',
                'orders.coupon_amount',
                DB::raw('DATE_FORMAT(orders.payment_transaction_date, "%d-%m-%Y") as payment_transaction_date'),
                'orders.is_cod',
                'order_items.order_status',
                'order_items.cod_status'
            )
                // ->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')
                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                        ->where('orders.customer_id', '=', NULL);
                })
                ->leftjoin('order_items', 'order_items.order_id', '=', 'orders.order_id')
                ->leftJoin('customer', function ($leftJoin) {
                    $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                        ->where('orders.bulk_order_enquiry_id', '=', NULL);
                })

                ->whereIn('order_items.order_status', [2, 3, 7, 9])
                ->groupBy('orders.order_id');

            $waitingcod->where(function ($query) use ($searchval, $column_search, $waitingcod) {
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
                $waitingcod->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $waitingcod->where(function ($query) use ($from_date) {
                    $query->whereDate('orders.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $waitingcod->where(function ($query) use ($to_date) {
                    $query->whereDate('orders.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $waitingcod->where('orders.is_cod', $filterByStatus);
            }
            $count = count($waitingcod->get());

            if ($offset) {
                $offset = $offset * $limit;
                $waitingcod->offset($offset);
            }
            if ($limit) {
                $waitingcod->limit($limit);
            }
            Log::channel("waitingdispatch")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType:: $from_date:: $to_date::");
            $waitingcod->orderBy('orders.order_id', 'desc');
            $waitingcod = $waitingcod->get();

            $final = [];

            if ($count > 0) {
                foreach ($waitingcod as $value) {

                    $ary = [];
                    $ary['order_id'] = $value['order_id'];
                    $ary['order_code'] = $value['order_code'];
                    $ary['order_date'] = $value['order_date'];
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['bulk_order_enquiry_id'] = $value['bulk_order_enquiry_id'];
                    if (!empty($value['customer_id'] != NULL)) {
                        $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                        $ary['mobile_no'] = $value['mobile_no'];
                    } else {
                        $ary['customer_name'] = $value['contact_person_name'];
                        $ary['mobile_no'] = $value['bulk_order_mobile_no'];
                    }
                    $ary['order_from'] = $value['order_from'];
                    // $ary['order_amount'] = $value['order_totalamount'];
                    $itemCount = OrderItems::where('order_id', $value['order_id'])->count();
                    if ($itemCount == 1) {
                        $subtotal = OrderItems::where('order_id', $value['order_id'])->sum('sub_total');
                        $ary['order_amount'] = $value['paid_amount'];
                    } else {
                        $subtotal = OrderItems::where('order_id', $value['order_id'])->where('order_status', '!=', 4)->sum('sub_total');
                        $ary['order_amount'] = $value['paid_amount'];
                    }
                    $ary['payment_mode'] = $value['payment_mode'];
                    $ary['transaction_id'] = $value['payment_transcation_id'];
                    $ary['payment_transaction_date'] = $value['payment_transcation_date'];
                    $ary['order_status'] = $value['order_status'];
                    $ary['cod_status'] = $value['cod_status'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("waitingcod")->info("list value :: $log");
                Log::channel("waitingcod")->info('** end the waitingcod list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Waiting Dispatch listed successfully'),
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
            Log::channel("waitingdispatch")->error($exception);
            Log::channel("waitingdispatch")->error('** end the waitingdispatch list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function BillGenerate(Request $request)
    {
        try {
            Log::channel("billgenerate")->info('** started the move to production method **');
            $order_item_ids = json_decode($request->order_item_ids, true);

            $billgenerate = new Bills();
            $billgenerate->order_id = $request->order_id;
            $billgenerate->created_on = Server::getDateTime();
            $billgenerate->created_by = JwtHelper::getSesUserId();
            $billgenerate->status = 1;
            if ($billgenerate->save()) {
                $bill_code = 'BILL_' . str_pad($billgenerate->bill_id, 3, '0', STR_PAD_LEFT);
                $billgenerate->bill_no = $bill_code;
                $billgenerate->save();
                foreach ($order_item_ids as $order_id) {
                    $billitem = new BillItems();
                    $billitem->bill_id = $billgenerate->bill_id;
                    $billitem->order_items_id = $order_id['id'];
                    $billitem->save();
                    $orderitem = OrderItems::find($order_id['id']);
                    $orderitem->bill_no = $billgenerate->bill_no;
                    $orderitem->shipped_on = Server::getDateTime();
                    $orderitem->order_status = 7;
                    $orderitem->cod_status = 7;
                    $orderitem->save();
                }

                $getOrderid = Orders::where('order_id', $request->order_id)->first();
                $getOrderItemsName = OrderItems::where('bill_no', $bill_code)->select('product_name', 'product_code')->get();

                $resultArray = [];
                if (!empty($getOrderItemsName)) {
                    foreach ($getOrderItemsName as $pd) {
                        $resultArray[] = $pd['product_name'] . ' - ' . $pd['product_code'];
                    }
                }

                $itmesNames = implode(", ", $resultArray) ?? "-";

                $desc =  'Waiting Dispatch - This ' . $getOrderid->order_code . '(' . $itmesNames . ') is bill generated by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Waiting Dispatch');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                //Send notification for Customer
                $userId = JwtHelper::getSesUserId();
                $title = "Order Ready for Dispatch" . " - " . $getOrderid->order_code;
                $body = "Your order $getOrderid->order_code is now ready to dispatch, we will process the order packaging soon.";
                $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $module = 'order_ready_for_dispatch';
                $page = 'order_ready_for_dispatch';
                $url = "account/orders/order-view?";
                $data = [
                    'order_id' => $getOrderid->order_id,
                    'order_code' => $getOrderid->order_code,
                    'random_id' => $random_id,
                    'page' => $page,
                    'url' => $url,
                ];
                $data2 = [
                    'order_id' => $getOrderid->order_id,
                    'order_code' => $getOrderid->order_code,
                    'random_id' => $random_id2,
                    'url' => $url,
                    'page' => $page
                ];
                $portal1 = 'mobile';
                $portal2 = 'website';


                if ($getOrderid->customer_id != "") {

                    $customer_recipient = Customer::where('customer_id', $getOrderid->customer_id)->first();


                    if ($customer_recipient->token != '') {
                        $message = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data2,
                            'portal' => $portal2
                        ];
                        $customer_key = $customer_recipient->token;
                        $receiver_id = $customer_recipient->customer_id;
                        $push = Firebase::sendSingle($customer_key, $message);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal2, $data2, $random_id2);
                    if ($customer_recipient->mbl_token != '') {
                        $message2 = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data,
                            'portal' => $portal1
                        ];
                        $customer_key = $customer_recipient->mbl_token;
                        $receiver_id = $customer_recipient->customer_id;
                        $push = Firebase::sendSingleMbl($customer_key, $message2);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId,  $customer_recipient->customer_id, $module, $page, $portal1, $data, $random_id);
                }
                //Send Notification For admin
                $title1 = "Order Ready for Dispatch" . " - " . $getOrderid->order_code;
                $body1 = "The order $getOrderid->order_code is now ready to dispatch.";
                $random_id3 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $module1 = 'order_ready_for_dispatch';
                $page1 = 'order_ready_for_dispatch';
                $url1 = "track-order?";
                $portal3 = 'admin';

                $data3 = [
                    'order_id' => $getOrderid->order_id,
                    'order_code' => $getOrderid->order_code,
                    'random_id' => $random_id3,
                    'url' => $url1,
                    'page' => $page1
                ];
                $token = UserModel::where('acl_user_id', 1)->where('token', '!=', NULL)->select('token')->first();

                if (!empty($token)) {
                    $message = [
                        'title' => $title1,
                        'body' => $body1,
                        'page' => $page1,
                        'data' => $data3,
                        'portal' => $portal3,
                        'module' => $module1
                    ];

                    $admin_key = $token->token;
                    $receiver_id = $token->acl_user_id;
                    $push = Firebase::sendSingle($admin_key, $message);
                }
                $getdata = GlobalHelper::notification_create($title, $body, 1, 1, 1, $module, $page, "admin", $data, $random_id);


                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Bill Generated Successsfully'),
                    'data'        => [$billgenerate]
                ]);
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('No data found'),
                    ]
                );
            }
        } catch (\Exception $exception) {
            Log::channel("billgenerate")->error($exception);
            Log::channel("billgenerate")->info('** end the statusChange method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function waitingdispatch_invoice(Request $request)
    {
        try {
            Log::channel("waitingdispatch")->info('** started the waitingdispatch list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            // $bill_no = ($request->bill_no) ? $request->bill_no : '';
            $order_item_id = json_decode($request->order_item_id, true);
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'

                'order_date' => 'orders.order_date',
                'order_id' => 'orders.order_id',
                'order_code' => 'orders.order_code',
                'customer_name' => 'customer.customer_first_name',
                'mobile_no' => 'customer.mobile_no',
                'order_from' => 'orders.order_from',
                'order_amount' => 'orders.order_totalamount',
                'payment_transaction_date' => 'orders.payment_transaction_date'

            ];

            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'orders.order_date', 'orders.order_id', DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'),
                'customer.mobile_no', 'orders.order_from', 'orders.order_totalamount', 'orders.order_code', 'orders.payment_transaction_date'
            );

            $dispatch_invoice = OrderItems::select('order_items.*', 'orders.order_code', 'orders.coupon_amount', 'orders.billing_state_id', 'orders.order_date', 'orders.coupon_code_percentage', 'orders.billing_state_id', 'orders.customer_id','orders.coupon_code')
                ->leftJoin('orders', 'order_items.order_id', '=', 'orders.order_id')
                ->whereIn('order_items.order_items_id', $order_item_id);

            $dispatch_invoice->where(function ($query) use ($searchval, $column_search, $dispatch_invoice) {
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
                $dispatch_invoice->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $dispatch_invoice->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $dispatch_invoice->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $dispatch_invoice->where('is_cod', $filterByStatus);
            }


            $count = $dispatch_invoice->count();

            if ($offset) {
                $offset = $offset * $limit;
                $dispatch_invoice->offset($offset);
            }
            if ($limit) {
                $dispatch_invoice->limit($limit);
            }
            Log::channel("waitingdispatch")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType:: $from_date:: $to_date::");
            $dispatch_invoice->orderBy('order_items.order_id', 'desc');
            $dispatch_invoice = $dispatch_invoice->get();
            $count = $dispatch_invoice->count();
            // $deliveryChargeAmount = $dispatch_invoice->sum('delivery_charge');
            if ($count > 0) {
                $final = [];
                $sum = 0;
                $deliveryChargeAmount = 0;
                $coupon_amount = 0;
                foreach ($dispatch_invoice as $value) {
                    $ary = [];
                    $ary['order_id'] = $value['order_id'];
                    $ary['order_code'] = $value['order_code'];
                    $ary['order_item_id'] = $value['order_items_id'];
                    $ary['product_id'] = $value['product_code'];
                    $ary['order_date'] = date("d-m-Y", strtotime($value['order_date']));
                    $ary['product_name'] = $value['product_name'];

                    if (!empty($value['customer_id'])) {
                        $ary['gross_amount'] = $value['sub_total'];
                        $ary['quantity'] = $value['quantity'];

                        $ary['discount_percent'] = $value['coupon_code_percentage'] ?? "-";
                        // $amt_find = $value['sub_total'] * $value['coupon_code_percentage'] / 100;

                        // $ary['discount'] =  sprintf("%.2f", $amt_find);

                        $ary['discount'] =  "0.00";

                        // if ($ary['discount'] != " ") {
                        //     $ary['taxable_amount'] = $value['sub_total'] - $ary['discount'];
                        // } else {
                        // $ary['taxable_amount'] = $value['sub_total'];
                        // }
                        $ary['taxable_amount'] = $value['sub_total'];
                        $gst_calc = 1 + ($value['gst_value'] / 100 * 1);
                        $exc_gst = $ary['taxable_amount'] / $gst_calc;
                        $exec_gst_amount = number_format(floor($exc_gst * 100) / 100, 2, '.', '');
                        $amt = $ary['taxable_amount'] - $exec_gst_amount;
                        $ary['taxable_amount'] = sprintf("%.2f", $value['sub_total'] - $amt);
                        // $round_exc_gst = round($exc_gst, 2);
                        if ($value['billing_state_id'] == 33) {
                            $ary['cgst_percent'] = $value['gst_value'] / 2;
                            $ary['cgst_amount'] = sprintf("%.2f", $amt / 2);
                            $ary['sgst_percent'] = $value['gst_value'] / 2;
                            $ary['sgst_amount'] =  sprintf("%.2f", $amt / 2);
                            $ary['net_amount'] = $value['sub_total'];
                            $ary['igst_percent'] = '-';
                            $ary['igst_amount'] = '-';
                        } else {
                            $ary['cgst_percent'] = '-';
                            $ary['cgst_amount'] = '-';
                            $ary['sgst_percent'] = '-';
                            $ary['sgst_amount'] =  '-';
                            $ary['igst_percent'] = $value['gst_value'];
                            $ary['igst_amount'] = sprintf("%.2f", $amt);
                            $ary['net_amount'] = $value['sub_total'];
                        }
                        $sum += sprintf("%.2f", $ary['net_amount']);
                        if(!empty($value['coupon_code'])){
                        $coupon_amount += sprintf("%.2f", $value['coupon_code_amount']);
                        } else {
                        $coupon_amount = NULL;
                        }
                        $total_amount = sprintf("%.2f", $sum);
                        $deliveryChargeAmount += $value['delivery_charge'];

                        $totalAmount = sprintf("%.2f", $total_amount + $deliveryChargeAmount - $coupon_amount);
                        $rounded_value = round($totalAmount);
                        $totalAmountRoundOff = sprintf("%.2f", $rounded_value);

                        $remainingValue = $rounded_value - $totalAmount;
                        // $remainingAbsValue = abs($remainingValue);
                        $roundOffValue = sprintf("%.2f", $remainingValue);

                    } else {
                        $subTotal = sprintf("%.2f", $value['unit_price'] * $value['quantity']);
                        $ary['gross_amount'] = $subTotal;
                        $ary['quantity'] = $value['quantity'];
                        $ary['discount_percent'] = $value['discount_percentage'] ?? "-";
                        $discountAmount = $value['discount_percentage'] * $value['discount_amount'];
                        $ary['discount'] = sprintf("%.2f", $discountAmount) ?? "-";
                        $ary['taxable_amount'] = $value['taxable_amount'];
                        if ($value['billing_state_id'] == 33) {
                            $ary['cgst_percent'] = $value['cgst_percentage'];
                            $ary['cgst_amount'] = $value['cgst_amount'];
                            $ary['sgst_percent'] = $value['sgst_percentage'];
                            $ary['sgst_amount'] = $value['sgst_amount'];
                            $ary['igst_percent'] = "-";
                            $ary['igst_amount'] = "0";
                            $ary['net_amount'] = $value['quote_amount'];
                        } else {
                            $ary['cgst_percent'] = "-";
                            $ary['cgst_amount'] = "0";
                            $ary['sgst_percent'] = "-";
                            $ary['sgst_amount'] = "0";
                            $ary['igst_percent'] = $value['igst_percentage'];
                            $ary['igst_amount'] = $value['igst_amount'];
                            $ary['net_amount'] = $value['quote_amount'];
                        }
                        $sum += $ary['net_amount'];
                        $deliveryChargeAmount += $value['delivery_charge'];
                        $coupon_amount = NULL;
                        $total_amount = sprintf("%.2f", $sum);

                        $totalAmount = sprintf("%.2f", $total_amount + $deliveryChargeAmount - $coupon_amount);
                        $rounded_value = round($totalAmount);
                        $totalAmountRoundOff = sprintf("%.2f", $rounded_value);

                        $remainingValue = $rounded_value - $totalAmount;
                        // $remainingAbsValue = abs($remainingValue);
                        $roundOffValue = sprintf("%.2f", $remainingValue);
                    }
                    $invoice_date = OrderItems::where('order_items.bill_no', $value['bill_no'])
                        ->leftJoin('bill', 'order_items.bill_no', '=', 'bill.bill_no')->select('bill.created_on')->first();
                    $ary['invoice_date'] = $invoice_date->created_on ?? date('Y-m-d');
                    $customer_details =  Orders::where('order_id', $value['order_id'])->leftjoin('district', 'orders.billing_city_id', '=', 'district.district_id')
                        ->leftJoin('state', 'orders.billing_state_id', '=', 'state.state_id')
                        ->select('state.state_name', 'district.district_name', 'orders.billing_customer_first_name', 'orders.billing_customer_last_name', 'orders.billing_email', 'orders.billing_mobile_number', 'orders.billing_gst_no', 'orders.billing_alt_mobile_number', 'orders.billing_address_1', 'orders.billing_address_2', 'orders.billing_landmark', 'orders.billing_pincode', 'orders.bulk_order_enquiry_id', 'orders.customer_id')->first();
                    $customer_first_name = $customer_details->billing_customer_first_name;
                    $customer_last_name = $customer_details->billing_customer_last_name;
                    $customer_address = $customer_details->billing_address_1;
                    $customer_mobile = $customer_details->billing_mobile_number;
                    $customer_email = $customer_details->billing_email;
                    $customer_district = $customer_details->district_name;
                    $customer_state = $customer_details->state_name;
                    $customer_pincode = $customer_details->billing_pincode;
                    $customer_landmark = $customer_details->billing_landmark;
                    $company_details = CompanyInfo::select('name', 'address', 'logo', 'mobile_no')->first();
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("dispatch_invoice")->info("list value :: $log");
                Log::channel("dispatch_invoice")->info('** end the dispatch_invoice list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Invoice data listed successfully'),
                    'data' => $final,
                    'count' => $count,
                    'net_amount' => sprintf("%.2f", $sum),
                    'coupon_amount' => !empty($coupon_amount) ? sprintf("%.2f", $coupon_amount) : NULL,
                    'deliveryChargeAmount' => sprintf("%.2f", $deliveryChargeAmount),
                    'total_amount' => $totalAmountRoundOff,
                    'round_off' => $roundOffValue,
                    'customer_details' => $customer_details,
                    'company_details' => $company_details
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
            Log::channel("dispatch_invoice")->error($exception);
            Log::channel("dispatch_invoice")->error('** end the dispatch_invoice list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function waitingdispatch_invoice_pdf(Request $request)
    {
        try {
            Log::channel("waitingdispatch")->info('** started the waitingdispatch list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $bill_no = ($request->bill_no) ? $request->bill_no : '';
            // $order_item_id = json_decode($request->order_item_id, true);
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'

                'order_date' => 'orders.order_date',
                'order_id' => 'orders.order_id',
                'order_code' => 'orders.order_code',
                'customer_name' => 'customer.customer_first_name',
                'mobile_no' => 'customer.mobile_no',
                'order_from' => 'orders.order_from',
                'order_amount' => 'orders.order_totalamount',
                'payment_transaction_date' => 'orders.payment_transaction_date'

            ];

            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'orders.order_date', 'orders.order_id', DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'),
                'customer.mobile_no', 'orders.order_from', 'orders.order_totalamount', 'orders.order_code', 'orders.payment_transaction_date'
            );

            $dispatch_invoice = OrderItems::select('order_items.*', 'orders.coupon_amount', 'orders.order_code', 'orders.order_date', 'orders.coupon_code_percentage', 'orders.billing_state_id', 'orders.customer_id','orders.coupon_code')
                ->leftJoin('orders', 'order_items.order_id', '=', 'orders.order_id')
                // ->whereIn('order_items.order_items_id', $order_item_id)
                ->where('order_items.bill_no', $bill_no);

            $dispatch_invoice->where(function ($query) use ($searchval, $column_search, $dispatch_invoice) {
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
                $dispatch_invoice->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $dispatch_invoice->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $dispatch_invoice->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $dispatch_invoice->where('is_cod', $filterByStatus);
            }

            $count = $dispatch_invoice->count();

            if ($offset) {
                $offset = $offset * $limit;
                $dispatch_invoice->offset($offset);
            }
            if ($limit) {
                $dispatch_invoice->limit($limit);
            }


            Log::channel("waitingdispatch")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType:: $from_date:: $to_date::");
            $dispatch_invoice->orderBy('order_items.order_id', 'desc');
            $dispatch_invoice = $dispatch_invoice->get();

            // $shipping_charge = $dispatch_invoice->pluck('shipping_cost')->sum();
            // $net_amount = $dispatch_invoice->pluck('net_amount')->sum();
            // $total_amount = $net_amount + $shipping_charge;

            $count = $dispatch_invoice->count();
            if ($count > 0) {
                $final = [];
                $sum = 0;
                $deliveryChargeAmount = 0;
                $coupon_amount = 0;
                $remaining_value = 0;
                $roundOffValueSymbol = "";
                foreach ($dispatch_invoice as $value) {

                    $ary = [];
                    $order_code = $value['order_code'];
                    $order_date = date("d-m-Y", strtotime($value['order_date']));
                    $ary['order_items_id'] = $value['order_items_id'];
                    $ary['product_id'] = $value['product_code'];
                    $ary['product_name'] = $value['product_name'];
                    $ary['customer_id'] = $value['customer_id'];

                    if (!empty($value['customer_id'])) {
                        // $ary['gross_amount'] = $value['sub_total'];
                        // $ary['quantity'] = $value['quantity'];

                        // $ary['discount_percent'] = $value['coupon_code_percentage'] ?? "-";
                        // $amt_find = $value['sub_total'] * $value['coupon_code_percentage'] / 100;

                        // $ary['discount'] =  sprintf("%.2f", $amt_find);
                        // if ($ary['discount'] != " ") {
                        //     $ary['taxable_amount'] = $value['sub_total'] - $ary['discount'];
                        // } else {
                        //     $ary['taxable_amount'] = $value['sub_total'];
                        // }
                        // $gst_calc = 1 + ($value['gst_value'] / 100 * 1);
                        // $exc_gst = $ary['taxable_amount'] / $gst_calc;
                        // $amt = $ary['taxable_amount'] - $exc_gst;
                        // $round_exc_gst = round($exc_gst, 2);
                        // if ($value['billing_state_id'] == 33) {
                        //     $ary['cgst_percent'] = $value['gst_value'] / 2 . "%";
                        //     $ary['cgst_amount'] = sprintf("%.2f", $amt / 2);
                        //     $ary['sgst_percent'] = $value['gst_value'] / 2 . "%";
                        //     $ary['sgst_amount'] =  sprintf("%.2f", $amt / 2);
                        //     $ary['net_amount'] = sprintf("%.2f", $ary['taxable_amount'] + $ary['cgst_amount'] + $ary['sgst_amount']);
                        //     $ary['igst_percent'] = '-';
                        //     $ary['igst_amount'] = '-';
                        // } else {
                        //     $ary['cgst_percent'] = '-';
                        //     $ary['cgst_amount'] = '-';
                        //     $ary['sgst_percent'] = '-';
                        //     $ary['sgst_amount'] =  '-';
                        //     $ary['igst_percent'] = $value['gst_value'] . "%";
                        //     $ary['igst_amount'] = sprintf("%.2f", $amt);
                        //     $ary['net_amount'] = sprintf("%.2f", $ary['taxable_amount'] + $ary['igst_amount']);
                        // }
                        // $sum += sprintf("%.2f", $ary['net_amount']);
                        // $total_amount = sprintf("%.2f", $sum);

                        $ary['gross_amount'] = $value['sub_total'];
                        $ary['quantity'] = $value['quantity'];

                        $ary['discount_percent'] = $value['coupon_code_percentage'] ?? "-";
                        // $amt_find = $value['sub_total'] * $value['coupon_code_percentage'] / 100;

                        // $ary['discount'] =  sprintf("%.2f", $amt_find);

                        $ary['discount'] =  "0.00";

                        // if ($ary['discount'] != " ") {
                        //     $ary['taxable_amount'] = $value['sub_total'] - $ary['discount'];
                        // } else {
                        // $ary['taxable_amount'] = $value['sub_total'];
                        // }
                        $ary['taxable_amount'] = $value['sub_total'];
                        $gst_calc = 1 + ($value['gst_value'] / 100 * 1);
                        $exc_gst = $ary['taxable_amount'] / $gst_calc;
                        $exec_gst_amount = number_format(floor($exc_gst * 100) / 100, 2, '.', '');
                        $amt = $ary['taxable_amount'] - $exec_gst_amount;
                        $ary['taxable_amount'] = sprintf("%.2f", $value['sub_total'] - $amt);
                        // $round_exc_gst = round($exc_gst, 2);
                        if ($value['billing_state_id'] == 33) {
                            $ary['cgst_percent'] = $value['gst_value'] / 2;
                            $ary['cgst_amount'] = sprintf("%.2f", $amt / 2);
                            $ary['sgst_percent'] = $value['gst_value'] / 2;
                            $ary['sgst_amount'] =  sprintf("%.2f", $amt / 2);
                            $ary['net_amount'] = $value['sub_total'];
                            $ary['igst_percent'] = '-';
                            $ary['igst_amount'] = '-';
                        } else {
                            $ary['cgst_percent'] = '-';
                            $ary['cgst_amount'] = '-';
                            $ary['sgst_percent'] = '-';
                            $ary['sgst_amount'] =  '-';
                            $ary['igst_percent'] = $value['gst_value'];
                            $ary['igst_amount'] = sprintf("%.2f", $amt);
                            $ary['net_amount'] = $value['sub_total'];
                        }
                        $sum += sprintf("%.2f", $ary['net_amount']);
                        if(!empty($value['coupon_code'])){
                        $coupon_amount += sprintf("%.2f", $value['coupon_code_amount']);
                        } else {
                        $coupon_amount = NULL;
                        }
                        $total_amount = sprintf("%.2f", $sum);
                        $deliveryChargeAmount += $value['delivery_charge'];
                        $totalAmount = sprintf("%.2f", $total_amount + $deliveryChargeAmount - $coupon_amount);
                        $rounded_value = round($totalAmount);

                        $totalAmountPdf = sprintf("%.2f", $rounded_value);
                        
                        $remainingValue = $rounded_value - $totalAmount;
                        // $remainingAbsValue = abs($remainingValue);
                        $remaining_value = sprintf("%.2f", $remainingValue);
                        if($remaining_value >= 0.00){
                            $roundOffValueSymbol = "+";
                        } else {
                            $roundOffValueSymbol = "-";
                        }
                    } else {
                        $subTotal = sprintf("%.2f", $value['unit_price'] * $value['quantity']);
                        $ary['gross_amount'] = $subTotal;
                        $ary['quantity'] = $value['quantity'];
                        $ary['discount_percent'] = $value['discount_percentage'] ?? "-";
                        $discountAmount = $value['discount_percentage'] * $value['discount_amount'];
                        $ary['discount'] = sprintf("%.2f", $discountAmount) ?? "-";
                        $ary['taxable_amount'] = $value['taxable_amount'];
                        if ($value['billing_state_id'] == 33) {
                            $ary['cgst_percent'] = $value['cgst_percentage'];
                            $ary['cgst_amount'] = $value['cgst_amount'];
                            $ary['sgst_percent'] = $value['sgst_percentage'];
                            $ary['sgst_amount'] = $value['sgst_amount'];
                            $ary['igst_percent'] = "-";
                            $ary['igst_amount'] = "0";
                            $ary['net_amount'] = $value['quote_amount'];
                        } else {
                            $ary['cgst_percent'] = "-";
                            $ary['cgst_amount'] = "0";
                            $ary['sgst_percent'] = "-";
                            $ary['sgst_amount'] = "0";
                            $ary['igst_percent'] = $value['igst_percentage'];
                            $ary['igst_amount'] = $value['igst_amount'];
                            $ary['net_amount'] = $value['quote_amount'];
                        }
                        $sum += $ary['net_amount'];
                        $deliveryChargeAmount += $value['delivery_charge'];
                        $coupon_amount = NULL;
                        $total_amount = sprintf("%.2f", $sum);
                        $totalAmount = sprintf("%.2f", $total_amount + $deliveryChargeAmount - $coupon_amount);
                        $rounded_value = round($totalAmount);

                        $totalAmountPdf = sprintf("%.2f", $rounded_value);

                        $remainingValue = $rounded_value - $totalAmount;
                        // $remainingAbsValue = abs($remainingValue);
                        $remaining_value = sprintf("%.2f", $remainingValue);
                        if($remaining_value >= 0.00){
                            $roundOffValueSymbol = "+";
                        } else {
                            $roundOffValueSymbol = "-";
                        }
                    }
                    $customer_details =  Orders::where('order_id', $value['order_id'])->leftjoin('district', 'orders.billing_city_id', '=', 'district.district_id')
                        ->leftJoin('state', 'orders.billing_state_id', '=', 'state.state_id')
                        ->select('state.state_name', 'billing_pincode', 'billing_customer_first_name', 'billing_customer_last_name', 'billing_landmark', 'billing_email', 'billing_mobile_number', 'billing_alt_mobile_number', 'billing_address_1', 'billing_address_2', 'district.district_name', 'billing_gst_no', 'customer_id')->first();
                    $customer_first_name = $customer_details->billing_customer_first_name;
                    $customer_last_name = $customer_details->billing_customer_last_name;
                    $customer_address = $customer_details->billing_address_1;
                    $customer_address_2 = $customer_details->billing_address_2;
                    $customer_alt_mobile_number = $customer_details->billing_alt_mobile_number;
                    $customer_gst_no = $customer_details->billing_gst_no;
                    $customer_mobile = $customer_details->billing_mobile_number;
                    $customer_email = $customer_details->billing_email;
                    $customer_district = $customer_details->district_name;
                    $customer_state = $customer_details->state_name;
                    $customer_pincode = $customer_details->billing_pincode;
                    $customer_landmark = $customer_details->billing_landmark;
                    $customer_id = $customer_details->customer_id;
                    $invoice_date = OrderItems::where('order_items.bill_no', $value['bill_no'])
                        ->leftJoin('bill', 'order_items.bill_no', '=', 'bill.bill_no')->select('bill.created_on')->first();
                    $final_invoice_date = date('d-m-Y', strtotime($invoice_date->created_on));
                    $company_details = CompanyInfo::select('name', 'address', 'logo', 'mobile_no')->first();
                    $company_name = $company_details->name;
                    $company_address = $company_details->address;
                    $company_mobile_no = $company_details->mobile_no;
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {

                $path = public_path() . "/dispatch";
                File::makeDirectory($path, env('PERMISSION_MODE_REPORT'), true, true);
                $fileName = "dispatch_" . time() . '.pdf';
                $location = public_path() . '/dispatch/' . $fileName;
                $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
                $mpdf->WriteHTML(\View::make('report/dispatch', $final)->with('final', $final)->with('order_code', $order_code)->with('order_date', $order_date)->with('customer_first_name', $customer_first_name)->with('customer_last_name', $customer_last_name)->with('customer_mobile', $customer_mobile)->with('customer_email', $customer_email)->with('customer_district', $customer_district)->with('customer_state', $customer_state)->with('customer_pincode', $customer_pincode)->with('customer_landmark', $customer_landmark)->with('final_invoice_date', $final_invoice_date)->with('company_name', $company_name)->with('company_address', $company_address)->with('company_mobile_no', $company_mobile_no)->with('req', $request)->with('sum', sprintf("%.2f", $sum))->with('deliveryChargeAmount', sprintf("%.2f", $deliveryChargeAmount))->with('coupon_amount', sprintf("%.2f", $coupon_amount))->with('customer_address', $customer_address)->with('total_amount', $totalAmountPdf)->with('count', $count)->with('no', 1)->with('customer_address_2', $customer_address_2)->with('customer_alt_mobile_number', $customer_alt_mobile_number)->with('customer_gst_no', $customer_gst_no)->with('customer_id', $customer_id)->with('remaining_value', abs($remaining_value))->with('roundOffValueSymbol', $roundOffValueSymbol)->render());
                $mpdf->Output($location, 'F');

                return response()->download($location, "dispatch.pdf");
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('No Data Found'),
                        'data' => []
                    ]
                );
            }
        } catch (\Exception $exception) {
            Log::channel("dispatch_invoice")->error($exception);
            Log::channel("dispatch_invoice")->error('** end the dispatch_invoice list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function orderDispatch_update(orderdispatchRequest $request)
    {
        $bill_no = $request->bill_no;
        $order = OrderItems::where('bill_no', $bill_no)->select('order_items.*')->first();
        // if (!empty($order)) {
        //     $dispatch = new OrderItems();
        //     // $dispatch->courier_name	 = $request->courier_name;
        //     // $dispatch->courier_no =  $request->courier_no;
        //     // $dispatch->updated_on = Server::getDateTime();
        //     // $dispatch->updated_by = JwtHelper::getSesUserId();
        //     $dispatch->save();
        //     if ($dispatch->save()) {
        if ($order->is_cod == 1) {
            $update = OrderItems::where('bill_no', $bill_no)->update(array(
                'order_status' => 3,
                'cod_status' => 4,
                'courier_no' => $request->courier_no,
                'courier_name' => $request->courier_name,
                'shipped_vendor_details_id' => $request->shipped_vendor_details_id,
                'updated_on' => Server::getDateTime(),
                'updated_by' => JwtHelper::getSesUserId(),
                'dispatched_on' => Server::getDateTime()
            ));

            $order = Orders::where('orders.order_id', $order->order_id)->leftJoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->select('orders.*', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'orders.order_id', 'orders.customer_id')->first();
            $customerName = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
            $orderItemDetails = OrderItems::where('bill_no', $bill_no)->select('order_items.*')->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('service.service_name')->get();
            $resultArray = [];
            if (!empty($orderItemDetails)) {
                foreach ($orderItemDetails as $pd) {
                    $resultArray[$pd->service_name] = $pd['service_name'];
                }
            }

            $itmesNames = implode(", ", $resultArray) ?? "-";
            $otp = '';
            if (isset($order->billing_mobile_number)) {
                $otp = GlobalHelper::getOTP(4);
                // $msg = $otp . " is your Print App verification code. Please enter the OTP to verify your mobile number. Please DO NOT share this OTP with anyone to ensure account's security";
                $msg = "Dear $customerName, We’re excited to say that Your package is shipped now. Your Order Item is $itmesNames & Order ID $order->order_code. Your delivery from Print App is on the way. Track your order #VAR4#.Team Print App";
                $isSmsSent = GlobalHelper::sendSMS($order->billing_mobile_number, $msg);
            }

            //Send notification for Customer
            $userId = JwtHelper::getSesUserId();

            $getOrderid = Orders::where('order_id', $order->order_id)->first();
            $getOrderItemsName = OrderItems::where('bill_no', $bill_no)->select('product_name', 'product_code')->get();

            $resultArrays = [];
            if (!empty($getOrderItemsName)) {
                foreach ($getOrderItemsName as $pd) {
                    $resultArrays[] = $pd['product_name'] . ' - ' . $pd['product_code'];
                }
            }

            $itmesOrderNames = implode(", ", $resultArrays) ?? "-";

            $desc =  'Waiting Dispatch - This ' . $getOrderid->order_code . '(' . $itmesOrderNames . ') is dispatched by ' . JwtHelper::getSesUserNameWithType() . '';
            $activitytype = Config('activitytype.Waiting Dispatch');
            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

            $title = "Order Dispatched" . " - " . $getOrderid->order_code;
            $body = "Your order (" . $getOrderid->order_code . ") is dispatched successfully";

            $module = 'order_dispatched';
            $page = 'order_dispatched';

            $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
            $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);

            $data = [
                'order_id' => $getOrderid->order_id,
                'order_code' => $getOrderid->order_code,
                'random_id' => $random_id,
                'page' => $page
            ];

            $url = "account/orders/order-view?";

            $data2 = [
                'order_id' => $getOrderid->order_id,
                'order_code' => $getOrderid->order_code,
                'random_id' => $random_id2,
                'url' => $url,
                'page' => $page
            ];


            $portal1 = 'mobile';
            $portal2 = 'website';


            $customer_recipient = Customer::where('customer_id', $getOrderid->customer_id)->first();
            if ($getOrderid->customer_id != "") {

                if ($customer_recipient->token != '') {
                    $message = [
                        'title' => $title,
                        'body' => $body,
                        'page' => $page,
                        'data' => $data2,
                        'portal' => $portal2
                    ];
                    $customer_key = $customer_recipient->token;
                    $receiver_id = $customer_recipient->customer_id;
                    $push = Firebase::sendSingle($customer_key, $message);
                }
                $getdata = GlobalHelper::notification_create($title, $body, 2, $userId,  $customer_recipient->customer_id, $module, $page, $portal2, $data2, $random_id2);
                if ($customer_recipient->mbl_token != '') {

                    $message2 = [
                        'title' => $title,
                        'body' => $body,
                        'page' => $page,
                        'data' => $data,
                        'portal' => $portal1
                    ];

                    $customer_key = $customer_recipient->mbl_token;
                    $receiver_id = $customer_recipient->customer_id;

                    $push = Firebase::sendSingleMbl($customer_key, $message2);
                }
                $getdata = GlobalHelper::notification_create($title, $body, 2, $userId,  $customer_recipient->customer_id, $module, $page, $portal1, $data, $random_id);
            }
            return response()->json([
                'keyword' => 'success',
                'message' => 'Order dispatched successfully',
                'data' => [$order]
            ]);
        }
        if ($order->is_cod == 2) {
            $update = OrderItems::where('bill_no', $bill_no)->update(array(
                'order_status' => 3,
                'courier_no' => $request->courier_no,
                'courier_name' => $request->courier_name,
                'shipped_vendor_details_id' => $request->shipped_vendor_details_id,
                'updated_on' => Server::getDateTime(),
                'updated_by' => JwtHelper::getSesUserId(),
                'dispatched_on' => Server::getDateTime()
            ));

            $order = Orders::where('orders.order_id', $order->order_id)->leftJoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->select('orders.*', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no')->first();
            $customerName = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
            $orderItemDetails = OrderItems::where('bill_no', $bill_no)->select('order_items.*')->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('service.service_name')->get();
            $resultArray = [];
            if (!empty($orderItemDetails)) {
                foreach ($orderItemDetails as $pd) {
                    $resultArray[$pd->service_name] = $pd['service_name'];
                }
            }
            // return $resultArray;
            $itmesNames = implode(", ", $resultArray) ?? "-";
            $otp = '';
            if (isset($order->billing_mobile_number)) {
                $otp = GlobalHelper::getOTP(4);
                // $msg = $otp . " is your Print App verification code. Please enter the OTP to verify your mobile number. Please DO NOT share this OTP with anyone to ensure account's security";
                $msg = "Dear $customerName, We’re excited to say that Your package is shipped now. Your Order Item is $itmesNames & Order ID $order->order_code. Your delivery from Print App is on the way. Track your order #VAR4#.Team Print App";
                $isSmsSent = GlobalHelper::sendSMS($order->billing_mobile_number, $msg);
            }



            //Send notification for Customer
            $userId = JwtHelper::getSesUserId();

            $getOrderid = Orders::where('order_id', $order->order_id)->first();

            $getOrderItemsName = OrderItems::where('bill_no', $bill_no)->select('product_name', 'product_code')->get();

            $resultArrays = [];
            if (!empty($getOrderItemsName)) {
                foreach ($getOrderItemsName as $pd) {
                    $resultArrays[] = $pd['product_name'] . ' - ' . $pd['product_code'];
                }
            }

            $itmesOrderNames = implode(", ", $resultArrays) ?? "-";

            $desc =  'Waiting Dispatch - This ' . $getOrderid->order_code . '(' . $itmesOrderNames . ') is dispatched by ' . JwtHelper::getSesUserNameWithType() . '';
            $activitytype = Config('activitytype.Waiting Dispatch');
            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

            $title = "Order Dispatched" . " - " . $getOrderid->order_code;
            $body = "Your order (" . $getOrderid->order_code . ") is dispatched successfully";

            $module = 'order_dispatched';
            $page = 'order_dispatched';

            $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
            $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);

            $data = [
                'order_id' => $getOrderid->order_id,
                'order_code' => $getOrderid->order_code,
                'random_id' => $random_id,
                'page' => $page
            ];

            $url = "account/orders/order-view?";

            $data2 = [
                'order_id' => $getOrderid->order_id,
                'order_code' => $getOrderid->order_code,
                'random_id' => $random_id2,
                'url' => $url,
                'page' => $page
            ];


            $portal1 = 'mobile';
            $portal2 = 'website';


            $customer_recipient = Customer::where('customer_id', $getOrderid->customer_id)->first();

            if ($getOrderid->customer_id != "") {
                if ($customer_recipient->token != '') {
                    $message = [
                        'title' => $title,
                        'body' => $body,
                        'page' => $page,
                        'data' => $data2,
                        'portal' => $portal2
                    ];
                    $customer_key = $customer_recipient->token;
                    $receiver_id = $customer_recipient->customer_id;
                    $push = Firebase::sendSingle($customer_key, $message);
                }
                $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal2, $data2, $random_id2);
                if ($customer_recipient->mbl_token != '') {

                    $message2 = [
                        'title' => $title,
                        'body' => $body,
                        'page' => $page,
                        'data' => $data,
                        'portal' => $portal1
                    ];

                    $customer_key = $customer_recipient->mbl_token;
                    $receiver_id = $customer_recipient->customer_id;

                    $push = Firebase::sendSingleMbl($customer_key, $message2);
                }
                $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal1, $data, $random_id);
            }
            return response()->json([
                'keyword' => 'success',
                'message' => 'Order dispatched successfully',
                'data' => [$order]
            ]);
        }
        //$order = Orders::where('order_id', $id)->select('orders.*')->first();
        //     $mail_data = [];
        //     $mail_data['email'] = $order->billing_email;
        //     $mail_data['name'] = $dispatch->dispatch_courier_name;
        //     $mail_data['no'] = $dispatch->dispatch_courier_no;
        //     $mail_data['url'] = $dispatch->dispatch_courier_tracking_url;
        //     if ($order->billing_email != '') {
        //         event(new SendEmail($mail_data));
        //     }
        //     $msg = "DEAR $order->billing_customer_first_name$order->billing_customer_last_name , U R PRODUCT IS DISPATCHED VIA COURIER $dispatch->dispatch_courier_name, TRK ID $dispatch->dispatch_courier_no , COPY U R TRK ID PASTE BELOW LINK $dispatch->dispatch_courier_tracking_url AND TRK U R PRODUCT, FOR SUPPORT CALL - 04567220705, 11AM TO 6PM. THANKS FOR CHOOSING NR INFOTECH.";
        //         $isSmsSent = GlobalHelper::sendSms($order->billing_mobile_number, $msg);
        // }

        // } 
        else {
            return response()
                ->json([
                    'keyword' => 'failed',
                    'message' => __('No Data Found'),
                    'data' => []
                ]);
        }
        // }
    }



    public function OrderItemsId($OrderItemsId)
    {
        $OrdItem = OrderItems::where('order_items_id', $OrderItemsId)
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->select('order_items.*', 'orders.order_code')->first();

        if (!empty($OrdItem)) {

            return $OrdItem;
        } else {

            $value = "";

            return $value;
        }
    }


    public function Order_Item_List($id)
    {
        try {
            // Log::channel("dispatchproducts")->info('** started the dispatchproducts view method **');
            if ($id != '' && $id > 0) {
                $dispatch_products = OrderItems::where('orders.order_id', $id)->whereIn('order_items.service_id', [5, 1, 2])
                    ->leftjoin('orders', 'order_items.order_id', '=', 'orders.order_id')
                    ->leftjoin('customer', 'orders.customer_id', '=', 'customer.customer_id')
                    ->select(
                        'order_items.*',
                        'customer.customer_first_name',
                        'customer.customer_last_name',
                        'customer.mobile_no',
                        'orders.customer_id',
                        'orders.order_date',
                        'orders.order_code',
                    )->get();
                // Log::channel("dispatchproducts")->info("request value gst_percentage_id:: $id");
                $count = $dispatch_products->count();
                if ($count > 0) {
                    $final = [];
                    foreach ($dispatch_products as $products) {
                        $ary = [];
                        $ary['order_items_id'] = $products['order_items_id'];
                        $ary['product_code'] = $products['product_code'];
                        $ary['product_name'] = $products['product_name'];
                        $ary['billing_status'] = $products['product_name'];
                        $ary['item_status'] = $products['product_name'];
                        if ($products['service_id'] == 1) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 2) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 3) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 4) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 5) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 6) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        $final[] = $ary;
                    }
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("dispatchproducts")->info("view value :: $log");
                    Log::channel("dispatchproducts")->info('** end the dispatchproducts view method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Order based products viewed successfully'),
                        'data' => $final
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {
            Log::channel("dispatchproducts")->error($exception);
            Log::channel("dispatchproducts")->info('** end the dispatchproducts view method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //Waiting Dispatch View
    public function track_order_item_view(Request $request, $ord_id, $orderItemId)
    {

        $get_role = Orders::where('orders.order_id', $ord_id)
            ->select(
                'orders.order_id',
                'orders.order_code',
                'orders.order_date',
                'orders.customer_id',
                'orders.bulk_order_enquiry_id',
                'orders.payment_mode',
                'orders.payment_amount',
                'orders.payment_transcation_id',
                'customer.customer_first_name',
                'customer.customer_last_name',
                'customer.mobile_no',
                'customer.email',
                'orders.billing_gst_no',
                'orders.billing_courier_type',
                'orders.billing_customer_first_name',
                'orders.billing_customer_last_name',
                'orders.billing_email',
                'orders.billing_mobile_number',
                'orders.billing_country_id',
                'orders.billing_state_id',
                'orders.billing_city_id',
                'orders.billing_address_1',
                'orders.billing_address_2',
                'orders.billing_alt_mobile_number',
                'orders.billing_place',
                'orders.billing_landmark',
                'orders.billing_pincode'
            )
            ->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')
            ->leftjoin('state as s', 's.state_id', '=', 'orders.billing_state_id')
            ->leftjoin('country as c', 'c.country_id', '=', 'orders.billing_country_id')
            ->leftjoin('district as d', 'd.district_id', '=', 'orders.billing_city_id')->first();
        // $get_role['product_details'] = OrderItems::where('order_items.order_id', $get_role->order_id)
        //     ->select('order_items.order_items_id', 'order_items.product_id', 'product.product_name', 'product.product_code', 'order_items.quantity', 'order_items.sub_total')
        //     ->leftjoin('product', 'product.product_id', '=', 'order_items.product_id')->get();
        $get_role['order_item_view'] = $this->trackOrderorderItemView($orderItemId);
        $count = $get_role->count();
        if ($count > 0) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Order tracked successfully'),
                'data' => [$get_role]
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => []
            ]);
        }
    }

    //myorder item view
    public function trackOrderorderItemView($orderItemId)
    {
        try {
            $orderItemList = OrderItems::where('order_items.order_items_id', $orderItemId)
                ->select('orders.order_code', 'orders.order_date', 'order_items.order_id', 'order_items.order_items_id', 'order_items.product_id', 'order_items.product_variant_id', 'order_items.service_id', 'order_items.quantity', 'order_items.sub_total', 'order_items.order_status', 'order_items.image', 'order_items.product_name', 'order_items.product_code', 'order_items.thumbnail_image', DB::raw('DATE_FORMAT(order_items.created_on, "%Y-%m-%d") as order_placed'), DB::raw('DATE_FORMAT(order_items.approved_on, "%Y-%m-%d") as approved_on'), DB::raw('DATE_FORMAT(order_items.disapproved_on, "%Y-%m-%d") as disapproved_on'), DB::raw('DATE_FORMAT(order_items.shipped_on, "%Y-%m-%d") as shipped_on'), DB::raw('DATE_FORMAT(order_items.dispatched_on, "%Y-%m-%d") as dispatched_on'), DB::raw('DATE_FORMAT(order_items.delivered_on, "%Y-%m-%d") as delivered_on'), DB::raw('DATE_FORMAT(order_items.cancelled_on, "%Y-%m-%d") as cancelled_on'))
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->orderBy('order_items.order_items_id', 'asc');

            $orderItemList = $orderItemList->get();

            if (!empty($orderItemList)) {
                $orderAry = [];
                foreach ($orderItemList as $value) {
                    $ary = [];

                    $ary['order_id'] = $value->order_id;
                    $ary['order_items_id'] = $value->order_items_id;
                    $ary['service_id'] = $value->service_id;
                    $ary['order_date'] = $value->order_date;
                    $ary['order_code'] = $value->order_code;
                    $ary['quantity'] = $value->quantity;
                    $ary['product_image'] = ($value->image != '') ? env('APP_URL') . env('ORDER_URL') . $value->image : env('APP_URL') . "avatar.jpg";
                    if ($value['service_id'] == 1) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 2) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 3) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 4) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 5) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 6) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    $ary['product_name'] = $value->product_name;
                    $ary['product_code'] = $value->product_code;
                    $ary['sub_total'] = $value->sub_total;
                    $ary['order_status'] = $value->order_status;
                    $expectDeliveryDate = ExpectedDays::where('status', 1)->select('expected_delivery_days')->first();
                    $ary['expected_days'] = $expectDeliveryDate->expected_delivery_days;
                    $ary['order_placed'] = $value->order_placed;
                    $ary['approved_on'] = $value->approved_on;
                    $ary['disapproved_on'] = $value->disapproved_on;
                    $ary['shipped_on'] = $value->shipped_on;
                    $ary['dispatched_on'] = $value->dispatched_on;
                    $ary['delivered_on'] = $value->delivered_on;
                    $ary['cancelled_on'] = $value->cancelled_on;
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

    //Waiting cod payments view
    public function track_order_view(Request $request, $ord_id)
    {

        $get_role = Orders::where('orders.order_id', $ord_id)
            ->select(
                'orders.order_id',
                'orders.order_code',
                'orders.order_date',
                'orders.payment_mode',
                'orders.bulk_order_enquiry_id',
                'orders.customer_id',
                'orders.payment_amount',
                'orders.payment_transcation_id',
                'customer.customer_first_name',
                'customer.customer_last_name',
                'customer.mobile_no',
                'customer.email',
                'orders.billing_gst_no',
                'orders.billing_courier_type',
                'orders.billing_customer_first_name',
                'orders.billing_customer_last_name',
                'orders.billing_email',
                'orders.billing_mobile_number',
                'orders.billing_alt_mobile_number',
                'orders.billing_country_id',
                'orders.billing_state_id',
                'orders.billing_city_id',
                'orders.billing_address_1',
                'orders.billing_address_2',
                'orders.billing_place',
                'orders.billing_landmark',
                'orders.billing_pincode'
            )
            ->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')
            ->leftjoin('state as s', 's.state_id', '=', 'orders.billing_state_id')
            ->leftjoin('country as c', 'c.country_id', '=', 'orders.billing_country_id')
            ->leftjoin('district as d', 'd.district_id', '=', 'orders.billing_city_id')->first();
        // $get_role['product_details'] = OrderItems::where('order_items.order_id', $get_role->order_id)
        //     ->select('order_items.order_items_id', 'order_items.product_id', 'product.product_name', 'product.product_code', 'order_items.quantity', 'order_items.sub_total')
        //     ->leftjoin('product', 'product.product_id', '=', 'order_items.product_id')->get();
        $get_role['order_item_list'] = $this->trackOrderorderList($ord_id);
        $count = $get_role->count();
        if ($count > 0) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Order tracked successfully'),
                'data' => [$get_role]
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => []
            ]);
        }
    }

    //myorder item view
    public function trackOrderorderList($ord_id)
    {
        try {
            $orderItemList = OrderItems::where('order_items.order_id', $ord_id)
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->select('orders.order_code', 'orders.order_date', 'order_items.order_id', 'order_items.order_items_id')
                ->orderBy('order_items.order_items_id', 'asc');

            $orderItemList = $orderItemList->get();

            if (!empty($orderItemList)) {
                $orderAry = [];
                foreach ($orderItemList as $value) {
                    $ary = [];

                    $ary['order_id'] = $value->order_id;
                    $ary['order_items_id'] = $value->order_items_id;
                    $ary['service_id'] = $value->service_id;
                    $ary['order_date'] = $value->order_date;
                    $ary['order_code'] = $value->order_code;
                    $ary['quantity'] = $value->quantity;
                    $ary['product_image'] = ($value->image != '') ? env('APP_URL') . env('ORDER_URL') . $value->image : env('APP_URL') . "avatar.jpg";
                    if ($value['service_id'] == 1) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 2) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 3) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 4) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 5) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 6) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    $ary['product_name'] = $value->product_name;
                    $ary['product_code'] = $value->product_code;
                    $ary['sub_total'] = $value->sub_total;
                    $ary['order_status'] = $value->order_status;
                    $expectDeliveryDate = ExpectedDays::where('status', 1)->select('expected_delivery_days')->first();
                    $ary['expected_days'] = $expectDeliveryDate->expected_delivery_days;
                    $ary['order_placed'] = $value->order_placed;
                    $ary['approved_on'] = $value->approved_on;
                    $ary['disapproved_on'] = $value->disapproved_on;
                    $ary['shipped_on'] = $value->shipped_on;
                    $ary['dispatched_on'] = $value->dispatched_on;
                    $ary['delivered_on'] = $value->delivered_on;
                    $ary['cancelled_on'] = $value->cancelled_on;
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

    //Product List
    public function Product_list($id)
    {
        try {
            if ($id != '' && $id > 0) {
                $product_list = OrderItems::where('order_items.order_id', $id)
                    ->whereIn('order_items.order_status', [2, 3, 9, 7])
                    ->leftJoin('product', 'order_items.product_id', '=', 'product.product_id')
                    ->leftJoin('cancel_reason', 'cancel_reason.cancel_reason_id', '=', 'order_items.cancel_reason_id')
                    ->select('order_items.*', 'product.product_name', 'cancel_reason.reason')->get();
                $count = $product_list->count();
                if ($count > 0) {
                    $final = [];
                    foreach ($product_list as $products) {
                        $ary = [];
                        $ary['order_items_id'] = $products['order_items_id'];
                        $ary['product_name'] = $products['product_name'];
                        $ary['product_code'] = $products['product_code'];
                        $ary['quantity'] = $products['quantity'];
                        $ary['price'] = sprintf("%.2f", $products['unit_price'] * $products['quantity']);
                        if ($products['service_id'] == 1) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 2) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 3) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 4) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 5) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 6) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        $ary['bill_no'] = $products['bill_no'];
                        if ($products['bill_no'] != "") {
                            $ary['bill_status'] = "Confirmed";
                        } else {
                            $ary['bill_status'] = "Waiting";
                        }
                        $ary['courier_no'] = $products['courier_no'];
                        $ary['courier_name'] = $products['courier_name'];
                        $ary['delivered_on'] = $products['delivered_on'];
                        $ary['cancelled_on'] = $products['cancelled_on'];
                        $ary['cancelled_reason'] = $products['reason'];
                        $ary['order_status'] = $products['order_status'];
                        if ($products['order_status'] == 2 || $products['order_status'] == 9) {
                            $ary['order_status'] = "Waiting for dispatch";
                        }
                        if ($products['order_status'] == 3) {
                            $ary['order_status'] = "Dispatched";
                        }
                        // if ($products['cod_status'] == 4 && $products['is_cod'] == 1 && $products['order_status'] == 3) {
                        //     $ary['order_status'] = "Dispatched";
                        // }
                        if ($products['order_status'] == 7) {
                            $ary['order_status'] = "Dispatch";
                        }
                        $final[] = $ary;
                    }
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("productslist")->info("view value :: $log");
                    Log::channel("productslist")->info('** end the productslist view method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Order item listed successfully'),
                        'data' => $final
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {
            Log::channel("dispatchproducts")->error($exception);
            Log::channel("dispatchproducts")->info('** end the dispatchproducts view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //cancelled product list
    public function cancelled_Product_list($id)
    {
        try {
            if ($id != '' && $id > 0) {
                $product_list = OrderItems::where('order_items.order_id', $id)
                    ->whereIn('order_items.order_status', [4, 6, 8])
                    ->leftJoin('product', 'order_items.product_id', '=', 'product.product_id')
                    ->leftJoin('cancel_reason', 'cancel_reason.cancel_reason_id', '=', 'order_items.cancel_reason_id')
                    ->select('order_items.*', 'cancel_reason.reason')->get();
                $count = $product_list->count();
                if ($count > 0) {
                    $final = [];
                    foreach ($product_list as $products) {
                        $ary = [];
                        $ary['order_items_id'] = $products['order_items_id'];
                        $ary['product_name'] = $products['product_name'];
                        $ary['product_code'] = $products['product_code'];
                        $ary['quantity'] = $products['quantity'];
                        $ary['price'] = sprintf("%.2f", $products['unit_price'] * $products['quantity']);
                        if ($products['service_id'] == 1) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 2) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 3) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 4) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 5) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 6) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        $ary['delivered_on'] = $products['delivered_on'];
                        $ary['cancelled_on'] = $products['cancelled_on'];
                        if ($products['cancel_reason_id'] != 7) {
                            $ary['cancelled_reason'] = $products['reason'];
                        } else {
                            $ary['cancelled_reason'] = $products['cancel_reason'];
                        }
                        $ary['order_status'] = $products['order_status'];
                        $final[] = $ary;
                    }
                }

                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("productslist")->info("view value :: $log");
                    Log::channel("productslist")->info('** end the productslist view method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Order item cancelled listed successfully'),
                        'data' => $final
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {
            Log::channel("dispatchproducts")->error($exception);
            Log::channel("dispatchproducts")->info('** end the dispatchproducts view method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //Delivery product list
    public function delivery_Product_list($id)
    {
        try {
            if ($id != '' && $id > 0) {
                $product_list = OrderItems::where('order_items.order_id', $id)
                    ->whereIn('order_items.order_status', [3, 5])
                    ->leftJoin('product', 'order_items.product_id', '=', 'product.product_id')
                    ->leftJoin('cancel_reason', 'cancel_reason.cancel_reason_id', '=', 'order_items.cancel_reason_id')
                    ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                    ->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')
                    ->select(
                        'order_items.*',
                        'cancel_reason.reason',
                        'customer.customer_first_name',
                        'customer.customer_last_name',
                    )->get();
                $count = $product_list->count();
                if ($count > 0) {
                    $final = [];
                    foreach ($product_list as $products) {
                        $ary = [];
                        $ary['order_items_id'] = $products['order_items_id'];
                        $ary['customer_name'] = !empty($products['customer_last_name']) ? $products['customer_first_name'] . ' ' . $products['customer_last_name'] : $products['customer_first_name'];
                        $ary['product_name'] = $products['product_name'];
                        $ary['product_code'] = $products['product_code'];
                        $ary['quantity'] = $products['quantity'];
                        $ary['price'] = sprintf("%.2f", $products['unit_price'] * $products['quantity']);
                        if ($products['service_id'] == 1) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 2) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 3) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 4) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 5) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        if ($products['service_id'] == 6) {
                            $ary['thumbnail_image_url'] = ($products['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $products['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        }
                        $ary['delivered_on'] = $products['delivered_on'];
                        $ary['bill_no'] = $products['bill_no'];
                        $ary['cancelled_on'] = $products['cancelled_on'];
                        $ary['cancelled_reason'] = $products['reason'];
                        $ary['order_status'] = $products['order_status'];
                        $final[] = $ary;
                    }
                }

                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("productslist")->info("view value :: $log");
                    Log::channel("productslist")->info('** end the productslist view method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Order item delivery listed successfully'),
                        'data' => $final
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {
            Log::channel("dispatchproducts")->error($exception);
            Log::channel("dispatchproducts")->info('** end the dispatchproducts view method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //myorderitem view
    public function orderItemView(Request $request, $ordId)
    {
        try {
            $orderView = OrderItems::where('order_items.order_items_id', $ordId)
                ->select('orders.order_code', 'orders.order_date', 'orders.customer_id', 'order_items.*', 'rating_review.review', 'rating_review.rating')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftJoin('rating_review', function ($leftJoin) use ($ordId) {
                    $leftJoin->on('rating_review.product_id', '=', 'order_items.product_id')
                        ->where('rating_review.order_id', $ordId);
                })->get();
            if (!empty($orderView)) {
                $orderAry = [];
                foreach ($orderView as $value) {
                    $ary = [];
                    $ary['order_id'] = $value->order_id;
                    $ary['customer_id'] = $value->customer_id;
                    $ary['order_items_id'] = $value->order_items_id;
                    $ary['service_id'] = $value->service_id;
                    $ary['order_date'] = $value->order_date;
                    $ary['order_code'] = $value->order_code;
                    $ary['product_id'] = $value->product_id;
                    $ary['product_name'] = $value->product_name;
                    $ary['product_code'] = $value->product_code;
                    $ary['is_customized'] = $value->is_customized;
                    $ary['image'] = $value['image'];
                    $ary['image_url'] = ($value['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $value['thumbnail_image'];
                    if ($value->service_id == 1) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value->service_id == 2) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value->service_id == 3) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value->service_id == 4) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value->service_id == 5) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value->service_id == 6) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    $ary['background_color'] = $value->background_color;
                    // $ary['variant_attributes'] = $this->getPersonalizedVariant(json_decode($value->variant_attributes, true));
                    $ary['variant_attributes'] = $this->getPersonalizedUpload($value->order_items_id, json_decode($value->variant_attributes, true));
                    $ary['variant_details'] = json_decode($value->pv_variant_attributes, true);
                    // $ary['frames'] = $this->getFrames(json_decode($value->frames, true));
                    $ary['frames'] = $this->getPhotoFrameUpload($value->order_items_id);
                    // $ary['photoprint_variant'] = $this->getPhotoPrintVariant(json_decode($value->photoprint_variant, true));
                    $ary['photoprint_variant'] = $this->getPhotoPrintUpload($value->order_items_id);
                    // $ary['images'] = $this->getProductImage(json_decode($value->images, true));
                    $ary['images'] = $this->getSelfieUpload($value->order_items_id);
                    $ary['quantity'] = $value->quantity;
                    $ary['sub_total'] = $value->unit_price * $value->quantity;
                    $ary['order_status'] = $value->order_status;
                    $ary['photoprint_width'] = $value->photoprint_width;
                    $ary['photoprint_height'] = $value->photoprint_height;
                    $ary['first_copy_selling_price'] = $value->first_copy_selling_price;
                    $ary['additional_copy_selling_price'] = $value->additional_copy_selling_price;
                    $expectDeliveryDate = ExpectedDays::where('status', 1)->select('expected_delivery_days')->first();
                    $ary['expected_days'] = $expectDeliveryDate->expected_delivery_days;
                    $taskManager = TaskManager::where('order_items_id', $value->order_items_id)->first();
                    $ary['task_manager_status'] = !empty($taskManager) ? $taskManager->current_task_stage : null;
                    $ary['variant_type_name'] = $value->variant_type_name;
                    $ary['variant_label'] = $value->variant_label;
                    $message = Messages::where('order_items_id', $value->order_items_id)->first();
                    $ary['is_chat_available'] = !empty($message) ? true : false;
                    // $messagesEmployeeId = Messages::where('messages.order_items_id', $value->order_items_id)->where('chat_user.user_type', '=', 'employee')->where('to.user_type', '=', 'customer')
                    //     ->leftjoin('chat_user', 'chat_user.chat_user_id', '=', 'messages.fromUserId')
                    //     ->leftjoin('chat_user as to', 'to.chat_user_id', '=', 'messages.toUserId')
                    //     ->select('chat_user.chat_user_id', 'chat_user.table_unique_id', 'messages.fromUserId', 'messages.id')->orderBy('messages.created_at', 'desc')->first();
                    $messagesEmployeeId = Messages::where('messages.order_items_id', $value->order_items_id)
                        ->leftJoin('chat_user', function ($leftJoin) {
                            $leftJoin->on('chat_user.chat_user_id', '=', 'messages.fromUserId')
                                ->where('chat_user.user_type', '=', 'employee');
                        })
                        ->leftJoin('chat_user as to', function ($leftJoin) {
                            $leftJoin->on('to.chat_user_id', '=', 'messages.touserId')
                                ->where('to.user_type', '=', 'employee');
                        })
                        ->select('chat_user.chat_user_id',  'chat_user.user_type as from_user_type', 'to.user_type as to_user_type', 'chat_user.table_unique_id as from_employee_id', 'to.table_unique_id as to_employee_id', 'messages.fromUserId', 'messages.id')->orderBy('messages.created_at', 'desc')->first();
                    if (!empty($messagesEmployeeId)) {
                        $ary['message_employee_id'] = !empty($messagesEmployeeId->from_employee_id) ? $messagesEmployeeId->from_employee_id : $messagesEmployeeId->to_employee_id;
                        $ary['message_id'] = !empty($messagesEmployeeId) ? $messagesEmployeeId->id : null;
                    } else {
                        $ary['message_employee_id'] = null;
                        $ary['message_id'] = null;
                    }
                    $orderAry[] = $ary;
                }
            }

            if (!empty($orderAry)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('My order item viewed successfully'),
                    'data' => $orderAry
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No Data'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function waitingdelivery_list(Request $request)
    {
        try {
            Log::channel("waitingdispatch")->info('** started the waitingdispatch list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';


            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'order_date' => 'orders.order_date',
                'order_id' => 'orders.order_id',
                'order_code' => 'orders.order_code',
                // 'customer_name' => 'customer.customer_first_name',
                'customer_name' => DB::raw('GROUP_CONCAT(CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name, bulk_order_enquiry.contact_person_name) SEPARATOR " ")'),
                // 'mobile_no' => 'customer.mobile_no',
                'mobile_no' => DB::raw('GROUP_CONCAT(CONCAT_WS(" ", customer.mobile_no, bulk_order_enquiry.mobile_no) SEPARATOR " ")'),
                'order_from' => 'orders.order_from',
                'order_amount' => 'orders.order_totalamount',
                'payment_transaction_date' => 'orders.payment_transaction_date'
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'orders.order_date', 'orders.order_id', DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'),
                'customer.mobile_no', 'orders.order_from', 'orders.order_totalamount', 'orders.order_code', 'orders.payment_transaction_date', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no'
            );
            $waitingcod = Orders::select(
                'orders.order_id',
                'orders.order_code',
                'orders.customer_id',
                'orders.bulk_order_enquiry_id',
                DB::raw('DATE_FORMAT(orders.order_date, "%d-%m-%Y") as
                order_date'),
                'customer.customer_first_name',
                'customer.customer_last_name',
                'customer.mobile_no',
                'bulk_order_enquiry.contact_person_name',
                'bulk_order_enquiry.mobile_no as bulk_order_mobile_no',
                'orders.order_from',
                'orders.payment_mode',
                'orders.order_totalamount',
                'orders.payment_amount as paid_amount',
                'orders.payment_transcation_id',
                'orders.shipping_cost',
                'orders.coupon_amount',
                DB::raw('DATE_FORMAT(orders.payment_transaction_date, "%d-%m-%Y") as payment_transaction_date'),
                'orders.is_cod',
                'order_items.order_status',
                'order_items.cod_status'
            )
                // ->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')
                ->leftJoin('customer', function ($leftJoin) {
                    $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                        ->where('orders.customer_id', '!=', NULL);
                })
                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                        ->where('orders.customer_id', '=', NULL);
                })
                ->leftjoin('order_items', 'order_items.order_id', '=', 'orders.order_id')
                ->whereIn('order_items.order_status', [3, 5])
                ->groupBy('orders.order_id');
            $waitingcod->where(function ($query) use ($searchval, $column_search, $waitingcod) {
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
                $waitingcod->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $waitingcod->where(function ($query) use ($from_date) {
                    $query->whereDate('orders.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $waitingcod->where(function ($query) use ($to_date) {
                    $query->whereDate('orders.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $waitingcod->where('orders.is_cod', $filterByStatus);
            }
            $count = $waitingcod->count();
            if ($offset) {
                $offset = $offset * $limit;
                $waitingcod->offset($offset);
            }
            if ($limit) {
                $waitingcod->limit($limit);
            }
            Log::channel("waitingdispatch")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType:: $from_date:: $to_date::");
            $waitingcod->orderBy('orders.order_id', 'desc');
            $waitingcod = $waitingcod->get();
            $final = [];
            $count = $waitingcod->count();

            if ($count > 0) {
                foreach ($waitingcod as $value) {
                    $ary = [];
                    $ary['order_id'] = $value['order_id'];
                    $ary['order_code'] = $value['order_code'];
                    $ary['order_date'] = $value['order_date'];
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['bulk_order_enquiry_id'] = $value['bulk_order_enquiry_id'];
                    if (!empty($value['customer_id'] != '')) {
                        $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                        $ary['mobile_no'] = $value['mobile_no'];
                    } else {
                        $ary['customer_name'] = $value['contact_person_name'];
                        $ary['mobile_no'] = $value['bulk_order_mobile_no'];
                    }
                    $ary['order_from'] = $value['order_from'];
                    // $ary['order_amount'] = $value['order_totalamount'];
                    $itemCount = OrderItems::where('order_id', $value['order_id'])->count();
                    if ($itemCount == 1) {
                        $subtotal = OrderItems::where('order_id', $value->order_id)->sum('sub_total');
                        $ary['order_amount'] = $value['paid_amount'];
                    } else {
                        $subtotal = OrderItems::where('order_id', $value->order_id)->where('order_status', '!=', 4)->sum('sub_total');
                        $ary['order_amount'] = $value['paid_amount'];
                    }
                    $ary['payment_mode'] = $value['payment_mode'];
                    $ary['transaction_id'] = $value['payment_transcation_id'];
                    $ary['payment_transaction_date'] = $value['payment_transcation_date'];
                    $ary['order_status'] = $value['order_status'];
                    $ary['cod_status'] = $value['cod_status'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("waitingcod")->info("list value :: $log");
                Log::channel("waitingcod")->info('** end the waitingcod list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Delivery listed successfully'),
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
            Log::channel("waitingdispatch")->error($exception);
            Log::channel("waitingdispatch")->error('** end the waitingdispatch list method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function cancelledList(Request $request)
    {
        try {
            Log::channel("waitingdispatch")->info('** started the waitingdispatch list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'OrderDate' => 'orders.order_date',
                'order_id' => 'orders.order_id',
                'order_code' => 'orders.order_code',
                'customer_Name' => 'customer.customer_first_name',
                'payment_mode' => 'orders.payment_mode',
                'mobileNo' => 'customer.mobile_no',
                'order_from' => 'orders.order_from',
                'order_amount' => 'orders.order_totalamount',
                'payment_transaction_date' => 'orders.payment_transaction_date'
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                DB::raw('DATE_FORMAT(orders.order_date, "%d-%m-%Y")'), 'orders.order_id', 'customer.customer_first_name', DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'),
                'customer.mobile_no', 'orders.order_from', 'orders.order_totalamount', 'orders.order_code', 'orders.payment_transaction_date', 'orders.payment_transcation_id',
                'orders.payment_mode',
            );
            $waitingcod = Orders::select(
                'orders.order_id',
                'orders.order_code',
                DB::raw('DATE_FORMAT(orders.order_date, "%d-%m-%Y") as
                order_date'),
                'customer.customer_first_name',
                'customer.customer_last_name',
                'customer.mobile_no',
                'orders.order_from',
                'orders.payment_mode',
                'orders.order_totalamount',
                'orders.payment_amount as paid_amount',
                'orders.payment_transcation_id',
                'orders.shipping_cost',
                'orders.coupon_amount',
                DB::raw('DATE_FORMAT(orders.payment_transaction_date, "%d-%m-%Y") as payment_transaction_date'),
                'orders.is_cod',
                'order_items.order_status',
                'order_items.cod_status'
            )
                ->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')
                ->leftjoin('order_items', 'order_items.order_id', '=', 'orders.order_id')
                ->whereIn('order_items.order_status', [4, 6, 8])
                ->groupBy('orders.order_id');
            $waitingcod->where(function ($query) use ($searchval, $column_search, $waitingcod) {
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
                $waitingcod->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $waitingcod->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $waitingcod->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $waitingcod->where('is_cod', $filterByStatus);
            }
            $count = count($waitingcod->get());
            if ($offset) {
                $offset = $offset * $limit;
                $waitingcod->offset($offset);
            }
            if ($limit) {
                $waitingcod->limit($limit);
            }
            Log::channel("waitingdispatch")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType:: $from_date:: $to_date::");
            $waitingcod->orderBy('order_id', 'desc');
            $waitingcod = $waitingcod->get();
            $final = [];

            if ($count > 0) {
                foreach ($waitingcod as $value) {
                    $ary = [];
                    $ary['order_id'] = $value['order_id'];
                    $ary['order_code'] = $value['order_code'];
                    $ary['order_date'] = $value['order_date'];
                    $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    $ary['mobile_no'] = $value['mobile_no'];
                    $ary['order_from'] = $value['order_from'];
                    // $ary['order_amount'] = $value['order_totalamount'];
                    $itemCount = OrderItems::where('order_id', $value['order_id'])->count();
                    if ($itemCount == 1) {
                        $subtotal = OrderItems::where('order_id', $value->order_id)->sum('sub_total');
                        $ary['order_amount'] = $value['paid_amount'];
                    } else {
                        $subtotal = OrderItems::where('order_id', $value->order_id)->where('order_status', '!=', 4)->sum('sub_total');
                        $ary['order_amount'] = $value['paid_amount'];
                    }
                    $ary['payment_mode'] = $value['payment_mode'];
                    $ary['transaction_id'] = $value['payment_transcation_id'];
                    $ary['payment_transaction_date'] = $value['payment_transcation_date'];
                    $ary['order_status'] = $value['order_status'];
                    $ary['cod_status'] = $value['cod_status'];
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("waitingcod")->info("list value :: $log");
                Log::channel("waitingcod")->info('** end the waitingcod list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Cancelled orders listed successfully'),
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
            Log::channel("waitingdispatch")->error($exception);
            Log::channel("waitingdispatch")->error('** end the waitingdispatch list method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function trackOrder_old(Request $request)
    {
        $id = $request->order_code;
        if (!empty($id)) {
            $ord_id = Orders::where('order_code', $id)->first();
            if (!empty($ord_id->order_id)) {
                $get_order = Orders::where('orders.order_id', $ord_id->order_id)
                    ->select(
                        'orders.order_id',
                        'orders.order_code',
                        'orders.order_date',
                        'orders.payment_mode',
                        'orders.payment_amount',
                        'orders.payment_transcation_id',
                        'customer.customer_first_name',
                        'customer.customer_last_name',
                        'customer.mobile_no',
                        'customer.email',
                        'orders.billing_gst_no',
                        'orders.billing_courier_type',
                        'orders.billing_customer_first_name',
                        'orders.billing_customer_last_name',
                        'orders.billing_email',
                        'orders.billing_mobile_number',
                        'orders.billing_country_id',
                        'orders.billing_state_id',
                        'orders.billing_city_id',
                        'orders.billing_address_1',
                        'orders.billing_landmark',
                        'orders.billing_pincode',
                        'd.district_name',
                        's.state_name',
                        'c.name as country_name',
                    )
                    ->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')
                    ->leftjoin('state as s', 's.state_id', '=', 'orders.billing_state_id')
                    ->leftjoin('country as c', 'c.country_id', '=', 'orders.billing_country_id')
                    ->leftjoin('district as d', 'd.district_id', '=', 'orders.billing_city_id')->first();
                $get_order['order_item_view'] = $this->orderItemDetailList($ord_id->order_id);
                $count = $get_order->count();
                if ($count > 0) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Order tracked successfully'),
                        'data' => [$get_order]
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
                    'message' => ('Invalid order code'),
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

    public function trackOrder(Request $request)
    {
        $id = $request->order_code;
        if (!empty($id)) {
            $ord_id = Orders::where('order_code', $id)->first();
            if (!empty($ord_id->order_id)) {
                $get_order = Orders::where('orders.order_id', $ord_id->order_id)
                    ->select(
                        'orders.order_id',
                        'orders.order_code',
                        'orders.order_date',
                        'orders.payment_mode',
                        'orders.payment_amount',
                        'orders.payment_transcation_id',
                        'orders.customer_id',
                        'orders.bulk_order_enquiry_id',
                        'customer.customer_first_name',
                        'customer.customer_last_name',
                        'customer.mobile_no',
                        'customer.email',
                        'orders.billing_gst_no',
                        'orders.billing_courier_type',
                        'orders.billing_customer_first_name',
                        'orders.billing_customer_last_name',
                        'orders.billing_email',
                        'orders.billing_mobile_number',
                        'orders.billing_alt_mobile_number',
                        'orders.billing_country_id',
                        'orders.billing_state_id',
                        'orders.billing_city_id',
                        'orders.billing_address_1',
                        'orders.billing_address_2',
                        'orders.billing_landmark',
                        'orders.billing_pincode',
                        'orders.shipping_cost',
                        'orders.coupon_amount',
                        'orders.purchase_document',
                        'd.district_name',
                        's.state_name',
                        'c.name as country_name',
                    )
                    ->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')
                    ->leftjoin('state as s', 's.state_id', '=', 'orders.billing_state_id')
                    ->leftjoin('country as c', 'c.country_id', '=', 'orders.billing_country_id')
                    ->leftjoin('district as d', 'd.district_id', '=', 'orders.billing_city_id')->first();
                $OrdDetail = [];
                if (!empty($get_order)) {
                    $ary = [];
                    $ary['order_id'] = $get_order->order_id;
                    $ary['order_code'] = $get_order->order_code;
                    $ary['order_date'] = $get_order->order_date;
                    $ary['payment_mode'] = $get_order->payment_mode;
                    $ary['purchase_document'] = $get_order->purchase_document;
                    $ary['purchase_document_url'] = ($get_order['purchase_document'] != '') ? env('APP_URL') . env('ORDER_URL') . $get_order['purchase_document'] : "";
                    // $ary['payment_amount'] = $get_order->payment_amount;
                    $itemCount = OrderItems::where('order_id', $get_order->order_id)->count();
                    if ($itemCount == 1) {
                        $subtotal = OrderItems::where('order_id', $get_order->order_id)->sum('sub_total');
                        $ary['payment_amount'] = $get_order->payment_amount;
                    } else {
                        $subtotal = OrderItems::where('order_id', $get_order->order_id)->where('order_status', '!=', 4)->sum('sub_total');
                        $ary['payment_amount'] = $get_order->payment_amount;
                    }
                    $ary['customer_id'] = $get_order->customer_id;
                    $ary['bulk_order_enquiry_id'] = $get_order->bulk_order_enquiry_id;
                    $ary['payment_transcation_id'] = $get_order->payment_transcation_id;
                    $ary['customer_first_name'] = $get_order->customer_first_name;
                    $ary['customer_last_name'] = $get_order->customer_last_name;
                    $ary['mobile_no'] = $get_order->mobile_no;
                    $ary['email'] = $get_order->email;
                    $ary['billing_gst_no'] = $get_order->billing_gst_no;
                    $ary['billing_courier_type'] = $get_order->billing_courier_type;
                    $ary['billing_customer_first_name'] = $get_order->billing_customer_first_name;
                    $ary['billing_customer_last_name'] = $get_order->billing_customer_last_name;
                    $ary['billing_email'] = $get_order->billing_email;
                    $ary['billing_mobile_number'] = $get_order->billing_mobile_number;
                    $ary['billing_alt_mobile_number'] = $get_order->billing_alt_mobile_number;
                    $ary['billing_alt_mobile_number'] = $get_order->billing_alt_mobile_number;
                    $ary['billing_country_id'] = $get_order->billing_country_id;
                    $ary['billing_state_id'] = $get_order->billing_state_id;
                    $ary['billing_city_id'] = $get_order->billing_city_id;
                    $ary['billing_address_1'] = $get_order->billing_address_1;
                    $ary['billing_address_2'] = $get_order->billing_address_2;
                    $ary['billing_landmark'] = $get_order->billing_landmark;
                    $ary['billing_pincode'] = $get_order->billing_pincode;
                    $ary['district_name'] = $get_order->district_name;
                    $ary['state_name'] = $get_order->state_name;
                    $ary['country_name'] = $get_order->country_name;
                    $ary['order_item_view'] = $this->orderItemDetailList($get_order->order_id);
                    $ary['pg_link_history'] = $this->pgLinkHistoryDetails($get_order->order_id);
                    $OrdDetail[] = $ary;
                }
                // $count = $get_order->count();
                if (!empty($OrdDetail)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Order tracked successfully'),
                        'data' => $OrdDetail
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
                    'message' => ('Invalid order code'),
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

    public function pgLinkHistoryDetails($ordId)
    {
        try {
            $pgLinkDetails = PgLinkHistory::where('order_id', $ordId)->where('payment_status', "!=", 0)->orderBy('pg_link_history_id', 'desc')->get();
            if (!empty($pgLinkDetails)) {
                $pgLinkAry = [];
                foreach ($pgLinkDetails as $value) {
                    $ary = [];
                    $ary['transaction_id'] = $value->transaction_id;
                    $ary['transaction_mode'] = $value->transaction_mode;
                    $carbonDate = Carbon::parse($value->updated_on);
                    $modifiedDate = $carbonDate->addDays(2);
                    $formattedDate = $modifiedDate->format('d/m/Y H:i:s');
                    $dateTime = Carbon::createFromFormat('d/m/Y H:i:s', $formattedDate);
                    $formattedDate = $dateTime->format('d-m-Y h:i A');
                    $ary['created_on'] = $formattedDate;
                    $ary['amount'] = $value->amount;
                    if ($value->payment_status == 0) {
                        $ary['payment_status'] = "unpaid";
                    } else if ($value->payment_status == 1) {
                        $ary['payment_status'] = "Paid";
                    } else if ($value->payment_status == 2) {
                        $ary['payment_status'] = "Failure";
                    }
                    $pgLinkAry[] = $ary;
                }
            }
            return $pgLinkAry;
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function orderItemDetailList($ordId)
    {
        try {
            $orderView = OrderItems::where('order_items.order_id', $ordId)
                ->select('orders.order_code', 'orders.order_date', 'order_items.order_id', 'order_items.order_items_id', 'rating_review.review', 'rating_review.rating', 'order_items.product_id', 'order_items.product_variant_id', 'order_items.service_id', 'order_items.quantity', 'order_items.sub_total', 'order_items.unit_price', 'order_items.order_status', 'order_items.image', 'order_items.product_name', 'order_items.product_code', 'order_items.thumbnail_image', DB::raw('DATE_FORMAT(order_items.created_on, "%Y-%m-%d") as order_placed'), DB::raw('DATE_FORMAT(order_items.approved_on, "%Y-%m-%d") as approved_on'), DB::raw('DATE_FORMAT(order_items.disapproved_on, "%Y-%m-%d") as disapproved_on'), DB::raw('DATE_FORMAT(order_items.shipped_on, "%Y-%m-%d") as shipped_on'), DB::raw('DATE_FORMAT(order_items.dispatched_on, "%Y-%m-%d") as dispatched_on'), DB::raw('DATE_FORMAT(order_items.delivered_on, "%Y-%m-%d") as delivered_on'), DB::raw('DATE_FORMAT(order_items.cancelled_on, "%Y-%m-%d") as cancelled_on'), 'order_items.first_copy_selling_price', 'order_items.additional_copy_selling_price')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftJoin('rating_review', function ($leftJoin) use ($ordId) {
                    $leftJoin->on('rating_review.product_id', '=', 'order_items.product_id')
                        ->where('rating_review.order_id', $ordId);
                })->orderBy('order_items.order_items_id', 'desc')->get();
            if (!empty($orderView)) {
                $orderAry = [];
                foreach ($orderView as $value) {
                    $ary = [];
                    $ary['order_id'] = $value->order_id;
                    $ary['order_items_id'] = $value->order_items_id;
                    $ary['service_id'] = $value->service_id;
                    $ary['order_date'] = $value->order_date;
                    $ary['order_code'] = $value->order_code;
                    $ary['quantity'] = $value->quantity;
                    $ary['product_image'] = ($value->image != '') ? env('APP_URL') . env('ORDER_URL') . $value->image : env('APP_URL') . "avatar.jpg";
                    if ($value['service_id'] == 1) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 2) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                        $ary['first_copy_selling_price'] = $value['first_copy_selling_price'];
                        $ary['additional_copy_selling_price'] = $value['additional_copy_selling_price'];
                    }
                    if ($value['service_id'] == 3) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 4) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 5) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 6) {
                        $ary['thumbnail_image'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    $ary['product_name'] = $value->product_name;
                    $ary['product_code'] = $value->product_code;
                    $ary['sub_total'] = $value->unit_price * $value->quantity;
                    $ary['order_status'] = $value->order_status;
                    $expectDeliveryDate = ExpectedDays::where('status', 1)->select('expected_delivery_days')->first();
                    $ary['expected_days'] = $expectDeliveryDate->expected_delivery_days;
                    $ary['order_placed'] = $value->order_placed;
                    $ary['approved_on'] = $value->approved_on;
                    $ary['disapproved_on'] = $value->disapproved_on;
                    $ary['shipped_on'] = $value->shipped_on;
                    $ary['dispatched_on'] = $value->dispatched_on;
                    $ary['delivered_on'] = $value->delivered_on;
                    $ary['cancelled_on'] = $value->cancelled_on;
                    $message = Messages::where('order_items_id', $value->order_items_id)->first();
                    $ary['is_chat_available'] = !empty($message) ? true : false;
                    $messagesEmployeeId = Messages::where('messages.order_items_id', $value->order_items_id)
                        ->leftJoin('chat_user', function ($leftJoin) {
                            $leftJoin->on('chat_user.chat_user_id', '=', 'messages.fromUserId')
                                ->where('chat_user.user_type', '=', 'employee');
                        })
                        ->leftJoin('chat_user as to', function ($leftJoin) {
                            $leftJoin->on('to.chat_user_id', '=', 'messages.touserId')
                                ->where('to.user_type', '=', 'employee');
                        })
                        ->select('chat_user.chat_user_id',  'chat_user.user_type as from_user_type', 'to.user_type as to_user_type', 'chat_user.table_unique_id as from_employee_id', 'to.table_unique_id as to_employee_id', 'messages.fromUserId', 'messages.id')->orderBy('messages.created_at', 'desc')->first();
                    if (!empty($messagesEmployeeId)) {
                        $ary['message_employee_id'] = !empty($messagesEmployeeId->from_employee_id) ? $messagesEmployeeId->from_employee_id : $messagesEmployeeId->to_employee_id;
                        $ary['message_id'] = !empty($messagesEmployeeId) ? $messagesEmployeeId->id : null;
                    } else {
                        $ary['message_employee_id'] = null;
                        $ary['message_id'] = null;
                    }
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

    //Cod Status
    public function codorderStatusUpdate(Request $request)
    {
        $id = $request->id;
        if (!empty($id)) {
            if ($request->cod_status == 2) {
                $userId = JwtHelper::getSesUserId();
                $order = Orders::where('order_id', $id)->first();
                $user = Customer::where('customer_id', $order->customer_id)->select('customer.*')->first();
                $update = OrderItems::whereIn('service_id', [1, 2])->where('order_id', $id)->update(array(
                    // 'cod_status' => $request->cod_status,
                    // 'order_status' => 9,
                    // 'production_status' => 1,
                    'cod_status' => $request->cod_status,
                    'order_status' => 10,
                    'approved_on' => Server::getDateTime(),
                    'updated_on' => Server::getDateTime(),
                ));
                $update = OrderItems::whereIn('service_id', [5])->where('order_id', $id)->update(array(
                    'cod_status' => $request->cod_status,
                    'order_status' => 9,
                    'production_status' => 1,
                    'approved_on' => Server::getDateTime(),
                    'updated_on' => Server::getDateTime(),
                ));
                $update = OrderItems::whereIn('service_id', [3, 4, 6])->where('order_id', $id)->update(array(
                    'cod_status' => $request->cod_status,
                    'order_status' => 10,
                    'approved_on' => Server::getDateTime(),
                    'updated_on' => Server::getDateTime(),
                ));

                // Activity Log
                $desc =  'Waiting COD - This ' . $order->order_code . ' is approved by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Waiting COD');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                //Mail for user
                $mail = $this->getCreatorMail($order->order_id);
                $mail_data = [];
                $mail_data['email'] = $order->billing_email;
                $mail_data['name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data['order_code'] = $order->order_code;
                $mail_data['order_date'] = date("d-m-Y", strtotime($order->order_date));
                $mail_data['billing_customer_name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data['order_totalamount'] = $order->order_totalamount;
                $mail_data['payment_amount'] = $order->payment_amount;
                $mail_data['mail_type'] = 1;
                if ($order->billing_email != '') {
                    event(new SendCodApproved($mail_data));
                }

                //Mail for admin
                $admin_recipient = UserModel::where('acl_user_id', 1)->select('acl_user.*')->first();

                $getSesIdname = UserModel::where('acl_user_id', $userId)->select('acl_user.*')->first();

                $mail_data_for_admin = [];
                $mail_data_for_admin['email'] = $admin_recipient->email;
                $mail_data_for_admin['name'] = $getSesIdname->name;
                $mail_data_for_admin['order_code'] = $order->order_code;
                $mail_data_for_admin['order_date'] = date("d-m-Y", strtotime($order->order_date));
                $mail_data_for_admin['billing_customer_name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data_for_admin['order_totalamount'] = $order->order_totalamount;
                $mail_data_for_admin['mail_type'] = 2;
                if ($admin_recipient->email != '') {
                    // event(new SendCodApproved($mail_data_for_admin));
                }

                //Send notification for Customer
                $title = "Order Confirmed" . " - " . $order->order_code;
                $body = "Your order (" . $order->order_code . ") is confirmed, we will process your order in next stage.";

                $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);


                $module = 'order_confirmed';
                $page = 'order_confirmation';
                $url = "account/orders/order-view?";

                $data = [
                    'order_id' => $order->order_id,
                    'order_code' => $order->order_code,
                    'random_id' => $random_id,
                    'page' => $page
                ];

                $data2 = [
                    'order_id' => $order->order_id,
                    'order_code' => $order->order_code,
                    'random_id' => $random_id2,
                    'url' => $url,
                    'page' => $page
                ];

                $portal1 = 'mobile';
                $portal2 = 'website';

                if ($order->customer_id != '') {


                    $customer_recipient = Customer::where('customer_id', $order->customer_id)->first();

                    if ($customer_recipient->token != '') {
                        $message = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data2,
                            'portal' => $portal2
                        ];
                        $customer_key = $customer_recipient->token;
                        $receiver_id = $customer_recipient->customer_id;
                        $push = Firebase::sendSingle($customer_key, $message);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal2, $data2, $random_id2);
                    if ($customer_recipient->mbl_token != '') {

                        $message2 = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data,
                            'portal' => $portal1
                        ];

                        $customer_key = $customer_recipient->mbl_token;
                        $receiver_id = $customer_recipient->customer_id;

                        $push = Firebase::sendSingleMbl($customer_key, $message2);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal1, $data, $random_id);
                }

                $msg = "Your order cod has approved!
                 Here is your Order ID - (" . $order->order_code . ") on Sat, 26 Feb, 2022 03:35pm
                 This transaction cannot be cancelled as per PRINTAPP Order cancellation policy.";
                $isSmsSent = GlobalHelper::sendSms($order->billing_mobile_number, $msg);
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Waiting cod approved successfully',
                    'data' => []
                ]);
            }
            if ($request->cod_status == 3) {
                $userId = JwtHelper::getSesUserId();
                $order = Orders::where('order_id', $id)->first();
                $user = Customer::where('customer_id', $order->created_by)->select('customer.*')->first();
                $update = OrderItems::where('order_id', $id)->update(array(
                    'cod_status' => $request->cod_status,
                    'order_status' => 8,
                    'production_status' => 1,
                    'cancelled_on' => Server::getDateTime(),
                    'updated_on' => Server::getDateTime(),
                ));

                // Activity Log
                $desc =  'Waiting COD - This ' . $order->order_code . ' is disapproved by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Waiting COD');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                $mail_data = [];
                $mail_data['email'] = $order->billing_email;
                $mail_data['name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data['order_code'] = $order->order_code;
                $mail_data['order_date'] = date("d-m-Y", strtotime($order->order_date));
                $mail_data['billing_customer_name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data['order_totalamount'] = $order->order_totalamount;
                $mail_data['payment_amount'] = $order->payment_amount;
                $mail_data['mail_type'] = 1;
                if ($order->billing_email != '') {
                    event(new SendCodDisapproved($mail_data));
                }

                //Mail for admin
                $admin_recipient = UserModel::where('acl_user_id', $userId)->select('acl_user.*')->first();

                $getSesIdname = UserModel::where('acl_user_id', $userId)->select('acl_user.*')->first();

                $mail_data_for_admin = [];
                $mail_data_for_admin['email'] = $admin_recipient->email;
                $mail_data_for_admin['name'] = $getSesIdname->name;
                $mail_data_for_admin['order_code'] = $order->order_code;
                $mail_data_for_admin['order_date'] = date("d-m-Y", strtotime($order->order_date));
                $mail_data_for_admin['billing_customer_name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data_for_admin['order_totalamount'] = $order->order_totalamount;
                $mail_data_for_admin['mail_type'] = 2;
                if ($admin_recipient->email != '') {
                    // event(new SendCodDisapproved($mail_data_for_admin));
                }

                //Send notification for Customer
                $title = "Order Cancelled" . " - " . $order->order_code;
                $body = "Your order (" . $order->order_code . ") is cancelled, we will process the refund soon.";

                $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);


                $module = 'order_cancelled';
                $page = 'order_cancelled';

                $data = [
                    'order_id' => $order->order_id,
                    'order_code' => $order->order_code,
                    'random_id' => $random_id,
                    'page' => $page
                ];


                $url = "account/orders/order-view?";

                $data2 = [
                    'order_id' => $order->order_id,
                    'order_code' => $order->order_code,
                    'random_id' => $random_id2,
                    'url' => $url,
                    'page' => $page
                ];


                $portal1 = 'mobile';
                $portal2 = 'website';

                if ($order->customer_id != "") {


                    $customer_recipient = Customer::where('customer_id', $order->customer_id)->first();



                    if ($customer_recipient->token != '') {
                        $message = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data2,
                            'portal' => $portal2
                        ];
                        $customer_key = $customer_recipient->token;
                        $receiver_id = $customer_recipient->customer_id;
                        $push = Firebase::sendSingle($customer_key, $message);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal2, $data2, $random_id2);
                    if ($customer_recipient->mbl_token != '') {

                        $message2 = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data,
                            'portal' => $portal1
                        ];

                        $customer_key = $customer_recipient->mbl_token;
                        $receiver_id = $customer_recipient->customer_id;

                        $push = Firebase::sendSingleMbl($customer_key, $message2);
                    }

                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal1, $data, $random_id);
                }

                //Sms
                $order = Orders::where('orders.order_id', $id)->leftJoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->select('orders.*', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no')->first();

                $customerName = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $WEBSITE_URL = env('WEBSITE_URL');

                //furture change this sms
                // $msg =  "Dear $customerName, Thanks for shopping with #VAR2#!! We’re sorry that, due to this reason #VAR3# Your order $order->order_code has been cancelled with us. We have started the refund process for your order, the details will be updated soon.For more detail: $WEBSITE_URL Team Print App";
                // $isSmsSent = GlobalHelper::sendSMS($order->billing_mobile_number, $msg);

                //Order Quantity
                $check = OrderItems::where('order_id', $id)->whereIn('service_id', [4, 5])->get();
                if (!empty($check)) {
                    for ($i = 0; $i < count($check); $i++) {
                        $product = ProductVariant::where('product_variant_id', $check[$i]['product_variant_id'])->first();

                        $quantity = $product->quantity + $check[$i]['quantity'];
                        $product_update = ProductVariant::where('product_variant_id', $check[$i]['product_variant_id'])->update(array(
                            'quantity' => $quantity,
                            'updated_on' => Server::getDateTime()
                        ));
                    }
                }

                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Waiting cod disapproved successfully',
                    'data' => []
                ]);
            }
            if ($request->cod_status == 1) {
                // $id = JwtHelper::getSesUserId();
                // $order = Orders::where('order_id', $id)->first();
                // $user = UserModel::where('acl_user_id', $id)->select('acl_user.*')->first();
                $update = OrderItems::where('order_id', $id)->update(array(
                    'cod_status' => $request->cod_status,
                    'order_status' => 0,
                    'disapproved_on' => NULL,
                    // 'cod_revoked_on' => Server::getDateTime(),
                    'updated_on' => Server::getDateTime()
                ));
                // $mail_data = [];
                // $mail_data['email'] = $user->email;
                // $mail_data['to'] = "info@kamesh@technogenesis.in";
                // $mail_data['name'] = $user->name;
                // $mail_data['order_code'] = $order->order_code;
                // $mail_data['order_date'] = date("d-m-Y", strtotime($order->order_date));
                // $mail_data['billing_customer_first_name'] = $order->billing_customer_first_name;
                // $mail_data['billing_customer_last_name'] = $order->billing_customer_last_name;
                // $mail_data['order_totalamount'] = $order->order_totalamount;
                // if ($user->email != '') {
                //     event(new SendCodRevoke($mail_data));
                // }
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Waiting cod revoked successfully',
                    'data' => []
                ]);
            }
        } else {
            return response()
                ->json([
                    'keyword' => 'failed',
                    'message' => __('Cod status failed'),
                    'data' => []
                ]);
        }
    }

    public function getCreatorMail($order_id)
    {
        $order = Orders::where('order_id', $order_id)->first();
        $email = "";
        $customer_recipient = Customer::where('customer_id', $order->created_by)->first();
        $email = $order->billing_email;
        return $email;
    }

    //Online status
    public function orderStatusUpdate(Request $request)
    {
        $id = $request->id;

        if (!empty($id)) {

            if ($request->order_status == 2) {
                $userId = JwtHelper::getSesUserId();
                $order = Orders::where('order_id', $id)->first();

                $update = OrderItems::whereIn('service_id', [1, 2])->where('order_id', $id)->update(array(
                    // 'order_status' => $request->order_status,
                    // 'production_status' => 1,
                    'order_status' => 10,
                    'approved_on' => Server::getDateTime(),
                    'updated_on' => Server::getDateTime(),

                ));
                $update = OrderItems::whereIn('service_id', [5])->where('order_id', $id)->update(array(
                    'order_status' => $request->order_status,
                    'production_status' => 1,
                    'approved_on' => Server::getDateTime(),
                    'updated_on' => Server::getDateTime(),

                ));
                $update = OrderItems::whereIn('service_id', [3, 4, 6])->where('order_id', $id)->update(array(
                    'order_status' => 10,
                    'approved_on' => Server::getDateTime(),
                    'updated_on' => Server::getDateTime(),
                ));
                // $msg = "DEAR $order->billing_customer_first_name$order->billing_customer_last_name, U R PAYMENT CREDITED SUCESSFUL,YOUR PRODUCT READY TO BILLING.YOU WILL GET REGULAR UPDATES ON FARE SALE AND SPECIAL OFFERS, BILLING INFORMATION. CALL - 04567355015";
                //     $isSmsSent = GlobalHelper::sendSms($order->billing_mobile_number, $msg);

                // Activity Log
                $desc =  'Waiting Payments - This ' . $order->order_code . ' is approved by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Waiting Payments');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                //mail send for user
                $mail = $this->getCreatorMail($order->order_id);

                $mail_data = [];
                $mail_data['email'] = $order->billing_email;
                $mail_data['name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data['order_code'] = $order->order_code;
                $mail_data['order_date'] = date("d-m-Y", strtotime($order->order_date));
                $mail_data['billing_customer_name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data['order_totalamount'] = $order->order_totalamount;
                $mail_data['payment_amount'] = $order->payment_amount;
                $mail_data['mail_type'] = 1;
                if ($order->billing_email != '') {
                    event(new SendApproved($mail_data));
                }

                //mail send for admin
                $admin_recipient = UserModel::where('acl_user_id', 1)->select('acl_user.*')->first();

                $getSesIdname = UserModel::where('acl_user_id', $userId)->select('acl_user.*')->first();

                $mail_data_admin = [];
                $mail_data_admin['email'] =  $admin_recipient->email;
                $mail_data_admin['name'] = $getSesIdname->name;
                $mail_data_admin['order_code'] = $order->order_code;
                $mail_data_admin['order_date'] = date("d-m-Y", strtotime($order->order_date));
                $mail_data_admin['billing_customer_name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data_admin['order_totalamount'] = $order->order_totalamount;
                $mail_data_admin['mail_type'] = 2;
                if ($admin_recipient->email != '') {
                    // event(new SendApproved($mail_data_admin));
                }

                //Send notification for Customer
                $title = "Order Confirmed" . " - " . $order->order_code;
                $body = "Your order (" . $order->order_code . ") is confirmed, we will process your order in next stage.";

                $module = 'order_confirmation';
                $page = 'order_confirmation';

                $random_id1 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);

                $url = "account/orders/order-view?";

                $data = [
                    'order_id' => $order->order_id,
                    'order_code' => $order->order_code,
                    'random_id' => $random_id1,
                    'url' => $url,
                    'page' => $page
                ];

                $data2 = [
                    'order_id' => $order->order_id,
                    'order_code' => $order->order_code,
                    'random_id' => $random_id2,
                    'url' => $url,
                    'page' => $page
                ];


                $portal1 = 'mobile';
                $portal2 = 'website';

                if ($order->customer_id != '') {
                    $customer_recipient = Customer::where('customer_id', $order->customer_id)->first();
                    if ($customer_recipient->token != '') {
                        $message1 = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data2,
                            'portal' => $portal2
                        ];
                        $customer_key = $customer_recipient->token;
                        $receiver_id = $customer_recipient->customer_id;
                        $push = Firebase::sendSingle($customer_key, $message1);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId,  $customer_recipient->customer_id, $module, $page, $portal2, $data2, $random_id1);
                    if ($customer_recipient->mbl_token != '') {
                        $message2 = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data,
                            'portal' => $portal1
                        ];
                        $customer_key = $customer_recipient->mbl_token;
                        $receiver_id = $customer_recipient->customer_id;
                        $push = Firebase::sendSingleMbl($customer_key, $message2);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal1, $data, $random_id2);
                }
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Order approved successfully',
                    'data' => []
                ]);
            }

            $payment_transcation_id = Orders::where([
                ['payment_transcation_id', '=', $request->payment_transcation_id], ['payment_transcation_id', '=', '']
            ])->first();
            if (empty($payment_transcation_id)) {
                if ($request->order_status == 5) {
                    $order = Orders::where('order_id', $id)->first();

                    $update = Orders::whereIn('order_id', $id)->update(array(
                        'order_status' => $request->order_status,
                        'cod_status' => 5,
                        'payment_transcation_id' =>  $request->input('payment_transcation_id'),
                        'confirm_billno' =>  $request->input('confirm_billno'),
                        'delivery_total_amount' =>  $request->input('delivery_total_amount'),
                        'payment_amount' =>  $request->input('payment_amount'),
                        'payment_status' =>  1,
                        'waiting_cod_approved_on' => Server::getDateTime(),
                        'updated_on' => Server::getDateTime()
                    ));
                    // $msg =  "Dear $order->billing_customer_first_name$order->billing_customer_last_name, Your order $order->order_code was successfully delivered. Please rate your experience here https://nrinfotechworld.com/. PLS PLACE NEW ORDER IN NR INFOTECH,THKS FOR CHOOSING. CALL - 04567355015, WHATSAPP - 9486360705.";
                    // $isSmsSent = GlobalHelper::sendSms($order->billing_mobile_number, $msg);
                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'Order cod approved successfully',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Payment transaction id already exist'),
                    'data'        => []
                ]);
            }
            if ($request->order_status == 6) {
                $userId = JwtHelper::getSesUserId();
                $order = Orders::where('order_id', $id)->first();

                $update = OrderItems::where('order_id', $id)->update(array(
                    'order_status' => $request->order_status,
                    'production_status' => 1,
                    'cancelled_on' => Server::getDateTime(),
                    'updated_on' => Server::getDateTime(),
                ));
                // $msg = "DEAR $order->billing_customer_first_name$order->billing_customer_last_name,U R ORDER DISAPPROVAL ,ORDER ID-$order->order_code CANCELLED, SORRY FOR INCONVENIENCE, PAYMENT NOT RECEIVED. PLS PLACE NEW ORDER IN NR INFOTECH,THKS FOR CHOOSING. CALL - 04567355015, WHATSAPP - 9486360705";
                //     $isSmsSent = GlobalHelper::sendSms($order->billing_mobile_number, $msg);

                // Activity Log
                $desc =  'Waiting Payments - This ' . $order->order_code . ' is disapproved by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Waiting Payments');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                //mail send for user
                // $mail = $this->getCreatorMail($order->order_id);

                $mail_data = [];
                $mail_data['email'] = $order->billing_email;
                $mail_data['name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data['order_code'] = $order->order_code;
                $mail_data['order_date'] = date("d-m-Y", strtotime($order->order_date));
                $mail_data['billing_customer_name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data['order_totalamount'] = $order->order_totalamount;
                $mail_data['payment_amount'] = $order->payment_amount;
                $mail_data['mail_type'] = 1;
                if ($order->billing_email != '') {
                    event(new SendDisapproved($mail_data));
                }

                //mail send for admin
                $admin_recipient = UserModel::where('acl_user_id', 1)->select('acl_user.*')->first();

                $getSesIdname = UserModel::where('acl_user_id', $userId)->select('acl_user.*')->first();

                $mail_data_admin = [];
                $mail_data_admin['email'] = $admin_recipient->email;
                $mail_data_admin['name'] = $getSesIdname->name;
                $mail_data_admin['order_code'] = $order->order_code;
                $mail_data_admin['order_date'] = date("d-m-Y", strtotime($order->order_date));
                $mail_data_admin['billing_customer_name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data_admin['order_totalamount'] = $order->order_totalamount;
                $mail_data_admin['mail_type'] = 2;
                if ($admin_recipient->email != '') {
                    // event(new SendDisapproved($mail_data_admin));
                }

                //Send notification for Customer
                $title = "Order Cancelled" . " - " . $order->order_code;
                $body = "Your order (" . $order->order_code . ") is cancelled, we will process the refund soon.";

                $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);


                $module = 'order_cancelled';
                $page = 'order_cancelled';

                $data = [
                    'order_id' => $order->order_id,
                    'order_code' => $order->order_code,
                    'random_id' => $random_id,
                    'page' => $page
                ];

                $url = "account/orders/order-view?";

                $data2 = [
                    'order_id' => $order->order_id,
                    'order_code' => $order->order_code,
                    'random_id' => $random_id2,
                    'url' => $url,
                    'page' => $page
                ];

                $portal1 = 'mobile';
                $portal2 = 'website';

                $customer_recipient = Customer::where('customer_id', $order->customer_id)->first();

                if ($order->customer_id != '') {


                    if ($customer_recipient->token != '') {
                        $message = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data2,
                            'portal' => $portal2
                        ];
                        $customer_key = $customer_recipient->token;
                        $receiver_id = $customer_recipient->customer_id;
                        $push = Firebase::sendSingle($customer_key, $message);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal2, $data2, $random_id2);
                    if ($customer_recipient->mbl_token != '') {

                        $message2 = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data,
                            'portal' => $portal1
                        ];

                        $customer_key = $customer_recipient->mbl_token;
                        $receiver_id = $customer_recipient->customer_id;

                        $push = Firebase::sendSingleMbl($customer_key, $message2);
                    }

                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal1, $data, $random_id);
                }


                $order = Orders::where('order_id', $id)->leftJoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->select('orders.billing_mobile_number', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'orders.order_id', 'orders.order_code', 'orders.customer_id')->first();
                $WEBSITE_URL = env('WEBSITE_URL');

                $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                // $msg =  "Dear $customerName, Thanks for shopping with #VAR2#!! We’re sorry that, due to this reason #VAR3# Your order $order->order_code has been cancelled with us. We have started the refund process for your order, the details will be updated soon. For more detail: $WEBSITE_URL Team Print App";
                // $isSmsSent = GlobalHelper::sendSMS($order->billing_mobile_number, $msg);

                //Order Quantity
                $check = OrderItems::where('order_id', $id)->whereIn('service_id', [4, 5])->get();
                if (!empty($check)) {
                    for ($i = 0; $i < count($check); $i++) {
                        $product = ProductVariant::where('product_variant_id', $check[$i]['product_variant_id'])->first();

                        $quantity = $product->quantity + $check[$i]['quantity'];
                        $product_update = ProductVariant::where('product_variant_id', $check[$i]['product_variant_id'])->update(array(
                            'quantity' => $quantity,
                            'updated_on' => Server::getDateTime()
                        ));
                    }
                }

                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Order disapproved successfully',
                    'data' => []
                ]);
            }
            if ($request->order_status == 1) {
                $id = JwtHelper::getSesUserId();
                $order = Orders::where('order_id', $id)->first();
                $user = UserModel::where('acl_user_id', $id)->select('acl_user.*')->first();

                $update = Orders::whereIn('order_id', $id)->update(array(
                    'order_status' => $request->order_status,
                    'disapproved_on' => NULL,
                    'waiting_revoked_on' => Server::getDateTime(),
                    'updated_on' => Server::getDateTime()
                ));

                // $mail_data = [];
                // $mail_data['email'] = $user->email;
                // $mail_data['to'] = "info@nrinfotech705@gmail.com";
                // $mail_data['name'] = $user->name;
                // $mail_data['order_code'] = $order->order_code;
                // $mail_data['order_date'] = date("d-m-Y", strtotime($order->order_date));
                // $mail_data['billing_customer_first_name'] = $order->billing_customer_first_name;
                // $mail_data['billing_customer_last_name'] = $order->billing_customer_last_name;
                // $mail_data['order_totalamount'] = $order->order_totalamount;

                // if($user->email !=''){
                //     event(new SendRevoke($mail_data));
                // }

                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Order revoked successfully',
                    'data' => []
                ]);
            }
        } else {
            return response()
                ->json([
                    'keyword' => 'failed',
                    'message' => __('Online status failed'),
                    'data' => []
                ]);
        }
    }


    //Cancel order
    public function cancelOrder(Request $request)
    {
        $id = $request->id;

        if (!empty($id)) {
            $order = Orders::where('order_id', $id)->first();

            $update = Orders::where('order_id', $id)->update(array(
                'order_status' => $request->order_status,
                'cod_status' => 6,
                'cancel_reason' => $request->cancel_reason,
                'canceled_on' => Server::getDateTime()
            ));
            // $msg =  "DEAR $order->billing_customer_first_name$order->billing_customer_last_name, YOUR ORDER $order->order_code IS CANCELLED. PLS PLACE NEW ORDER IN NR INFOTECH,THKS FOR CHOOSING. CALL - 04567355015, WHATSAPP - 9486360705.";
            //         $isSmsSent = GlobalHelper::sendSms($order->billing_mobile_number, $msg);
            //     $check = OrderItems::where('order_id',$id)->get();
            //     if (!empty($check)) {
            //     for ($i = 0; $i < count($check); $i++) {
            //         $order_data = new OrderItems();
            //         $order_data->order_id  = $check[$i]['order_id'];
            //         $order_data->product_id  = $check[$i]['product_id'];
            //         $order_data->quantity  = $check[$i]['quantity'];
            //         $product_data = $this->getProduct($check[$i]['product_id']);                        
            //         //if($order_data->save()){                        
            //         	$product = Product::where('product_id',$check[$i]['product_id'])->first();
            //         $product_quantity = $product->quantity;
            //         $quantity = $product->quantity + $check[$i]['quantity'];                        
            //         $product_update = Product::where('product_id',$check[$i]['product_id'])->update(array(
            //             'quantity' => $quantity,
            //             'updated_on' => Server::getDateTime()
            //         ));
            //       //}
            //     }
            // }
            return response()->json([
                'keyword' => 'success',
                'message' => 'Order Cancelled',
                'data' => []
            ]);
        } else {
            return response()
                ->json([
                    'keyword' => 'failed',
                    'message' => __('Order failed'),
                    'data' => []
                ]);
        }
    }

    public function updateDeliveredStatus(Request $request, $type)
    {
        $id = $request->id;

        if (!empty($id)) {
            if ($type == 'online') {
                $order = OrderItems::where('bill_no', $id)->first();
                $update = OrderItems::where('bill_no', $id)->update(array(
                    'order_status' => 5,
                    'cod_status' => 5,
                    'delivered_on' =>  Server::getDateTime()
                ));

                $orderDetails = OrderItems::where('bill_no', $id)->first();

                $orderCount = OrderItems::where('order_id', $orderDetails->order_id)->where('order_status', '!=', 4)->count();

                $deliveredCount = OrderItems::where('order_id', $orderDetails->order_id)->where('order_status', 5)->count();

                if ($orderCount == $deliveredCount) {
                    $orderUpdate = Orders::find($orderDetails->order_id);
                    $orderUpdate->payment_delivery_status = 1;
                    $orderUpdate->order_time = date('H');
                    $orderUpdate->save();
                }
                // $msg =  "Dear $order->billing_customer_first_name$order->billing_customer_last_name, Your order $order->order_code was successfully delivered. Please rate your experience here https://nrinfotechworld.com/. PLS PLACE NEW ORDER IN NR INFOTECH,THKS FOR CHOOSING. CALL - 04567355015, WHATSAPP - 9486360705.";
                //   $isSmsSent = GlobalHelper::sendSms($order->billing_mobile_number, $msg);

                $order = Orders::where('orders.order_id', $order->order_id)->leftJoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->select('orders.*', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no',)->first();

                $orderItemDetails = OrderItems::where('bill_no', $id)->select('order_items.*')->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('service.service_name')->get();
                $resultArray = [];
                if (!empty($orderItemDetails)) {
                    foreach ($orderItemDetails as $pd) {
                        $resultArray[$pd->service_name] = $pd['service_name'];
                    }
                }


                $itmesNames = implode(", ", $resultArray) ?? "-";
                $customerName = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $WEBSITE_URL = env('WEBSITE_URL');
                $msg =  "Dear $customerName, Great News!! We’re happy an update that your order $itmesNames has been delivered successfully. Enjoy with your order. Thanks for shopping with Print App. We hope you have a great day. For more order: $WEBSITE_URL Team Print App";

                $isSmsSent = GlobalHelper::sendSMS($order->billing_mobile_number, $msg);



                $customer_details_for_items = orderItems::where('bill_no', $id)->first();

                //mail send
                $mail_data = [];
                $mail_data['order_items'] = OrderItems::where('bill_no', $id)->select('sub_total', 'product_name', 'order_items_id')->get();
                $mail_data['customer_name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $mail_data['email'] = $order->billing_email;


                if ($order->billing_email != '') {
                    event(new DeliverySuccess($mail_data));
                }

                //Activity Log
                $getOrderid = Orders::where('order_id', $order->order_id)->first();
                $getOrderItemsName = OrderItems::where('bill_no', $id)->select('product_name', 'product_code')->get();

                $resultArrays = [];
                if (!empty($getOrderItemsName)) {
                    foreach ($getOrderItemsName as $pd) {
                        $resultArrays[] = $pd['product_name'] . ' - ' . $pd['product_code'];
                    }
                }

                $itmesOrderNames = implode(", ", $resultArrays) ?? "-";

                $desc =  'Delivery Details - This ' . $getOrderid->order_code . '(' . $itmesOrderNames . ') is delivered by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Delivery Details');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                //Send notification for Customer
                $userId = JwtHelper::getSesUserId();


                $title = "Order Delivered" . " - " . $getOrderid->order_code;
                $body = "Your order (" . $getOrderid->order_code . ") is delivered successfully.";

                $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $module = 'order_delivered';
                $page = 'order_delivered';

                $data = [
                    'order_id' => $getOrderid->order_id,
                    'order_code' => $getOrderid->order_code,
                    'random_id' => $random_id,
                    'page' => $page,
                ];

                $url = "account/orders/order-view?";

                $data2 = [
                    'order_id' => $getOrderid->order_id,
                    'order_code' => $getOrderid->order_code,
                    'random_id' => $random_id2,
                    'url' => $url,
                    'page' => $page,
                ];

                $portal1 = 'mobile';
                $portal2 = 'website';

                if ($getOrderid->customer_id != "") {

                    $customer_recipient = Customer::where('customer_id', $getOrderid->customer_id)->first();

                    if ($customer_recipient->token != '') {
                        $message = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data2,
                            'portal' => $portal2
                        ];
                        $customer_key = $customer_recipient->token;
                        $receiver_id = $customer_recipient->customer_id;
                        $push = Firebase::sendSingle($customer_key, $message);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId,  $customer_recipient->customer_id, $module, $page, $portal2, $data2, $random_id2);
                    if ($customer_recipient->mbl_token != '') {

                        $message2 = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data,
                            'portal' => $portal1
                        ];

                        $customer_key = $customer_recipient->mbl_token;
                        $receiver_id = $customer_recipient->customer_id;

                        $push = Firebase::sendSingleMbl($customer_key, $message2);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal1, $data, $random_id);
                }
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Online delivery status updated successfully',
                    'data' => []
                ]);
            }


            if ($type == 'cod') {
                $order = OrderItems::where('bill_no', $id)->first();

                $update = OrderItems::where('bill_no', $id)->update(array(
                    'cod_status' => 5,
                    'order_status' => 5,
                    'delivered_on' =>  $request->input('delivered_on'),
                    'is_receivedby' =>  $request->input('is_receivedby'),
                    'receivedby_name' =>  $request->input('receivedby_name'),
                    'payment_transcation_id' =>  $request->input('payment_transcation_id'),
                    'delivered_amount' =>  $request->input('delivered_amount'),
                    'payment_status' =>  1
                ));

                $order = OrderItems::where('bill_no', $id)->first();

                $orderCount = OrderItems::where('order_id', $order->order_id)->where('order_status', '!=', 4)->count();

                $deliveredCount = OrderItems::where('order_id', $order->order_id)->where('order_status', 5)->count();

                if ($orderCount == $deliveredCount) {
                    $orderUpdate = Orders::find($order->order_id);
                    $orderUpdate->payment_status = 1;
                    $orderUpdate->payment_delivery_status = 1;
                    $orderUpdate->order_time = date('H');
                    $orderUpdate->save();
                }

                // $msg =  "Dear $order->billing_customer_first_name$order->billing_customer_last_name, Your order $order->order_code was successfully delivered. Please rate your experience here https://nrinfotechworld.com/. PLS PLACE NEW ORDER IN NR INFOTECH,THKS FOR CHOOSING. CALL - 04567355015, WHATSAPP - 9486360705.";
                // $isSmsSent = GlobalHelper::sendSms($order->billing_mobile_number, $msg);

                $customer_details_for_items = orderItems::where('bill_no', $id)->first();

                $order = Orders::where('orders.order_id', $order->order_id)->leftJoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->select('orders.*', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no',)->first();

                $orderItemDetails = OrderItems::where('bill_no', $id)->select('order_items.*')->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('service.service_name')->get();
                $resultArray = [];
                if (!empty($orderItemDetails)) {
                    foreach ($orderItemDetails as $pd) {
                        $resultArray[$pd->service_name] = $pd['service_name'];
                    }
                }


                $itmesNames = implode(", ", $resultArray) ?? "-";
                $customerName = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
                $WEBSITE_URL = env('WEBSITE_URL');
                $msg =  "Dear $customerName, Great News!! We’re happy an update that your order $itmesNames has been delivered successfully. Enjoy with your order. Thanks for shopping with Print App. We hope you have a great day. For more order: $WEBSITE_URL Team Print App";

                $isSmsSent = GlobalHelper::sendSMS($order->billing_mobile_number, $msg);

                $getOrderidDet = Orders::where('order_id', $order->order_id)->first();

                //mail send
                $mail_data = [];
                $mail_data['order_items'] = OrderItems::where('bill_no', $id)->select('sub_total', 'product_name', 'order_items_id')->get();
                $mail_data['customer_name'] = !empty($getOrderidDet->billing_customer_last_name) ? $getOrderidDet->billing_customer_first_name . ' ' . $getOrderidDet->billing_customer_last_name : $getOrderidDet->billing_customer_first_name;
                $mail_data['email'] = $getOrderidDet->billing_email;


                if ($getOrderidDet->billing_email != '') {
                    event(new DeliverySuccess($mail_data));
                }

                //Log Activity
                $getOrderid = Orders::where('order_id', $order->order_id)->first();
                $getOrderItemsName = OrderItems::where('bill_no', $id)->select('product_name', 'product_code')->get();

                $resultArrays = [];
                if (!empty($getOrderItemsName)) {
                    foreach ($getOrderItemsName as $pd) {
                        $resultArrays[] = $pd['product_name'] . ' - ' . $pd['product_code'];
                    }
                }

                $itmesOrderNames = implode(", ", $resultArrays) ?? "-";

                $desc =  'Delivery Details - This ' . $getOrderid->order_code . '(' . $itmesOrderNames . ') is delivered by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Delivery Details');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                //Send notification for Customer
                $userId = JwtHelper::getSesUserId();
                $title = "Order Delivered" . " - " . $getOrderid->order_code;
                $body = "Your order (" . $getOrderid->order_code . ") is delivered successfully.";

                $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $module = 'order_delivered';
                $page = 'order_delivered';

                $data = [
                    'order_id' => $getOrderid->order_id,
                    'order_code' => $getOrderid->order_code,
                    'random_id' => $random_id,
                    'page' => $page
                ];

                $url = "account/orders/order-view?";

                $data2 = [
                    'order_id' => $getOrderid->order_id,
                    'order_code' => $getOrderid->order_code,
                    'random_id' => $random_id2,
                    'url' => $url,
                    'page' => $page,
                ];

                $portal1 = 'mobile';
                $portal2 = 'website';


                if ($getOrderid->customer_id != '') {
                    $customer_recipient = Customer::where('customer_id', $getOrderid->customer_id)->first();

                    if ($customer_recipient->token != '') {
                        $message = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data2,
                            'portal' => $portal2
                        ];
                        $customer_key = $customer_recipient->token;
                        $receiver_id = $customer_recipient->customer_id;
                        $push = Firebase::sendSingle($customer_key, $message);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal2, $data2, $random_id2);
                    if ($customer_recipient->mbl_token != '') {

                        $message2 = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data,
                            'portal' => $portal1
                        ];

                        $customer_key = $customer_recipient->mbl_token;
                        $receiver_id = $customer_recipient->customer_id;

                        $push = Firebase::sendSingleMbl($customer_key, $message2);
                    }

                    $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $customer_recipient->customer_id, $module, $page, $portal1, $data, $random_id);
                }

                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Cod delivery status updated successfully',
                    'data' => []
                ]);
            }
        }
    }

    public function courier_name_getcall($bill_no)
    {
        $order = OrderItems::where('bill_no', $bill_no)->select('courier_no', 'courier_name')->first();
        $final = [];
        if (!empty($order)) {
            $ary = [];
            $ary['courier_no'] = $order['courier_no'];
            $ary['courier_name'] = $order['courier_name'];
            $final[] = $ary;
        }
        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Courier name listed successfully'),
                    'data' => $final,
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                ]
            );
        }
    }

    public function courier_name_getall(Request $request)
    {
        $details = ShippedVendorDetails::select('*')->get();
        $final = [];
        if (!empty($details)) {

            foreach ($details as $data) {
                $ary = [];
                $ary['shipped_vendor_details_id'] = $data['shipped_vendor_details_id'];
                $ary['courier_name'] = $data['courier_name'];
                $final[] = $ary;
            }
        }
        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Courier name listed successfully'),
                    'data' => $final,
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                ]
            );
        }
    }

    public function item_invoice_view(Request $request, $order_item_id)
    {
        try {
            Log::channel("billview")->info('** started the billview list method **');

            $get_order_id = BillItems::where('bill_item.order_items_id', $order_item_id)->first();

            $get_billno = Bills::where('bill_id', $get_order_id->bill_id)->first();

            $bill_management_view = OrderItems::where('order_items.bill_no', $get_billno->bill_no)
                ->leftJoin('orders', 'order_items.order_id', '=', 'orders.order_id')
                ->select('orders.order_id', 'orders.order_code', 'orders.created_by', 'orders.order_date', 'order_items.*', 'orders.coupon_amount', 'orders.shipping_cost', 'orders.coupon_code_percentage', 'orders.billing_state_id', 'orders.customer_id','orders.coupon_code');

            // $count = $bill_management_view->count();

            $bill_management_view->orderBy('order_items.order_id', 'desc');
            $bill_management_view = $bill_management_view->get();
            $count = $bill_management_view->count();
            if ($count > 0) {
                $final = [];
                $sum = 0;
                $deliveryChargeAmount = 0;
                $coupon_amount = 0;
                foreach ($bill_management_view as $value) {
                    $ary = [];
                    $ary['order_id'] = $value['order_id'];
                    $ary['order_code'] = $value['order_code'];
                    $ary['order_item_id'] = $value['order_items_id'];
                    $ary['product_id'] = $value['product_code'];
                    $ary['product_name'] = $value['product_name'];
                    $invoice_date = OrderItems::where('order_items.bill_no', $value['bill_no'])
                        ->leftJoin('bill', 'order_items.bill_no', '=', 'bill.bill_no')->select('bill.created_on')->first();
                    $ary['invoice_date'] = $invoice_date->created_on;
                    $ary['order_date'] = $value['order_date'];


                    if (!empty($value['customer_id'])) {
                        // $ary['gross_amount'] = $value['sub_total'];
                        // $ary['quantity'] = $value['quantity'];

                        // $ary['discount_percent'] =$value['coupon_code_percentage'] ?? "-";
                        // $amt_find = $value['sub_total'] * $value['coupon_code_percentage'] / 100;

                        // $ary['discount'] =  sprintf("%.2f", $amt_find);
                        // if($ary['discount'] != " ")
                        // {
                        // $ary['taxable_amount'] = $value['sub_total'] - $ary['discount'];
                        // }
                        // else
                        // {
                        // // $ary['taxable_amount'] = round($value['sub_total'] - $ary['discount']);
                        //     $ary['taxable_amount'] = $value['sub_total'];
                        // }
                        // $gst_calc = 1 + ($value['gst_value'] / 100 * 1);
                        // $exc_gst = $ary['taxable_amount'] / $gst_calc;
                        // $amt = $ary['taxable_amount'] - $exc_gst;
                        // $round_exc_gst = round($exc_gst, 2);
                        // if($value['billing_state_id'] == 33)
                        // {
                        //     $ary['cgst_percent'] = $value['gst_value']/2 ."%";
                        //     $ary['cgst_amount'] = sprintf("%.2f", $amt / 2);
                        //     $ary['sgst_percent'] = $value['gst_value']/2 ."%";
                        //     $ary['sgst_amount'] =  sprintf("%.2f", $amt / 2);
                        //     $ary['net_amount'] = sprintf("%.2f", $ary['taxable_amount'] + $ary['cgst_amount'] + $ary['sgst_amount']);
                        //     $ary['igst_percent'] = '';
                        //     $ary['igst_amount'] = '';
                        // }
                        // else
                        // {   
                        //     $ary['cgst_percent'] = '';
                        //     $ary['cgst_amount'] = '';
                        //     $ary['sgst_percent'] = '';
                        //     $ary['sgst_amount'] =  '';
                        //     $ary['igst_percent'] = $value['gst_value']."%";
                        //     $ary['igst_amount'] = sprintf("%.2f", $amt);
                        // $ary['net_amount'] = sprintf("%.2f", $ary['taxable_amount'] + $ary['igst_amount']);
                        // }
                        // // $lhipping_charge = $value['shipping_cost'];
                        // $sum += sprintf("%.2f", $ary['net_amount']);
                        // // $total_amount = $value['shipping_cost'] + $sum;
                        // $total_amount = sprintf("%.2f", $sum);

                        $ary['gross_amount'] = $value['sub_total'];
                        $ary['quantity'] = $value['quantity'];

                        $ary['discount_percent'] = $value['coupon_code_percentage'] ?? "-";
                        // $amt_find = $value['sub_total'] * $value['coupon_code_percentage'] / 100;

                        // $ary['discount'] =  sprintf("%.2f", $amt_find);

                        $ary['discount'] =  "0.00";

                        // if ($ary['discount'] != " ") {
                        //     $ary['taxable_amount'] = $value['sub_total'] - $ary['discount'];
                        // } else {
                        // $ary['taxable_amount'] = $value['sub_total'];
                        // }
                        $ary['taxable_amount'] = $value['sub_total'];
                        $gst_calc = 1 + ($value['gst_value'] / 100 * 1);
                        $exc_gst = $ary['taxable_amount'] / $gst_calc;
                        $exec_gst_amount = number_format(floor($exc_gst * 100) / 100, 2, '.', '');
                        $amt = $ary['taxable_amount'] - $exec_gst_amount;
                        $ary['taxable_amount'] = sprintf("%.2f", $value['sub_total'] - $amt);
                        // $round_exc_gst = round($exc_gst, 2);
                        if ($value['billing_state_id'] == 33) {
                            $ary['cgst_percent'] = $value['gst_value'] / 2;
                            $ary['cgst_amount'] = sprintf("%.2f", $amt / 2);
                            $ary['sgst_percent'] = $value['gst_value'] / 2;
                            $ary['sgst_amount'] =  sprintf("%.2f", $amt / 2);
                            $ary['net_amount'] = $value['sub_total'];
                            $ary['igst_percent'] = '-';
                            $ary['igst_amount'] = '-';
                        } else {
                            $ary['cgst_percent'] = '-';
                            $ary['cgst_amount'] = '-';
                            $ary['sgst_percent'] = '-';
                            $ary['sgst_amount'] =  '-';
                            $ary['igst_percent'] = $value['gst_value'];
                            $ary['igst_amount'] = sprintf("%.2f", $amt);
                            $ary['net_amount'] = $value['sub_total'];
                        }
                        $sum += sprintf("%.2f", $ary['net_amount']);
                        if(!empty($value['coupon_code'])){
                        $coupon_amount += sprintf("%.2f", $value['coupon_code_amount']);
                        } else {
                        $coupon_amount = NULL;
                        }
                        $total_amount = sprintf("%.2f", $sum);
                        $deliveryChargeAmount += $value['delivery_charge'];

                        $totalAmount = sprintf("%.2f", $total_amount + $deliveryChargeAmount - $coupon_amount);
                        $rounded_value = round($totalAmount);
                        $totalAmountRoundOff = sprintf("%.2f", $rounded_value);

                        $remainingValue = $rounded_value - $totalAmount;
                        // $remainingAbsValue = abs($remainingValue);
                        $roundOffValue = sprintf("%.2f", $remainingValue);

                    } else {
                        $subTotal = sprintf("%.2f", $value['unit_price'] * $value['quantity']);
                        $ary['gross_amount'] = $subTotal;
                        $ary['quantity'] = $value['quantity'];
                        $ary['discount_percent'] = $value['discount_percentage'] ?? "-";
                        $discountAmount = $value['discount_percentage'] * $value['discount_amount'];
                        $ary['discount'] = sprintf("%.2f", $discountAmount) ?? "-";
                        $ary['taxable_amount'] = $value['taxable_amount'];
                        if ($value['billing_state_id'] == 33) {
                            $ary['cgst_percent'] = $value['cgst_percentage'];
                            $ary['cgst_amount'] = $value['cgst_amount'];
                            $ary['sgst_percent'] = $value['sgst_percentage'];
                            $ary['sgst_amount'] = $value['sgst_amount'];
                            $ary['igst_percent'] = "-";
                            $ary['igst_amount'] = "0";
                            $ary['net_amount'] = $value['quote_amount'];
                        } else {
                            $ary['cgst_percent'] = "-";
                            $ary['cgst_amount'] = "0";
                            $ary['sgst_percent'] = "-";
                            $ary['sgst_amount'] = "0";
                            $ary['igst_percent'] = $value['igst_percentage'];
                            $ary['igst_amount'] = $value['igst_amount'];
                            $ary['net_amount'] = $value['quote_amount'];
                        }
                        $sum += $ary['net_amount'];
                        $deliveryChargeAmount += $value['delivery_charge'];
                        $coupon_amount = NULL;
                        $total_amount = sprintf("%.2f", $sum);

                        $totalAmount = sprintf("%.2f", $total_amount + $deliveryChargeAmount - $coupon_amount);
                        $rounded_value = round($totalAmount);
                        $totalAmountRoundOff = sprintf("%.2f", $rounded_value);

                        $remainingValue = $rounded_value - $totalAmount;
                        // $remainingAbsValue = abs($remainingValue);
                        $roundOffValue = sprintf("%.2f", $remainingValue);
                    }
                    $customerdetails = Orders::where('order_id', $value['order_id'])
                        ->leftjoin('district', 'orders.billing_city_id', '=', 'district.district_id')
                        ->leftJoin('state', 'orders.billing_state_id', '=', 'state.state_id')
                        ->select('state.state_name', 'district.district_name', 'billing_customer_first_name', 'billing_customer_last_name', 'billing_email', 'billing_mobile_number', 'billing_address_1', 'billing_landmark', 'billing_pincode', 'billing_alt_mobile_number', 'billing_gst_no', 'billing_address_2','customer_id')->first();
                    $company_details = CompanyInfo::select('name', 'address', 'logo', 'mobile_no')->first();
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("billview")->info("list value :: $log");
                Log::channel("billview")->info('** end the billview list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Bill management viewed successfully'),
                    'data' => $final,
                    'no_of_items' => $count,
                    'net_amount' => sprintf("%.2f", $sum),
                    'coupon_amount' => !empty($coupon_amount) ? sprintf("%.2f", $coupon_amount) : NULL,
                    'deliveryChargeAmount' => sprintf("%.2f", $deliveryChargeAmount),
                    'total_amount' => $totalAmountRoundOff,
                    'round_off' => $roundOffValue,
                    'customer_details' => $customerdetails,
                    'company_details' => $company_details,
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
            Log::channel("billview")->error($exception);
            Log::channel("billview")->error('** end the billview list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function couriernameGetAll(Request $request)
    {
        $get_shipping = ShippedVendorDetails::where('status', 1)->get();

        $final = [];
        if (!empty($get_shipping)) {

            foreach ($get_shipping as $data) {
                $ary = [];
                $ary['shipped_vendor_details_id'] = $data['shipped_vendor_details_id'];
                $ary['courier_name'] = $data['courier_name'];
                $ary['courier_url'] = $data['courier_url'];
                $final[] = $ary;
            }
        }

        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Courier name listed successfully'),
                    'data' => $final,
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                ]
            );
        }
    }

    public function courierurlGetAll(Request $request, $id)
    {
        $get_shipping = ShippedVendorDetails::where('shipped_vendor_details_id', $id)->get();

        $final = [];
        if (!empty($get_shipping)) {

            foreach ($get_shipping as $data) {
                $ary = [];
                $ary['shipped_vendor_details_id'] = $data['shipped_vendor_details_id'];
                $ary['courier_url'] = $data['courier_url'];
                $final[] = $ary;
            }
        }

        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Courier url listed successfully'),
                    'data' => $final,
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                ]
            );
        }
    }
}
