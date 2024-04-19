<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Events\OrderPlaced;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use Illuminate\Support\Str;
use App\Models\Dispatch;
use App\Models\Orders;
use App\Models\OrderItems;
use App\Models\Product;
use App\Models\State;
use App\Models\Customer;
use App\Models\Cod;
use App\Models\AddToCart;
use \Firebase\JWT\JWT;
use App\Helpers\GlobalHelper;
use App\Models\UserModel;
use App\Helpers\Firebase;
use App\Models\CouponCode;
use App\Models\ExpectedDays;
use App\Models\PassportSizeUploadModel;
use App\Models\PersonalizedUploadModel;
use App\Models\PhotoFrameLabelModel;
use App\Models\PhotoFrameUploadModel;
use App\Models\PhotoPrintUploadModel;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\SelfieUploadModel;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use paytm\paytmchecksum\PaytmChecksum;

class OrderPlaceController extends Controller
{
  public function placeOrder(Request $request)
  {
    try {
      Log::channel("orderwebsite")->info('** started the order create method **');
      $id = JwtHelper::getSesUserId();
      $type = $request->input('type');

      $order_items = $this->getOrderItems($id, $type);
      if ($order_items != '[]') {
        $order_items = json_decode($order_items, true);

        if ($request->input('is_cod') == 1) {
          if (!empty($order_items)) {
            for ($i = 0; $i < count($order_items); $i++) {

              $pr =  $this->getProduct($order_items[$i]['product_id']);

              if ($pr->is_cod_available != 1) {

                return response()->json([
                  'keyword' => 'failed',
                  'data'        => [],
                  'message'      => ('Cod is not available for this order')
                ]);
              }
            }
          }
        }

        $customer = $this->getCustomer($id);

        $order = new Orders();
        $order->customer_id = $customer->customer_id;
        $order->customer_code = $customer->customer_code;
        $order->billing_customer_first_name = $customer->billing_customer_first_name;
        $order->billing_customer_last_name = $customer->billing_customer_last_name;
        $order->billing_email = $customer->billing_email;
        $order->billing_mobile_number = $customer->billing_mobile_number;
        $order->billing_alt_mobile_number = $customer->billing_alt_mobile_number;
        $order->billing_country_id  = $customer->billing_country_id;
        $order->billing_state_id  = $customer->billing_state_id;
        $order->billing_city_id  = $customer->billing_city_id;
        $order->billing_address_1 = $customer->billing_address_1;
        $order->billing_address_2 = $customer->billing_address_2;
        $order->billing_place = $customer->billing_place;
        $order->billing_landmark = $customer->billing_landmark;
        $order->billing_pincode = $customer->billing_pincode;
        $order->billing_gst_no = $customer->billing_gst_no;
        $order->other_district = $customer->other_district;
        $agent = new \Jenssegers\Agent\Agent;
        $mobile = $agent->isMobile();
        $web = $agent->isDesktop();
        if ($web) {
          $device = "Web";
        }
        if ($mobile) {
          $device = "Mobile";
        }
        $order->order_from = $device;

        if ($order->save()) {
          Log::channel("orderwebsite")->info("customer details :: $order");
          Log::channel("orderwebsite")->info('** end the order customer details **');
          $order->order_code = 'ORD' . str_pad($order->order_id, 3, '0', STR_PAD_LEFT);
          $order_code = $order->order_code;

          $order->save();
          $amount = 0;
          $deliveryTotalAmount = 0;
          $cod_total = 0;

          if (!empty($order_items)) {
            for ($i = 0; $i < count($order_items); $i++) {
              $order_data = new OrderItems();
              $order_data->order_id  = $order->order_id;
              $order_data->product_variant_id  = $order_items[$i]['product_variant_id'];
              $order_data->image  = $order_items[$i]['image'];
              $order_data->images  = $order_items[$i]['images'];
              $order_data->background_color  = $order_items[$i]['background_color'];
              $order_data->variant_attributes  = $order_items[$i]['variant_attributes'];
              $order_data->photoprint_variant  = $order_items[$i]['photoprint_variant'];
              $order_data->frames  = $order_items[$i]['frames'];
              $order_data->cart_type  = $order_items[$i]['cart_type'];
              $order_data->product_id  = $order_items[$i]['product_id'];
              $order_data->quantity  = $order_items[$i]['quantity'];
              $order_data->service_id  = $order_items[$i]['service_id'];
              $order_data->is_customized  = $order_items[$i]['is_customized'];
              $order_data->product_weight  = $order_items[$i]['product_weight'];
              $order_data->delivery_slab_details  = $order_items[$i]['delivery_slab_details'];

              if ($order_items[$i]['service_id'] == 1 || $order_items[$i]['service_id'] == 2) {
                $product = ProductCatalogue::where('product_id', $order_items[$i]['product_id'])
                  ->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'product.print_size')->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')->select('product.*', 'photo_print_setting.width', 'photo_print_setting.height', 'gst_percentage.gst_percentage as gst_value')->first();
                if ($order_items[$i]['service_id'] == 1) {
                  $order_data->unit_price  = $product->selling_price;
                }
                if ($order_items[$i]['service_id'] == 2) {
                  $order_data->unit_price  = $product->first_copy_selling_price;
                  $order_data->additional_price  = $product->additional_copy_selling_price;
                }
                $order_data->product_name  = $product->product_name;
                $order_data->product_code  = $product->product_code;
                $order_data->print_size  = $product->print_size;
                $order_data->customer_description  = $product->customer_description;
                $order_data->designer_description  = $product->designer_description;
                $order_data->product_description  = $product->product_description;
                $order_data->product_specification  = $product->product_specification;
                $order_data->p_mrp  = $product->mrp;
                $order_data->p_selling_price  = $product->selling_price;
                $order_data->first_copy_selling_price  = $product->first_copy_selling_price;
                $order_data->additional_copy_selling_price  = $product->additional_copy_selling_price;
                $order_data->thumbnail_image  = $product->thumbnail_image;
                $order_data->photoprint_width  = $product->width;
                $order_data->photoprint_height  = $product->height;
                $order_data->gst_value  = $product->gst_value;
              }


              if ($order_items[$i]['service_id'] == 3 || $order_items[$i]['service_id'] == 4 || $order_items[$i]['service_id'] == 5 || $order_items[$i]['service_id'] == 6) {
                $product = ProductVariant::where('product_variant_id', $order_items[$i]['product_variant_id'])->leftjoin('product', 'product.product_id', '=', 'product_variant.product_id')
                  ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
                  ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                  ->leftjoin('variant_type', 'variant_type.variant_type_id', '=', 'product_variant.variant_type_id')->select('product_variant.*', 'product.product_name', 'product.product_code', 'product.thumbnail_image', 'product.customer_description', 'product.designer_description', 'product.product_description', 'product.product_specification', 'product.service_id', 'product.is_customized', 'category.category_name', 'gst_percentage.gst_percentage as gst_value', 'variant_type.variant_type as variant_type_name')->first();
                if ($order_items[$i]['service_id'] == 3 || $order_items[$i]['service_id'] == 4 || $order_items[$i]['service_id'] == 5 || $order_items[$i]['service_id'] == 6) {
                  $order_data->unit_price  = $product->selling_price;
                }
                // $isCustomized = $this->isCustomized(json_decode($order_items[$i]['variant_attributes'], true));
                // if (implode(" ", $isCustomized) == "yes") {
                if ($order_items[$i]['service_id'] == 4) {
                  if ($order_items[$i]['is_customized'] == 1) {
                    $order_data->unit_price  = $product->customized_price;
                  }
                }
                $order_data->product_name  = $product->product_name;
                $order_data->product_code  = $product->product_code;
                $order_data->thumbnail_image  = $product->thumbnail_image;
                $order_data->customer_description  = $product->customer_description;
                $order_data->designer_description  = $product->designer_description;
                $order_data->product_description  = $product->product_description;
                $order_data->product_specification  = $product->product_specification;
                $order_data->variant_code  = $product->variant_code;
                $order_data->pv_mrp  = $product->mrp;
                $order_data->pv_selling_price  = $product->selling_price;
                $order_data->pv_variant_attributes  = $product->variant_attributes;
                $order_data->customized_price  = $product->customized_price;
                $order_data->pv_is_customized  = $product->is_customized;
                $order_data->category_name  = $product->category_name;
                $order_data->gst_value  = $product->gst_value;
                $order_data->variant_type_name  = $product->variant_type_name;
                $order_data->variant_label  = $product->label;
              }

              $product_data = $this->getProduct($order_items[$i]['product_id']);
              if ($order_items[$i]['service_id'] == 1 || $order_items[$i]['service_id'] == 3 || $order_items[$i]['service_id'] == 4 || $order_items[$i]['service_id'] == 5 || $order_items[$i]['service_id'] == 6) {
                $order_data->sub_total  = $order_data->unit_price * $order_items[$i]['quantity'];
              }

              if ($order_items[$i]['service_id'] == 2) {
                $photoprintVariant = $this->getPhotoPrintQuantity(json_decode($order_items[$i]['photoprint_variant'], true), $product->first_copy_selling_price, $product->additional_copy_selling_price);
                $order_data->sub_total  = $photoprintVariant;
              }
              // if ($request->input('is_cod') == 1) {
              //   $cod_data = $this->getCodPercentage($product_data->cod_id);
              //   $cod_percent = $cod_data->cod_percentage;
              //   $cod_charge = ($cod_percent / 100) * floatval($order_data->sub_total) ?? 0;
              //   $cod_pr = $cod_charge;
              // }
              // if ($request->input('is_cod') == 2) {
              //   $cod_pr = 0;
              // }
              // $order_data->cod_charge  = $cod_pr ?? 0;
              // $order_data->commission_unit_price  = $product_data->dealer_commision_amount;
              // $order_data->commission_amount  =  $product_data->dealer_commision_amount * $order_items[$i]['cart_quantity'];
              $shipping = $this->getShippingPrice($order_items[$i]['service_id']);
              // $order_data->delivery_charge = $shipping->delivery_charge;
              // $order_data->delivery_charge =  $order_items[$i]['delivery_charge'];
              $order_data->created_on  = Server::getDateTime();
              $order_data->created_by  = JwtHelper::getSesUserId();

              if ($order_data->save()) {

                $orderItemIds[] = $order_data->order_items_id;

                $deliveryDetailsAmount = $this->DeliveryCalculationAmount($order_data->quantity, $order_data->product_weight, $order_data->delivery_slab_details);

                $order_data->delivery_charge = $deliveryDetailsAmount;
                $order_data->save();

                $deliveryTotalAmount += $order_data->delivery_charge;

                if ($order_items[$i]['service_id'] == 1) {
                  $photoprintsave = $this->passportSizeSaveDetails($order_items[$i]['image'], $order_items[$i]['background_color'], $order_data);
                }

                if ($order_items[$i]['service_id'] == 2) {
                  $photoprintsave = $this->photoprintSaveDetails($order_items[$i]['photoprint_variant'], $order_data);
                }

                if ($order_items[$i]['service_id'] == 3) {
                  $photoframedetails = $this->photoframeaddtocartDetail($order_items[$i]['frames']);
                  $photoframesave = $this->photoframesavedetails($photoframedetails, $order_data);
                }

                if ($order_items[$i]['service_id'] == 4) {
                  $personalizedDetails = $this->personalizedaddtocartDetail($order_items[$i]['variant_attributes']);
                  $personalizedsave = $this->personalizedSaveDetails($personalizedDetails, $order_data);
                }

                if ($order_items[$i]['service_id'] == 6) {
                  $selfiesave = $this->selfieSaveDetails($order_items[$i]['images'], $order_data);
                }


                Log::channel("orderwebsite")->info("order items details :: $order_data");
                Log::channel("orderwebsite")->info('** end the order items details **');
                //change
                if ($order_items[$i]['service_id'] == 4 || $order_items[$i]['service_id'] == 5) {
                  $product = ProductVariant::where('product_variant_id', $order_items[$i]['product_variant_id'])->select('product_variant.*', 'product.product_name', 'product.product_code', 'product.service_id')->leftjoin('product', 'product.product_id', '=', 'product_variant.product_id')->first();
                  $product_quantity = $product->quantity;
                  $quantity = $product->quantity - $order_items[$i]['quantity'];

                  $product_update = ProductVariant::where('product_variant_id', $order_items[$i]['product_variant_id'])->update(array(
                    'quantity' => $quantity,
                    'updated_on' => Server::getDateTime()
                  ));
                  $outofStock = $this->outofstockPushNotification($quantity, $product);
                }
                $cartQuantity = $order_items[$i]['quantity'];
                if (!empty($cartQuantity)) {
                  Log::channel("orderwebsite")->info("cartQuantity :: $cartQuantity");
                }
                Log::channel("orderwebsite")->info("product quantity :: $product->quantity");
                // Log::channel("orderwebsite")->info("remaining product quantity :: $quantity");
                Log::channel("orderwebsite")->info('** end the order items quantity **');
                $addtocartId = $order_items[$i]['add_to_cart_id'];
                if (!empty($addtocartId)) {
                  Log::channel("orderwebsite")->info("addtocartId :: $addtocartId");
                  Log::channel("orderwebsite")->info('** end the addtocartId **');
                }
                $deliveryAmount = $this->maxDeliveryAmount($order_items);

                $add_to_cart = AddToCart::where('add_to_cart_id', $order_items[$i]['add_to_cart_id'])->delete();

                $total_quantity = array_sum(array_column($order_items, 'quantity'));

                // $total_delivery_charge = array_sum(array_column($order_items, 'delivery_charge'));

                // $deliveryAmount = $order_data->max('delivery_charge');
                $amount += $order_data->sub_total;
                if ($request->input('is_cod') == 1) {
                  $ship_cost = 0;
                  $order_data->cod_status = 1;
                  $order_data->is_cod = $request->input('is_cod');
                  $order_data->order_status = 0;
                  // $ship_cost = $deliveryAmount;
                  $ship_cost = $deliveryTotalAmount;

                  $order_data->save();
                }
                if ($request->input('is_cod') == 2) {
                  // $ship_cost = $deliveryAmount;
                  $ship_cost = $deliveryTotalAmount;
                }
              }
            }
            // print_r($deliveryAmount);
            // exit;
            $shipping_cost = $deliveryAmount;

            $order->payment_mode = $request->input('payment_mode');
            $order->payment_transcation_id = $request->input('payment_transcation_id');
            $order->paytm_response = $request->input('paytm_response');
            $order->enable_payment_mode = $request->input('enable_payment_mode');
            $order->paytm_payment_mode = $request->input('paytm_payment_mode');
            $order->paytm_payment_status = $request->input('paytm_payment_status');
            $order->payment_service_charge = $request->input('payment_service_charge');
            $order->payment_amount = $request->input('payment_amount') - $order->payment_service_charge;
            $order->payment_transaction_date = Server::getDateTime();
            $order->order_date = Server::getDateTime();

            if ($request->input('is_cod') == 2) {
              if ($order->paytm_payment_status == "TXN_SUCCESS") {
                $order->payment_status = 1;
              } else {
                $order->payment_status = 0;
              }
            }
            if ($request->input('is_cod') == 1) {
              // if ($order->paytm_payment_status == "TXN_SUCCESS") {
              $order->payment_status = 0;
              // } else {
              //   $order->payment_status = 2;
              // }
            }


            $paymentcharge = $order->payment_service_charge;
            $order->is_cod = $request->input('is_cod');
            $order->shipping_cost = $ship_cost  ?? 0;
            $order->total_quantity = $total_quantity;
            $order->order_roundoff = number_format(round($total_quantity) - $total_quantity, 2);
            $couponCodeDetails = $this->getCouponCode($request->coupon_code);
            $order->coupon_code_percentage = !empty($couponCodeDetails->percentage) ? $couponCodeDetails->percentage : null;
            // $order->coupon_amount = !empty($couponCodeDetails->set_min_amount) ? $couponCodeDetails->set_min_amount : 0;
            $order->coupon_amount = $order->coupon_code_percentage * $amount / 100;
            $coupon_amount = $order->coupon_code_percentage * $amount / 100;
            $order->order_totalamount = $request->input('payment_amount');
            $order->cancelled_order_totalamount = $request->input('payment_amount');
            $order->coupon_code = $request->coupon_code;
            $order->created_on = Server::getDateTime();
            $order->created_by = JwtHelper::getSesUserId();
            $order->save();
          }

          if(!empty($request->coupon_code)){
          $couponCodeChargeCalculation = $this->couponCodeChargeCalculation($orderItemIds, $coupon_amount);
          }
          
          //mail for order placed details get
          $order_det = Orders::where('order_id', $order->order_id)->first();
          $customerDetGet = Customer::where('customer_id', $id)->first();
          $orderItems_get = OrderItems::where('order_id', $order_det)->get();
          $ord_item_count = OrderItems::where('order_id', $order_det->order_id)->count();
          $exp_del_days = ExpectedDays::where('expected_delivery_days_id', '=', 1)->first();
          Log::channel("orderwebsite")->info("final order details :: $order_det");
          Log::channel("orderwebsite")->info('** end the order details method **');

          // $msg =  "DEAR $order_det->billing_customer_first_name$order_det->billing_customer_last_name, ORDER ID - $order_det->payment_transcation_id, U R ORDER RECEIVED, WAITING FOR APPROVAL IN PAYMENT,WE'LL LET U KNOW,THK FOR CHOOSING NR INFOTECH FOR MORE DETAILS CALL -04567355015";

          $customerName = !empty($order_det->billing_customer_last_name) ? $order_det->billing_customer_first_name . ' ' . $order_det->billing_customer_last_name : $order_det->billing_customer_first_name;

          $expect = ExpectedDays::select('*')->first();
          $days = $expect->expected_delivery_days . ' Bussiness';
          $WEBSITE_URL = env('WEBSITE_URL');

          $msg = "Dear $customerName,Thanks for shopping with us! We’re happy to let you know that we’ve received your order. Your order id is: $order_det->order_code, Now your order is under reviewing and your order should be delivered in $days Days. We’ll let you know when it’s on the way. Get more information: $WEBSITE_URL Team Print App";
          $isSmsSent = GlobalHelper::sendSMS($order_det->billing_mobile_number, $msg);


          //mail send
          $mail_data = [];
          $mail_data['expected_delivery_days'] = $exp_del_days->expected_delivery_days;
          $mail_data['order_items'] = OrderItems::where('order_id', $order_det->order_id)->select('sub_total', 'product_name', 'order_items_id')->get();
          // print_r($mail_data['order_items']);die;
          $mail_data['order_code'] = $order_det->order_code;
          $mail_data['coupon_code'] = $order_det->coupon_code;
          $mail_data['coupon_amount'] = $order_det->coupon_amount;
          $mail_data['shipping_cost'] = $order_det->shipping_cost;
          $mail_data['items_count'] = $ord_item_count;
          $mail_data['customer_name'] = !empty($order_det->billing_customer_last_name) ? $order_det->billing_customer_first_name . ' ' . $order_det->billing_customer_last_name : $order_det->billing_customer_first_name;
          $mail_data['email'] = $order_det->billing_email;

          $totalAmount = sprintf("%.2f", $amount + $ship_cost - $coupon_amount);
          $rounded_value = round($totalAmount);

          $remainingValue = $rounded_value - $totalAmount;
          $mail_data['payment_amount'] = $order_det->payment_amount;
          $remainingAbsValue = abs($remainingValue);
          $mail_data['remaining_value'] = sprintf("%.2f", $remainingAbsValue);
          if ($remainingValue >= 0.00) {
            $mail_data['roundOffValueSymbol'] = "+";
          } else {
            $mail_data['roundOffValueSymbol'] = "-";
          }

          if ($order_det->billing_email != '') {
            event(new OrderPlaced($mail_data));
          }

          //Send Push notification for Admin(Order Placed)
          $pushNotificationforAdmin = $this->OrderPlacedPushNotificationForAdmin($order_det->order_id);

          return response()->json([
            'keyword' => 'success',
            'message' => 'Order placed successfully',
            'data'   => [$order_det]

          ]);
        } else {
          return response()->json([
            'keyword' => 'failed',
            'data'        => [],
            'message'      => __('message.failed')
          ]);
        }
      } else {
        return response()->json([
          'keyword'      => 'failure',
          'message'      => __('There is no product in your cart'),
          'data'        => []
        ]);
      }
    } catch (\Exception $exception) {
      Log::channel("orderwebsite")->error($exception);
      Log::channel("orderwebsite")->error('** end the order create method **');

      return response()->json([
        'error' => 'Internal server error.',
        'message' => $exception->getMessage()
      ], 500);
    }
  }

  public function couponCodeChargeCalculation($orderItemIds, $coupon_amount)
    {
        $dispatch_invoice = OrderItems::select('order_items.*', 'orders.order_code', 'orders.coupon_amount', 'orders.billing_state_id', 'orders.order_date', 'orders.coupon_code_percentage', 'orders.billing_state_id', 'orders.customer_id')
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->whereIn('order_items.order_items_id', $orderItemIds)->get();
            
        if (!empty($dispatch_invoice)) {
            $final = [];
            foreach ($dispatch_invoice as $dispatch) {
                $ary = [];
                $ary['order_items_id'] = $dispatch['order_items_id'];
                $ary['quantity'] = $dispatch['quantity'];
                $ary['sub_total'] = $dispatch['sub_total'];

                $final[] = $ary;
            }
        }

        $totalAmount = array_sum(array_column($final, 'sub_total'));

        // Calculate the proportion each amount contributes to the total
        $proportions = array_map(function ($item) use ($totalAmount) {
            return $item['sub_total'] / $totalAmount;
        }, $final);

        // Distribute the required amount based on the proportions
        $newAmounts = array_map(function ($proportion) use ($coupon_amount) {
            // return round($proportion * $coupon_amount);
            // return $proportion * $coupon_amount;
            return  sprintf("%.2f", $proportion * $coupon_amount);
        }, $proportions);

        // If the sum of new amounts is less than the required amount,
        // distribute the remaining amount among the new amounts
        $remainingAmount = $coupon_amount - array_sum($newAmounts);
        if ($remainingAmount > 0) {
            // Add the remaining amount to the first original amount
            $newAmounts[0] += $remainingAmount;
        }

        // Prepare the result array with order_items_id and order_amount
        $result = [];
        foreach ($final as $key => $originalAmount) {
            $result[] = [
                'order_items_id' => $originalAmount['order_items_id'],
                'sub_total' => $newAmounts[$key]
            ];
        }

        if (!empty($result)) {
            foreach ($result as $res) {
                $updateQuoteDetails = OrderItems::find($res['order_items_id']);
                $updateQuoteDetails->coupon_code_amount = $res['sub_total'];
                $updateQuoteDetails->updated_on  = Server::getDateTime();
                $updateQuoteDetails->updated_by  = JwtHelper::getSesUserId();
                $updateQuoteDetails->save();
            }
        }

    }

  public function DeliveryCalculationAmount($quantity, $weight, $slabDetails)
  {
    $searchWeight = $weight * $quantity;
    $jsonArray = json_decode($slabDetails, true);
    $foundAmount = null;
    $highestAmount = null;
    if(!empty($jsonArray)){
    foreach ($jsonArray as $item) {
      $from = floatval($item['weight_from']);

      $to = floatval($item['weight_to']);
      $amount = floatval($item['amount']);

      if ($searchWeight >= $from && $searchWeight <= $to || $searchWeight < $from) {
        // Value is within the range
        $foundAmount = $amount;
        break;
      }
      // Track the highest amount
      if ($highestAmount === null || $amount > $highestAmount) {
        $highestAmount = $amount;
      }
    }
    if ($foundAmount !== null) {
      return $foundAmount;
    } else {
      return $highestAmount;
    }
  }
  }

  public function OrderPlacedPushNotificationForAdmin($orderId)
  {
    $orders_notify_data = Orders::where('orders.order_id', $orderId)->first();

    $cusDet = Customer::where('customer_id', $orders_notify_data->customer_id)->first();

    $customerName = !empty($cusDet->customer_last_name) ? $cusDet->customer_first_name . ' ' . $cusDet->customer_last_name : $cusDet->customer_first_name;
    $module = "Place Order";
    $title = "Order Placed - $orders_notify_data->order_code";
    $page = "order_page";
    $portal = "admin";
    $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
    $body = "Good News!, " . $customerName . " successfully placed the new order " . $orders_notify_data->order_code;
    $data = [
      'order_id' => $orders_notify_data->order_id,
      'order_code' => $orders_notify_data->order_code,
      'customer_name' => $customerName,
      'page' => $page,
      'url' => 'track-order?'
    ];
    $message = [
      'title' => $title,
      'module' => $module,
      'portal' => $portal,
      'body' => $body,
      'page' => $page,
      'data' => $data
    ];
    $admin_reciever = UserModel::where('token', '!=', " ")->where('acl_user_id', 1)->first();

    if (!empty($admin_reciever)) {
      $push = Firebase::sendSingle($admin_reciever->token, $message);
    }
    $getdata = GlobalHelper::notification_create($title, $body, 1, $orders_notify_data->created_by, 1, $module, $page, $portal, $data, $random_id);
  }

  public function outofstockPushNotification($quantity, $product)
  {
    $qnty_check = (!empty($quantity) || $quantity == 0) ? $quantity : 1;
    if ($qnty_check == 0) {

      $title = "Out of stock - $product->product_code";
      $body = "$product->product_name is out of stock now. Check & fill the stock.";
      $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
      $module = 'Out of stock';
      $portal = "admin";
      $page = 'out_of_stock';
      $data = [
        'product_name' => $product->product_name,
        'service_id' => $product->service_id,
        'product_id' => $product->product_id,
        'product_code' => $product->product_code,
        'random_id' => $random_id,
        'page' => $page,
        'url' => ''
      ];

      $token = UserModel::where('acl_user_id', 1)->where('token', '!=', NULL)->first();
      if (!empty($token)) {
        $message = [
          'title' => $title,
          'body' => $body,
          'page' => $page,
          'data' => $data,
          'portal' => $portal,
          'module' => $module
        ];
        $push = Firebase::sendSingle($token->token, $message);
      }
      $getdata = GlobalHelper::notification_create($title, $body, 2, 2, 1, $module, $page, "admin", $data, $random_id);
    }
  }

  public function photoframeaddtocartDetail($frames)
  {
    $DataArray = [];
    $resultArray = [];

    $Data = json_decode($frames, true);

    if (!empty($Data)) {

      foreach ($Data as $data) {

        $DataArray['label'] = $data['label'];
        $DataArray['images'] = $data['images'];
        $resultArray[] = $DataArray;
      }
    }

    return $resultArray;
  }

  public function photoframesavedetails($label, $orderItems)
  {
    if (!empty($label)) {
      foreach ($label as $lab) {
        $newLabel = new PhotoFrameLabelModel();
        $newLabel->label_name = $lab['label'];
        $newLabel->order_items_id = $orderItems->order_items_id;
        $newLabel->created_on = Server::getDateTime();
        $newLabel->created_by = JwtHelper::getSesUserId();
        $newLabel->save();
        $resultArray = $this->photoframeuploadsavedetails($lab['images'], $newLabel, $orderItems);
      }
    }
  }

  public function photoframeuploadsavedetails($images, $newLabel, $orderItems)
  {

    if (!empty($images)) {

      foreach ($images as $res) {
        $newupload = new PhotoFrameUploadModel();
        $newupload->order_items_id = $orderItems->order_items_id;
        $newupload->order_photoframe_upload_label_id = $newLabel->order_photoframe_upload_label_id;
        $newupload->image = $res['image'];
        $newupload->created_on = Server::getDateTime();
        $newupload->created_by = JwtHelper::getSesUserId();
        $newupload->save();
      }
    }
  }

  public function personalizedaddtocartDetail($variantattributes)
  {
    $DataArray = [];
    $resultArray = [];

    $Data = json_decode($variantattributes, true);

    if (!empty($Data)) {

      foreach ($Data as $data) {

        // $DataArray['is_customized'] = $data['is_customized'];
        $DataArray['reference_image'] = $data['reference_image'];
        $DataArray['image'] = $data['image'];
        $resultArray[] = $DataArray;
      }
    }

    return $resultArray;
  }

  public function personalizedSaveDetails($personalized, $orderItems)
  {
    if (!empty($personalized)) {
      foreach ($personalized as $person) {
        $resultArray = $this->personalizeduploadsavedetails($person['reference_image'], $person['image'], $orderItems);
      }
    }
  }

  public function personalizeduploadsavedetails($referenceImage, $image, $orderItems)
  {
    // print_r($image);exit;
    if (!empty($referenceImage)) {

      foreach ($referenceImage as $res) {
        $newupload = new PersonalizedUploadModel();
        $newupload->order_items_id = $orderItems->order_items_id;
        $newupload->is_customized = $orderItems->is_customized;
        $newupload->reference_image = $res['image'];
        $newupload->created_on = Server::getDateTime();
        $newupload->created_by = JwtHelper::getSesUserId();
        $newupload->save();
      }
    }

    if (!empty($image)) {

      foreach ($image as $img) {
        $newupload = new PersonalizedUploadModel();
        $newupload->order_items_id = $orderItems->order_items_id;
        $newupload->is_customized = $orderItems->is_customized;
        $newupload->image = $img['image'];
        $newupload->created_on = Server::getDateTime();
        $newupload->created_by = JwtHelper::getSesUserId();
        $newupload->save();
      }
    }
  }

  public function selfieSaveDetails($images, $orderItems)
  {
    $images = json_decode($images, true);
    if (!empty($images)) {

      foreach ($images as $img) {
        $newupload = new SelfieUploadModel();
        $newupload->order_items_id = $orderItems->order_items_id;
        $newupload->image = $img['image'];
        $newupload->created_on = Server::getDateTime();
        $newupload->created_by = JwtHelper::getSesUserId();
        $newupload->save();
      }
    }
  }

  public function photoprintSaveDetails($images, $orderItems)
  {
    $images = json_decode($images, true);
    if (!empty($images)) {

      foreach ($images as $img) {
        $newupload = new PhotoPrintUploadModel();
        $newupload->order_items_id = $orderItems->order_items_id;
        $newupload->image = $img['image'];
        $newupload->quantity = $img['quantity'];
        $newupload->created_on = Server::getDateTime();
        $newupload->created_by = JwtHelper::getSesUserId();
        $newupload->save();
      }
    }
  }

  public function passportSizeSaveDetails($image,$backgroundColor, $orderItems)
  {
    if (!empty($image)) {
        $newupload = new PassportSizeUploadModel();
        $newupload->order_items_id = $orderItems->order_items_id;
        $newupload->image = $image;
        $newupload->background_color = $backgroundColor;
        $newupload->created_on = Server::getDateTime();
        $newupload->created_by = JwtHelper::getSesUserId();
        $newupload->save();
    }
  }

  public function getPhotoPrintQuantity($photoprintVariant, $firstCopy, $addCopy)
  {

    $cusArray = [];
    $resultArray = [];
    $amount = 0;
    if (!empty($photoprintVariant)) {

      foreach ($photoprintVariant as $cm) {
        $ary = [];
        $ary['quantity'] = $cm['quantity'];
        $resultArray[] = $ary;
      }
      // print_r($resultArray);exit;
      foreach ($resultArray as $res) {
        if ($res['quantity'] > 1) {
          $qty = $res['quantity'] - 1;
          $add_price = $qty * $addCopy;
          $amount = $add_price + $firstCopy + $amount;
        } else {
          $amount = $res['quantity'] * $firstCopy + $amount;
        }
      }
      return $amount;
    }
  }


  public function isCustomized($customized)
  {

    $cusArray = [];
    $resultArray = [];

    if (!empty($customized)) {

      foreach ($customized as $cm) {
        $resultArray[] = $cm['is_customized'];
      }
    }


    return $resultArray;
  }



  public function getOrderItems($id, $type)
  {

    if ($type == 1) {
      $order_items = AddToCart::where('customer_id', $id)->where('cart_type', 1)->get();
    }
    if ($type == 2) {
      $order_items = AddToCart::where('customer_id', $id)->where('cart_type', 2)->get();
    }

    return $order_items;
  }


  public function getProduct($id)
  {

    return Product::where('product_id', $id)->first();
  }

  public function getProductVariant($id)
  {

    return ProductVariant::where('product_variant_id', $id)->first();
  }

  public function getCustomer($id)
  {

    return Customer::where('customer_id', $id)->first();
  }

  public function getShippingPrice($sId)
  {

    return Service::where('service_id', $sId)->first();
  }

  public function maxDeliveryAmount($addDetails)
  {
    if (!empty($addDetails)) {
      $final = [];
      foreach ($addDetails as $value) {
        $ary = [];
        $service_id[] = $value['service_id'];
        $ary = Service::whereIn('service_id', $service_id)->max('delivery_charge');
        $final = $ary;
      }
      return $final;
    }
  }

  public function getCouponCode($couponCode)
  {

    return CouponCode::where('coupon_code', $couponCode)->first();
  }


  // public function getCodPercentage($codID)
  // {

  //   return Cod::where('cod_id', $codID)->first();
  // }

  public function orderRepayment(Request $request)
  {
    try {
      Log::channel("repayment_website")->info('** started the repayment_website method **');
      $id = JwtHelper::getSesUserId();
      $order_id = $request->input('order_id');

      $ads_details = Orders::where('order_id', $order_id)->where('customer_id', $id)->first();

      if (!empty($ads_details)) {

        $orderUpdate = Orders::find($ads_details->order_id);

        if ($request->input('paytm_payment_status') == "success") {
          $pStatus = 1;
        } else {
          $pStatus =  0;
        }
        $orderUpdate->payment_status = $pStatus;
        $orderUpdate->payment_transcation_id = $request->input('payment_transcation_id');
        $orderUpdate->payment_mode = $request->input('payment_mode');
        $orderUpdate->paytm_payment_mode = $request->input('paytm_payment_mode');
        $orderUpdate->payment_amount = $request->input('payment_amount');
        $orderUpdate->paytm_response = $request->input('paytm_response');
        $orderUpdate->paytm_payment_mode = $request->input('paytm_payment_mode');
        $orderUpdate->paytm_payment_status = $request->input('paytm_payment_status');
        // $orderUpdate->payment_transaction_date = $request->input('payment_transaction_date');
        $orderUpdate->payment_transaction_date = Server::getDateTime();
        $orderUpdate->updated_on = Server::getDateTime();
        $orderUpdate->updated_by = JwtHelper::getSesUserId();
        if ($orderUpdate->save()) {

          $orderUpdate_detail = Orders::where('order_id', $orderUpdate->order_id)->leftjoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->select('orders.*', 'customer.customer_first_name', 'customer.customer_last_name')->first();
          Log::channel("repayment_website")->info("repayment_website value :: $orderUpdate_detail");
          Log::channel("repayment_website")->info('** end the repayment_website method **');

          return response()->json([
            'keyword' => 'success',
            'message' => 'Order payment completed',
            'data'   => [$orderUpdate_detail]

          ]);
        } else {
          return response()->json([
            'keyword' => 'failed',
            'data'        => [],
            'message'      => __('message.failed')
          ]);
        }
      } else {
        return response()->json([
          'keyword' => 'failed',
          'data'        => [],
          'message'      => __('message.failed')
        ]);
      }
    } catch (\Exception $exception) {
      Log::channel("repayment_website")->error($exception);
      Log::channel("repayment_website")->info('** end the repayment_website method **');

      return response()->json([
        'error' => 'Internal server error.',
        'message' => $exception->getMessage()
      ], 500);
    }
  }

  public function exisitingCustomerView($id)
  {
    if ($id != '' && $id > 0) {

      $get_customer = Customer::where('customer.customer_id', $id)
        ->select(
          'customer.customer_first_name',
          'customer.customer_last_name',
          'customer.mobile_no',
          'customer.alternative_mobile_no',
          'customer.email',
          'customer.billing_gst_no',
          'customer.billing_courier_type',
          'customer.shipping_customer_first_name',
          'customer.shipping_customer_last_name',
          'customer.shipping_email',
          'customer.shipping_mobile_number',
          'customer.shipping_country_id',
          'customer.shipping_state_id',
          'customer.shipping_city_id',
          'country.name as shipping_country_name',
          'state.name as shipping_state_name',
          'district.district as shipping_district_name',
          'customer.shipping_address_1',
          'customer.shipping_address_2',
          'customer.shipping_place',
          'customer.shipping_landmark',
          'customer.shipping_pincode',
          'customer.billing_customer_first_name',
          'customer.billing_customer_last_name',
          'customer.billing_email',
          'customer.billing_mobile_number',
          'customer.billing_country_id',
          'customer.billing_state_id',
          'customer.billing_city_id',
          'c.name as billing_country_name',
          's.name as billing_state_name',
          'd.district as billing_district_name',
          'customer.billing_address_1',
          'customer.billing_address_2',
          'customer.billing_place',
          'customer.billing_landmark',
          'customer.billing_pincode'
        )
        ->leftjoin('state', 'state.state_id', '=', 'customer.shipping_state_id')
        ->leftjoin('country', 'country.country_id', '=', 'customer.shipping_country_id')
        ->leftjoin('district', 'district.district_id', '=', 'customer.shipping_city_id')
        ->leftjoin('state as s', 's.state_id', '=', 'customer.billing_state_id')
        ->leftjoin('country as c', 'c.country_id', '=', 'customer.billing_country_id')
        ->leftjoin('district as d', 'd.district_id', '=', 'customer.billing_city_id')->first();
      $count = $get_customer->count();

      if ($count > 0) {
        return response()->json([
          'keyword' => 'success',
          'message' => __('Customer viewed successfully'),
          'data' => [$get_customer]
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

  public function getdefaultImages($gTImage)
  {
    $imG = [];
    if (!empty($gTImage)) {
      foreach ($gTImage as $im) {
        if ($im['set_default'] == 1) {
          $imG = ($im['image'] != '') ? env('APP_URL') . env('PRODUCT_URL') . $im['image'] : env('APP_URL') . "avatar.jpg";
        }
      }
    }
    return $imG;
  }

  public function getdefaultImages_allImages($gTImage)
  {
    $imG = [];
    if (!empty($gTImage)) {
      foreach ($gTImage as $im) {
        $ary = [];
        $ary['set_default'] = $im['set_default'];
        $ary['image'] = ($im['image'] != '') ? env('APP_URL') . env('PRODUCT_URL') . $im['image'] : env('APP_URL') . "avatar.jpg";
        $imG[] = $ary;
      }
    }
    return $imG;
  }

  public function getPaytmintegration_post(Request $request)
  {
    $id = JwtHelper::getSesUserId();
    $payment_amount = $request->input('payment_amount');
    $enable_payment_mode = $request->input('enable_payment_mode');
    // $enable_mode = json_decode($enable_payment_mode, true);
    $type = $request->input('type');
    $is_cod = $request->input('is_cod');
    $order_id = Str::random(10);
    // $payment_mood = "live";
    $payment_mood = env('PAYTM_CHECK');
    if ($payment_mood == "live") {
      //live data
      $merchantId = "iBtdUj08196534775411";
      $merchantKey = "c7zDcs7&nCd%y_L&";
      $PAYTM_MERCHANT_WEBSITE = "DEFAULT";
      $PAYTM_INDUSTRY_TYPE_ID = "Retail";
      $PAYTM_CHANNEL_ID   = "WEB";
      $url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=" . $merchantId . "&orderId=" . $order_id;
      $callBackUrl = "https://securegw.paytm.in/theia/paytmCallback?ORDER_ID=";
    } else {
      //Staging data
      $merchantId     = "DnNAod66020520893813";
      $merchantKey     = "t%wo_kYyiE3iQdeA";
      $PAYTM_MERCHANT_WEBSITE = "WEBSTAGING";
      $PAYTM_INDUSTRY_TYPE_ID = "Retail";
      $PAYTM_CHANNEL_ID   = "WAP";
      $url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=" . $merchantId . "&orderId=" . $order_id;
      $callBackUrl = "https://securegw-stage.paytm.in/theia/paytmCallback?ORDER_ID=";
    }
    $paytmParams["body"] = array(
      "requestType" => "Payment",
      "mid" => $merchantId,
      "websiteName" => $PAYTM_MERCHANT_WEBSITE,
      "orderId" => $order_id,
      // "enablePaymentMode" => $enable_mode,
      "callbackUrl"  => $callBackUrl . $order_id,
      "txnAmount" => array(
        "value" => $payment_amount,
        "currency" => "INR",
      ),
      "userInfo" => array(
        "custId" => $id
        // "mobile" => ($customerdetails->mobile_no) ? $customerdetails->mobile_no : $customerdetails->billing_mobile_number,
        // "firstName" => ($customerdetails->customer_first_name) ? $customerdetails->customer_first_name : $customerdetails->billing_customer_first_name,
        // "lastName" => ($customerdetails->customer_last_name) ? $customerdetails->customer_last_name : $customerdetails->billing_customer_last_name
        // "email" => ($customerdetails->email) ? $customerdetails->email : $customerdetails->billing_email
      ),
    );
    $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $merchantKey);
    $paytmParams["head"] = array(
      "signature" => $checksum
      // "channelId" => $PAYTM_CHANNEL_ID
    );
    $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    $response = curl_exec($ch);
    $custom_response = [
      'paytm_response' => json_decode($response, true),
      'order_id' => $order_id,
      'type' => $type,
      'is_cod' => $is_cod
    ];
    return response()->json([
      'keyword'      => 'success',
      'message'      => __('Your transaction successfully send to paytm'),
      'data'        => [$custom_response]
    ]);
  }
}
