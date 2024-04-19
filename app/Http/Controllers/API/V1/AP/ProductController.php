<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Events\SendAvailable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use Illuminate\Support\Str;
use App\Helpers\GlobalHelper;
use App\Models\Brand;
use App\Models\Cod;
use App\Models\Category;
use App\Models\Notify;
use App\Models\Product;
use File;
use Illuminate\Support\Facades\DB;
use App\Helpers\Firebase;
use App\Models\FcmToken;
use App\Models\Customer;

class ProductController extends Controller
{
    public function brand_name(Request $request)
    {
        $brand_name = Brand::where("status", 1)->select('brand_id', 'brand_name')
            ->orderBy('created_on', 'desc')->get();

        if (!empty($brand_name)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Brand name listed successfully'),
                'data' => $brand_name
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('Brand name not found'),
                'data' => []
            ]);
        }
    }

    public function product_cod_list(Request $request)
    {
        $cod = Cod::where("status", 1)->select('cod_id', 'cod_percentage')
            ->orderBy('created_on', 'desc')->get();

        if (!empty($cod)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Cod listed successfully'),
                'data' => $cod
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('Cod not found'),
                'data' => []
            ]);
        }
    }

    public function category_name(Request $request)
    {
        $category_name = Category::where("status", 1)->where('parent_category_id', 0)->select('category_id', 'category_name')
            ->orderBy('created_on', 'desc')->get();

        if (!empty($category_name)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Category name listed successfully'),
                'data' => $category_name
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('Category name not found'),
                'data' => []
            ]);
        }
    }

    public function subcategory_name(Request $request, $id)
    {

        if ($id != '' && $id > 0) {
            $subcategory_name = [];

            $subcategory_name = Category::where("status", 1)->where('parent_category_id', $id)->select('category_id', 'category_name', 'parent_category_id')->get();


            if (!empty($subcategory_name)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Subcategory name listed successfully'),
                    'data' => $subcategory_name
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failure',
                    'message' =>  __('No data found'), 'data' => $subcategory_name
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' =>  __('No data found'), 'data' => []
            ]);
        }
    }

    public function product_create(Request $request)
    {
        $name = Product::where([['product_name', $request->input('product_name')],  ['status', '!=', 2]])->first();
        if (empty($name)) {

            $productdetails = new Product();
            $productdetails->product_name = $request->input('product_name');
            $productdetails->brand_id = $request->input('brand_id');
            $productdetails->category_id = $request->input('category_id');
            $productdetails->subcategory_id = $request->input('subcategory_id');
            $productdetails->product_weight = $request->input('product_weight');
            $productdetails->product_description = $request->input('product_description');
            $productdetails->product_specification = $request->input('product_specification');
            $productdetails->other_specification =  $request->input('other_specification');
            $productdetails->product_image = $request->input('product_image');
            $productdetails->selling_price = $request->input('selling_price');
            $productdetails->mrp = $request->input('mrp');
            $productdetails->quantity = $request->input('quantity');
            $productdetails->dealer_commision_amount = $request->input('dealer_commision_amount');
            $productdetails->cod_id = $request->input('cod_id');
            $productdetails->is_cod = $request->input('is_cod');
            $productdetails->is_featured_product = $request->input('is_featured_product');
            $productdetails->is_notification = $request->input('is_notification');
            if ($productdetails->is_notification == 1) {
                $productdetails->publish = 1;
            }
            $productdetails->is_trending_item = $request->input('is_trending_item');
            $productdetails->is_hot_item = $request->input('is_hot_item');
            // $productdetails->product_available = $request->input('product_available');
            $productdetails->created_on = Server::getDateTime();
            $productdetails->created_by = JwtHelper::getSesUserId();

            if ($productdetails->save()) {
                $product_code = 'PRO_' . str_pad($productdetails->product_id, 3, '0', STR_PAD_LEFT);
                $update_productdetails = Product::find($productdetails->product_id);
                $update_productdetails->product_code = $product_code;
                $update_productdetails->save();
                $productdetails_data = Product::where('product_id', $productdetails->product_id)
                    ->leftjoin('brand', 'brand.brand_id', '=', 'product.brand_id')
                    ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
                    ->leftjoin('category as sub', 'sub.category_id', '=', 'product.subcategory_id')
                    ->select('product.*', 'brand.brand_name', 'category.category_name', 'sub.category_name as subcategory_name')
                    ->first();

                $productdata = Product::leftjoin('acl_user', 'acl_user.acl_user_id', '=', 'product.created_by')->leftjoin('category', 'category.category_id', '=', 'product.category_id')->select('acl_user.acl_user_id', 'acl_user.name', 'product.product_id', 'product.product_name', 'product.category_id', 'category.category_name')->where('product.product_id', $productdetails->product_id)->first();
                $emp_info = [
                    'proname' => $productdata->product_name,
                    'acl_name' => $productdata->name
                ];
                $title = Config('fcm_msg.title.new_product_create');
                $title2 = Config('fcm_msg.title.featured_product');
                $msg = Config('fcm_msg.body.product_create');
                $msg2 = Config('fcm_msg.body.featured_product');
                $body = GlobalHelper::mergeFields($msg, $emp_info);
                $body2 = GlobalHelper::mergeFields($msg2, $emp_info);
                $module = 'product_create';
                $module2 = 'featured_product_added';
                $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                $page = 'product_create';
                $page2 = 'featured_product_added';
                $portal = 'userview';
                $data = [
                    'product_id' => $productdata->product_id,
                    'product_name' => $productdata->product_name,
                    'category_id' => $productdata->category_id,
                    'category_name' => $productdata->category_name,
                    'page' => 'product_create',
                    'random_id' => $random_id
                ];
                $data2 = [
                    'product_id' => $productdata->product_id,
                    'product_name' => $productdata->product_name,
                    'category_id' => $productdata->category_id,
                    'category_name' => $productdata->category_name,
                    'page' => 'featured_product_added',
                    'random_id' => $random_id2
                ];
                $token = Customer::where('token', '!=', NULL)->orwhere('mbl_token', '!=', NULL)->select('token', 'mbl_token')->get();
                if (!empty($token)) {
                    $tokens = [];
                    foreach ($token as $tk) {
                        $tokens[] = $tk['token'];
                    }

                    $mbl_tokens = [];
                    foreach ($token as $tks) {
                        $mbl_tokens[] = $tks['mbl_token'];
                    }
                }
                if (!empty($tokens)) {
                    foreach (array_chunk($tokens, 500) as $tok) {
                        for ($i = 0; $i < count($tok); $i++) {
                            $key = ($tok[$i]) ? $tok[$i] : " ";
                            // $key = ($tok->token) ? $tok->token : " ";
                            // $key_mbl = ($tok->mbl_token) ? $tok->mbl_token : " ";
                            if (!empty($key)) {
                                $message = [
                                    'title' => $title,
                                    'body' => $body,
                                    'page' => $page,
                                    'data' => $data,
                                    'portal' => $portal,
                                    'module' => $module
                                ];
                                $message2 = [
                                    'title' => $title2,
                                    'body' => $body2,
                                    'page' => $page2,
                                    'data' => $data2,
                                    'portal' => $portal,
                                    'module' => $module2
                                ];
                                if ($productdetails->is_notification == 1) {
                                    $push = Firebase::sendMultiple($key, $message);
                                }
                                if ($productdetails->is_featured_product == 1) {
                                    $push = Firebase::sendMultiple($key, $message2);
                                }
                            }
                        }
                    }
                }

                //mobile app push
                if (!empty($mbl_tokens)) {
                    foreach (array_chunk($mbl_tokens, 500) as $mbl_tok) {
                        for ($i = 0; $i < count($mbl_tok); $i++) {
                            $key_mbl = ($mbl_tok[$i]) ? $mbl_tok[$i] : " ";
                            // $key = ($tok->token) ? $tok->token : " ";
                            // $key_mbl = ($tok->mbl_token) ? $tok->mbl_token : " ";
                            if (!empty($key_mbl)) {
                                $message = [
                                    'title' => $title,
                                    'body' => $body,
                                    'page' => $page,
                                    'data' => $data,
                                    'portal' => $portal,
                                    'module' => $module
                                ];
                                $message2 = [
                                    'title' => $title2,
                                    'body' => $body2,
                                    'page' => $page2,
                                    'data' => $data2,
                                    'portal' => $portal,
                                    'module' => $module2
                                ];
                                if ($productdetails->is_notification == 1) {
                                    $push2 = Firebase::sendMultipleMbl($key_mbl, $message);
                                }
                                if ($productdetails->is_featured_product == 1) {
                                    $push2 = Firebase::sendMultipleMbl($key_mbl, $message2);
                                }
                            }
                        }
                    }
                }

                if ($productdetails->publish == 1) {
                    if ($productdetails->is_notification == 1) {
                        $getdata = GlobalHelper::notification_create($title, $body, 1, 1, 2, $module, $page, "userview", $data, $random_id);
                    }
                    if ($productdetails->is_notification == 1) {
                        if ($productdetails->is_featured_product == 1) {
                            $getdata = GlobalHelper::notification_create($title2, $body2, 1, 1, 2, $module2, $page2, "userview", $data2, $random_id2);
                        }
                    }
                }

                // // log activity
                // $desc = $productdetails->name . ' Employee' . ' created by ' . JwtHelper::getSesUserNameWithType() . '';
                // $activitytype = Config('activitytype.employee');
                // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                return response()->json([
                    'keyword' => 'success',
                    'data'   => $productdetails_data,
                    'message' => __('Product information created successfully')
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => __('Product information create failed')
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'data'        => [],
                'message'      => __('Product name already exists')
            ]);
        }
    }

    public function product_update(Request $request)
    {
        $ids = $request->input('product_id');

        $name = Product::where([['product_name', $request->input('product_name')], ['status', '!=', 2], ['product_id', '!=', $ids]])->first();
        if (empty($name)) {

            $productdetails = Product::find($ids);
            $productdetails->product_name = $request->input('product_name');
            $productdetails->brand_id = $request->input('brand_id');
            $productdetails->category_id = $request->input('category_id');
            $productdetails->subcategory_id = $request->input('subcategory_id');
            $productdetails->product_weight = $request->input('product_weight');
            $productdetails->product_description = $request->input('product_description');
            $productdetails->product_specification = $request->input('product_specification');
            $productdetails->other_specification =  $request->input('other_specification');
            $productdetails->product_image = $request->input('product_image');
            $productdetails->selling_price = $request->input('selling_price');
            $productdetails->mrp = $request->input('mrp');
            $productdetails->quantity = $request->input('quantity');
            $productdetails->dealer_commision_amount = $request->input('dealer_commision_amount');
            $productdetails->cod_id = $request->input('cod_id');
            $productdetails->is_cod = $request->input('is_cod');
            $productdetails->is_featured_product = $request->input('is_featured_product');
            $productdetails->is_notification = $request->input('is_notification');
            $productdetails->is_trending_item = $request->input('is_trending_item');
            $productdetails->is_hot_item = $request->input('is_hot_item');
            $productdetails->updated_on = Server::getDateTime();
            $productdetails->updated_by = JwtHelper::getSesUserId();

            // $productdata = Product::leftjoin('acl_user', 'acl_user.acl_user_id', '=', 'product.created_by')->leftjoin('category', 'category.category_id', '=', 'product.category_id')->select('acl_user.acl_user_id', 'acl_user.name', 'product.*', 'category.category_name')->where('product.product_id', $ids)->first();
            // $pro = GlobalHelper::getProduct($productdata->product_id);    
            if ($productdetails->save()) {
                $product_code = 'PRO_' . str_pad($productdetails->product_id, 3, '0', STR_PAD_LEFT);
                $update_productdetails = Product::find($productdetails->product_id);
                $update_productdetails->product_code = $product_code;
                $update_productdetails->save();
                $productdetails_data = Product::where('product_id', $ids)
                    ->leftjoin('brand', 'brand.brand_id', '=', 'product.brand_id')
                    ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
                    ->leftjoin('category as sub', 'sub.category_id', '=', 'product.subcategory_id')
                    ->select('product.*', 'brand.brand_name', 'category.category_name', 'sub.category_name as subcategory_name')
                    ->first();

                    $productdata = Product::leftjoin('acl_user', 'acl_user.acl_user_id', '=', 'product.created_by')->leftjoin('category', 'category.category_id', '=', 'product.category_id')->select('acl_user.acl_user_id', 'acl_user.name', 'product.*', 'category.category_name')->where('product.product_id', $ids)->first();

            $emp_info = [
                'proname' => $productdetails->product_name,
                'acl_name' => $productdata->name,
                'quantity' => $productdetails->quantity
            ];
            // $key = $enq->firebase_token;
            // $key = $fcm_branch->firebase_token;
            // printf(json_encode($productdata->is_notification));exit;            
            $title = Config('fcm_msg.title.new_offer');
            $title2 = Config('fcm_msg.title.today_sales_offer');
            $title3 = Config('fcm_msg.title.out_of_stock');
            $title4 = Config('fcm_msg.title.product_available');
            $title5 = Config('fcm_msg.title.featured_product_updated');
            $msg = Config('fcm_msg.body.product_update_new_offer');
            $msg2 = Config('fcm_msg.body.product_update_new_sales_offer');
            $msg3 = Config('fcm_msg.body.product_update_ou_of_stock');
            $msg4 = Config('fcm_msg.body.notify_me');
            $msg5 = Config('fcm_msg.body.featured_product_update');
            $body = GlobalHelper::mergeFields($msg, $emp_info);
            $body2 = GlobalHelper::mergeFields($msg2, $emp_info);
            $body3 = GlobalHelper::mergeFields($msg3, $emp_info);
            $body4 = GlobalHelper::mergeFields($msg4, $emp_info);
            $body5 = GlobalHelper::mergeFields($msg5, $emp_info);
            $module = 'mrp_offer';
            $module2 = 'selling_offer';
            $module3 = 'out_of_stock';
            $module4 = 'product_available';
            $module5 = 'featured_product_updated';
            $portal = 'userview';
            $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
            $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
            $random_id3 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
            $random_id4 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
            $random_id5 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
            $data = [
                'product_id' => $productdata->product_id,
                'product_name' => $productdata->product_name,
                'category_id' => $productdata->category_id,
                'category_name' => $productdata->category_name,
                'page' => 'mrp_offer',
                'random_id' => $random_id
            ];
            $data2 = [
                'product_id' => $productdata->product_id,
                'product_name' => $productdata->product_name,
                'category_id' => $productdata->category_id,
                'category_name' => $productdata->category_name,
                'page' => 'selling_offer',
                'random_id' => $random_id2
            ];
            $data3 = [
                'product_id' => $productdata->product_id,
                'product_name' => $productdata->product_name,
                'category_id' => $productdata->category_id,
                'category_name' => $productdata->category_name,
                'page' => 'out_of_stock',
                'random_id' => $random_id3
            ];
            $data4 = [
                'product_id' => $productdata->product_id,
                'product_name' => $productdata->product_name,
                'category_id' => $productdata->category_id,
                'category_name' => $productdata->category_name,
                'page' => 'product_available',
                'random_id' => $random_id4
            ];
            $data5 = [
                'product_id' => $productdata->product_id,
                'product_name' => $productdata->product_name,
                'category_id' => $productdata->category_id,
                'category_name' => $productdata->category_name,
                'page' => 'featured_product_updated',
                'random_id' => $random_id5
            ];
            $page = 'mrp_offer';
            $page2 = 'selling_offer';
            $page3 = 'out_of_stock';
            $page4 = 'product_available';
            $page5 = 'featured_product_updated';
            $token = Customer::where('token', '!=', NULL)->orwhere('mbl_token', '!=', NULL)->select('token', 'mbl_token')->get();
            if (!empty($token)) {
                $tokens = [];
                foreach ($token as $tk) {
                    $tokens[] = $tk['token'];
                }

                $mbl_tokens = [];
                foreach ($token as $tks) {
                    $mbl_tokens[] = $tks['mbl_token'];
                }
            }
            if (!empty($tokens)) {
                foreach (array_chunk($tokens, 500) as $tok) {
                    for ($i = 0; $i < count($tok); $i++) {
                        $key = ($tok[$i]) ? $tok[$i] : " ";
                        // $key = ($tok->token) ? $tok->token : " ";
                        // $key_mbl = ($tok->mbl_token) ? $tok->mbl_token : " ";
                        if (!empty($key)) {

                            $message = [
                                'title' => $title,
                                'body' => $body,
                                'page' => $page,
                                'data' => $data,
                                'portal' => $portal,
                                'module' => $module
                            ];
                            $message2 = [
                                'title' => $title2,
                                'body' => $body2,
                                'page' => $page2,
                                'data' => $data2,
                                'portal' => $portal,
                                'module' => $module2
                            ];
                            $message3 = [
                                'title' => $title3,
                                'body' => $body3,
                                'page' => $page3,
                                'data' => $data3,
                                'portal' => $portal,
                                'module' => $module3
                            ];
                            $message4 = [
                                'title' => $title4,
                                'body' => $body4,
                                'page' => $page4,
                                'data' => $data4,
                                'portal' => $portal,
                                'module' => $module4
                            ];
                            $message5 = [
                                'title' => $title5,
                                'body' => $body5,
                                'page' => $page5,
                                'data' => $data5,
                                'portal' => $portal,
                                'module' => $module5
                            ];
                            if ($productdata->publish == 1) {
                                if ($productdetails->is_notification == 1) {
                                    if ($productdata->mrp > $productdetails->mrp) {
                                        $push = Firebase::sendMultiple($key, $message);
                                    }
                                    if ($productdata->selling_price > $productdetails->selling_price) {
                                        $push = Firebase::sendMultiple($key, $message2);
                                    }
                                    if ($productdetails->quantity == 0) {
                                        $push = Firebase::sendMultiple($key, $message3);
                                    }
                                    if ($productdetails->is_featured_product == 1) {
                                        $push = Firebase::sendMultiple($key, $message5);
                                    }
                                    if ($productdetails->quantity > 0) {
                                        $push = Firebase::sendMultiple($key, $message4);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //mobile push notification
            if (!empty($mbl_tokens)) {
                foreach (array_chunk($mbl_tokens, 500) as $mbl_tok) {
                    for ($i = 0; $i < count($mbl_tok); $i++) {
                        $key_mbl = ($mbl_tok[$i]) ? $mbl_tok[$i] : " ";
                        // $key = ($tok->token) ? $tok->token : " ";
                        // $key_mbl = ($tok->mbl_token) ? $tok->mbl_token : " ";
                        if (!empty($key_mbl)) {

                            $message = [
                                'title' => $title,
                                'body' => $body,
                                'page' => $page,
                                'data' => $data,
                                'portal' => $portal,
                                'module' => $module
                            ];
                            $message2 = [
                                'title' => $title2,
                                'body' => $body2,
                                'page' => $page2,
                                'data' => $data2,
                                'portal' => $portal,
                                'module' => $module2
                            ];
                            $message3 = [
                                'title' => $title3,
                                'body' => $body3,
                                'page' => $page3,
                                'data' => $data3,
                                'portal' => $portal,
                                'module' => $module3
                            ];
                            $message4 = [
                                'title' => $title4,
                                'body' => $body4,
                                'page' => $page4,
                                'data' => $data4,
                                'portal' => $portal,
                                'module' => $module4
                            ];
                            $message5 = [
                                'title' => $title5,
                                'body' => $body5,
                                'page' => $page5,
                                'data' => $data5,
                                'portal' => $portal,
                                'module' => $module5
                            ];
                            if ($productdata->publish == 1) {
                                if ($productdetails->is_notification == 1) {
                                    if ($productdata->mrp > $productdetails->mrp) {
                                        $push = Firebase::sendMultipleMbl($key_mbl, $message);
                                    }
                                    if ($productdata->selling_price > $productdetails->selling_price) {
                                        $push2 = Firebase::sendMultipleMbl($key_mbl, $message2);
                                    }
                                    if ($productdetails->quantity == 0) {
                                        $push2 = Firebase::sendMultipleMbl($key_mbl, $message3);
                                    }
                                    if ($productdetails->is_featured_product == 1) {
                                        $push2 = Firebase::sendMultipleMbl($key_mbl, $message5);
                                    }
                                    if ($productdetails->quantity > 0) {
                                        $push2 = Firebase::sendMultipleMbl($key_mbl, $message4);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($productdata->publish == 1) {
                if ($productdetails->is_notification == 1) {
                    if ($productdata->mrp > $productdetails->mrp) {
                        $getdata = GlobalHelper::notification_create($title, $body, 1, 1, 2, $module, $page, "userview", $data, $random_id);
                    }
                    if ($productdata->selling_price > $productdetails->selling_price) {
                        $getdata = GlobalHelper::notification_create($title2, $body2, 1, 1, 2, $module2, $page2, "userview", $data2, $random_id2);
                    }
                    if ($productdetails->quantity == 0) {
                        $getdata = GlobalHelper::notification_create($title3, $body3, 1, 1, 2, $module3, $page3, "userview", $data3, $random_id3);
                    }
                    if ($productdetails->is_featured_product == 1) {
                        $getdata = GlobalHelper::notification_create($title5, $body5, 1, 1, 2, $module5, $page5, "userview", $data5, $random_id5);
                    }
                    if ($productdetails->quantity > 0) {
                        $getdata = GlobalHelper::notification_create($title4, $body4, 1, 1, 2, $module4, $page4, "userview", $data4, $random_id4);
                    }
                }
            }
            if ($productdetails->quantity > 0) {
                $cusdetails = Notify::where('product_id', $ids)->where('email','!=','null')->select('notifyme.*')->get();
                // $cusdetails = $cus->get();
                $mails = [];
                foreach ($cusdetails as $cusdetail) {
                    if(!empty($cusdetail)){
                    $mails[] = $cusdetail['email'];
                    }
                }
                $prod = array_chunk($mails, 500);
                if (!empty($prod)) {
                    for ($i = 0; $i < count($prod); $i++) {
                        $sizeOfArrayChunk = sizeof($prod[$i]);
                        for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                            $mail_data = [];
                            $mail_data['email'] = $prod[$i][$j];
                            $mail_data['name'] = $productdetails->product_name;
                            $mail_data['quantity'] = $productdetails->quantity;
                            if ($prod[$i] != '') {
                                event(new SendAvailable($mail_data));
                            }
                        }
                    }
                }
            }
            $notify_delete = Notify::where('product_id', $ids)->select('notifyme.*');
            $notifydetails = $notify_delete->get();
            $notify_deleteId = [];
            foreach ($notifydetails as $notifydetail) {
                $notify_deleteId[] = $notifydetail['product_id'];
            }
            $update = Notify::whereIn('product_id', $notify_deleteId)->delete();


            // if ($productdetails->save()) {
            //     $product_code = 'PRO_' . str_pad($productdetails->product_id, 3, '0', STR_PAD_LEFT);
            //     $update_productdetails = Product::find($productdetails->product_id);
            //     $update_productdetails->product_code = $product_code;
            //     $update_productdetails->save();
            //     $productdetails_data = Product::where('product_id', $ids)
            //         ->leftjoin('brand', 'brand.brand_id', '=', 'product.brand_id')
            //         ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
            //         ->leftjoin('category as sub', 'sub.category_id', '=', 'product.subcategory_id')
            //         ->select('product.*', 'brand.brand_name', 'category.category_name', 'sub.category_name as subcategory_name')
            //         ->first();

                // log activity
                // $desc = $productdetails->name . ' Employee' . ' updated by ' . JwtHelper::getSesUserNameWithType() . '';
                // $activitytype = Config('activitytype.employee');
                // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Product informations updated successfully'),
                    'data' => $productdetails_data
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('Product informations update failed'),
                    'data' => []
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'data'        => [],
                'message'      => __('Product name already exists')
            ]);
        }
    }

    public function product_list(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByBrand = ($request->filterByBrand) ? $request->filterByBrand : '[]';
        $filterByCategory = ($request->filterByCategory) ? $request->filterByCategory : '[]';
        $filterByAvailable = ($request->filterByAvailable) ? $request->filterByAvailable : '[]';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '[]';
        $order_by_key = [
            // 'mention the api side column' => 'mention the mysql side column'
            'product_code' => 'product.product_code',
            'product_name' => 'product.product_name',
            'brand' => 'brand.brand_name',
            'mrp' => 'product.mrp',
            'selling_price' => 'product.selling_price',
            'product_weight' => 'product.product_weight',
            'created_on' => 'product.created_on',
            'is_featured_product' => 'product.is_featured_product'

        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "product_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('product.product_code', 'product.product_name', 'brand.brand_name', 'product.mrp', 'product.selling_price', 'product.product_weight', 'product.created_on', 'product.is_featured_product');

        $uniqueCategory = Product::select('product.category_id as category', 'category.category_name')->groupBy('category.category_id')->orderBy('category.category_id', 'desc')->leftjoin('category', 'category.category_id', '=', 'product.category_id')->get();

        $get_product = Product::where([
            ['product.status', "!=", 2]
        ])->leftjoin('brand', 'brand.brand_id', '=', 'product.brand_id')
            ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
            ->leftjoin('cod', 'cod.cod_id', '=', 'product.cod_id')
            ->leftjoin('category as sub', 'sub.category_id', '=', 'product.subcategory_id')
            ->select('product.*', 'brand.brand_name', 'category.category_name', 'sub.category_name as subcategory_name', 'cod.cod_id', 'cod.cod_percentage');


        $count = $get_product->count();
        $get_product->where(function ($query) use ($searchval, $column_search, $get_product) {
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
            $get_product->orderBy($order_by_key[$sortByKey], $sortType);
        }

        if (!empty($from_date)) {
            $get_product->where(function ($query) use ($from_date) {
                $query->whereDate('product.created_on', '>=', $from_date);
            });
        }

        if (!empty($to_date)) {
            $get_product->where(function ($query) use ($to_date) {
                $query->whereDate('product.created_on', '<=', $to_date);
            });
        }

        if ($filterByBrand != '[]') {
            $filterByBrand = json_decode($filterByBrand, true);
            $get_product->whereIn('product.brand_id', $filterByBrand);
        }

        if ($filterByCategory != '[]') {
            $filterByCategory = json_decode($filterByCategory, true);
            $get_product->whereIn('product.category_id', $filterByCategory);
        }

        if ($filterByAvailable != '[]') {
            $filterByAvailable = json_decode($filterByAvailable, true);
            if ($filterByAvailable == [1]) {
                $get_product->where('product.quantity', '>', 0);
            }
            if ($filterByAvailable == [0]) {
                $get_product->where('product.quantity', '<=', 0);
            }
        }

        if ($filterByStatus != '[]') {
            $filterByStatus = json_decode($filterByStatus, true);
            $get_product->whereIn('product.publish', $filterByStatus);
        }
        $clone_get_product = clone $get_product;
        $clone_get_product = $clone_get_product->get();
        if ($offset) {
            $offset = $limit * $offset;
            $get_product->offset($offset);
        }
        if ($limit) {
            $get_product->limit($limit);
        }


        $get_product->orderBy('product.product_id', 'desc');

        /*$productCollection = $get_product->get()->map(function ($value) {

            $value['quantity_stock']  = ($value->quantity > 0) ? "In stock" : "Out of stock";
            return $value;
        });

        $result = $uniqueCategory->map(function ($c) use ($productCollection) {
            $c['list'] = $this->getByProduct($productCollection, $c['category']);
            $c['listCount'] = count($this->getByProduct($productCollection, $c['category']));

            return $c;
        });*/
        $get_product = $get_product->get();
        if ($count > 0) {
            $final = [];
            foreach ($get_product as $value) {
                $ary = [];
                $ary['product_id'] = $value['product_id'];
                $ary['product_code'] = $value['product_code'];
                $ary['product_name'] = $value['product_name'];
                $ary['brand_id'] = $value['brand_id'];
                $ary['category_id'] = $value['category_id'];
                $ary['subcategory_id'] = $value['subcategory_id'];
                $ary['product_weight'] = $value['product_weight'];
                $ary['product_description'] = $value['product_description'];
                $ary['product_specification'] = $value['product_specification'];
                $ary['other_specification'] = $value['other_specification'];
                $gTImage = json_decode($value['product_image'], true);
                $ary['product_image'] = $this->getdefaultImages_allImages($gTImage);
                $ary['default_product_image']  = $this->getdefaultImages($gTImage);
                $ary['thumbnail_image'] = $value['thumbnail_image'];
                $ary['selling_price'] = $value['selling_price'];
                $ary['mrp'] = $value['mrp'];
                $ary['quantity'] = $value['quantity'];
                $ary['dealer_commision_amount'] = $value['dealer_commision_amount'];
                $ary['cod_id'] = $value['cod_id'];
                $ary['is_cod'] = $value['is_cod'];
                $ary['is_featured_product'] = $value['is_featured_product'];
                $ary['is_notification'] = $value['is_notification'];
                $ary['is_trending_item'] = $value['is_trending_item'];
                $ary['is_hot_item'] = $value['is_hot_item'];
                $ary['product_available'] = $value['product_available'];
                $ary['publish'] = $value['publish'];
                $ary['brand_name'] = $value['brand_name'];
                $ary['category_name'] = $value['category_name'];
                $ary['subcategory_name'] = $value['subcategory_name'];
                $ary['cod_percentage'] = $value['cod_percentage'];
                $ary['created_on'] = $value['created_on'];
                $ary['created_by'] = $value['created_by'];
                $ary['updated_on'] = $value['updated_on'];
                $ary['updated_by'] = $value['updated_by'];
                $ary['status'] = $value['status'];
                $ary['quantity_stock'] = ($value->quantity > 0) ? "In stock" : "Out of stock";
                $final[] = $ary;
            }
        }
        if (!empty($final)) {
            $result = $uniqueCategory->map(function ($c) use ($final) {
                $c['list'] = $this->getByProduct($final, $c['category']);
                $c['listCount'] = count($this->getByProduct($final, $c['category']));
                return $c;
            });
        }


        if ($count > 0) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Product list'),
                'data' => $result,
                'count' => count($clone_get_product)
            ]);
        } else {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Product list'),
                'data' => [],
                'count' => count($clone_get_product)
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
                $ary['image_name'] = $im['image'];
                $imG[] = $ary;
            }
        }
        return $imG;
    }

    public function product_list_pdf(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByBrand = ($request->filterByBrand) ? $request->filterByBrand : '[]';
        $filterByCategory = ($request->filterByCategory) ? $request->filterByCategory : '[]';
        $filterByAvailable = ($request->filterByAvailable) ? $request->filterByAvailable : '[]';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '[]';
        $order_by_key = [
            // 'mention the api side column' => 'mention the mysql side column'
            'product_code' => 'product.product_code',
            'product_name' => 'product.product_name',
            'brand' => 'brand.brand_name',
            'mrp' => 'product.mrp',
            'selling_price' => 'product.selling_price',
            'product_weight' => 'product.product_weight'

        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "product_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('product.product_code', 'product.product_name', 'brand.brand_name', 'product.mrp', 'product.selling_price', 'product.product_weight');

        $uniqueCategory = Product::select('product.category_id as category', 'category.category_name')->groupBy('category.category_id')->orderBy('category.category_id', 'desc')->leftjoin('category', 'category.category_id', '=', 'product.category_id')->get();

        $get_product = Product::where([
            ['product.status', "!=", 2]
        ])->leftjoin('brand', 'brand.brand_id', '=', 'product.brand_id')
            ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
            ->leftjoin('category as sub', 'sub.category_id', '=', 'product.subcategory_id')
            ->select('product.*', 'brand.brand_name', 'category.category_name', 'sub.category_name as subcategory_name', DB::raw('DATE_FORMAT(product.created_on, "%d-%m-%Y") as product_date'));

        $count = $get_product->count();
        $get_product->where(function ($query) use ($searchval, $column_search, $get_product) {
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
            $get_product->orderBy($order_by_key[$sortByKey], $sortType);
        }

        if (!empty($from_date)) {
            $get_product->where(function ($query) use ($from_date) {
                $query->whereDate('product.created_on', '>=', $from_date);
            });
        }

        if (!empty($to_date)) {
            $get_product->where(function ($query) use ($to_date) {
                $query->whereDate('product.created_on', '<=', $to_date);
            });
        }

        if ($filterByBrand != '[]') {
            $filterByBrand = json_decode($filterByBrand, true);
            $get_product->whereIn('product.brand_id', $filterByBrand);
        }

        if ($filterByCategory != '[]') {
            $filterByCategory = json_decode($filterByCategory, true);
            $get_product->whereIn('product.category_id', $filterByCategory);
        }

        if ($filterByAvailable != '[]') {
            $filterByAvailable = json_decode($filterByAvailable, true);
            if ($filterByAvailable == [1]) {
                $get_product->where('product.quantity', '>', 0);
            }
            if ($filterByAvailable == [0]) {
                $get_product->where('product.quantity', '<=', 0);
            }
        }

        if ($filterByStatus != '[]') {
            $filterByStatus = json_decode($filterByStatus, true);
            $get_product->whereIn('product.publish', $filterByStatus);
        }
        $clone_get_product = clone $get_product;
        $clone_get_product = $clone_get_product->get();
        if ($offset) {
            $offset = $limit * $offset;
            $get_product->offset($offset);
        }
        if ($limit) {
            $get_product->limit($limit);
        }


        $get_product->orderBy('product.product_id', 'desc');

        $productCollection = $get_product->get()->map(function ($value) {

            $value['quantity_stock']  = ($value->quantity > 0) ? "In stock" : "Out of stock";
            return $value;
        });

        $result = $uniqueCategory->map(function ($c) use ($productCollection) {
            $c['list'] = $this->getByProduct($productCollection, $c['category']);
            $c['listCount'] = count($this->getByProduct($productCollection, $c['category']));

            return $c;
        });

        if (!empty($result)) {

            $path = public_path() . "/product_list";
            File::makeDirectory($path, env('PERMISSION_MODE_REPORT'), true, true);

            $fileName = "product_list" . time() . '.pdf';
            $location = public_path() . '/product_list/' . $fileName;
            $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
            $mpdf->WriteHTML(\View::make('report/productList', $result)->with('result', $result)->with('no', 1)->render());
            $mpdf->Output($location, 'F');

            return response()->download($location, "product_list.pdf");
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('message.no_data'),
                    'data' => []
                ]
            );
        }
    }

    public function getByProduct($activityList, $category)
    {

        $rs = [];
        foreach ($activityList as $act) {

            if ($act['category_id'] == $category) {;
                $rs[] = $act;
            }
        }
        return  $rs;
    }

    public function product_view(Request $request, $id)
    {
        $product_view = Product::where('product_id', $id)->leftjoin('cod', 'cod.cod_id', '=', 'product.cod_id')->leftjoin('brand', 'brand.brand_id', '=', 'product.brand_id')
            ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
            ->leftjoin('category as sub', 'sub.category_id', '=', 'product.subcategory_id')
            ->select('product.*', 'brand.brand_name', 'category.category_name', 'sub.category_name as subcategory_name', 'cod.cod_id', 'cod.cod_percentage')->first();
        $final = [];
        if (!empty($product_view)) {
            $ary = [];
            $ary['product_id'] = $product_view['product_id'];
            $ary['product_code'] = $product_view['product_code'];
            $ary['product_name'] = $product_view['product_name'];
            $ary['brand_id'] = $product_view['brand_id'];
            $ary['category_id'] = $product_view['category_id'];
            $ary['subcategory_id'] = $product_view['subcategory_id'];
            $ary['product_weight'] = $product_view['product_weight'];
            $ary['product_description'] = $product_view['product_description'];
            $ary['product_specification'] = $product_view['product_specification'];
            $ary['other_specification'] = $product_view['other_specification'];
            $gTImage = json_decode($product_view['product_image'], true);
            $ary['product_image'] = $this->getdefaultImages_allImages($gTImage);
            $ary['default_product_image']  = $this->getdefaultImages($gTImage);
            $ary['thumbnail_image'] = $product_view['thumbnail_image'];
            $ary['selling_price'] = $product_view['selling_price'];
            $ary['mrp'] = $product_view['mrp'];
            $ary['quantity'] = $product_view['quantity'];
            $ary['dealer_commision_amount'] = $product_view['dealer_commision_amount'];
            $ary['cod_id'] = $product_view['cod_id'];
            $ary['is_cod'] = $product_view['is_cod'];
            $ary['is_featured_product'] = $product_view['is_featured_product'];
            $ary['is_notification'] = $product_view['is_notification'];
            $ary['is_trending_item'] = $product_view['is_trending_item'];
            $ary['is_hot_item'] = $product_view['is_hot_item'];
            $ary['product_available'] = $product_view['product_available'];
            $ary['publish'] = $product_view['publish'];
            $ary['brand_name'] = $product_view['brand_name'];
            $ary['category_name'] = $product_view['category_name'];
            $ary['subcategory_name'] = $product_view['subcategory_name'];
            $ary['cod_percentage'] = $product_view['cod_percentage'];
            $ary['created_on'] = $product_view['created_on'];
            $ary['created_by'] = $product_view['created_by'];
            $ary['updated_on'] = $product_view['updated_on'];
            $ary['updated_by'] = $product_view['updated_by'];
            $ary['status'] = $product_view['status'];
            $ary['quantity_stock'] = ($product_view->quantity > 0) ? "In stock" : "Out of stock";
            $final[] = $ary;
        }
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Product viewed'),
                'data' => $final
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No Data'),
                'data' => []
            ]);
        }
    }

    public function product_status(Request $request)
    {

        if (!empty($request)) {

            $ids = $request->product_id;
            $ids = json_decode($ids, true);

            if (!empty($ids)) {
                $productdetails = Product::where('product_id', $ids)->first();

                $update = Product::whereIn('product_id', $ids)->update(array(
                    'status' => $request->status,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));

                // log activity
                // $activity_status = ($request->status) ? 'activated' : 'deactivated';
                // $implode = implode(",", $ids);
                // $desc = $productdetails->name . ' Employee ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                // $activitytype = Config('activitytype.employee');
                // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                if ($request->status == 0) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product information inactivated successfully'),
                        'data' => []
                    ]);
                } else if ($request->status == 1) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  __('Product information activated successfully'),
                        'data' => []
                    ]);
                }
            } else {
                return response()
                    ->json([
                        'keyword' => 'failed',
                        'message' => __('failed'),
                        'data' => []
                    ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('failed'), 'data' => []
            ]);
        }
    }

    public function productPublish_status(Request $request)
    {

        if (!empty($request)) {

            $ids = $request->product_id;
            $ids = json_decode($ids, true);

            if (!empty($ids)) {
                $productdetails = Product::where('product_id', $ids)->first();

                $update = Product::whereIn('product_id', $ids)->update(array(
                    'publish' => $request->publish,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));

                if ($request->publish == 1) {
                    $productdata = Product::leftjoin('acl_user', 'acl_user.acl_user_id', '=', 'product.created_by')->leftjoin('category', 'category.category_id', '=', 'product.category_id')->select('acl_user.acl_user_id', 'acl_user.name', 'product.product_id', 'product.product_name', 'product.category_id', 'category.category_name')->where('product.product_id', $productdetails->product_id)->first();
                    $token = Customer::where('token', '!=', NULL)->orwhere('mbl_token', '!=', NULL)->select('token', 'mbl_token')->get();
                    $emp_info = [
                        'proname' => $productdata->product_name,
                        'acl_name' => $productdata->name
                    ];
                    $title = Config('fcm_msg.title.new_product_create');
                    $title2 = Config('fcm_msg.title.featured_product');
                    $msg = Config('fcm_msg.body.product_create');
                    $msg2 = Config('fcm_msg.body.featured_product');
                    $body = GlobalHelper::mergeFields($msg, $emp_info);
                    $body2 = GlobalHelper::mergeFields($msg2, $emp_info);
                    $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                    $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                    if (!empty($token)) {
                        foreach ($token as $tok) {
                            $key = ($tok->token) ? $tok->token : " ";
                            $key_mbl = ($tok->mbl_token) ? $tok->mbl_token : " ";
                            if (!empty($key) || !empty($key_mbl)) {
                                $module = 'product_create';
                                $module2 = 'featured_product_added';
                                $data = [
                                    'product_id' => $productdata->product_id,
                                    'product_name' => $productdata->product_name,
                                    'category_id' => $productdata->category_id,
                                    'category_name' => $productdata->category_name,
                                    'page' => 'product_create',
                                    'random_id' => $random_id
                                ];
                                $data2 = [
                                    'product_id' => $productdata->product_id,
                                    'product_name' => $productdata->product_name,
                                    'category_id' => $productdata->category_id,
                                    'category_name' => $productdata->category_name,
                                    'page' => 'featured_product_added',
                                    'random_id' => $random_id2
                                ];
                                $page = 'product_create';
                                $portal = 'admin';
                                $page2 = 'featured_product_added';
                                $message = [
                                    'title' => $title,
                                    'body' => $body,
                                    'page' => $page,
                                    'data' => $data,
                                    'portal' => $portal,
                                    'module' => $module
                                ];
                                $message2 = [
                                    'title' => $title2,
                                    'body' => $body2,
                                    'page' => $page2,
                                    'data' => $data2,
                                    'portal' => $portal,
                                    'module' => $module2
                                ];
                                if ($productdetails->is_notification == 1) {
                                    $push = Firebase::sendMultiple($key, $message);
                                    $push2 = Firebase::sendMultipleMbl($key_mbl, $message);
                                }
                                if ($productdetails->is_notification == 1) {
                                    if ($productdetails->is_featured_product == 1) {
                                        $push = Firebase::sendMultiple($key, $message2);
                                        $push2 = Firebase::sendMultipleMbl($key_mbl, $message2);
                                    }
                                }
                            }
                        }
                    }
                    if ($productdetails->is_notification == 1) {
                        $getdata = GlobalHelper::notification_create($title, $body, 1, 1, 2, $module, $page, "userview", $data, $random_id);
                    }
                    if ($productdetails->is_notification == 1) {
                        if ($productdetails->is_featured_product == 1) {
                            $getdata = GlobalHelper::notification_create($title2, $body2, 1, 1, 2, $module2, $page2, "userview", $data2, $random_id2);
                        }
                    }
                }

                // log activity
                // $activity_status = ($request->publish) ? 'published' : 'unpublished';
                // $implode = implode(",", $ids);
                // $desc = $productdetails->name . ' Product ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                //   $activitytype = Config('activitytype.employee');
                //   GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1); 


                if ($request->publish == 0) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product information unpublished successfully'),
                        'data' => []
                    ]);
                } else if ($request->publish == 1) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  __('Product information published successfully'),
                        'data' => []
                    ]);
                }
            } else {
                return response()
                    ->json([
                        'keyword' => 'failed',
                        'message' => __('failed'),
                        'data' => []
                    ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('failed'), 'data' => []
            ]);
        }
    }

    public function product_delete(Request $request)
    {

        if (!empty($request)) {

            $ids = $request->product_id;
            $ids = json_decode($ids, true);


            if (!empty($ids)) {
                $productdetails = Product::where('product_id', $ids)->first();
                $update = Product::whereIn('product_id', $ids)->update(array(
                    'status' => 2,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));

                // log activity
                // $implode = implode(",", $ids);
                // $desc = $productdetails->name . ' Employee' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                // $activitytype = Config('activitytype.employee');
                // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                return response()->json([
                    'keyword' => 'success',
                    'message' =>  __('Product information deleted successfully'),
                    'data' => []
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.failed'),
                    'data' => []
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('message.failed'),
                'data' => []
            ]);
        }
    }
}
