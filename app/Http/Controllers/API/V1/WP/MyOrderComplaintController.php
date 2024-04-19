<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\ExpectedDays;
use App\Models\Tickets;
use App\Models\TicketInbox;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\PhotoFrameUploadModel;
use App\Models\ProductVariant;
use App\Models\SelfieUploadModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\myorderRequest;
use App\Models\BillItems;
use App\Models\Bills;
use App\Models\CompanyInfo;
use File;

class MyOrderComplaintController extends Controller
{
    public function myordercomplaint_create(myorderRequest $request)
    {

        try {
        Log::channel("myordercomplaint")->info("** started the myordercomplaint create method **");          

        $myordercomplaint = new Tickets();
        $myordercomplaint->subject = $request->subject;
        $myordercomplaint->priority = $request->priority;
        $myordercomplaint->order_items_id = $request->order_items_id;
        $myordercomplaint->created_on = Server::getDateTime();
        $myordercomplaint->created_by = JwtHelper::getSesUserId();

        if ($myordercomplaint->save()) {


            $ticket_no = env('TICKETPREFIX') . str_pad($myordercomplaint->tickets_id, 3, '0', STR_PAD_LEFT);
            $update_ticketdetails = Tickets::find($myordercomplaint->tickets_id);
            $update_ticketdetails->ticket_no = $ticket_no;
            $update_ticketdetails->save();

            $ticket_message = new TicketInbox();
            $ticket_message->tickets_id = $myordercomplaint->tickets_id;
            $ticket_message->messages = $request->message;
            $ticket_message->customer_id = JwtHelper::getSesUserId();
            $ticket_message->reply_on = Server::getDateTime();
            $ticket_message->save();

            $myordercomplaints = Tickets::where('tickets_id', $myordercomplaint->tickets_id)->first();


            Log::channel("myordercomplaint")->info("** myordercomplaint save details : $myordercomplaints **");


            // log activity
            // $desc = 'myordercomplaint ' . '(' . $myordercomplaint->myordercomplaint_name . ')' . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
            // $activitytype = Config('activitytype.myordercomplaint');
            // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

            Log::channel("myordercomplaint")->info("** end the myordercomplaint create method **");

            return response()->json([
                'keyword' => 'success',
                'message' => __('My order complaint created successfully'),
                'data' => [$myordercomplaints],

            ]);
        } else {
            return response()->json([
                'keyword' => 'failure',
                'message' => __('My order complaint created failed'),
                'data' => [],
            ]);
        }    

        } catch (\Exception $exception) {
            Log::channel("myordercomplaint")->error($exception);
            Log::channel("myordercomplaint")->error('** end the myordercomplaint create method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
    }
}

    public function invoice_pdf_download(Request $request)
    {
        try 
        {
            $order_item_id = ($request->order_items_id) ? $request->order_items_id : '';
           
            $get_order_id = BillItems::where('bill_item.order_items_id',$order_item_id)->first();

            $get_billno = Bills::where('bill_id',$get_order_id->bill_id)->first();

            $dispatch_invoice = OrderItems::select('order_items.*', 'orders.coupon_amount', 'orders.order_code', 'orders.order_date','orders.coupon_code_percentage','orders.billing_state_id', 'orders.coupon_code')
                ->leftJoin('orders', 'order_items.order_id', '=', 'orders.order_id')
                ->where('order_items.bill_no', $get_billno->bill_no);
                
            $count = $dispatch_invoice->count();

            $dispatch_invoice->orderBy('order_items.order_id', 'desc');
            $dispatch_invoice = $dispatch_invoice->get();

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
                    // $ary['gross_amount'] = round($value['sub_total']);
                    // $ary['quantity'] = $value['quantity'];
                    
                    // $ary['discount_percent'] =$value['coupon_code_percentage'] ?? "-";
                    // $amt_find = $value['sub_total'] * $value['coupon_code_percentage'] / 100;

                    // $ary['discount'] =  round($amt_find);
                    // if ($ary['discount'] != " ") {
                    //     $ary['taxable_amount'] = round($value['sub_total'] - $ary['discount']);
                    // } else {
                    //     $ary['taxable_amount'] = round($value['sub_total']);
                    // }
                    // $gst_calc = 1 + ($value['gst_value'] / 100 * 1);
                    // $exc_gst = $ary['taxable_amount'] / $gst_calc;
                    // $amt = $ary['taxable_amount'] - $exc_gst;
                    // $round_exc_gst = round($exc_gst, 2);
                    // if ($value['billing_state_id'] == 33) {
                    //     $ary['cgst_percent'] = $value['gst_value'] / 2 . "%";
                    //     $ary['cgst_amount'] = round($amt / 2);
                    //     $ary['sgst_percent'] = $value['gst_value'] / 2 . "%";
                    //     $ary['sgst_amount'] =  round($amt / 2);
                    //     $ary['net_amount'] = $ary['taxable_amount'] + $ary['cgst_amount'] + $ary['sgst_amount'];
                    //     $ary['igst_percent'] = '-';
                    //     $ary['igst_amount'] = '-';
                    //     $deliveryChargeAmount += $value['delivery_charge'];
                    // } else {
                    //     $ary['cgst_percent'] = '-';
                    //     $ary['cgst_amount'] = '-';
                    //     $ary['sgst_percent'] = '-';
                    //     $ary['sgst_amount'] =  '-';
                    //     $ary['igst_percent'] = $value['gst_value'] . "%";
                    //     $ary['igst_amount'] = round($amt);
                    //     $ary['net_amount'] = $ary['taxable_amount'] + $ary['igst_amount'];
                    //     $deliveryChargeAmount += $value['delivery_charge'];
                    // }
                    // $sum += round($ary['net_amount']);
                    // $total_amount = $sum;

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
                    if ($remaining_value >= 0.00) {
                        $roundOffValueSymbol = "+";
                    } else {
                        $roundOffValueSymbol = "-";
                    }

                    $customer_details =  Orders::where('order_id', $value['order_id'])->leftjoin('district', 'orders.billing_city_id', '=', 'district.district_id')
                        ->leftJoin('state', 'orders.billing_state_id', '=', 'state.state_id')
                        ->select('state.state_name', 'billing_pincode', 'billing_customer_first_name', 'billing_customer_last_name', 'billing_landmark', 'billing_email', 'billing_mobile_number','billing_alt_mobile_number', 'billing_address_1', 'billing_address_2','district.district_name','billing_gst_no','customer_id')->first();
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
                $mpdf->WriteHTML(\View::make('report/dispatch', $final)->with('final', $final)->with('order_code', $order_code)->with('order_date', $order_date)->with('customer_first_name', $customer_first_name)->with('customer_last_name', $customer_last_name)->with('customer_mobile', $customer_mobile)->with('customer_email', $customer_email)->with('customer_district', $customer_district)->with('customer_state', $customer_state)->with('customer_pincode', $customer_pincode)->with('customer_landmark', $customer_landmark)->with('final_invoice_date', $final_invoice_date)->with('company_name', $company_name)->with('company_address', $company_address)->with('company_mobile_no', $company_mobile_no)->with('req', $request)->with('sum', sprintf("%.2f", $sum))->with('customer_address', $customer_address)->with('customer_address_2', $customer_address_2)->with('customer_alt_mobile_number', $customer_alt_mobile_number)->with('customer_gst_no', $customer_gst_no)->with('total_amount', $totalAmountPdf)->with('count', $count)->with('deliveryChargeAmount', sprintf("%.2f", $deliveryChargeAmount))->with('coupon_amount', sprintf("%.2f", $coupon_amount))->with('customer_id', $customer_id)->with('remaining_value', abs($remaining_value))->with('roundOffValueSymbol', $roundOffValueSymbol)->with('no', 1)->render());
                $mpdf->Output($location, 'F');

                return response()->download($location, "dispatch.pdf");
                
            } else {
                return response()->json(
                    [
                        'keyword' => 'failure',
                        'message' => __('No data found'),
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
}