<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    function transaction_list(Request $request)
    {
        try {
            Log::channel("transaction")->info('** started the transaction list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByPaymentMethod = ($request->filterByPaymentMethod) ? $request->filterByPaymentMethod : '[]';
            $filterByCity = ($request->filterByCity) ? $request->filterByCity : '[]';
            $filterByState = ($request->filterByState) ? $request->filterByState : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                'order_id' => 'orders.order_code',
                'order_date' =>  DB::raw('DATE_FORMAT(orders.order_date, "%d-%m-%Y")'),
                // 'customer_name' => 'customer.customer_first_name',
                'customer_name' => DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name, bulk_order_enquiry.contact_person_name)'),
                'location' => 'district.district_name',
                'order_amount' => 'orders.order_totalamount',
                'payment_mode' => 'orders.payment_mode',
                'transaction_id' => 'orders.payment_transcation_id',
                'status' => 'orders.payment_status',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('orders.order_code', DB::raw('DATE_FORMAT(orders.order_date, "%d-%m-%Y")'),DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'),'state.state_name','district.district_name','orders.order_totalamount', 'orders.payment_mode','orders.payment_transcation_id','orders.other_district','bulk_order_enquiry.contact_person_name');

            $payment = Orders::leftjoin('district', 'district.district_id', '=', 'orders.billing_city_id')
                 ->leftjoin('state', 'orders.billing_state_id', '=', 'state.state_id')
                ->leftjoin('pg_link_history', 'pg_link_history.order_id', '=', 'orders.order_id')
                ->leftJoin('customer', function ($leftJoin) {
                    $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                        ->where('orders.customer_id', '!=', NULL);
                })
                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                        ->where('orders.customer_id', '=', NULL);
                })
                ->select('orders.*', 'district.district_name', 'customer.customer_first_name', 'customer.customer_last_name','state.state_name','bulk_order_enquiry.contact_person_name','pg_link_history.transaction_id');

                $payment->where(function ($query) use ($searchval, $column_search, $payment) {
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
                $payment->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $payment->where(function ($query) use ($from_date) {
                    $query->whereDate('orders.order_date', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $payment->where(function ($query) use ($to_date) {
                    $query->whereDate('orders.order_date', '<=', $to_date);
                });
            }

            if (!empty($filterByState) && $filterByState != '[]' && $filterByState != 'all') {
                $filterByState = json_decode($filterByState, true);
                $payment->whereIn('state.state_id', $filterByState);
            }

            if (!empty($filterByCity) && $filterByCity != '[]' && $filterByCity != 'all') {
                $filterByCity = json_decode($filterByCity, true);
                $payment->whereIn('district.district_id', $filterByCity);
            }

            if (!empty($filterByPaymentMethod) && $filterByPaymentMethod != '[]' && $filterByPaymentMethod != 'all') {
                $filterByPaymentMethod = json_decode($filterByPaymentMethod, true);
                $payment->whereIn('orders.payment_status', $filterByPaymentMethod);
            }
          
            $count = $payment->count();
            if ($offset) {
                $offset = $offset * $limit;
                $payment->offset($offset);
            }
            if ($limit) {
                $payment->limit($limit);
            }
            $payment->orderBy('orders.order_id', 'desc');
            $payment = $payment->get();
            $final = [];
            if ($count > 0) {
               
                foreach ($payment as $value) {
                    $ary = [];
                    $ary['order_id'] = $value['order_code'] ?? null;
                    $ary['order_date'] = date('d-m-Y', strtotime($value['order_date'])) ?? null;
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['bulk_order_enquiry_id'] = $value['bulk_order_enquiry_id'];
                    // $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    if(!empty($value['customer_id'])){
                        $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    } else {
                        $ary['customer_name'] = $value['contact_person_name'];
                    }
                    $ary['billing_state_id'] = $value['billing_state_id'] ?? null;
                    $ary['billing_city_id'] = $value['billing_city_id'] ?? null;
                    $ary['state'] = $value['state_name'] ?? null;
                    if($value['billing_city_id'])
                    {
                    $ary['location'] = $value['district_name'] ?? null;
                    }
                    else{
                    $ary['location'] = $value['other_district'] ?? null;
                    }
                    $ary['order_amount'] = $value['order_totalamount'] ?? null;
                    $ary['paid_amount'] = $value['payment_amount'] ?? null;
                    $ary['payment_mode'] = $value['payment_mode'] ?? null;
                    $ary['transaction_id'] = $value['payment_transcation_id'] ?? null;
                    $ary['transaction_id'] = $value['transaction_id'] ?? null;
                    if ($value['payment_status'] == 1) {
                        $ary['status'] = "PAID";
                    } 
                    if ($value['payment_status'] == 0) {
                        $ary['status'] = "UNPAID";
                    }
                    if ($value['payment_status'] == 3) {
                        $ary['status'] = "PARTIALLY PAID";
                    }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $impl = json_encode($final, true);
                Log::channel("transaction")->info("Transaction Controller end:: save values :: $impl ::::end");
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Transaction listed successfully',
                    'data' => $final,
                    'count' => $count
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("transaction")->error('** start the transaction list error method **');
            Log::channel("transaction")->error($exception);
            Log::channel("transaction")->error('** end the transaction list error method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
