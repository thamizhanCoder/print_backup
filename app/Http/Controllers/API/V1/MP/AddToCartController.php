<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Http\Requests\AddToCartUpdateRequest;
use App\Models\Service;
use App\Models\VariantType;
use App\Models\AddToCart;
use App\Models\AppUpdate;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Notify;
use App\Models\ProductVisitHistory;
use App\Models\UserModel;
use App\Models\Visitors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent as Agent;


class AddToCartController extends Controller
{
    public function app_settings(Request $request)
    {
        $cusId = JwtHelper::getSesUserId();
        $addtocart_Details = AddToCart::where('customer_id', $cusId)->count();
        $cusId_get = Customer::where('customer_id', $cusId)->select('created_on')->first();
        $notification_unread_count = Notification::where('created_on', '>=', $cusId_get->created_on)
        ->where('portal', "mobile")
        ->where('receiver', $cusId)->where('msg_read',0)->count();

        $appSettings = ['playstore_url' => env('PLAYSTORE_URL'), 'appstore_url' => env('APPSTORE_URL'), 'appstore_id' => env('APPSTORE_ID'), 'addtocart_count' => $addtocart_Details, 'payment_gateway_key' => "DnNAod66020520893813",'message_unread_count' => $notification_unread_count, 'logo' => env('APP_URL') . "public/logo/p-logo.png"];
        return response()->json(
            [
                'keyword' => 'success',
                'message' => __('App settings successfully'),
                'data' => $appSettings
            ]
        );
    }

    public function playStore(Request $request)
    {

    $playstore = AppUpdate::select('*')->first();
    if (!empty($playstore)) {
      return response()->json(
        [
          'keyword' => 'success',
          'message' => __('App details listed successfully'),
          'data' => $playstore
        ]
      );
    } else {
      return response()->json(
        [
          'keyword' => 'failure',
          'message' => __('No data found'),
          'data' => []
        ]
      );
    }
    
    }

    public function insert(Request $request)
    {
        $data = new ProductVisitHistory();
        $data->service_id = $request->input('service_id');
        $data->visited_on = Server::getDateTime();
        $data->ip_address = $_SERVER['REMOTE_ADDR'];
        $Agent = new Agent();
        // agent detection influences the view storage path
        if ($Agent->isMobile()) {
            // you're a mobile device
            $data->user_agent = 'mobile';
        } else {
            $data->user_agent = $request->server('HTTP_USER_AGENT');
        }
        if ($data->save()) {
            return response()->json([
                'keyword' => 'success',
                'data'   => $data,
                'message' => __('Product visit history created')
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'data'        => [],
                'message'      => __('Product visit history failed')
            ]);
        }
    }


    public function visit_history(Request $request)
    {
        $websitevisitors = new Visitors();
        $websitevisitors->ip_address = $_SERVER['REMOTE_ADDR'];
        $websitevisitors->page_type = $request->input('page_type');
        $Agent = new Agent();
        // agent detection influences the view storage path
        if ($Agent->isMobile()) {
            // you're a mobile device
            $websitevisitors->user_agent = 'mobile';
        } else {
            $websitevisitors->user_agent = $request->server('HTTP_USER_AGENT');
        }
        $websitevisitors->visited_on = Server::getDateTime();
        if ($websitevisitors->save()) {
            return response()->json([
                'keyword' => 'success',
                'data'  => $websitevisitors,
                'message' => 'Visitor history created',
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'data'       => [],
                'message'     => __('message.failed')
            ]);
        }
    }


