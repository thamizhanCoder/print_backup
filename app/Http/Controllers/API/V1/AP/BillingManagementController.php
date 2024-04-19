<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\Bills;
use App\Models\BillItems;
use App\Models\CompanyInfo;
use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BillingManagementController extends Controller
{
    public function billing_management_list(Request $request)
    {
        try {
            Log::channel("billmanagement")->info('** started the billmanagement list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';

            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'bill_date' => 'bill.created_on',
                'bill_no' => 'bill.bill_no',
                'order_date' => 'orders.order_date',
                'order_code' => 'orders.order_code',
                // 'customer_name' => 'customer.customer_first_name',
                'customer_name' => DB::raw('GROUP_CONCAT(CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name, bulk_order_enquiry.contact_person_name) SEPARATOR " ")'),
                // 'mobile_no' => 'customer.mobile_no',
                'mobile_no' => DB::raw('GROUP_CONCAT(CONCAT_WS(" ", customer.mobile_no, bulk_order_enquiry.mobile_no) SEPARATOR " ")'),

            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_items_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'bill.created_on', 'order_items.bill_no', 'orders.order_date',
                'orders.order_code', DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'), 'customer.mobile_no', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no'
            );

            $bill_management = OrderItems::whereIn('order_status', [7, 3, 5])->leftjoin('bill_item', 'order_items.order_items_id', '=', 'bill_item.order_items_id')
                ->leftjoin('orders', 'order_items.order_id', '=', 'orders.order_id')
                ->leftjoin('bill', 'bill.bill_id', '=', 'bill_item.bill_id')
                // ->leftjoin('customer', 'orders.customer_id', '=', 'customer.customer_id')
                ->leftJoin('customer', function ($leftJoin) {
                    $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                        ->where('orders.customer_id', '!=', NULL);
                })
                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                        ->where('orders.customer_id', '=', NULL);
                })
                ->select('orders.customer_id', 'orders.order_code', 'orders.order_date', 'order_items.*', 'bill.created_on', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'bulk_order_enquiry.mobile_no as bulk_order_mobile_no', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.bulk_order_enquiry_id')
                ->groupby('order_items.bill_no');


            $bill_management->where(function ($query) use ($searchval, $column_search, $bill_management) {
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
                $bill_management->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $bill_management->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $bill_management->where(function ($query) use ($from_date) {
                    $query->whereDate('bill.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $bill_management->where(function ($query) use ($to_date) {
                    $query->whereDate('bill.created_on', '<=', $to_date);
                });
            }

            $count = count($bill_management->get());
            if ($offset) {
                $offset = $offset * $limit;
                $bill_management->offset($offset);
            }
            if ($limit) {
                $bill_management->limit($limit);
            }
            Log::channel("billmanagement")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $bill_management->orderBy('bill.created_on', 'DESC');
            $bill_management = $bill_management->get();

            if ($count > 0) {
                $final = [];
                foreach ($bill_management as $value) {
                    $ary = [];
                    $ary['order_items_id'] = $value['order_items_id'];
                    $ary['bill_date'] = $value['created_on'];
                    $ary['bill_no'] = $value['bill_no'];
                    $ary['order_date'] = $value['order_date'];
                    $ary['order_no'] = $value['order_code'];
                    // $ary['customer_name'] = $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    // $ary['mobile_no'] = $value['mobile_no'];
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['bulk_order_enquiry_id'] = $value['bulk_order_enquiry_id'];
                    if (!empty($value['customer_id'] != '')) {
                        $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                        $ary['mobile_no'] = $value['mobile_no'];
                    } else {
                        $ary['customer_name'] = $value['contact_person_name'];
                        $ary['mobile_no'] = $value['bulk_order_mobile_no'];
                    }
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("billmanagement")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Billing listed successfully'),
                    'data' => $final,
                    'count' => $count,
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
            Log::channel("billmanagement")->error($exception);
            Log::channel("billmanagement")->error('** end the billmanagement list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function Billing_Management_View(Request $request, $bill_no)
    {
        try {
            Log::channel("billview")->info('** started the billview list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
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

            $bill_management_view = OrderItems::where('order_items.bill_no', $bill_no)
                ->leftJoin('orders', 'order_items.order_id', '=', 'orders.order_id')
                ->select('orders.order_id', 'orders.order_code', 'orders.created_by', 'orders.order_date', 'order_items.*', 'orders.coupon_amount', 'orders.shipping_cost', 'orders.coupon_code_percentage', 'orders.billing_state_id', 'orders.customer_id', 'orders.coupon_code');

            $bill_management_view->where(function ($query) use ($searchval, $column_search, $bill_management_view) {
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
                $bill_management_view->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $bill_management_view->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $bill_management_view->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $bill_management_view->where('is_cod', $filterByStatus);
            }

            $count = $bill_management_view->count();

            if ($offset) {
                $offset = $offset * $limit;
                $bill_management_view->offset($offset);
            }
            if ($limit) {
                $bill_management_view->limit($limit);
            }
            Log::channel("billview")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType:: $from_date:: $to_date::");
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

                        // $ary['discount_percent'] = $value['coupon_code_percentage'] ?? "-";
                        // $amt_find = $value['sub_total'] * $value['coupon_code_percentage'] / 100;

                        // $ary['discount'] =  sprintf("%.2f", $amt_find);
                        // if ($ary['discount'] != " ") {
                        //     $ary['taxable_amount'] = $value['sub_total'] - $ary['discount'];
                        // } else {
                        //     $ary['taxable_amount'] = $value['sub_total'] - $ary['discount'];
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
                        //     $ary['net_amount'] = $ary['taxable_amount'] + $ary['cgst_amount'] + $ary['sgst_amount'];
                        //     $ary['igst_percent'] = '';
                        //     $ary['igst_amount'] = '';
                        // } else {
                        //     $ary['cgst_percent'] = '';
                        //     $ary['cgst_amount'] = '';
                        //     $ary['sgst_percent'] = '';
                        //     $ary['sgst_amount'] =  '';
                        //     $ary['igst_percent'] = $value['gst_value'] . "%";
                        //     $ary['igst_amount'] = sprintf("%.2f", $amt);
                        //     $ary['net_amount'] = $ary['taxable_amount'] + $ary['igst_amount'];
                        // }
                        // // $lhipping_charge = $value['shipping_cost'];
                        // $sum += sprintf("%.2f", $ary['net_amount']);
                        // // $total_amount = $value['shipping_cost'] + $sum;
                        // $total_amount = $sum;

                        $ary['gross_amount'] = $value['sub_total'];
                        $ary['quantity'] = $value['quantity'];
                        $ary['discount_percent'] = $value['coupon_code_percentage'] ?? "-";
                        $ary['discount'] =  "0.00";
                        // $ary['taxable_amount'] = $value['sub_total'];
                        $gst_calc = 1 + ($value['gst_value'] / 100 * 1);
                        $exc_gst = $value['sub_total'] / $gst_calc;
                        $exec_gst_amount = number_format(floor($exc_gst * 100) / 100, 2, '.', '');
                        $amt = $value['sub_total'] - $exec_gst_amount;
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
                        if (!empty($value['coupon_code'])) {
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
                        // $subTotal = sprintf("%.2f", $value['unit_price'] * $value['quantity']);
                        // $ary['gross_amount'] = $subTotal;
                        // $ary['quantity'] = $value['quantity'];

                        // $ary['discount_percent'] = $value['discount_percentage'] ?? "-";
                        // $amt_find = $subTotal * $value['discount_amount'] / 100;

                        // $discountAmount = sprintf("%.2f", $subTotal * ($value['discount_percentage'] / 100));
                        // $ary['discount'] = $discountAmount ?? "-";
                        // if ($value['discount_amount'] != " ") {
                        //     $ary['taxable_amount'] = $subTotal - $discountAmount;
                        // } else {
                        //     $ary['taxable_amount'] = $subTotal;
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
                        //     $ary['net_amount'] = $ary['taxable_amount'] + $ary['cgst_amount'] + $ary['sgst_amount'];
                        //     $ary['igst_percent'] = '';
                        //     $ary['igst_amount'] = '';
                        // } else {
                        //     $ary['cgst_percent'] = '';
                        //     $ary['cgst_amount'] = '';
                        //     $ary['sgst_percent'] = '';
                        //     $ary['sgst_amount'] =  '';
                        //     $ary['igst_percent'] = $value['gst_value'] . "%";
                        //     $ary['igst_amount'] = sprintf("%.2f", $amt);
                        //     $ary['net_amount'] = $ary['taxable_amount'] + $ary['igst_amount'];
                        // }
                        // // $lhipping_charge = $value['shipping_cost'];
                        // $sum += sprintf("%.2f", $ary['net_amount']);
                        // // $total_amount = $value['shipping_cost'] + $sum;
                        // $total_amount = $sum;

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
                        ->select('state.state_name', 'district.district_name', 'orders.billing_customer_first_name', 'orders.billing_customer_last_name', 'orders.billing_email', 'orders.billing_mobile_number', 'orders.billing_gst_no', 'orders.billing_alt_mobile_number', 'orders.billing_address_1', 'orders.billing_address_2', 'orders.billing_landmark', 'orders.billing_pincode', 'orders.bulk_order_enquiry_id', 'orders.customer_id')->first();
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
                    'message' => __('Bill viewed successfully'),
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
}
