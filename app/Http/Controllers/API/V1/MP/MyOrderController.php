<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Events\CancelOrder;
use App\Events\CancelOrderItems;
use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Traits\OrderResponseTrait;
use App\Models\BillItems;
use App\Models\Bills;
use App\Models\CompanyInfo;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeTaskHistory;
use App\Models\ExpectedDays;
use App\Models\Messages;
use App\Models\OrderItems;
use App\Models\OrderItemStage;
use App\Models\Orders;
use App\Models\PassportSizeUploadHistoryModel;
use App\Models\PassportSizeUploadModel;
use App\Models\PassportSizeUploadPreviewHistoryModel;
use App\Models\PersonalizedUploadHistoryModel;
use App\Models\PersonalizedUploadModel;
use App\Models\PhotoFrameLabelModel;
use App\Models\PhotoFramePreviewHistory;
use App\Models\PhotoFrameUploadHistoryModel;
use App\Models\PhotoFrameUploadModel;
use App\Models\PhotoPrintUploadHistoryModel;
use App\Models\PhotoPrintUploadModel;
use App\Models\PhotoPrintUploadPreviewHistoryModel;
use App\Models\ProductVariant;
use App\Models\Rating;
use App\Models\SelfieUploadHistoryModel;
use App\Models\SelfieUploadModel;
use App\Models\SelfieUploadPreviewHistoryModel;
use App\Models\TaskManager;
use App\Models\TaskManagerHistory;
use App\Models\TaskManagerPreviewHistory;
use App\Models\Tickets;
use App\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use File;

