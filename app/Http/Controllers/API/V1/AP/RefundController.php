<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\OrderItems;
use App\Models\refundInbox;
use App\Http\Requests\RefundRequest;


class RefundController extends Controller
{
    public function refund_list(Request $request)
    {
        try {
            Log::channel("refunds")->info('** started the refunds list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';


            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'date' => 'order_items.cancelled_on',
                'order_id' => 'orders.order_code',
                'product_id' => 'order_items.product_code',
                'product_name' => 'order_items.product_name',
                'customer_name' => 'customer.customer_first_name',
                'mobile_no' => 'customer.mobile_no',
                'product_amount' => 'order_items.sub_total',
                'payment_mode' => 'orders.payment_mode',
                'transaction_id' => 'orders.payment_transcation_id',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_items_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('order_items.cancelled_on', 'orders.order_code', 'product.product_code', 'product.product_name', 'customer.billing_customer_first_name', 'customer.billing_mobile_number', 'product.mrp', 'orders.payment_mode',);

            $refund = OrderItems::whereIn('order_items.order_status', [4, 6])->where('orders.payment_mode', 'Online')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftjoin('product', 'product.product_id', '=', 'order_items.product_id')
                ->leftjoin('customer', 'customer.customer_id', '=', 'order_items.created_by')
                ->select('order_items.*', 'orders.order_code', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'product.mrp', 'orders.payment_mode', 'orders.payment_transcation_id');

            $refund->where(function ($query) use ($searchval, $column_search, $refund) {
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
                $refund->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $refund->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $refund->where(function ($query) use ($from_date) {
                    $query->whereDate('order_items.cancelled_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $refund->where(function ($query) use ($to_date) {
                    $query->whereDate('order_items.cancelled_on', '<=', $to_date);
                });
            }

            $count = $refund->count();

            if ($offset) {
                $offset = $offset * $limit;
                $refund->offset($offset);
            }
            if ($limit) {
                $refund->limit($limit);
            }
            Log::channel("refunds")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $refund->orderBy('order_items_id', 'DESC');
            $refund = $refund->get();
            $final = [];
            // $count = $refund->count();
            if ($count > 0) {
                foreach ($refund as $value) {
                    $ary = [];
                    $ary['order_items_id'] = $value['order_items_id'];
                    $ary['date'] = date('d-m-Y', strtotime($value['cancelled_on'])) ?? "-";
                    $ary['order_id'] = $value['order_code'] ?? "-";
                    $ary['product_id'] = $value['product_code'] ?? "-";
                    $ary['product_name'] = $value['product_name'] ?? "-";
                    $ary['customer_name'] =  !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    $ary['mobile_no'] = $value['mobile_no'] ?? "-";
                    $ary['product_amount'] = $value['sub_total'] ?? "-";
                    $ary['payment_mode'] = $value['payment_mode'] ?? "-";
                    $ary['transaction_id'] = $value['payment_transcation_id'] ?? "-";
                    $ary['payment_mode'] = $value['payment_mode'] ?? "-";
                    $ary['is_refund'] = $value['is_refund'] ?? "-";
                    if ($value['is_refund'] == 0) {
                        $ary['status'] = 'Non Refunded';
                    }
                    if ($value['is_refund'] == 1) {
                        $ary['status'] = 'Refunded';
                    }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("refunds")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Refund listed successfully'),
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
            Log::channel("refunds")->error('** start the refund list error method **');
            Log::channel("refunds")->error($exception);
            Log::channel("refunds")->error('** end the refunds list error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    function refunded_order(RefundRequest $request)
    {
        try {
            Log::channel("refund")->info('** started the refund update method **');
            if (!empty($request)) {
                $ids = $request->id;
                $ids = json_decode($ids, true);
                if (!empty($ids)) {
                    $data = OrderItems::where('order_items_id', $ids)->update(array(
                        'is_refund' => 1,
                        'refund_amount' => $request->refund_amount,
                        'refund_reason' => $request->refund_reason,
                        'refunded_on' => Server::getDateTime(),
                        'refunded_by' => JwtHelper::getSesUserId()
                    ));

                    $cusDeatils = OrderItems::where('order_items_id', $ids)->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->select('orders.order_code', 'customer.customer_first_name', 'customer.customer_last_name')->first();
                    
                    $customerName = !empty($cusDeatils->customer_last_name) ? $cusDeatils->customer_first_name . ' ' . $cusDeatils->customer_last_name : $cusDeatils->customer_first_name;


                    $desc = 'Refunded the amount ' . $request->refund_amount . ' for ' . $cusDeatils->order_code . ' with ' . $customerName . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Refund');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    return response()->json([
                        'keyword' => 'success',
                        'message' => ('Refunded successfully'),
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("refund")->error($exception);
            Log::channel("refund")->error('** end the refund update method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
