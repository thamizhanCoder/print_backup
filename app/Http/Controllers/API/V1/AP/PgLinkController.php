<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Events\EnquiryAssign;
use App\Events\PaymentSuccessBulkOrder;
use App\Events\SendFinalPgLink;
use App\Events\SendPgLink;
use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Models\BulkOrderEnquiry;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\PgLinkHistory;
use App\Models\UserModel;
use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleHttpClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use paytm\paytmchecksum\PaytmChecksum;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class PgLinkController extends Controller
{
    public function generatePaytmLink(Request $request,$orderId)
    {
    
        try{
        $getOrderId = Orders::where('order_id','=',$orderId)->first();
        $getOrderAmount = $getOrderId->order_totalamount/2;
        $getOrderDate = $getOrderId->order_date;
        
        if ($getOrderId->customer_id != null) {
            $get_customer_details = Orders::where('orders.order_id', '=', $orderId)->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->leftJoin('bulk_order_quote','orders.bulk_order_quote_id','=','bulk_order_quote.bulk_order_quote_id')->select(DB::raw("CONCAT(customer.customer_first_name, ' ', customer.customer_last_name) AS contact_person_name"), 'customer.mobile_no', 'customer.email','bulk_order_quote.round_off')->first();
        } else if ($getOrderId->customer_id == null) {
            $get_customer_details = Orders::where('orders.order_id', '=', $orderId)->leftjoin('bulk_order_enquiry', 'bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')->leftJoin('bulk_order_quote','orders.bulk_order_quote_id','=','bulk_order_quote.bulk_order_quote_id')->select('bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email','bulk_order_quote.round_off')->first();
        }




        $currentDateTime = Server::getDateTime();
        $carbonDate = Carbon::parse($currentDateTime);

        $modifiedDate = $carbonDate->addDays(2);

        $formattedDate = $modifiedDate->format('d/m/Y H:i:s');

        $merchantKey = "c7zDcs7&nCd%y_L&";
        $mid = "iBtdUj08196534775411";

        // $mid = "DnNAod66020520893813";
        // $merchantKey = "t%wo_kYyiE3iQdeA";

        $generatedorderId =  "New payments for ".Str::random(7);
        $paytmParams = [
        "body" => [
          "mid"             => $mid,
          "linkType"        => "INVOICE",
          "linkDescription" => $generatedorderId,
          "linkName"        => "new",
          "expiryDate" => $formattedDate,
          "statusCallbackUrl" => "{{ env('WEBSITEURL') }}V1/AP/webhook/paytm",
          "invoiceId" => Str::random(10),
          "amount" => $getOrderAmount,
        //   "sendSms" => true,
        //   "sendEmail" => true,
          "customerContact" => [
            "customerName" => $get_customer_details->contact_person_name,
            "customerEmail" => $get_customer_details->email,
            "customerMobile" => $get_customer_details->mobile_no,
          ]
        ],
        "head" => [
          "tokenType" => "AES",
        ],
      ];

      $checksum = PaytmChecksum::generateSignature(
        json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES),
        $merchantKey
      );

      $paytmParams["head"]["signature"] = $checksum;

      $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

      $url = "https://securegw.paytm.in/link/create";

      $client = new GuzzleHttpClient();

      $response = $client->post($url, [
        'body' => $post_data,
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ]);

       $responseBody = $response->getBody()->getContents();
       $data = json_decode($responseBody, true);

       if (!empty($data['body']['linkId']) && isset($data['body']['amount'])) {
        $linkId = $data['body']['linkId'];
        $amount = $data['body']['amount'];
        $link = $data['body']['shortUrl'];
        $orderId;
        $get_item_details = OrderItems::where('order_id',$getOrderId->order_id)->get();
        $get_delivery_charge = Orders::where('order_id',$getOrderId->order_id)->first();
        
        $productDetails = [];
        $totalQuoteAmount = 0;
        foreach($get_item_details as $details)
        {
          $ary = [];
          $ary['product_name'] = $details['product_name'];
          $ary['quantity'] = $details['quantity'];
          $ary['unit_price'] = $details['unit_price'];
          $ary['quote_amount'] = $details['quote_amount'];
          $ary['discount'] = number_format($details['discount_amount'] * $details['quantity'],2);
          $productDetails[] = $ary;
          $totalQuoteAmount += $details['quote_amount'];
        }
        // $service_name = $this->getProductDetails($getOrderId->order_id);

        $createPgLinkHistory = new PgLinkHistory();
        $createPgLinkHistory->order_id = $orderId;
        $createPgLinkHistory->link_id = $linkId;
        $createPgLinkHistory->amount = $amount;
        $createPgLinkHistory->short_url = $link;
        $createPgLinkHistory->check_order_id = $generatedorderId; // Assuming $generatedorderId is defined
        $createPgLinkHistory->payment_status = 0;
        $createPgLinkHistory->expiry_date = $formattedDate;
    
        Log::channel("pglinkgenerate")->info("******* Pg link Generate Insert Method Start *******");
        Log::channel("pglinkgenerate")->info("Pg link Generate Controller start:: Request values :: " . json_encode($createPgLinkHistory));
    
        $createPgLinkHistory->save();
    
        $desc =  'Pg link Generate ' . $createPgLinkHistory->video_url . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
        $activitytype = Config('activitytype.Pg Link Generate');
        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
        
        if($getOrderId->payment_status == 0)
        {
        $dateTime = Carbon::createFromFormat('d/m/Y H:i:s', $formattedDate);

        $formattedDate = $dateTime->format('d/m/Y h:i A');  
        //mail send
        $mail_data = [];
        $mail_data['contact_person_name'] = !empty($get_customer_details->contact_person_name) ? $get_customer_details->contact_person_name : $get_customer_details->contact_person_name;
        $mail_data['email'] = $get_customer_details->email;
        $mail_data['support_email'] = "printapp2021@gmail.com";
        $mail_data['short_url'] = $link;
        $mail_data['order_id'] = $getOrderId->order_code;
        $mail_data['order_amount'] = $getOrderId->order_totalamount;
        $mail_data['initial_amount'] = $getOrderAmount;
        $mail_data['product_details'] = $productDetails;
        $mail_data['shipping_cost'] = $get_delivery_charge->shipping_cost;
        $mail_data['discount'] = $get_delivery_charge->shipping_cost;
        $mail_data['sub_total'] = number_format($totalQuoteAmount,2);
        $order_amount = round($get_delivery_charge->shipping_cost + $totalQuoteAmount);
        $formatted_order_amount = number_format($order_amount, 2);
        $mail_data['remaining_value'] = $get_customer_details->round_off;
                // if ($mail_data['remaining_value'] >= 0.00) {
                //   $mail_data['roundOffValueSymbol'] = "";
                // } else {
                //   $mail_data['roundOffValueSymbol'] = "-";
                // }
        $mail_data['order_amount'] = $formatted_order_amount;
        $amount_to_pay = ($get_delivery_charge->shipping_cost + $totalQuoteAmount)/2;
        $formatted_amount = number_format($amount_to_pay, 2);
        $mail_data['amount_to_pay'] = $formatted_amount;
        $mail_data['due_by'] = $formattedDate;
        $mail_data['quote_code'] = $get_delivery_charge->quote_code;
        
        if ($get_customer_details->email != '') {
            event(new SendPgLink($mail_data));
        }
        }

        if($getOrderId->payment_status == 3)
        {
          $dateTime = Carbon::createFromFormat('d/m/Y H:i:s', $formattedDate);

        $formattedDate = $dateTime->format('d/m/Y h:i A');
        
        $initial_transaction_date = $getOrderId->payment_transaction_date;

        $timestamp = strtotime($initial_transaction_date);

        $formatted_date = date('d/m/Y h:i A', $timestamp);

        //mail send
        $mail_data = [];
        $mail_data['contact_person_name'] = !empty($get_customer_details->contact_person_name) ? $get_customer_details->contact_person_name : $get_customer_details->contact_person_name;
        $mail_data['email'] = $get_customer_details->email;
        $mail_data['support_email'] = "printapp2021@gmail.com";
        $mail_data['short_url'] = $link;
        $mail_data['order_id'] = $getOrderId->order_code;
        $mail_data['order_amount'] = $getOrderId->order_totalamount;
        $mail_data['initial_paid_amount'] = $getOrderId->payment_amount;
        $mail_data['initial_transaction_id'] = $getOrderId->payment_transcation_id;
        $mail_data['initial_transaction_date'] = $formatted_date;
        $mail_data['product_details'] = $productDetails;
        $mail_data['shipping_cost'] = $get_delivery_charge->shipping_cost;
        $mail_data['sub_total'] = $totalQuoteAmount;
        $mail_data['remaining_value'] = $get_customer_details->round_off;
        $order_amount = round($get_delivery_charge->shipping_cost + $totalQuoteAmount);
        $formatted_order_amount = number_format($order_amount, 2);
        $mail_data['order_amount'] = $formatted_order_amount;
        $amount_to_pay = ($get_delivery_charge->shipping_cost + $totalQuoteAmount)/2;
        $formatted_amount = number_format($amount_to_pay, 2);
        if ($formatted_amount ==  $amount_to_pay) {
          $formatted_amount .= ".00";
        }
        $mail_data['amount_to_pay'] = $formatted_amount;
        $mail_data['due_by'] = $formattedDate;
        $mail_data['quote_code'] = $get_delivery_charge->quote_code;
        
        if ($get_customer_details->email != '') {
            event(new SendFinalPgLink($mail_data));
        }
        }



        // log end ***********
        Log::channel("pglinkgenerate")->info("Pg link Generate Controller end:: save values :: " . json_encode($createPgLinkHistory) . "::::end");
        Log::channel("pglinkgenerate")->info("******* Pg link Generate Insert Method End *******");
        Log::channel("pglinkgenerate")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
    } 
    
        return $responseBody;
    
    }
       catch (\Exception $exception) {

        // log for error start
        Log::channel("pglinkgenerate")->error("******* Pg link Generate Insert Method Error Start *******");
        Log::channel("pglinkgenerate")->error($exception);
        Log::channel("pglinkgenerate")->error("*******  Pg link Generate Insert Method Error End *******");
        Log::channel("pglinkgenerate")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
        // log for error end

        return response()->json([
            'error' => 'Internal server error.',
            'message' => $exception->getMessage()
        ], 500);
    }
    }


    public function getProductDetails($orderId)
    {
        $serviceClicked = OrderItems::whereIn('order_id', $orderId)->get();
        $service_name = [];
        foreach ($serviceClicked as $key => $name) {
            $service_name[$key] = $name->product_name;
        }
        return $service_name;
    }

    public function SendPgLinkEmail(Request $request,$orderId)
    {
      $get_short_url = PgLinkHistory::where('payment_status','!=',1)->where('order_id',$orderId)->first();
      $getOrderId = Orders::where('order_id',$orderId)->first();
      $getOrderAmount = $getOrderId->order_totalamount/2;

      if ($getOrderId->customer_id != null) {
        $get_customer_details = Orders::where('orders.order_id', '=', $orderId)->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->leftJoin('bulk_order_quote','orders.bulk_order_quote_id','=','bulk_order_quote.bulk_order_quote_id')->select(DB::raw("CONCAT(customer.customer_first_name, ' ', customer.customer_last_name) AS contact_person_name"), 'customer.mobile_no', 'customer.email','bulk_order_quote.round_off')->first();
    } else if ($getOrderId->customer_id == null) {
        $get_customer_details = Orders::where('orders.order_id', '=', $orderId)->leftjoin('bulk_order_enquiry', 'bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')->leftJoin('bulk_order_quote','orders.bulk_order_quote_id','=','bulk_order_quote.bulk_order_quote_id')->select('bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email','bulk_order_quote.round_off')->first();
    }

    $currentDateTime = Server::getDateTime();
        $carbonDate = Carbon::parse($currentDateTime);

        $modifiedDate = $carbonDate->addDays(2);

        $formattedDate = $modifiedDate->format('d/m/Y H:i:s');

    $get_item_details = OrderItems::where('order_id',$orderId)->get();
        $get_delivery_charge = Orders::where('order_id',$orderId)->first();
        
        $productDetails = [];
        $totalQuoteAmount = 0;
        foreach($get_item_details as $details)
        {
          $ary = [];
          $ary['product_name'] = $details['product_name'];
          $ary['quantity'] = $details['quantity'];
          $ary['unit_price'] = $details['unit_price'];
          $ary['quote_amount'] = $details['quote_amount'];
          $ary['discount'] = $details['discount_amount'] * $details['quantity'];
          $productDetails[] = $ary;
          $totalQuoteAmount += $details['quote_amount'];
        }
     //mail send
     if($getOrderId->payment_status == 0)
        {
        $dateTime = Carbon::createFromFormat('d/m/Y H:i:s', $formattedDate);

        $formattedDate = $dateTime->format('d/m/Y h:i A');  
        //mail send
        $mail_data = [];
        $mail_data['contact_person_name'] = !empty($get_customer_details->contact_person_name) ? $get_customer_details->contact_person_name : $get_customer_details->contact_person_name;
        $mail_data['email'] = $get_customer_details->email;
        $mail_data['support_email'] = "printapp2021@gmail.com";
        $mail_data['short_url'] = $get_short_url;
        $mail_data['order_id'] = $getOrderId->order_code;
        $mail_data['order_amount'] = $getOrderId->order_totalamount;
        $mail_data['initial_amount'] = $getOrderAmount;
        $mail_data['product_details'] = $productDetails;
        $mail_data['shipping_cost'] = $get_delivery_charge->shipping_cost;
        $mail_data['sub_total'] = $totalQuoteAmount;
        $order_amount = round($get_delivery_charge->shipping_cost + $totalQuoteAmount);
        $formatted_order_amount = number_format($order_amount, 2);
        $mail_data['order_amount'] = $formatted_order_amount;
        $mail_data['remaining_value'] = $get_customer_details->round_off;
        $amount_to_pay = ($get_delivery_charge->shipping_cost + $totalQuoteAmount)/2;
        $formatted_amount = number_format($amount_to_pay, 2);
        $mail_data['amount_to_pay'] = $formatted_amount;
        $mail_data['due_by'] = $formattedDate;
        $mail_data['quote_code'] = $get_delivery_charge->quote_code;
        
        if ($get_customer_details->email != '') {
            event(new SendPgLink($mail_data));
        }
        return response()->json([
          'keyword'      => 'success',
          'message'      => __('Mail Sent successfully'),
        ]);
        }

        if($getOrderId->payment_status == 3)
        {
          $dateTime = Carbon::createFromFormat('d/m/Y H:i:s', $formattedDate);

        $formattedDate = $dateTime->format('d/m/Y h:i A');
        
        $initial_transaction_date = $getOrderId->payment_transaction_date;

        $timestamp = strtotime($initial_transaction_date);

        $formatted_date = date('d/m/Y h:i A', $timestamp);

        //mail send
        $mail_data = [];
        $mail_data['contact_person_name'] = !empty($get_customer_details->contact_person_name) ? $get_customer_details->contact_person_name : $get_customer_details->contact_person_name;
        $mail_data['email'] = $get_customer_details->email;
        $mail_data['support_email'] = "printapp2021@gmail.com";
        $mail_data['short_url'] = $get_short_url;
        $mail_data['order_id'] = $getOrderId->order_code;
        $mail_data['order_amount'] = $getOrderId->order_totalamount;
        $mail_data['initial_paid_amount'] = $getOrderId->payment_amount;
        $mail_data['initial_transaction_id'] = $getOrderId->payment_transcation_id;
        $mail_data['initial_transaction_date'] = $formatted_date;
        $mail_data['product_details'] = $productDetails;
        $mail_data['shipping_cost'] = $get_delivery_charge->shipping_cost;
        $mail_data['sub_total'] = number_format($totalQuoteAmount,2);
        $order_amount = $get_delivery_charge->shipping_cost + $totalQuoteAmount;
        $formatted_order_amount = number_format($order_amount, 2);
        $mail_data['order_amount'] = $formatted_order_amount;
        $amount_to_pay = ($get_delivery_charge->shipping_cost + $totalQuoteAmount)/2;
        $formatted_amount = number_format($amount_to_pay, 2);
        if ($formatted_amount ==  $amount_to_pay) {
          $formatted_amount .= ".00";
        }
        $mail_data['amount_to_pay'] = $formatted_amount;
        $mail_data['due_by'] = $formattedDate;
        $mail_data['quote_code'] = $get_delivery_charge->quote_code;
        
        if ($get_customer_details->email != '') {
            event(new SendFinalPgLink($mail_data));
        }
        return response()->json([
          'keyword'      => 'success',
          'message'      => __('Mail Sent successfully'),
        ]);
        }
     else {
      return response()->json([
        'keyword'      => 'failed',
        'message'      => __('Mail Send failed'),
        'data'        => []
      ]);
    }
    }


    public function handlePaytmWebhook(Request $request)
    {
        Log::channel("webhook")->info("******* initial Response Method Start *******");
        Log::channel("webhook")->info("initial start:: Response values :: " . $request);
        Log::channel("webhook")->info("End Response");

        $requestContent = file_get_contents("php://input");

        $parsedData = [];
        parse_str($requestContent, $parsedData);

        $status = $parsedData['STATUS'];

        $udf_1 = $parsedData['udf_1'];

        if($status == "TXN_FAILURE")
        {
          $udf_1 = $parsedData['udf_1'];
          Log::channel("webhook")->info("initial udf_1:: Response values :: " . $udf_1);

          $check_udf_1 = PgLinkHistory::where('check_order_id', 'LIKE', '%' . $udf_1 . '%')->first();

          Log::channel("webhook")->info("final udf_1:: Response values :: " . $check_udf_1);

          //Send Mail
          $getOrderId = PgLinkHistory::where('check_order_id', 'LIKE', '%' . $udf_1 . '%')->first();

            $update = PgLinkHistory::where('check_order_id', 'LIKE', '%' . $udf_1 . '%')->update(array(
              'payment_status' => 2,
              'transaction_id'=> $parsedData['TXNID'],
              'transaction_mode'=> $parsedData['PAYMENTMODE'],
              'paytm_response'=>$requestContent,
              'updated_on' => Server::getDateTime(),
              'updated_by' => 1
          ));
          
        $txnamount = $parsedData['TXNAMOUNT'];
        }
        
        else if($status == "TXN_SUCCESS")
        {
        $udf_1 = $parsedData['udf_1'];
        $txnamount = $parsedData['TXNAMOUNT'];
        $check_udf_1 = PgLinkHistory::where('check_order_id', 'LIKE', '%' . $udf_1 . '%')->first();

            $update = PgLinkHistory::where('check_order_id', 'LIKE', '%' . $udf_1 . '%')->update(array(
                'payment_status' => 1,
                'paytm_response'=>$requestContent,
              'transaction_id'=> $parsedData['TXNID'],
              'transaction_mode'=> $parsedData['PAYMENTMODE'],
                'updated_on' => Server::getDateTime(),
                'updated_by' => 1
            ));
            $getOrderId = PgLinkHistory::where('check_order_id', 'LIKE', '%' . $udf_1 . '%')->first();

            //order details
            $totalAmount = PgLinkHistory::where('order_id', $getOrderId->order_id)->where('payment_status',1)->sum('amount');
            $updatePaymentAmount = Orders::where('order_id',  $getOrderId->order_id)->update(array(
                'payment_amount' => $totalAmount,
                'payment_transaction_date' => Server::getDateTime(),
                'payment_transcation_id'=> $parsedData['TXNID'],
                'paytm_payment_mode'=>$parsedData['PAYMENTMODE'],
                'payment_status'=> 3,
                'updated_on' => Server::getDateTime(),
                'updated_by' => 1
            ));

            $checkAmount = Orders::where('order_id',  $getOrderId->order_id)->first();
            
            if($checkAmount->payment_amount == $checkAmount->order_totalamount)
            {
            Orders::where('order_id',  $getOrderId->order_id)->update(array(
                'payment_status' => 1,
                'payment_delivery_status' => 1,
                'order_time' => date('H'),
                'payment_transcation_id'=> $parsedData['TXNID'],
                'updated_on' => Server::getDateTime(),
                'updated_by' => 1
            ));
            }
            $getOrderId = PgLinkHistory::where('check_order_id', $udf_1)->first();
            $txnamount = $parsedData['TXNAMOUNT'];

            $quoteSaveDetails = $this->SendMailForPaymentSuccess($getOrderId->order_code,$getOrderId->order_id,$txnamount);

            $getcurrentdateTime = Server::getDateTime();
            $getCustomerEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id',$checkAmount->bulk_order_enquiry_id)->first();

            $paymentSuccessSendEmail = $this->SendMailForPaymentSuccessForCustomer($getCustomerEmail->contact_person_name,$getCustomerEmail->email,$getOrderId->order_id,$txnamount,$parsedData['TXNID'],$checkAmount->order_code,$getcurrentdateTime);

            $title = "Amount Paid By Customer"." - ".$checkAmount->order_code;
            $body = "You have successfully received the amount $txnamount information for bulk order $checkAmount->order_code ";
            $module = 'Amount Paid By Customer';
            $page = 'amount_paid_by_customer';
            $portal = 'admin';
            $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
            $data = [
                'order_id' => $getOrderId->order_id,
                'platform' => "admin",
                'amount'=>$txnamount,
                'random_id' => $random_id,
                'page' => 'amount_paid_by_customer',
                'url' => ''
            ];

            $message = [
                'title' => $title,
                'body' => $body,
                'page' => $page,
                'data' => $data,
                'portal' => $portal,
                'module' => $module
            ];
        $get_admin_token = UserModel::where('acl_user_id',1)->where('token', '!=', NULL)->select('token')->first();

            if (!empty($get_admin_token)) {
                $push = Firebase::sendSingle($get_admin_token->token, $message);
                Log::channel("webhook")->info("push values:: push values :: " . $push);
            }
            $getdata = GlobalHelper::notification_create($title, $body, 1, 1, 1, $module, $page, $portal, $data, $random_id);
        }

        Log::channel("webhook")->info("Webhook end:: Response values");

    }


    public function SendMailForPaymentSuccessForCustomer($customer_name,$email,$orderId,$txnamount,$txnid,$order_code,$transactionDate)
    {
      $timestamp = strtotime($transactionDate);

        $formatted_date = date('d/m/Y h:i A', $timestamp);
      $mail_data = [
        'customer_name'=> $customer_name,
        'email' => $email,
        'order_id'=> $orderId,
        'txn_amount'=> $txnamount,
        'txn_id'=> $txnid,
        'order_code'=> $order_code,
        'transaction_date'=> $formatted_date,
    ];

    Mail::send('mail.sendPaymentSuccessEmailToCustomer', $mail_data, function ($message) use ($mail_data) {
        $message->to($mail_data['email'])
                ->subject('Payment Successfully Received');
    });
    }

    public function SendMailForPaymentSuccess($order_code,$orderId,$amount)
    {
      
        $get_admin_email = UserModel::where('acl_user_id',1)->select('email')->first();
        if($get_admin_email->email != null)
        {
            $mail_data = [];
            $mail_data['order_id'] = $orderId;
            $mail_data['order_code'] = $order_code;
            $mail_data['amount'] = $amount;
            $mail_data['email'] = $get_admin_email->email;

            if ($get_admin_email->email != '') {
                event(new PaymentSuccessBulkOrder($mail_data));
        }
        }
        return $get_admin_email->email;
    }
    
}
