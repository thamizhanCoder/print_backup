<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\ExpectedDays;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class MyOrderController extends Controller
{
    //myorder list
    public function myorder_list(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $id = JwtHelper::getSesUserId();
        $orders = Orders::where('orders.customer_id', $id)->whereIn('orders.payment_status', [0, 1])
            ->leftjoin('order_items', 'order_items.order_id', '=', 'orders.order_id')
            ->select('orders.order_id', 'orders.order_code', 'orders.total_quantity', 'orders.order_totalamount', 'orders.customer_id', DB::raw('DATE_FORMAT(orders.order_date, "%Y-%m-%d") as order_date'))->groupBy('orders.order_id')->orderBy('orders.order_id', 'desc');

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
            $ary['customer_id'] = $value->customer_id;
            $ary['order_status'] = "";
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
    }

    //myorder item view
    public function myorder_view(Request $request, $ordId)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $orderItemList = OrderItems::where('order_items.order_id', $ordId)
            ->select('orders.order_code', 'orders.order_date', 'order_items.order_id', 'order_items.order_items_id', 'order_items.product_id', 'order_items.product_variant_id', 'order_items.service_id', 'order_items.quantity', 'order_items.sub_total', 'order_items.order_status', 'order_items.image', 'order_items.product_name', 'order_items.product_code', 'order_items.thumbnail_image', 'rating_review.review', 'rating_review.rating', DB::raw('DATE_FORMAT(order_items.created_on, "%Y-%m-%d") as order_placed'), DB::raw('DATE_FORMAT(order_items.approved_on, "%Y-%m-%d") as approved_on'), DB::raw('DATE_FORMAT(order_items.disapproved_on, "%Y-%m-%d") as disapproved_on'), DB::raw('DATE_FORMAT(order_items.shipped_on, "%Y-%m-%d") as shipped_on'), DB::raw('DATE_FORMAT(order_items.dispatched_on, "%Y-%m-%d") as dispatched_on'), DB::raw('DATE_FORMAT(order_items.delivered_on, "%Y-%m-%d") as delivered_on'), DB::raw('DATE_FORMAT(order_items.cancelled_on, "%Y-%m-%d") as cancelled_on'))
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

        $orderDetails = Orders::where('orders.order_id', $ordId)->whereIn('orders.payment_status', [0, 1])
            ->leftjoin('order_items', 'order_items.order_id', '=', 'orders.order_id')
            ->leftjoin('district', 'district.district_id', '=', 'orders.billing_city_id')
            ->leftjoin('state', 'state.state_id', '=', 'orders.billing_state_id')
            ->select('orders.order_id', 'orders.order_code', 'orders.total_quantity', 'orders.order_totalamount', 'orders.paytm_payment_mode', 'orders.payment_transcation_id', 'orders.customer_id', DB::raw('DATE_FORMAT(orders.order_date, "%Y-%m-%d") as order_date'), 'orders.billing_customer_first_name', 'orders.billing_customer_last_name', 'orders.billing_address_1', 'orders.billing_place', 'orders.billing_landmark', 'orders.billing_pincode', 'orders.billing_state_id', 'orders.billing_city_id', 'district.district_name as city_name', 'state.state_name')->groupBy('orders.order_id')->orderBy('orders.order_id', 'desc')->first();
        $OrdDetail = [];
        if (!empty($orderDetails)) {
            $ary = [];
            $ary['order_id'] = $orderDetails->order_id;
            $ary['order_date'] = $orderDetails->order_date;
            $ary['order_code'] = $orderDetails->order_code;
            $orderItemsCount = OrderItems::where('order_id', $orderDetails->order_id)->count();
            $ary['no_of_items'] = !empty($orderItemsCount) ? $orderItemsCount : '';
            $ary['total_quantity'] = $orderDetails->total_quantity;
            $ary['order_totalamount'] = $orderDetails->order_totalamount;
            $ary['paytm_payment_mode'] = $orderDetails->paytm_payment_mode;
            $ary['payment_transcation_id'] = $orderDetails->payment_transcation_id;
            $ary['customer_id'] = $orderDetails->customer_id;
            $ary['customer_name'] = !empty($orderDetails->billing_customer_last_name) ? $orderDetails->billing_customer_first_name . ' ' . $orderDetails->billing_customer_last_name : $orderDetails->billing_customer_first_name;
            $ary['billing_address_1'] = $orderDetails->billing_address_1;
            $ary['billing_place'] = $orderDetails->billing_place;
            $ary['billing_landmark'] = $orderDetails->billing_landmark;
            $ary['billing_pincode'] = $orderDetails->billing_pincode;
            $ary['state_name'] = $orderDetails->state_name;
            $ary['city_name'] = $orderDetails->city_name;
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
    }

    //Cancel order item
    public function cancelOrderItem(Request $request)
    {
        $id = $request->order_items_id;

        if (!empty($id)) {
            $order = Orders::where('order_id', $id)->first();

            $update = OrderItems::where('order_items_id', $id)->update(array(
                'order_status' => 4,
                'cod_status' => 6,
                'cancel_reason' => $request->cancel_reason,
                'cancelled_on' => Server::getDateTime()
            ));
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
            return response()->json([
                'keyword' => 'success',
                'message' => 'Order cancelled',
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

    public function replaceImage(Request $request)
    {
        //start passport
        // if (!empty($request->image)) {
        //     $Extension =  pathinfo($request->input('image'), PATHINFO_EXTENSION);
        //     $extension_ary = ['jpeg', 'png', 'jpg'];
        //     if (in_array($Extension, $extension_ary)) {
        //             $request->image;

        //     } else {
        //         return response()->json([
        //             'keyword'      => 'failed',
        //             'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
        //             'data'        => []
        //         ]);
        //     }
        // }
        // //end passport

        // //start photo print
        // if (!empty($request->photoprint_variant)) {
        //     $gTImage = json_decode($request->photoprint_variant, true);
        //     if (!empty($gTImage)) {
        //         foreach ($gTImage as $im) {
        //             $ary[] = pathinfo($im['replace_image'], PATHINFO_EXTENSION);
        //         }
        //         $extension_array = ['jpeg', 'png', 'jpg'];
        //         if (!array_diff($ary, $extension_array)) {
        //             $request->photoprint_variant;
        //         } else {
        //             return response()->json([
        //                 'keyword'      => 'failed',
        //                 'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
        //                 'data'        => []
        //             ]);
        //         }
        //     }
        // }
        // //end photo print

        // //start photo frame
        // if (!empty($request->frames)) {
        //     $gTImage = json_decode($request->frames, true);
        //     if (!empty($gTImage)) {
        //         foreach ($gTImage as $im) {
        //             $imageArray = $im['images'];
        //         }
        //         if (!empty($imageArray)) {
        //             foreach ($imageArray as $img) {
        //                 $ary[] = pathinfo($img['replace_image'], PATHINFO_EXTENSION);
        //             }
        //         }
        //         $extension_array = ['jpeg', 'png', 'jpg'];
        //         if (!array_diff($ary, $extension_array)) {
        //             $request->frames;
        //         } else {
        //             return response()->json([
        //                 'keyword'      => 'failed',
        //                 'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
        //                 'data'        => []
        //             ]);
        //         }
        //     }
        // }
        // //end photo frame

        // //start personalized
        // if (!empty($request->variant_attributes)) {
        //     $gTImage = json_decode($request->variant_attributes, true);
        //     if (!empty($gTImage)) {
        //         foreach ($gTImage as $im) {
        //             if (isset($im['reference_image'])) {
        //                 $reference_image = $im['reference_image'];
        //             }
        //             if (isset($im['image'])) {
        //                 $image = $im['image'];
        //             }
        //         }
        //         //reference_image
        //         if (!empty($reference_image)) {
        //             foreach ($reference_image as $img) {
        //                 $ary[] = pathinfo($img['replace_image'], PATHINFO_EXTENSION);
        //             }
        //             $extension_array = ['jpeg', 'png', 'jpg'];
        //             if (!array_diff($ary, $extension_array)) {
        //                 $request->variant_attributes;
        //             } else {
        //                 return response()->json([
        //                     'keyword'      => 'failed',
        //                     'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
        //                     'data'        => []
        //                 ]);
        //             }
        //         }

        //         //image
        //         if (!empty($image)) {
        //             foreach ($image as $img) {
        //                 $ary[] = pathinfo($img['replace_image'], PATHINFO_EXTENSION);
        //             }
        //             $extension_array = ['jpeg', 'png', 'jpg'];
        //             if (!array_diff($ary, $extension_array)) {
        //                 $request->variant_attributes;
        //             } else {
        //                 return response()->json([
        //                     'keyword'      => 'failed',
        //                     'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
        //                     'data'        => []
        //                 ]);
        //             }
        //         }
        //     }
        // }
        // //end personalized

        // //start selfie
        // if (!empty($request->images)) {
        //     $gTImage = json_decode($request->images, true);
        //     if (!empty($gTImage)) {
        //         foreach ($gTImage as $im) {
        //             $ary[] = pathinfo($im['replace_image'], PATHINFO_EXTENSION);
        //         }
        //     $extension_array = ['jpeg', 'png', 'jpg'];
        //     if (!array_diff($ary, $extension_array)) {
        //         $request->images;
        //     } else {
        //         return response()->json([
        //             'keyword'      => 'failed',
        //             'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
        //             'data'        => []
        //         ]);
        //     }
        // }
        // }
        //end selfie

        $id = $request->order_items_id;
        $orderItem = OrderItems::find($id);
        //passport
        if (!empty($request->image)) {
            $orderItem->image = $request->image;
        }
        //photoprint
        if (!empty($request->photoprint_variant)) {
            $orderItem->photoprint_variant = $request->photoprint_variant;
        }
        //photoframe
        if (!empty($request->frames)) {
            $orderItem->frames = $request->frames;
        }
        //personalized
        if (!empty($request->variant_attributes)) {
            $orderItem->variant_attributes = $request->variant_attributes;
        }
        //selfie
        if (!empty($request->images)) {
            $orderItem->images = $request->images;
        }
        $orderItem->updated_on = Server::getDateTime();
        $orderItem->updated_by = JwtHelper::getSesUserId();

        if ($orderItem->save()) {
            $orderItems = OrderItems::where('order_items_id', $orderItem->order_items_id)->select('order_items_id', 'image', 'photoprint_variant', 'frames', 'variant_attributes', 'images')->first();

            return response()->json([
                'keyword'      => 'success',
                'message'      => __('Replace image updated successfully'),
                'data'        => [$orderItems]
            ]);
        } else {
            return response()->json([
                'keyword'      => 'failure',
                'message'      => __('Replace image update failed'),
                'data'        => []
            ]);
        }
    }

    //myorderitem view
    public function myorderItem_view(Request $request, $ordId)
    {
        $orderView = OrderItems::where('order_items.order_items_id', $ordId)
            ->select('orders.order_code', 'orders.order_date', 'order_items.*', 'rating_review.review', 'rating_review.rating')
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
                $ary['order_items_id'] = $value->order_items_id;
                $ary['service_id'] = $value->service_id;
                $ary['order_date'] = $value->order_date;
                $ary['order_code'] = $value->order_code;
                $ary['product_id'] = $value->product_id;
                $ary['product_name'] = $value->product_name;
                $ary['product_code'] = $value->product_code;
                $ary['image'] = $value['image'];
                $ary['image_url'] = ($value['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['image'] : env('APP_URL') . "avatar.jpg";
                $ary['background_color'] = $value->background_color;
                $ary['variant_attributes'] = $this->getPersonalizedVariant(json_decode($value->variant_attributes, true));
                $ary['variant_details'] = json_decode($value->pv_variant_attributes, true);
                $ary['frames'] = $this->getFrames(json_decode($value->frames, true));
                $ary['photoprint_variant'] = $this->getPhotoPrintVariant(json_decode($value->photoprint_variant, true));
                $ary['images'] = $this->getProductImage(json_decode($value->images, true));
                $ary['quantity'] = $value->quantity;
                $ary['sub_total'] = $value->sub_total;
                $ary['order_status'] = $value->order_status;
                $expectDeliveryDate = ExpectedDays::where('status', 1)->select('expected_delivery_days')->first();
                $ary['expected_days'] = $expectDeliveryDate->expected_delivery_days;
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
    }

    public function getPersonalizedVariant($customized)
    {

        $cusArray = [];
        $resultArray = [];

        if (!empty($customized)) {

            foreach ($customized as $cm) {


                // $cusArray['is_customized'] = $cm['is_customized'];
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
}