    public function notify_product(Request $request)
    {
        $notify = Notify::where([
            ['email', '=', $request->input('email')], ['product_id', '=', $request->product_id],
            ['product_variant_id', '=', $request->product_variant_id],
            ['status', '!=', 2]
        ])->first();
        if (empty($notify)) {
            $notify = new Notify();
            $notify->customer_id = JwtHelper::getSesUserId();
            $notify->product_id  = $request->input('product_id');
            $notify->product_variant_id  = $request->input('product_variant_id');
            $notify->email  = $request->input('email');
            $notify->created_on = Server::getDateTime();
            $notify->customer_registered_from  = "mobile";

            if ($notify->save()) {
                $notifys_data = Notify::where('notifyme_id', $notify->notifyme_id)->leftjoin('customer', 'customer.customer_id', '=', 'notifyme.customer_id')->leftjoin('product', 'product.product_id', '=', 'notifyme.product_id')->select('notifyme.*', 'product.product_name', 'product.product_code', 'product.product_id', 'product.service_id', 'customer.customer_first_name', 'customer.customer_last_name')->first();

                $customerName = !empty($notifys_data->customer_last_name) ? $notifys_data->customer_first_name . ' ' . $notifys_data->customer_last_name : $notifys_data->customer_first_name;

                $title = "Notify me - $notifys_data->product_code";
                $body = "$customerName requested the $notifys_data->product_name by using notify me for product purchase.";
                $module = 'Notify me';
                $page = 'notify_me';
                $portal = 'admin';
                $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $data = [
                    'product_id' => $notifys_data->product_id,
                    'product_name' => $notifys_data->product_name,
                    'product_code' => $notifys_data->product_code,
                    'service_id' => $notifys_data->service_id,
                    'random_id' => $random_id,
                    'page' => 'notify_me',
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
                $token = UserModel::where('token', '!=', NULL)->where('acl_role_id', 1)->select('token')->first();
                if (!empty($token)) {
                    $push = Firebase::sendSingle($token->token, $message);
                }
                $getdata = GlobalHelper::notification_create($title, $body, 2, 2, 1, $module, $page, $portal, $data, $random_id);


                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Product notified successfully'),
                    'data'        => [$notifys_data]
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __(''),
                    'data'        => []
                ]);
            }
        } else {
            return response()->json([
                'keyword'      => 'failure',
                'message'      => __('Already notify me added'),
                'data'        => []
            ]);
        }
    }

    public function cartQuantityUpdate(AddToCartUpdateRequest $request)
    {
        try {
            Log::channel("addtocart_mobile")->info('** started the addtocart quantity update method in photo frame**');

            $cart = AddToCart::find($request->add_to_cart_id);
            $cart->quantity  = $request->quantity;
            $cart->updated_on = Server::getDateTime();
            $cart->updated_by = JwtHelper::getSesUserId();
            if ($cart->save()) {

                Log::channel("addtocart_mobile")->info("request value :: " . implode(' / ', $request->all()));
                Log::channel("addtocart_mobile")->info('** end the addtocart update method in photo frame**');

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Quantity updated successfully'),
                    'data'        => [$cart]
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Quantity update failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("addtocart_mobile")->error($exception);
            Log::channel("addtocart_mobile")->error('** end the addtocart quantity update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function photoprintQuantityUpdate(Request $request)
    {
        try {
            Log::channel("addtocart_web")->info('** started the addtocart quantity update method in photo frame**');

            $cart = AddToCart::find($request->add_to_cart_id);
            $cart->quantity  = $this->getQuantity(json_decode($request->photoprint_variant, true));
            $cart->photoprint_variant  = $request->photoprint_variant;
            $cart->updated_on = Server::getDateTime();
            $cart->updated_by = JwtHelper::getSesUserId();
            if ($cart->save()) {

                Log::channel("addtocart_web")->info("request value :: " . implode(' / ', $request->all()));
                Log::channel("addtocart_web")->info('** end the addtocart update method in photo frame**');

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Photo print quantity updated successfully'),
                    'data'        => [$cart]
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Quantity update failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("addtocart_web")->error($exception);
            Log::channel("addtocart_web")->error('** end the addtocart quantity update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function getQuantity($photoprintDetails)
    {

        $frameArray = [];
        $resultArray = [];

        if (!empty($photoprintDetails)) {

            foreach ($photoprintDetails as $ppd) {
                $frameArray[] = $ppd['quantity'];
                $resultArray = $frameArray;
            }
            $qty = collect($resultArray)->sum();
        }


        return $qty;
    }

    public function addToCart_update(Request $request)
    {
        try {
            Log::channel("addtocart_mobile")->info('** started the addtocart update method **');

            $addToCart_update = json_decode($request->addToCart_update, true);

            for ($i = 0; $i < count($addToCart_update); $i++) {

                if (!empty($addToCart_update[$i]['add_to_cart_id'])) {

                    $cart = AddToCart::find($addToCart_update[$i]['add_to_cart_id']);
                    $cart->quantity = $addToCart_update[$i]['quantity'];
                    $cart->cart_type = 1;
                    $cart->updated_on = Server::getDateTime();
                    $cart->updated_by = JwtHelper::getSesUserId();
                    $cart->save();

                    $pro_Id = AddToCart::where('add_to_cart_id', $addToCart_update[$i]['add_to_cart_id'])->first();

                    AddToCart::where('cart_type', 2)->where('product_id', $pro_Id->product_id)->where('customer_id', JwtHelper::getSesUserId())->delete();
                }
                $update[] = $addToCart_update[$i]['add_to_cart_id'];
            }
            if (!empty($update)) {
                $carts = AddToCart::whereIn('add_to_cart_id', $update)->get();
                Log::channel("addtocart_mobile")->info("request value :: $carts");
                Log::channel("addtocart_mobile")->info('** end the addtocart update method **');
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Add to cart updated successfully'),
                    'data'        => $carts
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Add to cart update failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("addtocart_mobile")->error($exception);
            Log::channel("addtocart_mobile")->error('** end the addtocart update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function addToCart_delete(Request $request)
    {
        try {
            Log::channel("addtocart_mobile")->info('** started the addtocart delete method **');
            $id = $request->add_to_cart_id;
            $id = json_decode($id, true);


            if (!empty($id)) {
                $addToCartdetails = AddToCart::where('add_to_cart_id', $id)->first();
                $update = AddToCart::where('add_to_cart_id', $id)->delete();
                Log::channel("addtocart_mobile")->info("delete add_to_cart_id value :: $request->add_to_cart_id");
                Log::channel("addtocart_mobile")->info("delete customer_id value :: $addToCartdetails->customer_id");
                Log::channel("addtocart_mobile")->info('** end the addtocart delete method **');

                return response()->json([
                    'keyword' => 'success',
                    'message' =>  __('Add to cart information deleted successfully'),
                    'data' => []
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.failed'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("addtocart_mobile")->error($exception);
            Log::channel("addtocart_mobile")->error('** end the addtocart delete method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function addToCartList(Request $request)
    {
        try {

            $list = $this->getAddToCartList();

            $final = [];

            if (!empty($list)) {
                foreach ($list as $value) {
                    $ary = [];
                    $ary['add_to_cart_id'] = $value['add_to_cart_id'];
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['product_id'] = $value['product_id'];
                    $ary['is_customized'] = $value['is_customized'];
                    if ($value['service_id'] == 1) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pro_status'] == 2 ? false : true;
                        $ary['delivery_charge'] = $value['delivery_charge'];
                    }
                    if ($value['service_id'] == 2) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pro_status'] == 2 ? false : true;
                        $ary['delivery_charge'] = $value['delivery_charge'];
                    }
                    if ($value['service_id'] == 3) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                    }
                    if ($value['service_id'] == 4) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                    }
                    if ($value['service_id'] == 5) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                    }
                    if ($value['service_id'] == 6) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                    }
                    if ($value['service_id'] == 1 || $value['service_id'] == 2) {
                        $product = $this->getProduct($value['product_id']);
                        if ($value['service_id'] == 1) {
                            $ary['unit_price']  = $product->selling_price;
                        }
                        if ($value['service_id'] == 2) {
                            $photoprintVariant = $this->getPhotoPrintQuantity(json_decode($value['photoprint_variant'], true), $product->first_copy_selling_price, $product->additional_copy_selling_price);
                            $ary['unit_price']  = $product->first_copy_selling_price;
                            $ary['additional_price']  = $product->additional_copy_selling_price;
                            $ary['selling_price']  = $photoprintVariant;
                        }
                    }

                    if ($value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                        $product = $this->getProductVariant($value['product_variant_id']);
                        if ($value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                            $ary['unit_price']  = !empty($product->selling_price) ? $product->selling_price : 0;
                        }
                        // $isCustomized = $this->isCustomized(json_decode($value['variant_attributes'], true));
                        // if (implode(" ", $isCustomized) == "yes") {
                        if ($value['service_id'] == 4) {
                            if ($value['is_customized'] == 1) {
                                $ary['unit_price']  = !empty($product->customized_price) ? $product->customized_price : 0;
                            }
                        }
                    }
                    if ($value['service_id'] == 1 || $value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                        $ary['selling_price'] = sprintf("%.2f", $ary['unit_price'] * $value['quantity']);
                    }
                    $ary['quantity'] = $value['quantity'];
                    $qtyCheck = AddToCart::where('customer_id', JwtHelper::getSesUserId())->where('product_variant_id', $value['product_variant_id'])->sum('quantity');
                    $ary['personalized_quantitycheck'] = $qtyCheck;
                    $ary['variant_quantity'] = $value['variant_quantity'];
                    $ary['product_variant_id'] = $value['product_variant_id'];
                    $ary['image'] = $value['image'];
                    $ary['image_url'] = ($value['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['background_color'] = $value['background_color'];
                    $ary['variant_attributes'] = $this->getPersonalizedVariant(json_decode($value['variant_attributes'], true));
                    $ary['variant_details'] = json_decode($value['variant_details'], true);
                    $ary['frames'] = $this->getFrames(json_decode($value['frames'], true));
                    $ary['photoprint_variant'] = $this->getPhotoPrintVariant(json_decode($value['photoprint_variant'], true));
                    $ary['cart_type'] = $value['cart_type'];
                    $ary['created_on'] = $value['created_on'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['images'] = $this->getProductImage(json_decode($value['images'], true));
                    $ary['product_name'] = $value['product_name'];
                    $ary['service_id'] = $value['service_id'];
                    $ary['category_id'] = $value['category_id'];
                    $ary['category_name'] = $value['category_name'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['gst_percentage_id'] = $value['gst_percentage_id'];
                    $ary['gst_percentage'] = $value['gst_percentage'];
                    $ary['is_cod_available'] = $value['is_cod_available'];
                    $ary['mrp'] = $value['mrp'];
                    $ary['customized_price'] = $value['customized_price'];
                    $ary['variant_type_id'] = $value['variant_type_id'];
                    $ary['variant_type_name'] = $value['variant_type_name'];
                    $ary['variant_label'] = $value['variant_label'];
                    $ary['first_copy_selling_price'] = $value['first_copy_selling_price'];
                    $ary['additional_copy_selling_price'] = $value['additional_copy_selling_price'];
                    $ary['print_width'] = $value->width;
                    $ary['print_height'] = $value->height;
                    $ary['weight'] = $value['weight'];
                    $ary['slab_details'] = json_decode($value['slab_details'],true);
                    $ary['quantity_wise_weight'] = $value['weight'] * $value['quantity'];
                    // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($value['quantity'], $value['weight'],$value['slab_details']);
                    // $ary['delivery_charge_amount'] = sprintf("%.2f", $DeliveryCalculationAmount);
                    $carttypechange = AddToCart::where('add_to_cart_id', $value['add_to_cart_id'])->update(array(
                        // 'delivery_charge' => $DeliveryCalculationAmount,
                        'product_weight' => $value['weight'],
                        'delivery_slab_details' => $value['slab_details'],
                        'cart_type' => 1,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {

                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Products listed successfully'),
                    'data' => $final,
                    'count' => intval(json_encode(count($final)))
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }


    public function getProduct($id)
    {
        return ProductCatalogue::where('product_id', $id)->first();
    }

    public function getProductVariant($id)
    {
        return ProductVariant::where('product_variant_id', $id)->leftjoin('product', 'product.product_id', '=', 'product_variant.product_id')->select('product_variant.*', 'product.service_id', 'product.is_customized')->first();
    }

    public function DeliveryCalculationAmount($quantity, $weight, $slabDetails){
      
        $searchWeight = $weight * $quantity;
        $jsonArray = json_decode($slabDetails, true);
        $foundAmount = null;
        $highestAmount = null;
        foreach ($jsonArray as $item) {
            $from = floatval($item['weight_from']);
            
            $to = floatval($item['weight_to']);
            $amount = floatval($item['amount']);
        
            if ($searchWeight >= $from && $searchWeight <= $to) {
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

    public function getAddToCartList()
    {

        $user_id = JwtHelper::getSesUserId();

        $first_two_service = AddToCart::select(
            'add_to_cart.*',
            'p.product_name',
            'p.product_code',
            'p.selling_price',
            'p.mrp',
            'p.status as pro_status',
            'p.first_copy_selling_price',
            'p.additional_copy_selling_price',
            'p.gst_percentage as gst_percentage_id',
            'gst_percentage.gst_percentage',
            'service.delivery_charge',
            'p.is_cod_available',
            'p.thumbnail_image',
            'photo_print_setting.width',
            'photo_print_setting.height',
            'p.weight',
            'delivery_management.slab_details'
        )
            ->leftjoin('product as p', 'p.product_id', '=', 'add_to_cart.product_id')
            ->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'p.print_size')
            ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'p.gst_percentage')
            ->leftjoin('service', 'service.service_id', '=', 'p.service_id')
            ->leftjoin('delivery_management', 'delivery_management.service_id', '=', 'p.service_id')
            ->whereIn('p.service_id', [1, 2])
            ->where('add_to_cart.customer_id', $user_id)->orderby('add_to_cart.created_on', 'desc')->get();

        $first_two_service_collect = collect($first_two_service);


        $remaining_services = AddToCart::select(
            'add_to_cart.*',
            'ps.product_name',
            'ps.category_id',
            'c.category_name',
            'ps.product_code',
            'ps.gst_percentage as gst_percentage_id',
            'gst_percentage.gst_percentage',
            'service.delivery_charge',
            'ps.is_cod_available',
            'ps.thumbnail_image',
            'pv.mrp',
            'pv.selling_price',
            'pv.customized_price',
            'pv.quantity as variant_quantity',
            'pv.variant_type_id',
            'pv.image as variant_image',
            'pv.label as variant_label',
            'pv.variant_attributes as variant_details',
            'pv.product_variant_id as pv_id',
            'variant_type.variant_type as variant_type_name',
            'pv.weight',
            'delivery_management.slab_details'
        )
            ->leftjoin('product as ps', 'ps.product_id', '=', 'add_to_cart.product_id')
            ->leftjoin('product_variant as pv', 'pv.product_variant_id', '=', 'add_to_cart.product_variant_id')
            ->leftjoin('product as p', 'p.product_id', '=', 'pv.product_id')
            ->leftjoin('variant_type', 'variant_type.variant_type_id', '=', 'pv.variant_type_id')

            ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'ps.gst_percentage')
            ->leftjoin('service', 'service.service_id', '=', 'ps.service_id')
            ->leftjoin('delivery_management', 'delivery_management.service_id', '=', 'ps.service_id')
            ->leftjoin('category as c', 'c.category_id', '=', 'ps.category_id')
            ->whereIn('add_to_cart.service_id', [3, 4, 5, 6])
            ->where('add_to_cart.customer_id', $user_id)->orderby('add_to_cart.created_on', 'desc')
            ->get();

        $remaining_services_collect = collect($remaining_services);

        $merged_result = $first_two_service_collect->merge($remaining_services_collect)->sortByDesc('created_on');

        if (!empty($merged_result)) {

            return $merged_result;
        } else {

            return false;
        }
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
                $resultArray[] = $imageArray;
            }
        }

        return $resultArray;
    }


    public function getVariantTypeName($variantTypeId)
    {

        $VariantType = VariantType::where('variant_type_id', $variantTypeId)->first();

        if (!empty($VariantType)) {

            return $VariantType->variant_type;
        } else {

            $value = "";

            return $value;
        }
    }


    public function getVariantAtribute($VariantAttributeData)
    {

        $VariantAttributeData = json_decode($VariantAttributeData, true);

        $resultArray = [];

        if (!empty($VariantAttributeData)) {

            foreach ($VariantAttributeData as $dt) {

                $variant = $dt['variants'];
                $resultArray['variants'] = $this->getVariants($variant);
                if (isset($dt['customized'])) {

                    $customized = $dt['customized'];
                    $resultArray['customized'] = $this->getCustomized($customized);
                }
            }
        }


        return $resultArray;
    }


    public function getCustomized($customized)
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

    //kamesh
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
        }
        return $amount;
    }



    public function getVariants($variant)
    {

        $variantArray = [];
        $resultArray = [];

        if (!empty($variant)) {

            foreach ($variant as $vr) {


                $variantArray['variant_type_id'] = $vr['variant_type_id'];
                $variantArray['variant_type'] = $this->getVariantTypeName($vr['variant_type_id']);
                $variantArray['value'] = $vr['value'];
                $resultArray[] = $variantArray;
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
                $frameArray['quantity'] = $ppd['quantity'];
                $resultArray[] = $frameArray;
            }
        }


        return $resultArray;
    }

    //buynow
    public function buynowList(Request $request)
    {
        try {

            $list = $this->getBuynowList();

            $final = [];

            if (!empty($list)) {
                foreach ($list as $value) {
                    $ary = [];
                    $ary['add_to_cart_id'] = $value['add_to_cart_id'];
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['product_id'] = $value['product_id'];
                    $ary['is_customized'] = $value['is_customized'];
                    if ($value['service_id'] == 1) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pro_status'] == 2 ? false : true;
                        $ary['delivery_charge'] = $value['delivery_charge'];
                    }
                    if ($value['service_id'] == 2) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pro_status'] == 2 ? false : true;
                        $ary['delivery_charge'] = $value['delivery_charge'];
                    }
                    if ($value['service_id'] == 3) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                    }
                    if ($value['service_id'] == 4) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                    }
                    if ($value['service_id'] == 5) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                    }
                    if ($value['service_id'] == 6) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                    }
                    if ($value['service_id'] == 1 || $value['service_id'] == 2) {
                        $product = $this->getProduct($value['product_id']);
                        if ($value['service_id'] == 1) {
                            $ary['unit_price']  = $product->selling_price;
                        }
                        if ($value['service_id'] == 2) {
                            $photoprintVariant = $this->getPhotoPrintQuantity(json_decode($value['photoprint_variant'], true), $product->first_copy_selling_price, $product->additional_copy_selling_price);
                            $ary['unit_price']  = $product->first_copy_selling_price;
                            $ary['additional_price']  = $product->additional_copy_selling_price;
                            $ary['selling_price']  = $photoprintVariant;
                        }
                    }

                    if ($value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                        $product = $this->getProductVariant($value['product_variant_id']);
                        if ($value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                            $ary['unit_price']  = !empty($product->selling_price) ? $product->selling_price : 0;
                        }
                        if ($value['service_id'] == 4) {
                            if ($value['is_customized'] == 1) {
                                $ary['unit_price']  = !empty($product->customized_price) ? $product->customized_price : 0;
                            }
                        }
                    }
                    if ($value['service_id'] == 1 || $value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                        $ary['selling_price'] = sprintf("%.2f", $ary['unit_price'] * $value['quantity']);
                    }
                    $ary['quantity'] = $value['quantity'];
                    $qtyCheck = AddToCart::where('customer_id', JwtHelper::getSesUserId())->where('product_variant_id', $value['product_variant_id'])->sum('quantity');
                    $ary['personalized_quantitycheck'] = $qtyCheck;
                    $ary['variant_quantity'] = $value['variant_quantity'];
                    $ary['product_variant_id'] = $value['product_variant_id'];
                    $ary['image'] = $value['image'];
                    $ary['image_url'] = ($value['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['background_color'] = $value['background_color'];
                    $ary['variant_attributes'] = $this->getPersonalizedVariant(json_decode($value['variant_attributes'], true));
                    $ary['variant_details'] = json_decode($value['variant_details'], true);
                    $ary['frames'] = $this->getFrames(json_decode($value['frames'], true));
                    $ary['photoprint_variant'] = $this->getPhotoPrintVariant(json_decode($value['photoprint_variant'], true));
                    $ary['cart_type'] = $value['cart_type'];
                    $ary['created_on'] = $value['created_on'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['images'] = $this->getProductImage(json_decode($value['images'], true));
                    $ary['product_name'] = $value['product_name'];
                    $ary['service_id'] = $value['service_id'];
                    $ary['category_id'] = $value['category_id'];
                    $ary['category_name'] = $value['category_name'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['gst_percentage_id'] = $value['gst_percentage_id'];
                    $ary['gst_percentage'] = $value['gst_percentage'];
                    $ary['is_cod_available'] = $value['is_cod_available'];
                    $ary['mrp'] = $value['mrp'];
                    $ary['customized_price'] = $value['customized_price'];
                    $ary['variant_type_id'] = $value['variant_type_id'];
                    $ary['variant_type_name'] = $value['variant_type_name'];
                    $ary['variant_label'] = $value['variant_label'];
                    $ary['first_copy_selling_price'] = $value['first_copy_selling_price'];
                    $ary['additional_copy_selling_price'] = $value['additional_copy_selling_price'];
                    $ary['print_width'] = $value->width;
                    $ary['print_height'] = $value->height;
                    $ary['weight'] = $value['weight'];
                    $ary['slab_details'] = json_decode($value['slab_details'],true);
                    $ary['quantity_wise_weight'] = $value['weight'] * $value['quantity'];
                    // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($value['quantity'], $value['weight'],$value['slab_details']);
                    // $ary['delivery_charge_amount'] = sprintf("%.2f", $DeliveryCalculationAmount);
                    $carttypechange = AddToCart::where('add_to_cart_id', $value['add_to_cart_id'])->update(array(
                        // 'delivery_charge' => $DeliveryCalculationAmount,
                        'product_weight' => $value['weight'],
                        'delivery_slab_details' => $value['slab_details'],
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {

                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Products listed successfully'),
                    'data' => $final,
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }


    public function getBuynowList()
    {

        $user_id = JwtHelper::getSesUserId();

        $first_two_service = AddToCart::select(
            'add_to_cart.*',
            'p.product_name',
            'p.product_code',
            'p.selling_price',
            'p.mrp',
            'p.status as pro_status',
            'p.first_copy_selling_price',
            'p.additional_copy_selling_price',
            'p.gst_percentage as gst_percentage_id',
            'gst_percentage.gst_percentage',
            'service.delivery_charge',
            'p.is_cod_available',
            'p.thumbnail_image',
            'photo_print_setting.width',
            'photo_print_setting.height',
            'p.weight',
            'delivery_management.slab_details'
        )
            ->leftjoin('product as p', 'p.product_id', '=', 'add_to_cart.product_id')
            ->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'p.print_size')
            ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'p.gst_percentage')
            ->leftjoin('service', 'service.service_id', '=', 'p.service_id')
            ->leftjoin('delivery_management', 'delivery_management.service_id', '=', 'p.service_id')
            ->whereIn('p.service_id', [1, 2])
            ->where('add_to_cart.cart_type', 2)->where('add_to_cart.customer_id', $user_id)->orderby('add_to_cart.created_on', 'desc')->limit(1)->get();

        $first_two_service_collect = collect($first_two_service);


        $remaining_services = AddToCart::select(
            'add_to_cart.*',
            'ps.product_name',
            'ps.category_id',
            'c.category_name',
            'ps.product_code',
            'ps.gst_percentage as gst_percentage_id',
            'gst_percentage.gst_percentage',
            'service.delivery_charge',
            'ps.is_cod_available',
            'ps.thumbnail_image',
            'pv.mrp',
            'pv.selling_price',
            'pv.customized_price',
            'pv.quantity as variant_quantity',
            'pv.variant_type_id',
            'pv.image as variant_image',
            'pv.label as variant_label',
            'pv.variant_attributes as variant_details',
            'pv.product_variant_id as pv_id',
            'variant_type.variant_type as variant_type_name',
            'pv.weight',
            'delivery_management.slab_details'
        )
            ->leftjoin('product as ps', 'ps.product_id', '=', 'add_to_cart.product_id')
            ->leftjoin('product_variant as pv', 'pv.product_variant_id', '=', 'add_to_cart.product_variant_id')
            ->leftjoin('product as p', 'p.product_id', '=', 'pv.product_id')
            ->leftjoin('variant_type', 'variant_type.variant_type_id', '=', 'pv.variant_type_id')

            ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'ps.gst_percentage')
            ->leftjoin('service', 'service.service_id', '=', 'ps.service_id')
            ->leftjoin('delivery_management', 'delivery_management.service_id', '=', 'ps.service_id')
            ->leftjoin('category as c', 'c.category_id', '=', 'ps.category_id')
            ->whereIn('add_to_cart.service_id', [3, 4, 5, 6])->where('add_to_cart.cart_type', 2)
            ->where('add_to_cart.customer_id', $user_id)->orderby('add_to_cart.created_on', 'desc')->limit(1)
            ->get();

        $remaining_services_collect = collect($remaining_services);

        $merged_result = $first_two_service_collect->merge($remaining_services_collect)->sortByDesc('created_on');

        if (!empty($merged_result)) {

            return $merged_result;
        } else {

            return false;
        }
    }

    //photprintaddtocart
    public function getPhotoPrintAddToCartList($proId)
    {

        $user_id = JwtHelper::getSesUserId();

        $photoprintget = AddToCart::select(
            'add_to_cart.*',
            'p.product_name',
            'p.product_code',
            'p.service_id',
            'p.selling_price',
            'p.mrp',
            'p.first_copy_selling_price',
            'p.additional_copy_selling_price',
            'p.gst_percentage as gst_percentage_id',
            'gst_percentage.gst_percentage',
            'service.delivery_charge',
            'p.is_cod_available',
            'p.thumbnail_image',
            'photo_print_setting.width',
            'photo_print_setting.height',
            'photo_print_setting.min_resolution_width',
            'photo_print_setting.min_resolution_height',
            'photo_print_setting.max_resolution_width',
            'photo_print_setting.max_resolution_height',
            'p.weight',
            'delivery_management.slab_details'
        )
            ->leftjoin('product as p', 'p.product_id', '=', 'add_to_cart.product_id')
            ->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'p.print_size')
            ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'p.gst_percentage')
            ->leftjoin('service', 'service.service_id', '=', 'p.service_id')
            ->leftjoin('delivery_management', 'delivery_management.service_id', '=', 'p.service_id')
            ->where('p.service_id', 2)
            ->where('add_to_cart.cart_type', 1)->where('add_to_cart.customer_id', $user_id)->where('add_to_cart.product_id', $proId)->orderby('add_to_cart.created_on', 'desc')->groupby('add_to_cart.add_to_cart_id')->get();
        if (!empty($photoprintget)) {

            return $photoprintget;
        } else {

            return false;
        }
    }

    public function photoprintaddToCartList(Request $request)
    {
        try {

            $list = $this->getPhotoPrintAddToCartList($request->product_id);

            $final = [];

            if (!empty($list)) {
                foreach ($list as $value) {
                    $ary = [];
                    $ary['add_to_cart_id'] = $value['add_to_cart_id'];
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['product_id'] = $value['product_id'];
                    if ($value['service_id'] == 1 || $value['service_id'] == 2) {
                        $product = $this->getProduct($value['product_id']);
                        if ($value['service_id'] == 1) {
                            $ary['unit_price']  = $product->selling_price;
                        }
                        if ($value['service_id'] == 2) {
                            $photoprintVariant = $this->getPhotoPrintQuantity(json_decode($value['photoprint_variant'], true), $product->first_copy_selling_price, $product->additional_copy_selling_price);
                            $ary['unit_price']  = $product->first_copy_selling_price;
                            $ary['additional_price']  = $product->additional_copy_selling_price;
                            $ary['selling_price']  = $photoprintVariant;
                            $ary['weight'] = $value['weight'];
                            if($value['slab_details']!=''){
                            $ary['slab_details'] = json_decode($value['slab_details'],true);
                            }else{
                                $ary['slab_details'] = $value['slab_details'];
                            }
                            $ary['quantity_wise_weight'] = $value['weight'] * $value['quantity'];
                            // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($value['quantity'], $value['weight'],$value['slab_details']);
                            // $ary['delivery_charge_amount'] = sprintf("%.2f", $DeliveryCalculationAmount);
                            $carttypechange = AddToCart::where('add_to_cart_id', $value['add_to_cart_id'])->update(array(
                                // 'delivery_charge' => $DeliveryCalculationAmount,
                                'product_weight' => $value['weight'],
                                'delivery_slab_details' => $value['slab_details'],
                                'updated_on' => Server::getDateTime(),
                                'updated_by' => JwtHelper::getSesUserId()
                            ));
                        }
                    }

                    if ($value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                        $product = $this->getProductVariant($value['product_variant_id']);
                        if ($value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                            $ary['unit_price']  = !empty($product->selling_price) ? $product->selling_price : 0;
                        }
                        // $isCustomized = $this->isCustomized(json_decode($value['variant_attributes'], true));
                        // if (implode(" ", $isCustomized) == "yes") {
                        if ($value['service_id'] == 4) {
                            if ($value['is_customized'] == 1) {
                                $ary['unit_price']  = !empty($product->customized_price) ? $product->customized_price : 0;
                            }
                        }
                    }
                    if ($value['service_id'] == 1 || $value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                        $ary['selling_price'] = sprintf("%.2f", $ary['unit_price'] * $value['quantity']);
                    }
                    $ary['quantity'] = $value['quantity'];
                    $ary['product_variant_id'] = $value['product_variant_id'];
                    $ary['image'] = $value['image'];
                    $ary['image_url'] = ($value['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['background_color'] = $value['background_color'];
                    $ary['variant_attributes'] = $this->getPersonalizedVariant(json_decode($value['variant_attributes'], true));
                    if ($value['variant_details'] != '[]') {
                        $ary['variant_details'] = json_decode($value['variant_details'], true);
                    } else {
                        $ary['variant_details'] = [['variant_type_id' => $value['variant_type_id'], 'variant_type' => $value['variant_type_name'], 'value' => $value['variant_label']]];
                    }
                    $ary['frames'] = $this->getFrames(json_decode($value['frames'], true));
                    $ary['photoprint_variant'] = $this->getPhotoPrintVariant(json_decode($value['photoprint_variant'], true));
                    $ary['cart_type'] = $value['cart_type'];
                    $ary['created_on'] = $value['created_on'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['images'] = $this->getProductImage(json_decode($value['images'], true));
                    $ary['product_name'] = $value['product_name'];
                    $ary['service_id'] = $value['service_id'];
                    $ary['category_id'] = $value['category_id'];
                    $ary['category_name'] = $value['category_name'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['gst_percentage_id'] = $value['gst_percentage_id'];
                    $ary['gst_percentage'] = $value['gst_percentage'];
                    $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                    $ary['is_cod_available'] = $value['is_cod_available'];
                    $ary['mrp'] = $value['mrp'];
                    $ary['customized_price'] = $value['customized_price'];
                    $ary['variant_type_id'] = $value['variant_type_id'];
                    $ary['variant_type_name'] = $value['variant_type_name'];
                    $ary['variant_label'] = $value['variant_label'];
                    $ary['first_copy_selling_price'] = $value['first_copy_selling_price'];
                    $ary['additional_copy_selling_price'] = $value['additional_copy_selling_price'];
                    $final[] = $ary;
                }

                //
                $result = [];
                if (!empty($final)) {
                    $ary = [];
                    // $total_productAmount = $final->pluck('selling_price')->sum();
                    $productDetails = ProductCatalogue::where('product.product_id', $request->product_id)
                        ->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'product.print_size')
                        ->select(
                            'product.*',
                            'photo_print_setting.width',
                            'photo_print_setting.height',
                            'photo_print_setting.min_resolution_width',
                            'photo_print_setting.min_resolution_height',
                            'photo_print_setting.max_resolution_width',
                            'photo_print_setting.max_resolution_height'
                        )
                        ->first();
                    $ary['product_id'] = $productDetails->product_id;
                    $ary['product_name'] = $productDetails->product_name;
                    $ary['addtocartdata'] = $final;
                    $ary['first_copy_selling_price'] = $productDetails->first_copy_selling_price;
                    $ary['additional_copy_selling_price'] = $productDetails->additional_copy_selling_price;
                    $ary['print_width'] = $productDetails->width;
                    $ary['print_height'] = $productDetails->height;
                    $ary['min_resolution_width'] = $productDetails->min_resolution_width;
                    $ary['min_resolution_height'] = $productDetails->min_resolution_height;
                    $ary['max_resolution_width'] = $productDetails->max_resolution_width;
                    $ary['max_resolution_height'] = $productDetails->max_resolution_height;
                    // $ary['totalProductAmount'] = $total_productAmount;
                    $result = $ary;
                }
            }

            if (!empty($result)) {

                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Photo print product listed successfully'),
                    'data' => $result,
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }


    //get one call in addtocart
    public function singleaddToCartList(Request $request)
    {
        try {

            $list = $this->getsingleAddToCartList($request->add_to_cart_id);

            $final = [];

            if (!empty($list)) {
                foreach ($list as $value) {
                    $ary = [];
                    $ary['add_to_cart_id'] = $value['add_to_cart_id'];
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['product_id'] = $value['product_id'];
                    $ary['is_customized'] = $value['is_customized'];
                    if ($value['service_id'] == 1) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pro_status'] == 2 ? false : true;
                        $ary['delivery_charge'] = $value['delivery_charge'];
                        $ary['weight'] = $value['weight'];
                        if(!empty($value['slab_details'])){
                            $ary['slab_details'] = json_decode($value['slab_details'],true);
                        }
                        else{
                        $ary['slab_details'] = $value['slab_details'];
                        }
                        $ary['quantity_wise_weight'] = $value['weight'] * $value['quantity'];
                        // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($value['quantity'], $value['weight'], $value['slab_details']);
                        // $ary['delivery_charge_amount'] = sprintf("%.2f", $DeliveryCalculationAmount);
                        $carttypechange = AddToCart::where('add_to_cart_id', $value['add_to_cart_id'])->update(array(
                            // 'delivery_charge' => $DeliveryCalculationAmount,
                            'product_weight' => $value['weight'],
                            'delivery_slab_details' => $value['slab_details'],
                            'updated_on' => Server::getDateTime(),
                            'updated_by' => JwtHelper::getSesUserId()
                        ));
                        
                    }
                    if ($value['service_id'] == 2) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pro_status'] == 2 ? false : true;
                        $ary['delivery_charge'] = $value['delivery_charge'];
                        $ary['weight'] = $value['weight'];
                        if(!empty($value['slab_details'])){
                            $ary['slab_details'] = json_decode($value['slab_details'],true);
                        }
                        else{
                        $ary['slab_details'] = $value['slab_details'];
                        }
                        $ary['quantity_wise_weight'] = $value['weight'] * $value['quantity'];
                        // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($value['quantity'], $value['weight'], $value['slab_details']);
                        // $ary['delivery_charge_amount'] = sprintf("%.2f", $DeliveryCalculationAmount);
                        $carttypechange = AddToCart::where('add_to_cart_id', $value['add_to_cart_id'])->update(array(
                            // 'delivery_charge' => $DeliveryCalculationAmount,
                            'product_weight' => $value['weight'],
                            'delivery_slab_details' => $value['slab_details'],
                            'updated_on' => Server::getDateTime(),
                            'updated_by' => JwtHelper::getSesUserId()
                        ));
                    }
                    if ($value['service_id'] == 3) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                        $ary['weight'] = $value['pv_weight'];
                        if(!empty($value['slab_details'])){
                            $ary['slab_details'] = json_decode($value['slab_details'],true);
                        }
                        else{
                        $ary['slab_details'] = $value['slab_details'];
                        }
                        $ary['quantity_wise_weight'] = $value['pv_weight'] * $value['quantity'];
                        // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($value['quantity'], $value['pv_weight'], $value['slab_details']);
                        // $ary['delivery_charge_amount'] = sprintf("%.2f", $DeliveryCalculationAmount);
                        $carttypechange = AddToCart::where('add_to_cart_id', $value['add_to_cart_id'])->update(array(
                            // 'delivery_charge' => $DeliveryCalculationAmount,
                            'product_weight' => $value['pv_weight'],
                            'delivery_slab_details' => $value['slab_details'],
                            'updated_on' => Server::getDateTime(),
                            'updated_by' => JwtHelper::getSesUserId()
                        ));
                    }
                    if ($value['service_id'] == 4) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                        $ary['weight'] = $value['pv_weight'];
                        if(!empty($value['slab_details'])){
                            $ary['slab_details'] = json_decode($value['slab_details'],true);
                        }
                        else{
                        $ary['slab_details'] = $value['slab_details'];
                        }
                        $ary['quantity_wise_weight'] = $value['pv_weight'] * $value['quantity'];
                        // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($value['quantity'], $value['pv_weight'], $value['slab_details']);
                        // $ary['delivery_charge_amount'] = sprintf("%.2f", $DeliveryCalculationAmount);
                        $carttypechange = AddToCart::where('add_to_cart_id', $value['add_to_cart_id'])->update(array(
                            // 'delivery_charge' => $DeliveryCalculationAmount,
                            'product_weight' => $value['pv_weight'],
                            'delivery_slab_details' => $value['slab_details'],
                            'updated_on' => Server::getDateTime(),
                            'updated_by' => JwtHelper::getSesUserId()
                        ));
                    }
                    if ($value['service_id'] == 5) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                        $ary['weight'] = $value['pv_weight'];
                        if(!empty($value['slab_details'])){
                            $ary['slab_details'] = json_decode($value['slab_details'],true);
                        }
                        else{
                        $ary['slab_details'] = $value['slab_details'];
                        }
                        $ary['quantity_wise_weight'] = $value['pv_weight'] * $value['quantity'];
                        // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($value['quantity'], $value['pv_weight'], $value['slab_details']);
                        // $ary['delivery_charge_amount'] = sprintf("%.2f", $DeliveryCalculationAmount);
                        $carttypechange = AddToCart::where('add_to_cart_id', $value['add_to_cart_id'])->update(array(
                            // 'delivery_charge' => $DeliveryCalculationAmount,
                            'product_weight' => $value['pv_weight'],
                            'delivery_slab_details' => $value['slab_details'],
                            'updated_on' => Server::getDateTime(),
                            'updated_by' => JwtHelper::getSesUserId()
                        ));
                    }
                    if ($value['service_id'] == 6) {
                        $ary['thumbnail_image'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                        $ary['is_check'] = $value['pv_id'] == null ? false : true;
                        $ary['delivery_charge'] = !empty($value['pv_id']) ? $value['delivery_charge'] : 0;
                        $ary['weight'] = $value['pv_weight'];
                        if(!empty($value['slab_details'])){
                            $ary['slab_details'] = json_decode($value['slab_details'],true);
                        }
                        else{
                        $ary['slab_details'] = $value['slab_details'];
                        }
                        $ary['quantity_wise_weight'] = $value['pv_weight'] * $value['quantity'];
                        // $DeliveryCalculationAmount =  $this->DeliveryCalculationAmount($value['quantity'], $value['pv_weight'], $value['slab_details']);
                        // $ary['delivery_charge_amount'] = sprintf("%.2f", $DeliveryCalculationAmount);
                        $carttypechange = AddToCart::where('add_to_cart_id', $value['add_to_cart_id'])->update(array(
                            // 'delivery_charge' => $DeliveryCalculationAmount,
                            'product_weight' => $value['pv_weight'],
                            'delivery_slab_details' => $value['slab_details'],
                            'updated_on' => Server::getDateTime(),
                            'updated_by' => JwtHelper::getSesUserId()
                        ));
                    }
                    if ($value['service_id'] == 1 || $value['service_id'] == 2) {
                        $product = $this->getProduct($value['product_id']);
                        if ($value['service_id'] == 1) {
                            $ary['unit_price']  = $product->selling_price;
                        }
                        if ($value['service_id'] == 2) {
                            $photoprintVariant = $this->getPhotoPrintQuantity(json_decode($value['photoprint_variant'], true), $product->first_copy_selling_price, $product->additional_copy_selling_price);
                            $ary['unit_price']  = $product->first_copy_selling_price;
                            $ary['additional_price']  = $product->additional_copy_selling_price;
                            $ary['selling_price']  = $photoprintVariant;
                        }
                    }

                    if ($value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                        $product = $this->getProductVariant($value['product_variant_id']);
                        if ($value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                            $ary['unit_price']  = !empty($product->selling_price) ? $product->selling_price : 0;
                        }
                        if ($value['service_id'] == 4) {
                            if ($value['is_customized'] == 1) {
                                $ary['unit_price']  = !empty($product->customized_price) ? $product->customized_price : 0;
                            }
                        }
                    }
                    if ($value['service_id'] == 1 || $value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                        $ary['selling_price'] = sprintf("%.2f", $ary['unit_price'] * $value['quantity']);
                    }
                    $ary['quantity'] = $value['quantity'];
                    $qtyCheck = AddToCart::where('customer_id', JwtHelper::getSesUserId())->where('product_variant_id', $value['product_variant_id'])->sum('quantity');
                    $ary['personalized_quantitycheck'] = $qtyCheck;
                    $ary['variant_quantity'] = $value['variant_quantity'];
                    $ary['product_variant_id'] = $value['product_variant_id'];
                    $ary['image'] = $value['image'];
                    $ary['image_url'] = ($value['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['background_color'] = $value['background_color'];
                    $ary['variant_attributes'] = $this->getPersonalizedVariant(json_decode($value['variant_attributes'], true));
                    $ary['variant_details'] = json_decode($value['variant_details'], true);
                    $ary['frames'] = $this->getFrames(json_decode($value['frames'], true));
                    $ary['photoprint_variant'] = $this->getPhotoPrintVariant(json_decode($value['photoprint_variant'], true));
                    $ary['cart_type'] = $value['cart_type'];
                    $ary['created_on'] = $value['created_on'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['images'] = $this->getProductImage(json_decode($value['images'], true));
                    $ary['product_name'] = $value['product_name'];
                    $ary['service_id'] = $value['service_id'];
                    $ary['category_id'] = $value['category_id'];
                    $ary['category_name'] = $value['category_name'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['gst_percentage_id'] = $value['gst_percentage_id'];
                    $ary['gst_percentage'] = $value['gst_percentage'];
                    $ary['is_cod_available'] = $value['is_cod_available'];
                    $ary['mrp'] = $value['mrp'];
                    $ary['customized_price'] = $value['customized_price'];
                    $ary['variant_type_id'] = $value['variant_type_id'];
                    $ary['variant_type_name'] = $value['variant_type_name'];
                    $ary['variant_label'] = $value['variant_label'];
                    $ary['first_copy_selling_price'] = $value['first_copy_selling_price'];
                    $ary['additional_copy_selling_price'] = $value['additional_copy_selling_price'];
                    $ary['print_width'] = $value->width;
                    $ary['print_height'] = $value->height;
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {

                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Products listed successfully'),
                    'data' => $final,
                    'count' => intval(json_encode(count($final)))
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function getsingleAddToCartList($addToCartId)
    {

        $user_id = JwtHelper::getSesUserId();

        $merged_result = AddToCart::select(
            'add_to_cart.*',
            'pd.product_name',
            'pd.category_id',
            'c.category_name',
            'pd.first_copy_selling_price',
            'pd.additional_copy_selling_price',
            'pd.product_code',
            'pd.gst_percentage as gst_percentage_id',
            'gst_percentage.gst_percentage',
            'service.delivery_charge',
            'pd.is_cod_available',
            'pd.thumbnail_image',
            'pd.status as pro_status',
            'pv.mrp',
            'pv.selling_price',
            'pv.customized_price',
            'pv.quantity as variant_quantity',
            'pv.product_variant_id as pv_id',
            'pv.variant_type_id',
            'pv.image as variant_image',
            'pv.label as variant_label',
            'pv.variant_attributes as variant_details',
            'variant_type.variant_type as variant_type_name',
            'photo_print_setting.width',
            'photo_print_setting.height',
            'pd.weight',
            'delivery_management.slab_details',
            'pv.weight as pv_weight',
        )
            ->leftjoin('product_variant as pv', 'pv.product_variant_id', '=', 'add_to_cart.product_variant_id')
            ->leftjoin('product as p', 'p.product_id', '=', 'pv.product_id')
            ->leftjoin('product as pd', 'pd.product_id', '=', 'add_to_cart.product_id')
            ->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'pd.print_size')
            ->leftjoin('variant_type', 'variant_type.variant_type_id', '=', 'pv.variant_type_id')

            ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'pd.gst_percentage')
            ->leftjoin('service', 'service.service_id', '=', 'pd.service_id')
            ->leftjoin('delivery_management', 'delivery_management.service_id', '=', 'pd.service_id')
            ->leftjoin('category as c', 'c.category_id', '=', 'pd.category_id')
            ->where('add_to_cart.add_to_cart_id', $addToCartId)
            ->where('add_to_cart.customer_id', $user_id)->orderby('add_to_cart.created_on', 'desc')
            ->get();

        // $remaining_services_collect = collect($remaining_services);

        // $merged_result = $first_two_service_collect->merge($remaining_services_collect)->sortByDesc('created_on');

        if (!empty($merged_result)) {

            return $merged_result;
        } else {

            return false;
        }
    }

    //checkout verify
    public function checkoutVerifyQuantity_old(Request $request)
    {
        $type = ($request->type) ? $request->type : '';
        $id = JwtHelper::getSesUserId();
        $service = AddToCart::whereIn('service_id', [1, 2, 3, 6])->where('customer_id', $id)->get();
        if (!empty($service)) {
            $add_cart_products = AddToCart::select(
                'add_to_cart.*',
                'p.product_name',
                'p.category_id',
                'c.category_name',
                'p.product_code',
                'p.gst_percentage as gst_percentage_id',
                'gst_percentage.gst_percentage',
                'service.delivery_charge',
                'p.is_cod_available',
                'p.thumbnail_image',
                'pv.product_variant_id',
                'pv.mrp',
                'pv.selling_price',
                'pv.customized_price',
                'pv.quantity as variant_quantity',
                'pv.variant_type_id',
                'pv.image as variant_image',
                'pv.label as variant_label',
                'pv.variant_attributes as variant_details',
                'variant_type.variant_type as variant_type_name'
            )
                ->leftjoin('product_variant as pv', 'pv.product_variant_id', '=', 'add_to_cart.product_variant_id')
                ->leftjoin('product as p', 'p.product_id', '=', 'pv.product_id')
                ->leftjoin('variant_type', 'variant_type.variant_type_id', '=', 'pv.variant_type_id')

                ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'p.gst_percentage')
                ->leftjoin('service', 'service.service_id', '=', 'p.service_id')
                ->leftjoin('category as c', 'c.category_id', '=', 'p.category_id')
                ->where('add_to_cart.cart_type', $type)
                ->whereIn('add_to_cart.service_id', [4, 5])
                ->where('add_to_cart.customer_id', $id)->orderby('add_to_cart.created_on', 'desc');

            $count = count($add_cart_products->get());

            $add_cart_products = $add_cart_products->get();


            if ($count > 0) {

                $carts = [];
                $failure_carts = [];
                foreach ($add_cart_products as $key => $value) {
                    $product = ProductVariant::where('product_variant_id', $value->product_variant_id)->first();
                    if (!empty($product)) {
                        if ($value->quantity <= $product->quantity) {
                            $carts[$key]['add_to_cart_id'] = $value['add_to_cart_id'];
                            $carts[$key]['product_id'] = $value['product_id'];
                            $carts[$key]['product_variant_id'] = $value['product_variant_id'];
                            $carts[$key]['variant_quantity'] = $value['variant_quantity'];
                            $carts[$key]['product_name'] = $value['product_name'];
                        } else {
                            $failure_carts[$key]['add_to_cart_id'] = $value['add_to_cart_id'];
                            $failure_carts[$key]['product_id'] = $value['product_id'];
                            $failure_carts[$key]['product_variant_id'] = $value['product_variant_id'];
                            $failure_carts[$key]['variant_quantity'] = $value['variant_quantity'];
                            $failure_carts[$key]['product_name'] = $value['product_name'];
                        }
                    }
                    if (empty($product)) {
                        return response()->json(
                            [
                                'keyword' => 'failed',
                                'message' => __('Product unavailable'),
                                'data' => []
                            ]
                        );
                    }
                }
                $collection_failure_carts = collect($failure_carts)->values()->all();
                $response = [
                    'failure' => $collection_failure_carts
                ];
                if (!empty($collection_failure_carts)) {
                    return response()->json(
                        [
                            'keyword' => 'failed',
                            'message' => __('Checkout quantity failed'),
                            'data' => $collection_failure_carts,
                            'count' => $count
                        ]
                    );
                } else {
                    return response()->json(
                        [
                            'keyword' => 'success',
                            'message' => __('Checkout quantity successfully'),
                            'data' => $collection_failure_carts
                        ]
                    );
                }
            } else {
                return response()->json(
                    [
                        'keyword' => 'success',
                        'message' => __('Checkout quantity successfully'),
                        'data' => []
                    ]
                );
            }
        }
    }

    //checkout verify
    public function checkoutVerifyQuantity(Request $request)
    {
        $type = ($request->type) ? $request->type : '';
        $id = JwtHelper::getSesUserId();
        // $service = AddToCart::whereIn('service_id', [1, 2, 3, 6])->where('customer_id', $id)->get();
        // if (!empty($service)) {
        $add_cart_products = AddToCart::select(
            'add_to_cart.*',
            'p.product_name',
            'p.service_id',
            'p.category_id',
            'c.category_name',
            'p.product_code',
            'p.gst_percentage as gst_percentage_id',
            'gst_percentage.gst_percentage',
            'service.delivery_charge',
            'p.is_cod_available',
            'p.thumbnail_image',
            'p.product_id as pro_id',
            'p.status as pro_status',
            'pv.product_variant_id',
            'pv.mrp',
            'pv.selling_price',
            'pv.customized_price',
            'pv.quantity as variant_quantity',
            'pv.variant_type_id',
            'pv.image as variant_image',
            'pv.label as variant_label',
            'pv.variant_attributes as variant_details',
            'variant_type.variant_type as variant_type_name'
        )
            ->leftjoin('product_variant as pv', 'pv.product_variant_id', '=', 'add_to_cart.product_variant_id')
            ->leftjoin('product as p', 'p.product_id', '=', 'add_to_cart.product_id')
            ->leftjoin('variant_type', 'variant_type.variant_type_id', '=', 'pv.variant_type_id')

            ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'p.gst_percentage')
            ->leftjoin('service', 'service.service_id', '=', 'p.service_id')
            ->leftjoin('category as c', 'c.category_id', '=', 'p.category_id')
            ->where('add_to_cart.cart_type', $type)
            // ->whereIn('add_to_cart.service_id', [4, 5])
            ->where('add_to_cart.customer_id', $id)->orderby('add_to_cart.created_on', 'desc');

        $count = count($add_cart_products->get());

        $add_cart_products = $add_cart_products->get();
        // echo($add_cart_products);exit;

        if ($count > 0) {

            $carts = [];
            $failure_carts = [];
            foreach ($add_cart_products as $key => $value) {
                if ($value['service_id'] == 4 || $value['service_id'] == 5) {
                    $product = ProductVariant::where('product_variant_id', $value->product_variant_id)->first();
                    // if(!empty($product)){
                    if ($value->product_variant_id != null) {
                        if ($value->quantity <= $product->quantity) {
                            $carts[$key]['add_to_cart_id'] = $value['add_to_cart_id'];
                            $carts[$key]['product_id'] = $value['product_id'];
                            $carts[$key]['service_id'] = $value['service_id'];
                            $carts[$key]['product_variant_id'] = $value['product_variant_id'];
                            $carts[$key]['variant_quantity'] = $value['variant_quantity'];
                            $carts[$key]['product_name'] = $value['product_name'];
                            $carts[$key]['is_check'] = $value['product_variant_id'] == null ? false : true;
                        } else {
                            $failure_carts[$key]['add_to_cart_id'] = $value['add_to_cart_id'];
                            $failure_carts[$key]['product_id'] = $value['product_id'];
                            $failure_carts[$key]['service_id'] = $value['service_id'];
                            $failure_carts[$key]['product_variant_id'] = $value['product_variant_id'];
                            $failure_carts[$key]['variant_quantity'] = $value['variant_quantity'];
                            $failure_carts[$key]['product_name'] = $value['product_name'];
                            $failure_carts[$key]['is_check'] = $value['product_variant_id'] == null ? false : true;
                        }
                    }
                }
                if ($value['service_id'] == 3 || $value['service_id'] == 4 || $value['service_id'] == 5 || $value['service_id'] == 6) {
                    if ($value->product_variant_id == null) {
                        $product_unavailable[$key]['add_to_cart_id'] = $value['add_to_cart_id'];
                        $product_unavailable[$key]['product_id'] = $value['product_id'];
                        $product_unavailable[$key]['service_id'] = $value['service_id'];
                        $product_unavailable[$key]['product_variant_id'] = $value['product_variant_id'];
                        $product_unavailable[$key]['variant_quantity'] = $value['variant_quantity'];
                        $product_unavailable[$key]['product_name'] = $value['product_name'];
                        $product_unavailable[$key]['is_check'] = $value['product_variant_id'] == null ? false : true;
                    }
                }
                if ($value['service_id'] == 1 || $value['service_id'] == 2) {
                    if ($value->pro_id != null && $value->pro_status == 2) {
                        $product_unavailable[$key]['add_to_cart_id'] = $value['add_to_cart_id'];
                        $product_unavailable[$key]['product_id'] = $value['product_id'];
                        $product_unavailable[$key]['service_id'] = $value['service_id'];
                        $product_unavailable[$key]['product_variant_id'] = $value['product_variant_id'];
                        $product_unavailable[$key]['variant_quantity'] = $value['variant_quantity'];
                        $product_unavailable[$key]['product_name'] = $value['product_name'];
                        $product_unavailable[$key]['is_check'] = $value['pro_status'] == 2 ? false : true;
                    }
                }
                // }
                // }
                //     if(empty($product)){
                //     return response()->json(
                //         [
                //             'keyword' => 'failed',
                //             'message' => __('Product unavailable'),
                //             'data' => []
                //         ]
                //     );
                // }
            }
            $collection_failure_carts = collect($failure_carts)->values()->all();
            $response = [
                'failure' => $collection_failure_carts
            ];
            if (!empty($product_unavailable)) {
                $product_unavailable = collect($product_unavailable)->values()->all();
                $response = [
                    'failure' => $product_unavailable
                ];
                if (!empty($product_unavailable)) {
                    return response()->json(
                        [
                            'keyword' => 'failed',
                            'message' => __('Product unavailable'),
                            'data' => $product_unavailable,
                            // 'count' => $count
                        ]
                    );
                } else {
                    return response()->json(
                        [
                            'keyword' => 'success',
                            'message' => __('Checkout quantity successfully'),
                            'data' => $product_unavailable
                        ]
                    );
                }
            }
            if (!empty($collection_failure_carts)) {
                return response()->json(
                    [
                        'keyword' => 'failed',
                        'message' => __('Checkout quantity failed'),
                        'data' => $collection_failure_carts,
                        // 'count' => $count
                    ]
                );
            } else {
                return response()->json(
                    [
                        'keyword' => 'success',
                        'message' => __('Checkout quantity successfully'),
                        'data' => $collection_failure_carts
                    ]
                );
            }
        } else {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Checkout quantity successfully'),
                    'data' => []
                ]
            );
        }
        // }
    }
}