class MyOrderController extends Controller
{
    use OrderResponseTrait;
    //myorder list
    public function myorder_list(Request $request)
    {
        try {
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $id = JwtHelper::getSesUserId();
            $orders = Orders::where('orders.customer_id', $id)->whereIn('orders.payment_status', [0, 1])
                ->leftjoin('order_items', 'order_items.order_id', '=', 'orders.order_id')
                ->select('orders.order_id', 'orders.order_code', 'orders.total_quantity', 'orders.order_totalamount', 'orders.customer_id', 'orders.shipping_cost', 'orders.coupon_amount', DB::raw('DATE_FORMAT(orders.order_date, "%Y-%m-%d") as order_date'), 'order_items.order_status')->groupBy('orders.order_id')->orderBy('orders.order_id', 'desc');

            $count = count($orders->get());

            if ($offset) {
                $offset = $offset * $limit;
                $orders->offset($offset);
            }
            if ($limit) {
                $orders->limit($limit);
            }

            $orders = $orders->get();

            $orderAry = [];
            foreach ($orders as $value) {
                $ary = [];
                $ary['order_id'] = $value->order_id;
                $ary['order_date'] = $value->order_date;
                $ary['order_code'] = $value->order_code;
                $orderItemsCount = OrderItems::where('order_id', $value->order_id)->count();
                $ary['no_of_items'] = !empty($orderItemsCount) ? $orderItemsCount : '';
                $ary['total_quantity'] = $value->total_quantity;
                $ary['order_totalamount'] = $value->order_totalamount;
                // $itemCount = OrderItems::where('order_id', $value->order_id)->count();
                // if ($itemCount == 1) {
                //     $subtotal = OrderItems::where('order_id', $value->order_id)->sum('sub_total');
                //     $ary['order_totalamount'] = sprintf("%.2f", $subtotal + $value->shipping_cost - $value->coupon_amount);
                // } else {
                //     $subtotal = OrderItems::where('order_id', $value->order_id)->where('order_status', '!=', 4)->sum('sub_total');
                //     $ary['order_totalamount'] = sprintf("%.2f", $subtotal + $value->shipping_cost - $value->coupon_amount);
                // }
                $ary['customer_id'] = $value->customer_id;
                $ary['order_status'] = $value->order_status;
                // $cancelDetails = OrderItems::where('order_items.order_id', $value->order_id)->where('production_status', 1)->where('order_status', '!=', 4)->where('order_status', '!=', 10)->first();
                // $ary['cancel_production'] = !empty($cancelDetails) ? true : false;

                $pendingcancelDetails = OrderItems::where('order_items.order_id', $value->order_id)
                    ->where(function ($query) {
                        $query->where('production_status', 0)
                            ->where(function ($query) {
                                $query->where('order_status', '=', 0)
                                    ->orWhere('order_status', '=', 1);
                            });
                    })
                    ->first();

                // $ary['pendingcancelDetails'] = !empty($pendingcancelDetails) ? true : false;

                $twocancelDetails = OrderItems::where('order_items.order_id', $value->order_id)
                    ->where(function ($query) {
                        $query->where('production_status', 1)
                            ->where(function ($query) {
                                $query->whereIn('order_status',  [4, 0, 1, 6, 8]);
                            });
                    })
                    ->count();

                // $ary['twocancelDetails'] = !empty($twocancelDetails) ? true : false;

                $allcancelDetails = OrderItems::where('order_items.order_id', $value->order_id)
                    ->where('production_status', 1)
                    ->whereNotIn('order_status',  [4, 6, 8])
                    ->first();

                // $ary['allcancelDetails'] = !empty($allcancelDetails) ? true : false;

                $thirdcancelDetails = OrderItems::where('order_items.order_id', $value->order_id)
                    ->where('production_status', 1)
                    ->where(function ($query) {
                        $query->whereNotIn('order_status',  [4, 0, 1, 6, 8]);
                    })
                    ->count();

                // $ary['thirdcancelDetailsquery'] = $thirdcancelDetails;

                $thirdcancelDetailsStatus = !empty($thirdcancelDetails) ? false : true;

                if ($thirdcancelDetailsStatus == false) {
                    $ary['cancel_production'] = false;
                } else if (!empty($pendingcancelDetails) || !empty($twocancelDetails) && !empty($allcancelDetails)) {
                    $ary['cancel_production'] = true;
                } else {
                    $ary['cancel_production'] = false;
                }

                $orderAry[] = $ary;
            }
            if (!empty($orderAry)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('My order listed successfully'),
                    'data' => $orderAry,
                    'count' => $count
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

    //myorder item view
    public function myorder_view(Request $request, $ordId)
    {
        try {
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $orderItemList = OrderItems::where('order_items.order_id', $ordId)
                ->select('orders.order_code', 'orders.order_date', 'orders.customer_id', 'order_items.delivery_charge', 'order_items.order_id', 'order_items.order_items_id', 'order_items.product_id', 'order_items.product_variant_id', 'order_items.service_id', 'order_items.quantity', 'order_items.sub_total', 'order_items.order_status', 'order_items.image', 'order_items.product_name', 'order_items.product_code', 'order_items.thumbnail_image', 'order_items.production_status', 'rating_review.review', 'rating_review.rating', DB::raw('DATE_FORMAT(order_items.created_on, "%Y-%m-%d") as order_placed'), DB::raw('DATE_FORMAT(order_items.approved_on, "%Y-%m-%d") as approved_on'), DB::raw('DATE_FORMAT(order_items.disapproved_on, "%Y-%m-%d") as disapproved_on'), DB::raw('DATE_FORMAT(order_items.shipped_on, "%Y-%m-%d") as shipped_on'), DB::raw('DATE_FORMAT(order_items.dispatched_on, "%Y-%m-%d") as dispatched_on'), DB::raw('DATE_FORMAT(order_items.delivered_on, "%Y-%m-%d") as delivered_on'), DB::raw('DATE_FORMAT(order_items.cancelled_on, "%Y-%m-%d") as cancelled_on'))
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftJoin('rating_review', function ($leftJoin) use ($ordId) {
                    $leftJoin->on('rating_review.product_id', '=', 'order_items.product_id')
                        ->where('rating_review.order_id', $ordId);
                })->orderBy('order_items.order_items_id', 'desc');

            $count = count($orderItemList->get());

            if ($offset) {
                $offset = $offset * $limit;
                $orderItemList->offset($offset);
            }
            if ($limit) {
                $orderItemList->limit($limit);
            }

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
                    $ary['thumbnail_image'] = $value->thumbnail_image;
                    $ary['product_image'] = ($value->image != '') ? env('APP_URL') . env('ORDER_URL') . $value->image : env('APP_URL') . "avatar.jpg";
                    if ($value['service_id'] == 1) {
                        $ary['thumbnail_image_url'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 2) {
                        $ary['thumbnail_image_url'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 3) {
                        $ary['thumbnail_image_url'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 4) {
                        $ary['thumbnail_image_url'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 5) {
                        $ary['thumbnail_image_url'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value['service_id'] == 6) {
                        $ary['thumbnail_image_url'] = ($value->thumbnail_image != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $value->thumbnail_image : env('APP_URL') . "avatar.jpg";
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
                    $ary['delivery_charge'] = $value->delivery_charge;
                    // $cancelItem = TaskManager::where('task_manager.order_items_id', $value->order_items_id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('orders.customer_id', $value->customer_id)->whereIn('task_manager.current_task_stage', [2, 3, 4])->first();
                    // $ary['cancel_production'] = !empty($cancelItem) ? false : true;
                    $ary['cancel_production'] = $value->production_status == 1 ? false : true;
                    $ary['pdf_url'] = $this->myorderinvoice_pdf_download($value->order_items_id);
                    $orderAry[] = $ary;
                }
            }

            $orderDetails = Orders::where('orders.order_id', $ordId)->whereIn('orders.payment_status', [0, 1])
                ->leftjoin('order_items', 'order_items.order_id', '=', 'orders.order_id')
                ->leftjoin('district', 'district.district_id', '=', 'orders.billing_city_id')
                ->leftjoin('state', 'state.state_id', '=', 'orders.billing_state_id')
                ->select('orders.order_id', 'orders.order_code', 'orders.total_quantity', 'orders.order_totalamount', 'orders.shipping_cost', 'orders.coupon_amount', 'orders.paytm_payment_mode', 'orders.payment_mode', 'orders.payment_transcation_id', 'orders.customer_id', DB::raw('DATE_FORMAT(orders.order_date, "%Y-%m-%d") as order_date'), 'orders.billing_customer_first_name', 'orders.billing_customer_last_name', 'orders.billing_address_1', 'orders.billing_place', 'orders.billing_landmark', 'orders.billing_pincode', 'orders.billing_state_id', 'orders.billing_city_id', 'district.district_name as city_name', 'state.state_name', 'orders.other_district', 'order_items.order_status','orders.billing_mobile_number','orders.billing_email')->groupBy('orders.order_id')->orderBy('orders.order_id', 'desc')->first();
            $OrdDetail = [];
            if (!empty($orderDetails)) {
                $ary = [];
                $ary['order_id'] = $orderDetails->order_id;
                $ary['order_date'] = $orderDetails->order_date;
                $ary['order_code'] = $orderDetails->order_code;
                $orderItemsCount = OrderItems::where('order_id', $orderDetails->order_id)->count();
                $ary['no_of_items'] = !empty($orderItemsCount) ? $orderItemsCount : '';
                $ary['total_quantity'] = $orderDetails->total_quantity;
                $itemCount = OrderItems::where('order_id', $orderDetails->order_id)->count();
                // if ($itemCount == 1) {
                //     $subtotal =  DB::table('order_items')
                //         ->where([['order_id', $orderDetails->order_id]])
                //         ->sum('sub_total');
                $ary['order_totalamount'] =  $orderDetails->order_totalamount;
                // } else {
                //     $subtotal =  DB::table('order_items')
                //         ->where([['order_id', $orderDetails->order_id], ['order_status', '!=', 4]])
                //         ->sum('sub_total');
                //     $ary['order_totalamount'] = sprintf("%.2f", $subtotal + $orderDetails->shipping_cost - $orderDetails->coupon_amount);
                // }
                $ary['paytm_payment_mode'] = $orderDetails->paytm_payment_mode;
                $ary['payment_mode'] = $orderDetails->payment_mode;
                $ary['payment_transcation_id'] = $orderDetails->payment_transcation_id;
                $ary['customer_id'] = $orderDetails->customer_id;
                $ary['customer_name'] = !empty($orderDetails->billing_customer_last_name) ? $orderDetails->billing_customer_first_name . ' ' . $orderDetails->billing_customer_last_name : $orderDetails->billing_customer_first_name;
                $ary['billing_email'] = $orderDetails->billing_email;
                $ary['billing_mobile_number'] = $orderDetails->billing_mobile_number;
                $ary['billing_address_1'] = $orderDetails->billing_address_1;
                $ary['billing_place'] = $orderDetails->billing_place;
                $ary['billing_landmark'] = $orderDetails->billing_landmark;
                $ary['billing_pincode'] = $orderDetails->billing_pincode;
                $ary['state_name'] = $orderDetails->state_name;
                $ary['city_name'] = !empty($orderDetails->city_name) ? $orderDetails->city_name : $orderDetails->other_district;
                $ary['order_status'] = $orderDetails->order_status;
                $ary['delivery_charge'] = $orderDetails->shipping_cost;
                // $cancelDetails = OrderItems::where('order_items.order_id', $orderDetails->order_id)->where('production_status', 1)->where('order_status', '!=', 4)->where('order_status', '!=', 10)->first();
                // $ary['cancel_production'] = !empty($cancelDetails) ? true : false;

                $pendingcancelDetails = OrderItems::where('order_items.order_id', $value->order_id)
                    ->where(function ($query) {
                        $query->where('production_status', 0)
                            ->where(function ($query) {
                                $query->where('order_status', '=', 0)
                                    ->orWhere('order_status', '=', 1);
                            });
                    })
                    ->first();

                // $ary['pendingcancelDetails'] = !empty($pendingcancelDetails) ? true : false;

                $twocancelDetails = OrderItems::where('order_items.order_id', $value->order_id)
                    ->where(function ($query) {
                        $query->where('production_status', 1)
                            ->where(function ($query) {
                                $query->whereIn('order_status',  [4, 0, 1, 6, 8]);
                            });
                    })
                    ->count();

                // $ary['twocancelDetails'] = !empty($twocancelDetails) ? true : false;

                $allcancelDetails = OrderItems::where('order_items.order_id', $value->order_id)
                    ->where('production_status', 1)
                    ->whereNotIn('order_status',  [4, 6, 8])
                    ->first();

                // $ary['allcancelDetails'] = !empty($allcancelDetails) ? true : false;

                $thirdcancelDetails = OrderItems::where('order_items.order_id', $value->order_id)
                    ->where('production_status', 1)
                    ->where(function ($query) {
                        $query->whereNotIn('order_status',  [4, 0, 1, 6, 8]);
                    })
                    ->count();

                // $ary['thirdcancelDetailsquery'] = $thirdcancelDetails;

                $thirdcancelDetailsStatus = !empty($thirdcancelDetails) ? false : true;

                if ($thirdcancelDetailsStatus == false) {
                    $ary['cancel_production'] = false;
                } else if (!empty($pendingcancelDetails) || !empty($twocancelDetails) && !empty($allcancelDetails)) {
                    $ary['cancel_production'] = true;
                } else {
                    $ary['cancel_production'] = false;
                }
                
                $OrdDetail = $ary;
            }

            if (!empty($orderAry)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Order item listed successfully'),
                    'data' => $OrdDetail,
                    'order_item_list' => $orderAry,
                    'count' => $count
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

    //Cancel order item
    public function cancelOrderItem(Request $request)
    {
        try {
            $id = $request->order_items_id;

            if (!empty($id)) {
                $order = OrderItems::where('order_items_id', $id)->first();

                $update = OrderItems::where('order_items_id', $id)->update(array(
                    'order_status' => 4,
                    'cod_status' => 6,
                    'production_status' => 1,
                    'cancel_reason_id' => $request->cancel_reason_id,
                    'cancel_reason' => $request->cancel_reason,
                    'cancelled_on' => Server::getDateTime()
                ));

                $orderTotalAmount = Orders::where('order_id', $order->order_id)->first();

                $itemCount = OrderItems::where('order_id', $order->order_id)->where('order_status', '!=', 4)->count();

                if (!empty($itemCount)) {
                    if ($itemCount == 1) {
                        $update = Orders::where('order_id', $order->order_id)->update(array(
                            'cancelled_order_totalamount' => 0,
                        ));
                    } else {
                        $update = Orders::where('order_id', $order->order_id)->update(array(
                            'cancelled_order_totalamount' => $orderTotalAmount->cancelled_order_totalamount - $order->sub_total,
                        ));
                    }
                }

                $order = Orders::where('order_id', $order->order_id)->leftJoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'orders.order_id', 'orders.order_code', 'orders.customer_id')->first();

                $orderItemDetails = OrderItems::where('order_items_id', $id)->select('order_items.*')->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('service.service_name')->get();
                $resultArray = [];
                if (!empty($orderItemDetails)) {
                    foreach ($orderItemDetails as $pd) {
                        $resultArray[$pd->service_name] = $pd['service_name'];
                    }
                }


                $itmesNames = implode(", ", $resultArray) ?? "-";
                $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                $msg =  "Dear $customerName,
                Thanks for shopping with Print App!! We’re sorry that you’re unsatisfied with your order from Print App. Your order $itmesNames has been cancelled successfully. We have started the refund process for your order, the details will be updated soon. For more detail: #VAR3#.
                Team Print App";
                $isSmsSent = GlobalHelper::sendSMS($order->mobile_no, $msg);
                // $msg =  "DEAR $order->billing_customer_first_name$order->billing_customer_last_name, YOUR ORDER $order->order_code IS CANCELLED. PLS PLACE NEW ORDER IN NR INFOTECH,THKS FOR CHOOSING. CALL - 04567355015, WHATSAPP - 9486360705.";
                //         $isSmsSent = GlobalHelper::sendSms($order->billing_mobile_number, $msg);
                $check = OrderItems::where('order_items_id', $id)->whereIn('service_id', [4, 5])->get();
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

                //Send Mail for Customer In Order Item Cancel
                $sendMailforOrderItemCancel = $this->SendEmailforOrderItemcancel($id);

                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Order cancelled successfully',
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
        } catch (\Exception $exception) {

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function SendEmailforOrderItemcancel($item_id)
    {

        //Get OrderItem details
        $orderItem = OrderItems::where('order_items_id', $item_id)->first();

        //Get OrderCode
        $order = Orders::where('order_id', $orderItem->order_id)->first();

        //Get Customer Details
        $getCustomer = Customer::where('customer_id', $orderItem->customer_id)->first();

        //mail send
        $mail_data = [];
        $mail_data['product_name'] = $orderItem->product_name;
        $mail_data['sub_total'] = $orderItem->sub_total;
        $mail_data['order_code'] = $order->order_code;
        $mail_data['is_cod'] = $order->is_cod;
        $mail_data['customer_name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
        $mail_data['email'] = $order->billing_email;

        if ($order->billing_email != '') {
            event(new CancelOrderItems($mail_data));
        }
    }

    //Cancel order
    public function cancelOrder(Request $request)
    {
        $user_Id = JwtHelper::getSesUserId();
        $order = Orders::where('order_id', $request->order_id)->first();

        $orderItems = OrderItems::where('order_items.order_id', $order->order_id)->first();
        $update = OrderItems::where('order_id', $order->order_id)->update(array(
            'order_status' => 4,
            'cod_status' => 6,
            'production_status' => 1,
            'cancel_reason_id' => $request->cancel_reason_id,
            'cancel_reason' => $request->cancel_reason,
            'cancelled_on' => Server::getDateTime()
        ));

        $update = Orders::where('order_id', $order->order_id)->update(array(
            'cancelled_order_totalamount' => 0,
        ));


        $order = Orders::where('order_id', $order->order_id)->leftJoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'orders.is_cod')->first();

        $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
        $msg =  "Dear $customerName,
                Thanks for shopping with Print App!! We’re sorry that you’re unsatisfied with your order from Print App. Your order $order->order_code has been cancelled successfully. We have started the refund process for your order, the details will be updated soon. For more detail: #VAR3#.
                Team Print App";
        $isSmsSent = GlobalHelper::sendSMS($order->mobile_no, $msg);

        $order = Orders::where('order_id', $order->order_id)->first();
        $getCustomer = Customer::where('customer_id', $user_Id)->first();

        //mail send
        $mail_data = [];
        $mail_data['order_items'] = OrderItems::where('order_id', $order->order_id)->select('sub_total', 'product_name', 'order_items_id')->get();
        $mail_data['total'] = sprintf("%.2f", $mail_data['order_items']->sum('sub_total'));
        $mail_data['order_code'] = $order->order_code;
        $mail_data['is_cod'] = $order->is_cod;
        $mail_data['customer_name'] = !empty($order->billing_customer_last_name) ? $order->billing_customer_first_name . ' ' . $order->billing_customer_last_name : $order->billing_customer_first_name;
        $mail_data['email'] = $order->billing_email;

        if ($order->billing_email != '') {
            event(new CancelOrder($mail_data));
        }

        //Order Quantity
        $check = OrderItems::where('order_id', $request->order_id)->whereIn('service_id', [4, 5])->get();
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

        $user_id = JwtHelper::getSesUserId();
        $customer_details = Orders::where('order_id', $request->order_id)->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->select('customer.*', 'orders.order_id', 'orders.order_code')->first();
        $customerName = !empty($customer_details->customer_last_name) ? $customer_details->customer_first_name . ' ' . $customer_details->customer_last_name : $customer_details->customer_first_name;
        $title = "Order Cancelled - $customer_details->order_code";
        $portal = 'admin';
        $module = "Order_cancel";
        $random_id1 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $body = "The Order $customer_details->order_code is cancelled by $customerName";
        $page = "order_cancelled";
        $data = [
            'customer_id' => $customer_details->customer_id,
            'customer_name' => !empty($customer_details->customer_last_name) ? $customer_details->customer_first_name . ' ' . $customer_details->customer_last_name : $customer_details->customer_first_name,
            'order_id' => $customer_details->order_id,
            'order_code' => $customer_details->order_code,
            'page' => $page,
            'url' => 'track-order?'
        ];
        $message = [
            'title' => $title,
            'page' => $page,
            'portal' => $portal,
            'body' => $body,
            'data' => $data
        ];

        $admin_reciever_token = UserModel::where('acl_role_id', 1)->where('token', '!=', " ")->get();
        $key = [];
        if (!empty($admin_reciever_token)) {
            foreach ($admin_reciever_token as $recipient) {
                $key[] = $recipient['token'];
            }
        }
        $push = Firebase::sendMultiple($key, $message);
        $getdata = GlobalHelper::notification_create($title, $body, 2, $customer_details->customer_id, 1, $module, $page, $portal, $data, $random_id1);

        $customer_token = Customer::where('customer.customer_id', $user_id)->where('token', '!=', NULL)->orwhere('mbl_token', '!=', NULL)->first();
        $title1 = "Order Cancelled - $customer_details->order_code";
        $portal1 = 'mobile';
        $module1 = "Order_cancel";
        $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $body1 = "Your Order " . $customer_details->order_code . " is cancelled, we will process the refund soon";
        $page1 = "order_cancelled";
        $data1 = [
            'customer_id' => $customer_details->customer_id,
            'customer_name' => !empty($customer_details->customer_last_name) ? $customer_details->customer_first_name . ' ' . $customer_details->customer_last_name : $customer_details->customer_first_name,
            'order_id' => $customer_details->order_id,
            'order_code' => $customer_details->order_code,
            'page' => $page,
            'url' => 'track-order?'
        ];
        $message1 = [
            'title' => $title,
            'page' => $page,
            'portal' => $portal,
            'body' => $body,
            'data' => $data
        ];
        if ($customer_token->token != " ") {
            $key1 = $customer_token->token;
        } else {
            $key1 = $customer_token->mbl_token;
        }
        $push = Firebase::sendSingleMbl($key1, $message);

        $get_data = GlobalHelper::notification_create($title1, $body1, 2, $user_id, $customer_token->customer_id, $module1, $page1, $portal1, $data1, $random_id2);

        return response()->json([
            'keyword'      => 'success',
            'message'      => __('Order cancelled successfully'),
            'data'        => []

        ]);
    }

    //myorderitem view
    public function myorderItem_view(Request $request, $ordId)
    {
        try {
            $orderView = OrderItems::where('order_items.order_items_id', $ordId)
                ->select('orders.order_code', 'orders.order_date', 'orders.customer_id', 'order_items.*',  'task_manager.qc_status', 'task_manager.qc_image', 'task_manager.qc_reason', 'task_manager.qc_reason_on', 'task_manager.qc_on', 'task_manager.preview_status', 'task_manager.preview_image', 'task_manager.preview_reason', 'task_manager.preview_reason_on', 'task_manager.preview_on', 'task_manager.task_manager_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_items.order_items_id')->get();
            if (!empty($orderView)) {
                $orderAry = [];
                foreach ($orderView as $value) {
                    $ary = [];
                    $ary['order_id'] = $value->order_id;
                    $ary['customer_id'] = $value->customer_id;
                    $ary['order_items_id'] = $value->order_items_id;
                    $ary['service_id'] = $value->service_id;
                    $ary['is_customized'] = $value->is_customized;
                    $ary['order_date'] = $value->order_date;
                    $ary['order_code'] = $value->order_code;
                    $ary['product_id'] = $value->product_id;
                    $ary['product_name'] = $value->product_name;
                    $ary['product_code'] = $value->product_code;
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
                    $ary['variant_attributes'] = $this->getPersonalizedUpload($value->order_items_id, json_decode($value->variant_attributes, true));
                    $ary['variant_details'] = json_decode($value->pv_variant_attributes, true);
                    $ary['frames'] = $this->getPhotoFrameUpload($value->order_items_id);
                    $ary['photoprint_variant'] = $this->getPhotoPrintUpload($value->order_items_id);
                    $ary['passportsize_variant'] = $this->getPassportSizeUpload($value->order_items_id);
                    $ary['images'] = $this->getSelfieUpload($value->order_items_id);
                    $ary['quantity'] = $value->quantity;
                    $ary['sub_total'] = $value->sub_total;
                    $ary['order_status'] = $value->order_status;
                    $ary['photoprint_width'] = $value->photoprint_width;
                    $ary['photoprint_height'] = $value->photoprint_height;
                    $ary['first_copy_selling_price'] = $value->first_copy_selling_price;
                    $ary['additional_copy_selling_price'] = $value->additional_copy_selling_price;
                    $ary['variant_type_name'] = $value->variant_type_name;
                    $ary['variant_label'] = $value->variant_label;
                    $expectDeliveryDate = ExpectedDays::where('status', 1)->select('expected_delivery_days')->first();
                    $ary['expected_days'] = $expectDeliveryDate->expected_delivery_days;
                    $cancelDetails = OrderItems::where('order_items.order_items_id', $value->order_items_id)->where('production_status', 1)->first();
                    $ary['cancel_production'] = !empty($cancelDetails) ? false : true;
                    $ary['task_manager_id'] = $value['task_manager_id'];
                    $ary['qc_image'] = $value['qc_image'];
                    $ary['qc_image_url'] = ($value['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['qc_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['qc_on'] = $value['qc_on'];
                    $ary['qc_reason'] = $value['qc_reason'];
                    $ary['qc_reason_on'] = $value['qc_reason_on'];
                    $ary['qc_status'] = $value['qc_status'];
                    $ary['preview_image'] = $value['preview_image'];
                    $ary['preview_image_url'] = ($value['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['preview_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['preview_on'] = $value['preview_on'];
                    $ary['preview_reason'] = $value['preview_reason'];
                    $ary['preview_reason_on'] = $value['preview_reason_on'];
                    $ary['preview_status'] = $value['preview_status'];
                    $ratingDetails = Rating::where('customer_id', $value->customer_id)->where('order_id', $value->order_id)->where('product_id', $value->product_id)->first();
                    $ary['review'] = !empty($ratingDetails) ? $ratingDetails->review : null;
                    $ary['rating'] = !empty($ratingDetails) ? $ratingDetails->rating : null;
                    $message = Messages::where('order_items_id', $value->order_items_id)->first();
                    $ary['is_chat_available'] = !empty($message) ? true : false;
                    $singleChatCheck = OrderItemStage::where('order_items_id', $value->order_items_id)->where('is_status_check', 1)->where('status', 1)->first();
                    $ary['is_single_chat_available'] = !empty($singleChatCheck) ? true : false;
                    $ratingDetails = Rating::where('order_id', $value->order_id)->first();
                    $ary['is_rating_available'] = !empty($ratingDetails) ? false : true;
                    $checkComplaint = Tickets::where('order_items_id', $value->order_items_id)->first();
                    $ary['can_complaint'] = !empty($checkComplaint) ? false : true;
                    $ary['pdf_url'] = $this->myorderinvoice_pdf_download($value->order_items_id);
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

    public function previewDetails($id)
    {
        try {
            Log::channel("employeeitemview")->info('** started the employeeitemview view method **');

            $orderItem = OrderItems::where('order_items_id', $id)->first();

            Log::channel("employeeitemview")->info("request value task_manager_id:: $id");



            $final = [];

            if (!empty($orderItem)) {
                if ($orderItem['service_id'] == 1) {
                    $final = $this->getPassportSizePreviewUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 2) {
                    $final = $this->getPhotoPrintPreviewUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 3) {
                    $final = $this->getPhotoFramePreviewUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 4) {
                    $final = $this->getPersonalizedPreviewUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 6) {
                    $final = $this->getSelfiePreviewUpload($orderItem['order_items_id']);
                }
            }

            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("employeeitemview")->info("view value :: $log");
                Log::channel("employeeitemview")->info('** end the employeeitemview view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('My order preview listed successfully'),
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
            Log::channel("employeeitemview")->error($exception);
            Log::channel("employeeitemview")->info('** end the employeeitemview view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function replaceImage(Request $request)
    {
        try {

            $id = $request->id;
            $json_image = json_decode($request->json_image, true);

            //passport
            if ($request->service_id == 1) {
                if (!empty($request->id) || !empty($request->image)) {
                    $passportImage = OrderItems::find($id);
                    $passportImage->image = $request->image;
                    $passportImage->updated_on = Server::getDateTime();
                    $passportImage->updated_by = JwtHelper::getSesUserId();
                    $passportImage->save();
                }

                if (!empty($json_image)) {
                    foreach ($json_image as $image) {
                        $passportSizeUpdate = PassportSizeUploadModel::find($image['id']);
                        $passportSizeUpdate->image = $image['image'];
                        $passportSizeUpdate->updated_on = Server::getDateTime();
                        $passportSizeUpdate->updated_by = JwtHelper::getSesUserId();
                        $passportSizeUpdate->save();

                        $passportImage = OrderItems::find($passportSizeUpdate->order_items_id);
                        $passportImage->image = $image['image'];
                        $passportImage->updated_on = Server::getDateTime();
                        $passportImage->updated_by = JwtHelper::getSesUserId();
                        $passportImage->save();
                    }
                }
            }

            //photoprint
            if ($request->service_id == 2) {
                foreach ($json_image as $image) {
                    $photoprintImage = PhotoPrintUploadModel::find($image['id']);
                    $photoprintImage->image = $image['image'];
                    $photoprintImage->updated_on = Server::getDateTime();
                    $photoprintImage->updated_by = JwtHelper::getSesUserId();
                    $photoprintImage->save();
                }
            }

            //photoframe
            if ($request->service_id == 3) {
                foreach ($json_image as $image) {
                    $photoframeImage = PhotoFrameUploadModel::find($image['id']);
                    $photoframeImage->image = $image['image'];
                    $photoframeImage->updated_on = Server::getDateTime();
                    $photoframeImage->updated_by = JwtHelper::getSesUserId();
                    $photoframeImage->save();
                }
            }

            //personalized
            if ($request->service_id == 4) {
                foreach ($json_image as $image) {
                    $personalizedImage = PersonalizedUploadModel::find($image['id']);
                    $personalizedImage->reference_image = $image['reference_image'];
                    $personalizedImage->image = $image['image'];
                    $personalizedImage->updated_on = Server::getDateTime();
                    $personalizedImage->updated_by = JwtHelper::getSesUserId();
                    $personalizedImage->save();
                }
            }

            //selfie
            if ($request->service_id == 6) {
                foreach ($json_image as $image) {
                    $selfieImage = SelfieUploadModel::find($image['id']);
                    $selfieImage->image = $image['image'];
                    $selfieImage->updated_on = Server::getDateTime();
                    $selfieImage->updated_by = JwtHelper::getSesUserId();
                    $selfieImage->save();
                }
            }

            if (!empty($request->service_id)) {
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Replace image updated successfully'),
                    'data'        => []
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Replace image update failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //attachedImageUpload
    public function attachedImageUpload(Request $request)
    {
        try {

            //Passport size
            if ($request->service_id == 1) {
                Log::channel("attachedImageUpload")->info('** started the passportsize attachedImageUpload method **');
                $photoframeData = PassportSizeUploadModel::where('order_passport_upload_id', $request->id)->first();
                if (!empty($photoframeData->image)) {
                    $photoframeHistory = new PassportSizeUploadHistoryModel();
                    $photoframeHistory->order_passport_upload_id = $photoframeData->order_passport_upload_id;
                    $photoframeHistory->image = $photoframeData->image;
                    $photoframeHistory->created_on = $photoframeData->updated_on;
                    $photoframeHistory->created_by = $photoframeData->updated_by;
                    $photoframeHistory->reject_reason = $photoframeData->reject_reason;
                    $photoframeHistory->rejected_on = $photoframeData->rejected_on;
                    $photoframeHistory->status = $photoframeData->status;
                    $photoframeHistory->save();
                }
                Log::channel("attachedImageUpload")->info("request value PassportSizeUploadHistoryModel attachedImageUpload_id:: $photoframeData");
                Log::channel("attachedImageUpload")->info("passportsize upload successfully");
                $upload = PassportSizeUploadModel::find($request->id);
                $upload->image = $request->image;
                $upload->updated_on = Server::getDateTime();
                $upload->updated_by = JwtHelper::getSesUserId();
                $upload->status = 0;
                $upload->reject_reason = null;
                $upload->rejected_on = null;
                $upload->save();
                Log::channel("attachedImageUpload")->info('** end the passportsize attachedImageUpload method **');

                $uploadDetails = PassportSizeUploadModel::where('order_passport_upload_id', $request->id)->first();
                if (!empty($uploadDetails)) {
                    $ary = [];
                    $ary['id'] = $uploadDetails['order_passport_upload_id'];
                    $ary['image'] = $uploadDetails['image'];
                    $ary['image_url'] = ($uploadDetails['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $uploadDetails['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['status'] = $uploadDetails['status'];
                    $ary['approved_on'] = $uploadDetails['updated_on'];
                    $ary['received_on'] = $uploadDetails['updated_on'] == null ? $uploadDetails['created_on'] : $uploadDetails['updated_on'];
                    $ary['reject_reason'] = $uploadDetails['reject_reason'];
                    $ary['rejected_on'] = $uploadDetails['rejected_on'];
                    $ary['image_history'] = $this->passportSizeUploadHistory($uploadDetails['order_passport_upload_id']);
                    $final[] = $ary;
                }
            }

            //Photoprint
            if ($request->service_id == 2) {
                Log::channel("attachedImageUpload")->info('** started the Photoprint attachedImageUpload method **');
                $photoframeData = PhotoPrintUploadModel::where('order_photoprint_upload_id', $request->id)->first();
                if (!empty($photoframeData->image)) {
                    $photoframeHistory = new PhotoPrintUploadHistoryModel();
                    $photoframeHistory->order_photoprint_upload_id = $photoframeData->order_photoprint_upload_id;
                    $photoframeHistory->image = $photoframeData->image;
                    $photoframeHistory->created_on = $photoframeData->updated_on;
                    $photoframeHistory->created_by = $photoframeData->updated_by;
                    $photoframeHistory->reject_reason = $photoframeData->reject_reason;
                    $photoframeHistory->rejected_on = $photoframeData->rejected_on;
                    $photoframeHistory->status = $photoframeData->status;
                    $photoframeHistory->save();
                }
                Log::channel("attachedImageUpload")->info("request value PhotoPrintUploadHistoryModel attachedImageUpload_id:: $photoframeData");
                Log::channel("attachedImageUpload")->info("Photoprint upload successfully");
                $upload = PhotoPrintUploadModel::find($request->id);
                $upload->image = $request->image;
                $upload->updated_on = Server::getDateTime();
                $upload->updated_by = JwtHelper::getSesUserId();
                $upload->status = 0;
                $upload->reject_reason = null;
                $upload->rejected_on = null;
                $upload->save();
                Log::channel("attachedImageUpload")->info('** end the Photoprint attachedImageUpload method **');

                $uploadDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $request->id)->first();
                if (!empty($uploadDetails)) {
                    $ary = [];
                    $ary['id'] = $uploadDetails['order_photoprint_upload_id'];
                    $ary['image'] = $uploadDetails['image'];
                    $ary['image_url'] = ($uploadDetails['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $uploadDetails['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['status'] = $uploadDetails['status'];
                    $ary['approved_on'] = $uploadDetails['updated_on'];
                    $ary['received_on'] = $uploadDetails['updated_on'] == null ? $uploadDetails['created_on'] : $uploadDetails['updated_on'];
                    $ary['reject_reason'] = $uploadDetails['reject_reason'];
                    $ary['rejected_on'] = $uploadDetails['rejected_on'];
                    $ary['image_history'] = $this->photoPrintSizeUploadHistory($uploadDetails['order_photoprint_upload_id']);
                    $final[] = $ary;
                }
            }

            //Photo frame
            if ($request->service_id == 3) {

                Log::channel("attachedImageUpload")->info('** started the photo frame attachedImageUpload method **');

                $photoframeData = PhotoFrameUploadModel::where('order_photoframe_upload_id', $request->id)->first();

                if (!empty($photoframeData->image)) {
                    $photoframeHistory = new PhotoFrameUploadHistoryModel();
                    $photoframeHistory->order_photoframe_upload_id = $photoframeData->order_photoframe_upload_id;
                    $photoframeHistory->image = $photoframeData->image;
                    $photoframeHistory->created_on = $photoframeData->updated_on;
                    $photoframeHistory->created_by = $photoframeData->updated_by;
                    $photoframeHistory->reject_reason = $photoframeData->reject_reason;
                    $photoframeHistory->rejected_on = $photoframeData->rejected_on;
                    $photoframeHistory->status = $photoframeData->status;
                    $photoframeHistory->save();
                }
                Log::channel("attachedImageUpload")->info("request value PhotoFrameUploadHistoryModel attachedImageUpload_id:: $photoframeData");

                Log::channel("attachedImageUpload")->info("Photo frame upload successfully");
                $upload = PhotoFrameUploadModel::find($request->id);

                $upload->image = $request->image;
                $upload->updated_on = Server::getDateTime();
                $upload->updated_by = JwtHelper::getSesUserId();
                $upload->status = 0;
                $upload->reject_reason = null;
                $upload->rejected_on = null;
                $upload->save();
                Log::channel("attachedImageUpload")->info('** end the photo frame attachedImageUpload method **');
            }

            //Personalized
            if ($request->service_id == 4) {
                Log::channel("attachedImageUpload")->info('** started the Personalized attachedImageUpload method **');

                $personalizedData = PersonalizedUploadModel::where('order_personalized_upload_id', $request->id)->first();

                if (!empty($personalizedData->image)) {
                    $personalizedHistory = new PersonalizedUploadHistoryModel();
                    $personalizedHistory->order_personalized_upload_id = $personalizedData->order_personalized_upload_id;
                    $personalizedHistory->image = $personalizedData->image;
                    $personalizedHistory->created_on = $personalizedData->updated_on;
                    $personalizedHistory->created_by = $personalizedData->updated_by;
                    $personalizedHistory->reject_reason = $personalizedData->reject_reason;
                    $personalizedHistory->rejected_on = $personalizedData->rejected_on;
                    $personalizedHistory->status = $personalizedData->status;
                    $personalizedHistory->save();
                }

                Log::channel("attachedImageUpload")->info("request value PersonalizedUploadHistoryModel attachedImageUpload_id:: $personalizedData");

                Log::channel("attachedImageUpload")->info("Personalized upload successfully");

                $upload = PersonalizedUploadModel::find($request->id);
                $upload->image = $request->image;
                $upload->reference_image = $request->reference_image;
                $upload->updated_on = Server::getDateTime();
                $upload->updated_by = JwtHelper::getSesUserId();
                $upload->status = 0;
                $upload->reject_reason = null;
                $upload->rejected_on = null;
                $upload->save();
                Log::channel("attachedImageUpload")->info('** end the personalized attachedImageUpload method **');
            }

            //Selfie
            if ($request->service_id == 6) {
                Log::channel("attachedImageUpload")->info('** started the Selfie attachedImageUpload method **');

                $selfieData = SelfieUploadModel::where('order_selfie_upload_id', $request->id)->first();

                if (!empty($selfieData->image)) {
                    $selfieHistory = new SelfieUploadHistoryModel();
                    $selfieHistory->order_selfie_upload_id = $selfieData->order_selfie_upload_id;
                    $selfieHistory->image = $selfieData->image;
                    $selfieHistory->created_on = $selfieData->updated_on;
                    $selfieHistory->created_by = $selfieData->updated_by;
                    $selfieHistory->reject_reason = $selfieData->reject_reason;
                    $selfieHistory->rejected_on = $selfieData->rejected_on;
                    $selfieHistory->status = $selfieData->status;
                    $selfieHistory->save();
                }


                Log::channel("attachedImageUpload")->info("request value SelfieUploadHistoryModel attachedImageUpload_id:: $selfieData");

                Log::channel("attachedImageUpload")->info("Selfie upload successfully");

                $upload = SelfieUploadModel::find($request->id);
                $upload->image = $request->image;
                $upload->updated_on = Server::getDateTime();
                $upload->updated_by = JwtHelper::getSesUserId();
                $upload->status = 0;
                $upload->reject_reason = null;
                $upload->rejected_on = null;
                $upload->save();
                Log::channel("attachedImageUpload")->info('** end the personalized attachedImageUpload method **');
            }

            if ($request->service_id == 3) {
                $uploadDetails = PhotoFrameUploadModel::where('order_photoframe_upload_id', $request->id)->first();
                if (!empty($uploadDetails)) {
                    $ary = [];
                    $ary['id'] = $uploadDetails['order_photoframe_upload_id'];
                    $ary['image'] = $uploadDetails['image'];
                    $ary['image_url'] = ($uploadDetails['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $uploadDetails['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['status'] = $uploadDetails['status'];
                    $ary['approved_on'] = $uploadDetails['updated_on'];
                    $ary['received_on'] = $uploadDetails['updated_on'] == null ? $uploadDetails['created_on'] : $uploadDetails['updated_on'];
                    $ary['reject_reason'] = $uploadDetails['reject_reason'];
                    $ary['rejected_on'] = $uploadDetails['rejected_on'];
                    $ary['image_history'] = $this->photoFrameUploadHistory($uploadDetails['order_photoframe_upload_id']);
                    $final[] = $ary;
                }
            }
            if ($request->service_id == 4) {
                $uploadDetails = PersonalizedUploadModel::where('order_personalized_upload_id', $request->id)->first();
                if (!empty($uploadDetails)) {
                    $ary = [];
                    $ary['id'] = $uploadDetails['order_personalized_upload_id'];
                    $ary['image'] = $uploadDetails['image'];
                    $ary['status'] = $uploadDetails['status'];
                    $ary['approved_on'] = $uploadDetails['updated_on'];
                    $ary['received_on'] = $uploadDetails['updated_on'] == null ? $uploadDetails['created_on'] : $uploadDetails['updated_on'];
                    $ary['reject_reason'] = $uploadDetails['reject_reason'];
                    $ary['rejected_on'] = $uploadDetails['rejected_on'];
                    $ary['image_url'] = ($uploadDetails['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $uploadDetails['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['image_history'] = $this->personalizedUploadHistory($uploadDetails['order_personalized_upload_id']);
                    $final[] = $ary;
                }
            }
            if ($request->service_id == 6) {
                $uploadDetails = SelfieUploadModel::where('order_selfie_upload_id', $request->id)->first();
                if (!empty($uploadDetails)) {
                    $ary = [];
                    $ary['id'] = $uploadDetails['order_selfie_upload_id'];
                    $ary['image'] = $uploadDetails['image'];
                    $ary['image_url'] = ($uploadDetails['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $uploadDetails['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['status'] = $uploadDetails['status'];
                    $ary['approved_on'] = $uploadDetails['updated_on'];
                    $ary['received_on'] = $uploadDetails['updated_on'] == null ? $uploadDetails['created_on'] : $uploadDetails['updated_on'];
                    $ary['reject_reason'] = $uploadDetails['reject_reason'];
                    $ary['rejected_on'] = $uploadDetails['rejected_on'];
                    $ary['image_history'] = $this->selfieUploadHistory($uploadDetails['order_selfie_upload_id']);
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                Log::channel("attachedImageUpload")->info("request value attachedImageUpload_id:: $upload");
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Uploaded successfully'),
                    'data'        => $final

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Upload failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("attachedimages")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function approvedRejectedStatus_old(Request $request)
    {
        try {

            if (!empty($request)) {

                $type = $request->type;

                $id = $request->id;

                if (!empty($id)) {
                    if ($type == "approved") {
                        Log::channel("attachedImageApproved")->info('** started the attachedImageApproved method **');
                        if ($request->service_id == 3) {
                            Log::channel("attachedImageApproved")->info("request value PhotoFrameUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Photo frame approved successfully");
                            $update = PhotoFrameUploadModel::where('order_photoframe_upload_id', $id)->update(array(
                                'status' => $request->status,
                                'updated_on' => Server::getDateTime(),
                                'updated_by' => JwtHelper::getSesUserId()
                            ));
                        }

                        if ($request->service_id == 4) {
                            Log::channel("attachedImageApproved")->info("request value PersonalizedUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Personalized approved successfully");
                            $update = PersonalizedUploadModel::where('order_personalized_upload_id', $id)->update(array(
                                'status' => $request->status,
                                'updated_on' => Server::getDateTime(),
                                'updated_by' => JwtHelper::getSesUserId()
                            ));
                        }

                        if ($request->service_id == 6) {
                            Log::channel("attachedImageApproved")->info("request value SelfieUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Selfie approved successfully");
                            $update = SelfieUploadModel::where('order_selfie_upload_id', $id)->update(array(
                                'status' => $request->status,
                                'updated_on' => Server::getDateTime(),
                                'updated_by' => JwtHelper::getSesUserId()
                            ));
                        }
                        Log::channel("attachedImageApproved")->info('** end the attachedImageApproved method **');
                    }

                    if ($type == "rejected") {
                        Log::channel("attachedImageRejected")->info('** started the attachedImageRejected method **');

                        if ($request->service_id == 3) {
                            Log::channel("attachedImageRejected")->info("request value PhotoFrameUploadModel attachedImageRejected_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageRejected")->info("Photo frame rejected successfully");

                            $update = PhotoFrameUploadModel::where('order_photoframe_upload_id', $id)->update(array(
                                'status' => $request->status,
                                'reject_reason' => $request->reason,
                                'rejected_on' => Server::getDateTime()
                            ));
                        }

                        if ($request->service_id == 4) {
                            Log::channel("attachedImageRejected")->info("request value PhotoFrameUploadModel attachedImageRejected_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageRejected")->info("Personalized rejected successfully");

                            $update = PersonalizedUploadModel::where('order_personalized_upload_id', $id)->update(array(
                                'status' => $request->status,
                                'reject_reason' => $request->reason,
                                'rejected_on' => Server::getDateTime()
                            ));
                        }

                        if ($request->service_id == 6) {
                            Log::channel("attachedImageRejected")->info("request value PhotoFrameUploadModel attachedImageRejected_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageRejected")->info("Selfie rejected successfully");

                            $update = SelfieUploadModel::where('order_selfie_upload_id', $id)->update(array(
                                'status' => $request->status,
                                'reject_reason' => $request->reason,
                                'rejected_on' => Server::getDateTime()
                            ));
                        }
                        Log::channel("attachedImageRejected")->info('** end the attachedImageRejected method **');
                    }

                    if ($request->status == 1) {
                        Log::channel("attachedimages")->info("save value :: attachedimages_id :: $id :: attachedimages inactive successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Approved successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 2) {
                        Log::channel("attachedimages")->info("save value :: attachedimages_id :: $id :: attachedimages active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Rejected successfully'),
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
            Log::channel("attachedimages")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    //preview image approved rejected
    public function previewApprovedRejectedStatus(Request $request)
    {
        // try {

        if (!empty($request)) {

            $type = $request->type;

            $id = $request->id;

            if (!empty($id)) {
                if ($type == "approved") {
                    Log::channel("previewImageApproved")->info('** started the previewImageApproved method **');


                    //Passport
                    if ($request->service_id == 1) {
                        Log::channel("previewImageApproved")->info("request value PassportSizeUploadModel previewImageApproved_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageApproved")->info("Passport approved successfully");
                        $update = PassportSizeUploadModel::where('order_passport_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_on' => Server::getDateTime(),
                            'preview_by' => JwtHelper::getSesUserId()
                        ));

                        $passportDetails = PassportSizeUploadModel::where('order_passport_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_passport_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $passportDetails->task_manager_id)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'task_manager.task_manager_id')->first();

                        //total stage count
                        $totalStageCount = PassportSizeUploadModel::where('order_passport_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_passport_upload.order_items_id')->select('task_manager.task_manager_id', 'task_manager.order_items_id')->first();

                        //approved after check the label details
                        $labelItemQcCount = PassportSizeUploadModel::where('order_items_id', $totalStageCount->order_items_id)->count();

                        $labelTotalQcCount = PassportSizeUploadModel::where('order_items_id', $totalStageCount->order_items_id)->where('qc_status', 1)->count();

                        if ($labelItemQcCount != $labelTotalQcCount) {
                            $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskHisStatusUpdate->work_stage = 2;
                            $taskHisStatusUpdate->save();

                            $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHisUpdate->status = 2;
                            $employeetaskManagerHisUpdate->save();

                            $taskStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 2;
                            $taskStatusUpdate->save();
                        }

                        $labelTotalItemCount = PassportSizeUploadModel::where('order_items_id', $passportDetails->order_items_id)->count();

                        $labelTotalPreviewCount = PassportSizeUploadModel::where('order_items_id', $passportDetails->order_items_id)->where('preview_status', 1)->count();

                        if ($labelTotalItemCount == $labelTotalPreviewCount) {
                            if ($updateStatus->is_customer_preview == 1 && $updateStatus->is_qc == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesUserId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();

                                $taskManStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                                $taskManStatusUpdate->updated_on = Server::getDateTime();
                                $taskManStatusUpdate->updated_by = JwtHelper::getSesUserId();
                                $taskManStatusUpdate->current_task_stage = 2;
                                $taskManStatusUpdate->save();
                            }
                        }

                        $orderitemstageCount = TaskManager::where('task_manager_id', $passportDetails->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        $stageCompletedCount = TaskManager::where('task_manager_id', $passportDetails->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        if ($orderitemstageCount == $stageCompletedCount) {
                            Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                            $taskStatusUpdate = TaskManager::find($passportDetails->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 4;
                            $taskStatusUpdate->save();
                        }

                        $pushNotificationforQcImageApprove = $this->customerPreviewApprovedPushNotification($taskManDetails->order_items_id);
                    }

                    //Photo print
                    if ($request->service_id == 2) {
                        Log::channel("previewImageApproved")->info("request value Photo print previewImageApproved_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageApproved")->info("Photo print approved successfully");
                        $update = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_on' => Server::getDateTime(),
                            'preview_by' => JwtHelper::getSesUserId()
                        ));

                        $photoPrintDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoprint_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $photoPrintDetails->task_manager_id)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'task_manager.task_manager_id')->first();

                        //total stage count
                        $totalStageCount = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoprint_upload.order_items_id')->select('task_manager.task_manager_id', 'task_manager.order_items_id')->first();

                        //approved after check the label details
                        $labelItemQcCount = PhotoPrintUploadModel::where('order_items_id', $totalStageCount->order_items_id)->count();

                        $labelTotalQcCount = PhotoPrintUploadModel::where('order_items_id', $totalStageCount->order_items_id)->where('qc_status', 1)->count();

                        if ($labelItemQcCount != $labelTotalQcCount) {
                            $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskHisStatusUpdate->work_stage = 2;
                            $taskHisStatusUpdate->save();

                            $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHisUpdate->status = 2;
                            $employeetaskManagerHisUpdate->save();

                            $taskStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 2;
                            $taskStatusUpdate->save();
                        }

                        $labelTotalItemCount = PhotoPrintUploadModel::where('order_items_id', $photoPrintDetails->order_items_id)->count();

                        $labelTotalPreviewCount = PhotoPrintUploadModel::where('order_items_id', $photoPrintDetails->order_items_id)->where('preview_status', 1)->count();

                        if ($labelTotalItemCount == $labelTotalPreviewCount) {
                            if ($updateStatus->is_customer_preview == 1 && $updateStatus->is_qc == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesUserId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();

                                $taskManStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                                $taskManStatusUpdate->updated_on = Server::getDateTime();
                                $taskManStatusUpdate->updated_by = JwtHelper::getSesUserId();
                                $taskManStatusUpdate->current_task_stage = 2;
                                $taskManStatusUpdate->save();
                            }
                        }

                        $orderitemstageCount = TaskManager::where('task_manager_id', $photoPrintDetails->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        $stageCompletedCount = TaskManager::where('task_manager_id', $photoPrintDetails->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        if ($orderitemstageCount == $stageCompletedCount) {
                            Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                            $taskStatusUpdate = TaskManager::find($photoPrintDetails->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 4;
                            $taskStatusUpdate->save();
                        }

                        $pushNotificationforQcImageApprove = $this->customerPreviewApprovedPushNotification($taskManDetails->order_items_id);
                    }

                    if ($request->service_id == 4) {
                        Log::channel("previewImageApproved")->info("request value PhotoFrameUploadModel previewImageApproved_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageApproved")->info("Photo frame approved successfully");
                        $update = TaskManager::where('task_manager_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_on' => Server::getDateTime(),
                            'preview_by' => JwtHelper::getSesUserId()
                        ));

                        //Order item stage check update

                        $taskManDetails = TaskManager::where('task_manager_id', $id)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc', 'orderitem_stage.orderitem_stage_id', 'task_manager.qc_status', 'task_manager.task_manager_id', 'task_manager.preview_status', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id')->first();


                        if ($updateStatus->is_customer_preview == 1 && $updateStatus->is_qc == 1) {
                            if ($updateStatus->qc_status == 1 && $updateStatus->preview_status == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesUserId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();

                                $taskManStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                                $taskManStatusUpdate->updated_on = Server::getDateTime();
                                $taskManStatusUpdate->updated_by = JwtHelper::getSesUserId();
                                $taskManStatusUpdate->current_task_stage = 2;
                                $taskManStatusUpdate->save();
                            }
                        }

                        $orderitemstageCount = TaskManager::where('task_manager_id', $id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        $stageCompletedCount = TaskManager::where('task_manager_id', $id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        if ($orderitemstageCount == $stageCompletedCount) {
                            Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                            $taskStatusUpdate = TaskManager::find($id);
                            $taskStatusUpdate->current_task_stage = 4;
                            $taskStatusUpdate->save();
                        }

                        $pushNotificationforQcImageApprove = $this->customerPreviewApprovedPushNotification($taskManDetails->order_items_id);
                    }

                    //Photoframe
                    if ($request->service_id == 3) {
                        Log::channel("previewImageApproved")->info("request value PhotoFrameUploadModel previewImageApproved_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageApproved")->info("Photo frame approved successfully");
                        $update = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_on' => Server::getDateTime(),
                            'preview_by' => JwtHelper::getSesUserId()
                        ));

                        $labelDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id', 'task_manager.task_manager_id')->first();

                        //total stage count
                        $totalStageCount = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->select('task_manager.task_manager_id', 'task_manager.order_items_id')->first();

                        //approved after check the label details
                        $labelItemQcCount = PhotoFrameLabelModel::where('order_items_id', $totalStageCount->order_items_id)->count();

                        $labelTotalQcCount = PhotoFrameLabelModel::where('order_items_id', $totalStageCount->order_items_id)->where('qc_status', 1)->count();

                        if ($labelItemQcCount != $labelTotalQcCount) {
                            $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskHisStatusUpdate->work_stage = 2;
                            $taskHisStatusUpdate->save();

                            $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHisUpdate->status = 2;
                            $employeetaskManagerHisUpdate->save();

                            $taskStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 2;
                            $taskStatusUpdate->save();
                        }



                        $labelTotalItemCount = PhotoFrameLabelModel::where('order_items_id', $totalStageCount->order_items_id)->count();

                        $labelTotalPreviewCount = PhotoFrameLabelModel::where('order_items_id', $totalStageCount->order_items_id)->where('preview_status', 1)->count();

                        $orderitemstageCount = TaskManager::where('task_manager_id', $totalStageCount->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        $stageCompletedCount = TaskManager::where('task_manager_id', $totalStageCount->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        if ($labelTotalItemCount == $labelTotalPreviewCount) {
                            if ($updateStatus->is_customer_preview == 1 && $updateStatus->is_qc == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesUserId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();

                                $taskManStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                                $taskManStatusUpdate->updated_on = Server::getDateTime();
                                $taskManStatusUpdate->updated_by = JwtHelper::getSesUserId();
                                $taskManStatusUpdate->current_task_stage = 2;
                                $taskManStatusUpdate->save();
                            }
                        }

                        if ($orderitemstageCount == $stageCompletedCount) {
                            Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                            $taskStatusUpdate = TaskManager::find($totalStageCount->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 4;
                            $taskStatusUpdate->save();
                        }

                        $pushNotificationforQcImageApprove = $this->customerPreviewApprovedPushNotification($taskManDetails->order_items_id);
                    }

                    //Selfie
                    if ($request->service_id == 6) {
                        Log::channel("previewImageApproved")->info("request value PhotoFrameUploadModel previewImageApproved_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageApproved")->info("Photo frame approved successfully");
                        $update = SelfieUploadModel::where('order_selfie_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_on' => Server::getDateTime(),
                            'preview_by' => JwtHelper::getSesUserId()
                        ));

                        $selfieDetails = SelfieUploadModel::where('order_selfie_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_selfie_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'task_manager.task_manager_id')->first();

                        //total stage count
                        $totalStageCount = SelfieUploadModel::where('order_selfie_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_selfie_upload.order_items_id')->select('task_manager.task_manager_id', 'task_manager.order_items_id')->first();

                        //approved after check the label details
                        $labelItemQcCount = SelfieUploadModel::where('order_items_id', $totalStageCount->order_items_id)->count();

                        $labelTotalQcCount = SelfieUploadModel::where('order_items_id', $totalStageCount->order_items_id)->where('qc_status', 1)->count();

                        if ($labelItemQcCount != $labelTotalQcCount) {
                            $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskHisStatusUpdate->work_stage = 2;
                            $taskHisStatusUpdate->save();

                            $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHisUpdate->status = 2;
                            $employeetaskManagerHisUpdate->save();

                            $taskStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 2;
                            $taskStatusUpdate->save();
                        }

                        $labelTotalItemCount = SelfieUploadModel::where('order_items_id', $selfieDetails->order_items_id)->count();

                        $labelTotalPreviewCount = SelfieUploadModel::where('order_items_id', $selfieDetails->order_items_id)->where('preview_status', 1)->count();

                        $orderitemstageCount = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        $stageCompletedCount = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        if ($labelTotalItemCount == $labelTotalPreviewCount) {
                            if ($updateStatus->is_customer_preview == 1 && $updateStatus->is_qc == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesUserId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();

                                $taskManStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                                $taskManStatusUpdate->updated_on = Server::getDateTime();
                                $taskManStatusUpdate->updated_by = JwtHelper::getSesUserId();
                                $taskManStatusUpdate->current_task_stage = 2;
                                $taskManStatusUpdate->save();
                            }
                        }

                        if ($orderitemstageCount == $stageCompletedCount) {
                            Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                            $taskStatusUpdate = TaskManager::find($selfieDetails->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 4;
                            $taskStatusUpdate->save();
                        }

                        $pushNotificationforQcImageApprove = $this->customerPreviewApprovedPushNotification($taskManDetails->order_items_id);
                    }

                    Log::channel("previewImageApproved")->info('** end the previewImageApproved method **');
                }

                if ($type == "rejected") {

                    Log::channel("previewImageRejected")->info('** started the previewImageRejected method **');


                    //Passport
                    if ($request->service_id == 1) {
                        Log::channel("previewImageRejected")->info("request value Passport previewImageRejected_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageRejected")->info("Passport rejected successfully");

                        $update = PassportSizeUploadModel::where('order_passport_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_reason' => $request->preview_reason,
                            'preview_reason_on' => Server::getDateTime(),
                            'qc_status' => $request->preview_status,
                            'qc_reason' => $request->preview_reason,
                            'qc_reason_on' => Server::getDateTime()
                        ));

                        $passportPreviewDetails = PassportSizeUploadModel::where('order_passport_upload_id', $id)->first();
                        if (!empty($passportPreviewDetails->preview_image)) {
                            $previewHistory = new PassportSizeUploadPreviewHistoryModel();
                            $previewHistory->order_passport_upload_id = $request->id;
                            $previewHistory->preview_image = $passportPreviewDetails->preview_image;
                            $previewHistory->preview_on = $passportPreviewDetails->preview_on;
                            $previewHistory->preview_by = $passportPreviewDetails->preview_by;
                            $previewHistory->preview_reason = $passportPreviewDetails->preview_reason;
                            $previewHistory->preview_reason_on = $passportPreviewDetails->preview_reason_on;
                            $previewHistory->preview_status = $passportPreviewDetails->preview_status;
                            $previewHistory->save();
                            Log::channel("previewAttachedImageUpload")->info("request value PassportSizeUploadPreviewHistoryModel previewAttachedImageUpload_id:: $previewHistory");
                        }

                        $passportDetails = PassportSizeUploadModel::where('order_passport_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_passport_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $passportDetails->task_manager_id)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id')->first();

                        $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                        $orderItemStatus->qc_on = Server::getDateTime();
                        $orderItemStatus->qc_by = JwtHelper::getSesUserId();
                        $orderItemStatus->qc_status = 2;
                        $orderItemStatus->save();

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $taskManagerUpdate = TaskManager::find($updateStatus->task_manager_id);
                        $taskManagerUpdate->current_task_stage = 2;
                        $taskManagerUpdate->save();

                        $pushNotificationforQcImageApprove = $this->customerPreviewRejectedPushNotification($taskManDetails->order_items_id);
                    }

                    //Photo print
                    if ($request->service_id == 2) {
                        Log::channel("previewImageRejected")->info("request value SelfieUploadModel previewImageRejected_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageRejected")->info("Photo print rejected successfully");

                        $update = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_reason' => $request->preview_reason,
                            'preview_reason_on' => Server::getDateTime(),
                            'qc_status' => $request->preview_status,
                            'qc_reason' => $request->preview_reason,
                            'qc_reason_on' => Server::getDateTime()
                        ));

                        $photoPrintPreviewDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->first();
                        if (!empty($photoPrintPreviewDetails->preview_image)) {
                            $previewHistory = new PhotoPrintUploadPreviewHistoryModel();
                            $previewHistory->order_photoprint_upload_id = $request->id;
                            $previewHistory->preview_image = $photoPrintPreviewDetails->preview_image;
                            $previewHistory->preview_on = $photoPrintPreviewDetails->preview_on;
                            $previewHistory->preview_by = $photoPrintPreviewDetails->preview_by;
                            $previewHistory->preview_reason = $photoPrintPreviewDetails->preview_reason;
                            $previewHistory->preview_reason_on = $photoPrintPreviewDetails->preview_reason_on;
                            $previewHistory->preview_status = $photoPrintPreviewDetails->preview_status;
                            $previewHistory->save();
                            Log::channel("previewAttachedImageUpload")->info("request value PhotoPrintUploadPreviewHistoryModel previewAttachedImageUpload_id:: $previewHistory");
                        }

                        $photoPrintDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoprint_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $photoPrintDetails->task_manager_id)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id')->first();

                        $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                        $orderItemStatus->qc_on = Server::getDateTime();
                        $orderItemStatus->qc_by = JwtHelper::getSesUserId();
                        $orderItemStatus->qc_status = 2;
                        $orderItemStatus->save();

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $taskManagerUpdate = TaskManager::find($updateStatus->task_manager_id);
                        $taskManagerUpdate->current_task_stage = 2;
                        $taskManagerUpdate->save();

                        $pushNotificationforQcImageApprove = $this->customerPreviewRejectedPushNotification($taskManDetails->order_items_id);
                    }

                    if ($request->service_id == 4) {
                        Log::channel("previewImageRejected")->info("request value PhotoFrameUploadModel previewImageRejected_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageRejected")->info("Photo frame rejected successfully");

                        $update = TaskManager::where('task_manager_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_reason' => $request->preview_reason,
                            'preview_reason_on' => Server::getDateTime(),
                            'qc_status' => $request->preview_status,
                            'qc_reason' => $request->preview_reason,
                            'qc_reason_on' => Server::getDateTime()
                        ));

                        $taskManagerDetails = TaskManager::where('task_manager_id', $id)->first();

                        if (!empty($taskManagerDetails->preview_image)) {
                            $previewHistory = new TaskManagerPreviewHistory();
                            $previewHistory->task_manager_id = $request->id;
                            $previewHistory->preview_image = $taskManagerDetails->preview_image;
                            $previewHistory->preview_on = $taskManagerDetails->preview_on;
                            $previewHistory->preview_by = $taskManagerDetails->preview_by;
                            $previewHistory->preview_reason = $taskManagerDetails->preview_reason;
                            $previewHistory->preview_reason_on = $taskManagerDetails->preview_reason_on;
                            $previewHistory->preview_status = $taskManagerDetails->preview_status;
                            $previewHistory->save();
                        }

                        $taskManDetails = TaskManager::where('task_manager_id', $id)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.orderitem_stage_id', 'orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc', 'task_manager.qc_status', 'task_manager.preview_status', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id')->first();

                        $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                        $orderItemStatus->qc_on = Server::getDateTime();
                        $orderItemStatus->qc_by = JwtHelper::getSesUserId();
                        $orderItemStatus->qc_status = 2;
                        $orderItemStatus->save();

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $taskManagerUpdate = TaskManager::find($updateStatus->task_manager_id);
                        $taskManagerUpdate->current_task_stage = 2;
                        $taskManagerUpdate->save();

                        $pushNotificationforQcImageApprove = $this->customerPreviewRejectedPushNotification($taskManDetails->order_items_id);
                    }

                    if ($request->service_id == 3) {
                        Log::channel("previewImageRejected")->info("request value PhotoFrameUploadModel previewImageRejected_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageRejected")->info("Photo frame rejected successfully");

                        $update = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_reason' => $request->preview_reason,
                            'preview_reason_on' => Server::getDateTime(),
                            'qc_status' => $request->preview_status,
                            'qc_reason' => $request->preview_reason,
                            'qc_reason_on' => Server::getDateTime()
                        ));

                        $framePreviewDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->first();
                        if (!empty($framePreviewDetails->preview_image)) {
                            $previewHistory = new PhotoFramePreviewHistory();
                            $previewHistory->order_photoframe_upload_label_id = $request->id;
                            $previewHistory->preview_image = $framePreviewDetails->preview_image;
                            $previewHistory->preview_on = $framePreviewDetails->preview_on;
                            $previewHistory->preview_by = $framePreviewDetails->preview_by;
                            $previewHistory->preview_reason = $framePreviewDetails->preview_reason;
                            $previewHistory->preview_reason_on = $framePreviewDetails->preview_reason_on;
                            $previewHistory->preview_status = $framePreviewDetails->preview_status;
                            $previewHistory->save();
                            Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $previewHistory");
                        }

                        $labelDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id')->first();

                        $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                        $orderItemStatus->qc_on = Server::getDateTime();
                        $orderItemStatus->qc_by = JwtHelper::getSesUserId();
                        $orderItemStatus->qc_status = 2;
                        $orderItemStatus->save();

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $taskManagerUpdate = TaskManager::find($updateStatus->task_manager_id);
                        $taskManagerUpdate->current_task_stage = 2;
                        $taskManagerUpdate->save();

                        $pushNotificationforQcImageApprove = $this->customerPreviewRejectedPushNotification($taskManDetails->order_items_id);
                    }

                    //Selfie
                    if ($request->service_id == 6) {
                        Log::channel("previewImageRejected")->info("request value PhotoFrameUploadModel previewImageRejected_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageRejected")->info("Photo frame rejected successfully");

                        $update = SelfieUploadModel::where('order_selfie_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_reason' => $request->preview_reason,
                            'preview_reason_on' => Server::getDateTime(),
                            'qc_status' => $request->preview_status,
                            'qc_reason' => $request->preview_reason,
                            'qc_reason_on' => Server::getDateTime()
                        ));

                        $selfiePreviewDetails = SelfieUploadModel::where('order_selfie_upload_id', $id)->first();
                        if (!empty($selfiePreviewDetails->preview_image)) {
                            $previewHistory = new SelfieUploadPreviewHistoryModel();
                            $previewHistory->order_selfie_upload_id = $request->id;
                            $previewHistory->preview_image = $selfiePreviewDetails->preview_image;
                            $previewHistory->preview_on = $selfiePreviewDetails->preview_on;
                            $previewHistory->preview_by = $selfiePreviewDetails->preview_by;
                            $previewHistory->preview_reason = $selfiePreviewDetails->preview_reason;
                            $previewHistory->preview_reason_on = $selfiePreviewDetails->preview_reason_on;
                            $previewHistory->preview_status = $selfiePreviewDetails->preview_status;
                            $previewHistory->save();
                            Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $previewHistory");
                        }

                        $selfieDetails = SelfieUploadModel::where('order_selfie_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_selfie_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id')->first();

                        $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                        $orderItemStatus->qc_on = Server::getDateTime();
                        $orderItemStatus->qc_by = JwtHelper::getSesUserId();
                        $orderItemStatus->qc_status = 2;
                        $orderItemStatus->save();

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $taskManagerUpdate = TaskManager::find($updateStatus->task_manager_id);
                        $taskManagerUpdate->current_task_stage = 2;
                        $taskManagerUpdate->save();

                        $pushNotificationforQcImageApprove = $this->customerPreviewRejectedPushNotification($taskManDetails->order_items_id);
                    }
                    Log::channel("previewImageRejected")->info('** end the previewImageRejected method **');
                }

                if ($request->preview_status == 1) {
                    Log::channel("previewimages")->info("save value :: previewimages_id :: $id :: previewimages inactive successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Preview image approved successfully'),
                        'data' => []
                    ]);
                } else if ($request->preview_status == 2) {
                    Log::channel("previewimages")->info("save value :: previewimages_id :: $id :: previewimages active successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Preview image rejected successfully'),
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
        // } catch (\Exception $exception) {
        //     Log::channel("previewImageApproved")->info('** end the previewImageApproved method **');
        //     return response()->json([
        //         'error' => 'Internal server error.',
        //         'message' => $exception->getMessage()
        //     ], 500);
        // }
    }

    //customer preview approved push notification
    public function customerPreviewApprovedPushNotification($orderItemId)
    {
        $senderId = JwtHelper::getSesUserId();
        $orderDetails = TaskManager::where('task_manager.order_items_id', $orderItemId)->where('task_manager_history.production_status', 1)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->leftJoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'task_manager.order_items_id', 'task_manager_history.task_manager_history_id', 'task_manager_history.employee_id', 'task_manager_history.orderitem_stage_id')->first();

        $title = "Customer Preview Approved - $orderDetails->order_code($orderDetails->product_code)";
        $body = "Your order $orderDetails->order_code - $orderDetails->product_code attachment has been approved by the customer. We'll process the next stage";

        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'Customer Preview Approved';
        $portal = "employee";
        $page = 'customer_preview_approved';

        $data = [
            'employee_id' => $orderDetails->employee_id,
            'product_code' => $orderDetails->product_code,
            'order_code' => $orderDetails->order_code,
            'order_id' => $orderDetails->order_id,
            'product_name' => $orderDetails->product_name,
            'order_items_id' => $orderDetails->order_items_id,
            'task_manager_history_id' => $orderDetails->task_manager_history_id,
            'orderitem_stage_id' => $orderDetails->orderitem_stage_id,
            'service_id' => $orderDetails->service_id,
            'random_id' => $random_id,
            'page' => $page,
            'url' => "employee/employee-task-manger/employee-task-detail?"
        ];
        $message = [
            'title' => $title,
            'module' => $module,
            'portal' => $portal,
            'body' => $body,
            'page' => $page,
            'data' => $data
        ];

        $token = Employee::where('employee_id', $orderDetails->employee_id)->where('fcm_token', '!=', NULL)->select('fcm_token', 'employee_id')->first();

        $employeeDetail = Employee::where('employee_id', $orderDetails->employee_id)->first();
        if (!empty($token)) {
            $push = Firebase::sendSingle($token->fcm_token, $message);
        }
        $getdata = GlobalHelper::notification_create($title, $body, 3, $senderId, $employeeDetail->employee_id, $module, $page, $portal, $data, $random_id);
    }

    //customer preview rejection push notification
    public function customerPreviewRejectedPushNotification($orderItemId)
    {
        $senderId = JwtHelper::getSesUserId();
        $orderDetails = TaskManager::where('task_manager.order_items_id', $orderItemId)->where('task_manager_history.production_status', 1)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->leftJoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'task_manager.order_items_id', 'task_manager_history.task_manager_history_id', 'task_manager_history.employee_id', 'task_manager_history.orderitem_stage_id')->first();

        $title = "Customer Preview Rejection - $orderDetails->order_code($orderDetails->product_code)";
        $body = "Your order $orderDetails->order_code - $orderDetails->product_code attachment has been rejected by the customer. please attach some other";

        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'Customer Preview Rejection';
        $portal = "employee";
        $page = 'customer_preview_rejection';

        $data = [
            'employee_id' => $orderDetails->employee_id,
            'product_code' => $orderDetails->product_code,
            'order_code' => $orderDetails->order_code,
            'product_name' => $orderDetails->product_name,
            'order_items_id' => $orderDetails->order_items_id,
            'task_manager_history_id' => $orderDetails->task_manager_history_id,
            'orderitem_stage_id' => $orderDetails->orderitem_stage_id,
            'service_id' => $orderDetails->service_id,
            'random_id' => $random_id,
            'order_id' => $orderDetails->order_id,
            'page' => $page,
            'url' => "employee/employee-task-manger/employee-task-detail?"
        ];
        $message = [
            'title' => $title,
            'module' => $module,
            'portal' => $portal,
            'body' => $body,
            'page' => $page,
            'data' => $data
        ];

        $token = Employee::where('employee_id', $orderDetails->employee_id)->where('fcm_token', '!=', NULL)->select('fcm_token', 'employee_id')->first();

        $employeeDetail = Employee::where('employee_id', $orderDetails->employee_id)->first();
        if (!empty($token)) {
            $push = Firebase::sendSingle($token->fcm_token, $message);
        }
        $getdata = GlobalHelper::notification_create($title, $body, 3, $senderId, $employeeDetail->employee_id, $module, $page, $portal, $data, $random_id);
    }


    public function getPersonalizedVariant($customized)
    {

        $cusArray = [];
        $resultArray = [];

        if (!empty($customized)) {

            foreach ($customized as $cm) {

                $cusArray['reference_image'] = $this->getProductImage($cm['reference_image']);
                $cusArray['image'] = $this->getProductImage($cm['image']);
                $cusArray['labels'] = $cm['labels'];
                $resultArray[] = $cusArray;
            }
        }


        return $resultArray;
    }

    public function getFrames($frameDetails)
    {

        $frameArray = [];
        $resultArray = [];

        if (!empty($frameDetails)) {

            foreach ($frameDetails as $fd) {


                $frameArray['label'] = $fd['label'];
                $frameArray['images'] = $this->getProductImage($fd['images']);
                $resultArray[] = $frameArray;
            }
        }


        return $resultArray;
    }

    public function getPhotoPrintVariant($photoprintDetails)
    {

        $frameArray = [];
        $resultArray = [];

        if (!empty($photoprintDetails)) {

            foreach ($photoprintDetails as $ppd) {


                $frameArray['image'] = $ppd['image'];
                $frameArray['image_url'] = ($ppd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $ppd['image'] : env('APP_URL') . "avatar.jpg";
                if (isset($ppd['replace_image'])) {
                    $frameArray['replace_image'] = $ppd['replace_image'];
                    $frameArray['replace_image_url'] = ($ppd['replace_image'] != 'null') ? env('APP_URL') . env('ORDER_URL') . $ppd['replace_image'] : env('APP_URL') . "avatar.jpg";
                }
                $frameArray['quantity'] = $ppd['quantity'];
                $resultArray[] = $frameArray;
            }
        }


        return $resultArray;
    }

    public function getProductImage($productImageData)
    {

        $imageArray = [];
        $resultArray = [];

        $productImageData = json_decode(json_encode($productImageData), true);

        if (!empty($productImageData)) {

            foreach ($productImageData as $data) {

                $imageArray['image'] = $data['image'];
                $imageArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
                if (isset($data['replace_image'])) {
                    $imageArray['replace_image'] = $data['replace_image'];
                    $imageArray['replace_image_url'] = ($data['replace_image'] != 'null') ? env('APP_URL') . env('ORDER_URL') . $data['replace_image'] : env('APP_URL') . "avatar.jpg";
                }
                $resultArray[] = $imageArray;
            }
        }

        return $resultArray;
    }

    public function downloadSingle_file(Request $request)
    {
        $attachment = $request->attachment;
        $module = $request->module;

        if (!empty($attachment)) {

            $fileName = $attachment;

            $myFile = base_path('public/public' . '/' . $module . '/') . $attachment;

            $headers = [
                'Content-Description: File Transfer',
                'Content-Type: application/octet-stream',
                'Content-Disposition: attachment; filename="' . basename($myFile) . '"',
                'Expires: 0',
                'Cache-Control: must-revalidate',
                'Pragma: public',
                'Content-Length: ' . filesize($myFile)
            ];

            $newName = $fileName;
            return response()->download($myFile, $newName, $headers);
        } else {
            return response()->json([
                'keyword'      => 'failed',
                'message'      => __('message.failed'),
                'data'        => [],

            ]);
        }
    }

    public function invoice_view(Request $request, $order_item_id)
    {
        try {
            Log::channel("mobilebillview")->info('** started the mobilebillview list method **');

            $get_order_id = BillItems::where('bill_item.order_items_id', $order_item_id)->first();

            $get_billno = Bills::where('bill_id', $get_order_id->bill_id)->first();

            $invoice_view = OrderItems::where('order_items.bill_no', $get_billno->bill_no)
                ->leftJoin('orders', 'order_items.order_id', '=', 'orders.order_id')
                ->select('orders.order_id', 'orders.order_code', 'orders.created_by', 'orders.order_date', 'order_items.*', 'orders.coupon_amount', 'orders.shipping_cost', 'orders.coupon_code_percentage', 'orders.billing_state_id');

            $count = $invoice_view->count();

            $invoice_view->orderBy('order_items.order_id', 'desc');
            $invoice_view = $invoice_view->get();
            $count = $invoice_view->count();
            if ($count > 0) {
                $final = [];
                $sum = 0;
                foreach ($invoice_view as $value) {
                    $ary = [];
                    $ary['order_id'] = $value['order_id'];
                    $ary['order_code'] = $value['order_code'];
                    $ary['order_item_id'] = $value['order_items_id'];
                    $ary['product_id'] = $value['product_code'];
                    $ary['product_name'] = $value['product_name'];
                    $invoice_date = OrderItems::where('order_items.bill_no', $value['bill_no'])
                        ->leftJoin('bill', 'order_items.bill_no', '=', 'bill.bill_no')->select('bill.created_on')->first();
                    $ary['invoice_date'] = $invoice_date->created_on;
                    $ary['gross_amount'] = round($value['sub_total']);
                    $ary['order_date'] = $value['order_date'];
                    $ary['quantity'] = $value['quantity'];

                    $ary['discount_percent'] = $value['coupon_code_percentage'] ?? "-";
                    $amt_find = $value['sub_total'] * $value['coupon_code_percentage'] / 100;

                    $ary['discount'] = round($amt_find);
                    if ($ary['discount'] != " ") {
                        $ary['taxable_amount'] = round($value['sub_total'] - $ary['discount']);
                    } else {
                        $ary['taxable_amount'] = round($value['sub_total']);
                    }
                    $gst_calc = 1 + ($value['gst_value'] / 100 * 1);
                    $exc_gst = $ary['taxable_amount'] / $gst_calc;
                    $amt = $ary['taxable_amount'] - $exc_gst;
                    $round_exc_gst = round($exc_gst, 2);
                    if ($value['billing_state_id'] == 33) {
                        $ary['cgst_percent'] = $value['gst_value'] / 2;
                        $ary['cgst_amount'] = round($amt / 2);
                        $ary['sgst_percent'] = $value['gst_value'] / 2;
                        $ary['sgst_amount'] =  round($amt / 2);
                        $ary['net_amount'] = $ary['taxable_amount'] + $ary['cgst_amount'] + $ary['sgst_amount'];
                        $ary['igst_percent'] = '';
                        $ary['igst_amount'] = '';
                    } else {
                        $ary['cgst_percent'] = '';
                        $ary['cgst_amount'] = '';
                        $ary['sgst_percent'] = '';
                        $ary['sgst_amount'] =  '';
                        $ary['igst_percent'] = $value['gst_value'];
                        $ary['igst_amount'] = round($amt);
                        $ary['net_amount'] = $ary['taxable_amount'] + $ary['igst_amount'];
                    }
                    // $lhipping_charge = $value['shipping_cost'];
                    $sum += round($ary['net_amount']);
                    // $total_amount = $value['shipping_cost'] + $sum;
                    $total_amount = $sum;
                    $customerdetails = Orders::where('order_id', $value['order_id'])->leftjoin('district', 'orders.billing_city_id', '=', 'district.district_id')
                        ->leftJoin('state', 'orders.billing_state_id', '=', 'state.state_id')
                        ->select('state.state_name', 'district.district_name', 'billing_customer_first_name', 'billing_customer_last_name', 'billing_email', 'billing_mobile_number', 'billing_address_1', 'billing_landmark', 'billing_pincode')->first();
                    $company_details = CompanyInfo::select('name', 'address', 'logo', 'mobile_no')->first();
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("mobilebillview")->info("list value :: $log");
                Log::channel("mobilebillview")->info('** end the mobilebillview list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Bill management viewed successfully'),
                    'data' => $final,
                    'no_of_items' => $count,
                    'net_amount' => $sum,
                    'total_amount' => $total_amount,
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
            Log::channel("mobilebillview")->error($exception);
            Log::channel("mobilebillview")->error('** end the mobilebillview list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function myorderinvoice_pdf_download($orderItemsId)
    {
        try {

            $get_order_id = BillItems::where('bill_item.order_items_id', $orderItemsId)->first();

            $get_billno = Bills::where('bill_id', $get_order_id->bill_id)->first();

            $dispatch_invoice = OrderItems::select('order_items.*', 'orders.coupon_amount', 'orders.order_code', 'orders.order_date', 'orders.coupon_code_percentage', 'orders.billing_state_id', 'orders.coupon_code')
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

                    // $ary['discount_percent'] = $value['coupon_code_percentage'] ?? "-";
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
                    if (!empty($value['coupon_code'])) {
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
                $fileName = "invoice_" . time() . '.pdf';
                $location = public_path() . '/dispatch/' . $fileName;
                $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
                $mpdf->WriteHTML(\View::make('report/dispatch', $final)->with('final', $final)->with('order_code', $order_code)->with('order_date', $order_date)->with('customer_first_name', $customer_first_name)->with('customer_last_name', $customer_last_name)->with('customer_mobile', $customer_mobile)->with('customer_email', $customer_email)->with('customer_district', $customer_district)->with('customer_state', $customer_state)->with('customer_pincode', $customer_pincode)->with('customer_landmark', $customer_landmark)->with('final_invoice_date', $final_invoice_date)->with('company_name', $company_name)->with('company_address', $company_address)->with('customer_address_2', $customer_address_2)->with('customer_alt_mobile_number', $customer_alt_mobile_number)->with('customer_gst_no', $customer_gst_no)->with('company_mobile_no', $company_mobile_no)->with('sum', sprintf("%.2f", $sum))->with('customer_address', $customer_address)->with('deliveryChargeAmount', sprintf("%.2f", $deliveryChargeAmount))->with('coupon_amount', sprintf("%.2f", $coupon_amount))->with('total_amount', $totalAmountPdf)->with('customer_id', $customer_id)->with('remaining_value', abs($remaining_value))->with('roundOffValueSymbol', $roundOffValueSymbol)->with('count', $count)->with('no', 1)->render());
                $mpdf->Output($location, 'F');


                return env('APP_URL') . 'dispatch/' . $fileName;
            }
            //  else {
            //     return response()->json(
            //         [
            //             'keyword' => 'failure',
            //             'message' => __('No Data Found'),
            //             'data' => []
            //         ]
            //     );
            // }
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

// public function getPhotoFrameUpload($orderItemsId)
    // {

    //     $photoframeUpload = PhotoFrameUploadModel::where('order_items_id', $orderItemsId)->leftjoin('order_photoframe_upload_label', 'order_photoframe_upload_label.order_photoframe_upload_label_id', '=', 'order_photoframe_upload.order_photoframe_upload_label_id')->select('order_photoframe_upload_label.label_name', 'order_photoframe_upload.*')->groupby('order_photoframe_upload.order_photoframe_upload_label_id')->get();

    //     $frameArray = [];
    //     $resultArray = [];

    //     if (!empty($photoframeUpload)) {

    //         foreach ($photoframeUpload as $pd) {


    //             $frameArray['label_name'] = $pd['label_name'];
    //             $frameArray['images'] = $this->getPhotoFrameUploadImage($pd['order_photoframe_upload_label_id']);
    //             $resultArray[] = $frameArray;
    //         }
    //     }


    //     return $resultArray;
    // }

    // public function getPhotoFrameUploadImage($uploadlabelId)
    // {

    //     $photoframeUpload = PhotoFrameUploadModel::where('order_photoframe_upload.order_photoframe_upload_label_id', $uploadlabelId)->leftjoin('order_photoframe_upload_label', 'order_photoframe_upload_label.order_photoframe_upload_label_id', '=', 'order_photoframe_upload.order_photoframe_upload_label_id')->select('order_photoframe_upload_label.label_name', 'order_photoframe_upload.*')->get();

    //     $frameArray = [];
    //     $resultArray = [];

    //     if (!empty($photoframeUpload)) {

    //         foreach ($photoframeUpload as $pd) {

    //             $frameArray['order_photoframe_upload_id'] = $pd['order_photoframe_upload_id'];
    //             $frameArray['image'] = $pd['image'];
    //             $frameArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
    //             $resultArray[] = $frameArray;
    //         }
    //     }


    //     return $resultArray;
    // }


    // public function getPersonalizedUpload($orderItemsId, $variantDetails)
    // {
    //     if (!empty($orderItemsId)) {
    //         $resultArray = [];

    //         $personalizedArray['reference_image'] = $this->personalizedreferenceImage($orderItemsId);
    //         $personalizedArray['image'] = $this->personalizedImage($orderItemsId);
    //         $personalizedArray['labels'] = $this->getPersonalizedLabel($variantDetails);
    //         $resultArray[] = $personalizedArray;

    //         return $resultArray;
    //     }
    // }

    // public function personalizedreferenceImage($orderItemsId)
    // {

    //     $personalizedUpload = PersonalizedUploadModel::where('order_items_id', $orderItemsId)->where('reference_image', '!=', '')->get();

    //     $personalizedArray = [];
    //     $resultArray = [];

    //     if (!empty($personalizedUpload)) {

    //         foreach ($personalizedUpload as $pd) {

    //             $personalizedArray['order_personalized_upload_id'] = $pd['order_personalized_upload_id'];
    //             $personalizedArray['image'] = $pd['reference_image'];
    //             $personalizedArray['image_url'] = ($pd['reference_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['reference_image'] : env('APP_URL') . "avatar.jpg";
    //             $resultArray[] = $personalizedArray;
    //         }
    //     }


    //     return $resultArray;
    // }

    // public function personalizedImage($orderItemsId)
    // {

    //     $personalizedUpload = PersonalizedUploadModel::where('order_items_id', $orderItemsId)->where('image', '!=', '')->get();

    //     $personalizedArray = [];
    //     $resultArray = [];

    //     if (!empty($personalizedUpload)) {

    //         foreach ($personalizedUpload as $pd) {

    //             $personalizedArray['order_personalized_upload_id'] = $pd['order_personalized_upload_id'];
    //             $personalizedArray['image'] = $pd['image'];
    //             $personalizedArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
    //             $resultArray[] = $personalizedArray;
    //         }
    //     }


    //     return $resultArray;
    // }

    // public function getPersonalizedLabel($variantDetails)
    // {

    //     $labelArray = [];
    //     $resultArray = [];

    //     if (!empty($variantDetails)) {

    //         foreach ($variantDetails as $cm) {

    //             $labelArray = $cm['labels'];
    //             $resultArray = $labelArray;
    //         }
    //     }


    //     return $resultArray;
    // }

    // public function getSelfieUpload($orderItemsId)
    // {

    //     $selfieUpload = SelfieUploadModel::where('order_items_id', $orderItemsId)->get();

    //     $selfieArray = [];
    //     $resultArray = [];

    //     if (!empty($selfieUpload)) {

    //         foreach ($selfieUpload as $sd) {

    //             $selfieArray['order_selfie_upload_id'] = $sd['order_selfie_upload_id'];
    //             $selfieArray['image'] = $sd['image'];
    //             $selfieArray['image_url'] = ($sd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['image'] : env('APP_URL') . "avatar.jpg";
    //             $resultArray[] = $selfieArray;
    //         }
    //     }


    //     return $resultArray;
    // }

    // public function getPhotoPrintUpload($orderItemsId)
    // {

    //     $photoprintUpload = PhotoPrintUploadModel::where('order_items_id', $orderItemsId)->get();

    //     $photoprintArray = [];
    //     $resultArray = [];

    //     if (!empty($photoprintUpload)) {

    //         foreach ($photoprintUpload as $sd) {

    //             $photoprintArray['order_photoprint_upload_id'] = $sd['order_photoprint_upload_id'];
    //             $photoprintArray['image'] = $sd['image'];
    //             $photoprintArray['image_url'] = ($sd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $sd['image'] : env('APP_URL') . "avatar.jpg";
    //             $photoprintArray['quantity'] = $sd['quantity'];
    //             $resultArray[] = $photoprintArray;
    //         }
    //     }


    //     return $resultArray;
    // }
