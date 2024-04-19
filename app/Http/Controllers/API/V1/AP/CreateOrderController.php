<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Events\OrderCreateFromAdmin;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\updateQuoteAttachmentRequest;
use App\Http\Traits\CreateOrderTrait;
use App\Models\BulkOrderEnquiry;
use App\Models\BulkOrderQuote;
use App\Models\BulkOrderQuoteDetails;
use App\Models\BulkOrderTrackHistory;
use App\Models\OrderItems;
use App\Models\Orders;

class CreateOrderController extends Controller
{
    use CreateOrderTrait;

    public function bulkOrderCreate(CreateOrderRequest $request)
    {
        try {
            Log::channel("createOrder")->info('** started the bulkOrderCreate method **');

            $quoteUpdateDetailsMessage = "";

            $quoteDetails = $this->getQuoteDetails($request->bulk_order_quote_id);

            $order = new Orders();
            $order->bulk_order_enquiry_id = $quoteDetails->bulk_order_enquiry_id;
            $order->bulk_order_quote_id = $quoteDetails->bulk_order_quote_id;
            $order->enquiry_code = $quoteDetails->enquiry_code;
            $order->quote_code = $quoteDetails->quote_code;
            $order->billing_customer_first_name = $quoteDetails->billing_customer_first_name;
            $order->billing_mobile_number = $quoteDetails->billing_mobile_number;
            $order->billing_alt_mobile_number = $quoteDetails->billing_alt_mobile_number;
            $order->billing_email = $quoteDetails->billing_email;
            $order->billing_gst_no = $quoteDetails->billing_gst_no;
            $order->billing_pincode = $quoteDetails->billing_pincode;
            $order->billing_address_1 = $quoteDetails->billing_address_1;
            $order->billing_address_2 = $quoteDetails->billing_address_2;
            $order->billing_landmark = $quoteDetails->billing_landmark;
            $order->billing_state_id = $quoteDetails->billing_state_id;
            $order->billing_city_id = $quoteDetails->billing_city_id;
            $order->purchase_document = $request->purchase_document;
            // $order->payment_transaction_date = Server::getDateTime();
            $order->order_date = Server::getDateTime();
            $order->shipping_cost = $quoteDetails->delivery_charge;
            $order->payment_amount = $quoteDetails->grand_total;
            $order->order_totalamount = $quoteDetails->grand_total;
            $order->cancelled_order_totalamount = $quoteDetails->grand_total;
            $order->payment_status = 0;
            $order->payment_mode = "PG";
            $order->is_cod = 2;
            $order->order_from = "Direct";
            $order->created_on = Server::getDateTime();
            $order->created_by = JwtHelper::getSesUserId();

            if ($order->save()) {
                $order->order_code = 'ORD' . str_pad($order->order_id, 3, '0', STR_PAD_LEFT);

                $quoteJsonDetails = $request->quote_details;
                
                if (!empty($quoteJsonDetails)) {

                    $quoteUpdateDetails = $this->updateQuoteItems($quoteJsonDetails);

                    if ($quoteUpdateDetails == true) {

                        $quoteUpdateDetailsMessage = "Quote details successfullly updated in sub table";
                        Log::channel("createOrder")->info($quoteUpdateDetailsMessage);
                    } else {

                        $quoteUpdateDetailsMessage = "Quote detail sub table records not updated correctly";
                        Log::channel("createOrder")->info($quoteUpdateDetailsMessage);
                    }
                }

                $orderItemDetails = $this->insertOrderItems($request->bulk_order_quote_id, $order->order_id);
                $order->total_quantity = $orderItemDetails;
                $order->save();

                //Update the quote table
                $updateStatusQuote = BulkOrderQuote::find($request->bulk_order_quote_id);
                $updateStatusQuote->is_orderplaced = 1;
                $updateStatusQuote->status = 8;
                $updateStatusQuote->updated_on = Server::getDateTime();
                $updateStatusQuote->updated_by = JwtHelper::getSesUserId();
                $updateStatusQuote->save();
                Log::channel("createOrder")->info("update the enquiry status save value :: $updateStatusQuote");

                //Update the enquiry table
                $updateStatusEnquiry = BulkOrderEnquiry::find($quoteDetails->bulk_order_enquiry_id);
                $updateStatusEnquiry->status = 11;
                $updateStatusEnquiry->updated_on = Server::getDateTime();
                $updateStatusEnquiry->updated_by = JwtHelper::getSesUserId();
                $updateStatusEnquiry->save();
                Log::channel("createOrder")->info("update the enquiry status save value :: $updateStatusEnquiry");

                //BulkOrderTrackHistory
                $updateStatusEnquiryHistory = new BulkOrderTrackHistory();
                $updateStatusEnquiryHistory->bulk_order_enquiry_id = $quoteDetails->bulk_order_enquiry_id;
                $updateStatusEnquiryHistory->status = 11;
                $updateStatusEnquiryHistory->portal_type = 1;
                $updateStatusEnquiryHistory->created_on = Server::getDateTime();
                $updateStatusEnquiryHistory->acl_user_id = JwtHelper::getSesUserId();
                $updateStatusEnquiryHistory->save();
                Log::channel("createOrder")->info("track history table save value :: $updateStatusEnquiryHistory");

                // log activity
                $desc =  $order->order_code . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Quote');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                $getOrderItemDetails = OrderItems::where('order_id',$order->order_id)->get();

                $getDeliveryCharge = Orders::where('order_id',$order->order_id)->leftjoin('bulk_order_quote', 'bulk_order_quote.bulk_order_quote_id', '=', 'orders.bulk_order_quote_id')->select('orders.*', 'bulk_order_quote.round_off', 'bulk_order_quote.grand_total')->first();

                $final = [];
                $totalQuoteAmount = 0;
                foreach($getOrderItemDetails as $details)
                {
                    $ary = [];
                    $ary['product_name'] = $details['product_name'];
                    $ary['amount'] = $details['quote_amount'];
                    $final[] = $ary;
                    $totalQuoteAmount += $details['quote_amount'];
                }

                $get_customer_details = BulkOrderEnquiry::where('bulk_order_enquiry_id',$quoteDetails->bulk_order_enquiry_id)->first();
                $getOrderItemCount = OrderItems::where('order_id',$order->order_id)->count();
                if($get_customer_details->email != null)
                {
                    $mail_data = [];
                    $mail_data['contact_person_name'] = !empty($get_customer_details->contact_person_name) ? $get_customer_details->contact_person_name : $get_customer_details->contact_person_name;
                    $mail_data['email'] = $get_customer_details->email;
                    $mail_data['order_code'] = $order->order_code;
                    $mail_data['order_item_count'] = $getOrderItemCount;
                    $mail_data['product_details'] = $final;
                    $mail_data['total_amount'] = sprintf("%.2f", $totalQuoteAmount);
                    $mail_data['shipping_cost'] = sprintf("%.2f", $getDeliveryCharge->shipping_cost);
                    // $mail_data['grand_total'] =sprintf("%.2f", $totalQuoteAmount + $getDeliveryCharge->shipping_cost);
                    $mail_data['grand_total'] = $getDeliveryCharge->grand_total;
                    $mail_data['remaining_value'] = $getDeliveryCharge->round_off;
                    if ($mail_data['remaining_value'] >= 0.00) {
                    $mail_data['roundOffValueSymbol'] = "+";
                    } else {
                    $mail_data['roundOffValueSymbol'] = "-";
                    }
                    if ($get_customer_details->email != '') {
                        event(new OrderCreateFromAdmin($mail_data));
                }
                }

                Log::channel("createOrder")->info("quote bulkOrderCreate order save value :: $order");
                Log::channel("createOrder")->info('** end the bulkOrderCreate method **');


                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Order placed successfully',
                    'data'   => [],
                    'quoteUpdateDetailsMessage' => $quoteUpdateDetailsMessage,

                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message'      => 'Order placed failed',
                    'data'        => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("createOrder")->info('** start the bulkOrderCreate error method **');
            Log::channel("createOrder")->error($exception);
            Log::channel("createOrder")->info('** end the bulkOrderCreate error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function bulkOrderUpdate(Request $request)
    {
        try {
            Log::channel("createOrder")->info('** started the bulkOrderUpdate method **');

            $orderItemUpdateDetailsMessage = "";

            $order = Orders::find($request->order_id);
            $order->purchase_document = $request->purchase_document;
            $order->updated_on = Server::getDateTime();
            $order->updated_by = JwtHelper::getSesUserId();

            if ($order->save()) {

                $orderItemJsonDetails = $request->order_item_details;
                
                if (!empty($orderItemJsonDetails)) {

                    $orderItemUpdateDetails = $this->updateOrderItems($orderItemJsonDetails);

                    if ($orderItemUpdateDetails == true) {

                        $orderItemUpdateDetailsMessage = "Order item details successfullly updated in sub table";
                        Log::channel("createOrder")->info($orderItemUpdateDetailsMessage);
                    } else {

                        $orderItemUpdateDetailsMessage = "Order item detail sub table records not updated correctly";
                        Log::channel("createOrder")->info($orderItemUpdateDetailsMessage);
                    }
                }

                // log activity
                $desc =  'Waiting Payments - This ' . $order->order_code . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Waiting Payments');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                Log::channel("createOrder")->info("quote bulkOrderUpdate order save value :: $order");
                Log::channel("createOrder")->info('** end the bulkOrderUpdate method **');


                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Order updated successfully',
                    'data'   => [],
                    'orderItemUpdateDetailsMessage' => $orderItemUpdateDetailsMessage,

                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message'      => 'Order placed failed',
                    'data'        => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("createOrder")->info('** start the bulkOrderUpdate error method **');
            Log::channel("createOrder")->error($exception);
            Log::channel("createOrder")->info('** end the bulkOrderUpdate error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function updateQuoteAttachment(updateQuoteAttachmentRequest $request)
    {
        try {

            Log::channel("createOrder")->info('** started the updateQuoteAttachment method **');

            $quoteDetailId = $request->bulk_order_quote_details_id;

            $quoteDetail = BulkOrderQuoteDetails::where('bulk_order_quote_details_id', $quoteDetailId)->first();


            if ($quoteDetail->service_id == 2) {
                $photoPrintValidateImageJson = $this->photoPrintImageValidation($request->images);
                if ($photoPrintValidateImageJson == "success") {
                    $images = $request->images;
                } else {
                    return $photoPrintValidateImageJson;
                }
            }
            if ($quoteDetail->service_id == 3) {
                $phototFrameValidateImageJson = $this->photoFrameImageValidation($request->images);
                if ($phototFrameValidateImageJson == "success") {
                    $images = $request->images;
                } else {
                    return $phototFrameValidateImageJson;
                }
            }
            if ($quoteDetail->service_id == 4) {
                $personalizedValidateImageJson = $this->personalizedImageValidation($request->images);
                if ($personalizedValidateImageJson == "success") {
                    $images = $request->images;
                } else {
                    return $personalizedValidateImageJson;
                }
            }
            if ($quoteDetail->service_id == 6) {
                $selfieValidateImageJson = $this->selfieImageValidation($request->images);
                if ($selfieValidateImageJson == "success") {
                    $images = $request->images;
                } else {
                    return $selfieValidateImageJson;
                }
            }

            $updateQuoteDetails = BulkOrderQuoteDetails::find($quoteDetailId);
            if ($quoteDetail->service_id == 1) {
                $updateQuoteDetails->image = $request->images;
                $updateQuoteDetails->background_color = $request->background_color;
                Log::channel("createOrder")->info("quote updateQuoteAttachment passport request value :: $request->images, $request->background_color");
            } else if ($quoteDetail->service_id == 2) {
                $updateQuoteDetails->photoprint_variant = $images;
                Log::channel("createOrder")->info("quote updateQuoteAttachment photoprint request value :: $images");
            } else if ($quoteDetail->service_id == 3) {
                $updateQuoteDetails->frames = $images;
                Log::channel("createOrder")->info("quote updateQuoteAttachment phtoframe request value :: $images");
            } else if ($quoteDetail->service_id == 4) {
                $updateQuoteDetails->variant_attributes = $images;
                Log::channel("createOrder")->info("quote updateQuoteAttachment personalized request value :: $images");
            } else if ($quoteDetail->service_id == 6) {
                $updateQuoteDetails->images = $images;
                Log::channel("createOrder")->info("quote updateQuoteAttachment selfie request value :: $images");
            }
            $updateQuoteDetails->updated_on = Server::getDateTime();
            $updateQuoteDetails->updated_by = JwtHelper::getSesUserId();

            if ($updateQuoteDetails->save()) {

                Log::channel("createOrder")->info("quote updateQuoteAttachment save value :: $updateQuoteDetails");
                Log::channel("createOrder")->info('** end the updateQuoteAttachment method **');

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Quote details updated successfully'),
                    'data'        => []

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Quote details update failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("createOrder")->info('** start the updateQuoteAttachment error create method **');
            Log::channel("createOrder")->error($exception);
            Log::channel("createOrder")->info('** end the updateQuoteAttachment error create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function createOrderview(Request $request, $quoteId)
    {
        try {
            Log::channel("createOrder")->error('**started the createOrderview method**');
            $quoteDetails = BulkOrderQuote::select('bulk_order_quote.*','bulk_order_enquiry.enquiry_code')->leftjoin('bulk_order_enquiry', 'bulk_order_enquiry.bulk_order_enquiry_id', '=', 'bulk_order_quote.bulk_order_enquiry_id')->where('bulk_order_quote.bulk_order_quote_id', $quoteId)->first();

            if (!empty($quoteDetails)) {

                $ary = [];
                $ary['bulk_order_quote_id'] = $quoteDetails->bulk_order_quote_id;
                $ary['enquiry_code'] = $quoteDetails->enquiry_code;
                $ary['quote_code'] = $quoteDetails->quote_code;
                $ary['enquiry_id'] = $quoteDetails->bulk_order_enquiry_id;
                $ary['quote_id'] = $quoteDetails->bulk_order_quote_id;
                $ary['quote_status'] = $quoteDetails->status;
                $ary['order_date'] = date('d-m-Y');
                $ary['grand_total'] = $quoteDetails->grand_total;
                $quoteOrderDetails = $this->bulkOrderQuoteDetails($quoteDetails->bulk_order_quote_id);
                $ary['quoteOrderDetails'] = $quoteOrderDetails;
                $final[] = $ary;
            }


            if (!empty($final)) {
                return response()->json(
                    [
                        'keyword' => 'success',
                        'message' => __('Create order viewed successfully'),
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
        } catch (\Exception $exception) {
            Log::channel("createOrder")->error('**started the error createOrderview method**');
            Log::channel("createOrder")->error($exception);
            Log::channel("createOrder")->error('**ended the error createOrderview method**');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function bulkOrderView(Request $request, $orderId)
    {
        try {
            Log::channel("createOrder")->error('**started the createOrderview method**');
            $orderDetails = Orders::select('orders.*','bulk_order_enquiry.bulk_order_enquiry_id','bulk_order_enquiry.enquiry_code','bulk_order_quote.bulk_order_quote_id','bulk_order_quote.quote_code')->leftjoin('bulk_order_enquiry', 'bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')->leftjoin('bulk_order_quote', 'bulk_order_quote.bulk_order_quote_id', '=', 'orders.bulk_order_quote_id')->where('orders.order_id', $orderId)->first();

            if (!empty($orderDetails)) {

                $ary = [];
                $ary['order_id'] = $orderDetails->order_id;
                $ary['order_code'] = $orderDetails->order_code;
                $ary['order_date'] = $orderDetails->order_date;
                $ary['enquiry_code'] = $orderDetails->enquiry_code;
                $ary['quote_code'] = $orderDetails->quote_code;
                $ary['enquiry_id'] = $orderDetails->bulk_order_enquiry_id;
                $ary['quote_id'] = $orderDetails->bulk_order_quote_id;
                $ary['grand_total'] = $orderDetails->order_totalamount;
                $ary['purchase_document'] = $orderDetails->purchase_document;
                $ary['purchase_document_url'] = ($orderDetails['purchase_document'] != '') ? env('APP_URL') . env('ORDER_URL') . $orderDetails['purchase_document'] : "";
                $orderitems = $this->bulkOrderItemDetails($orderDetails->order_id);
                $ary['orderitems'] = $orderitems;
                $final[] = $ary;
            }


            if (!empty($final)) {
                return response()->json(
                    [
                        'keyword' => 'success',
                        'message' => __('Create order viewed successfully'),
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
        } catch (\Exception $exception) {
            Log::channel("createOrder")->error('**started the error createOrderview method**');
            Log::channel("createOrder")->error($exception);
            Log::channel("createOrder")->error('**ended the error createOrderview method**');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
