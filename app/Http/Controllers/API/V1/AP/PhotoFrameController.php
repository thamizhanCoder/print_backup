<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Requests\PersonalizedRequest;
use App\Http\Requests\PhotoFrameRequest;
use App\Http\Traits\PhotoFrameTrait;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\RelatedProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class PhotoFrameController extends Controller
{

    use PhotoFrameTrait;

    public function countSummaryPhotoframe()
    {
        //Total Products
        $total_count = ProductCatalogue::where('service_id', 3)->whereIn('status', [0, 1])->get();
        $total_count = $total_count->count();
        //Published Products
        $published_count = ProductCatalogue::where('service_id', 3)->where('is_publish', 1)->whereIn('status', [0, 1])->get();
        $published_count = $published_count->count();
        //UnPublished products
        $unpublished_count = ProductCatalogue::where('service_id', 3)->where('is_publish', 2)->whereIn('status', [0, 1])->get();
        $unpublished_count = $unpublished_count->count();
        $count = ['total_count' => $total_count, 'published_count' => $published_count, 'unpublished_count' => $unpublished_count];
        return response()->json([
            'keyword' => 'success',
            'message' => __('Count showed successfully'),
            'data' => [$count],
        ]);
    }

    public function photoframe_create(PhotoFrameRequest $request)
    {
        try {

            $product_variant_message = "";
            $related_product_message = "";

            Log::channel("photoframe")->info('** started the photoframe create method **');
            $exist = ProductCatalogue::where([['product_name', $request->product_name], ['service_id', 3], ['status', '!=', 2]])->first();

            if (empty($exist)) {

                $photoframe = new ProductCatalogue();
                $photoframe->product_name = $request->product_name;
                $photoframe->service_id = 3;
                $photoframe->gst_percentage = $request->gst_percentage;
                $photoframe->help_url = $request->help_url;
                $photoframe->frame_details = $request->frame_details;

                //Thumbnail Image validation starts
                $thumnailValidation = $this->thumbnailValidation($request->thumbnail_image);
                if ($thumnailValidation == "true") {

                    $photoframe->thumbnail_image = $request->thumbnail_image;
                } else {

                    return response()->json([
                        'keyword' => 'failed',
                        'message' => $thumnailValidation,
                        'data' => [],
                    ]);
                }
                //Thumbnail Image validation end

                $photoframe->customer_description = $request->customer_description;
                $photoframe->designer_description = $request->designer_description;

                $photoframe->is_cod_available = $request->is_cod_available;
                $photoframe->is_related_product_available = $request->is_related_product_available;
                $photoframe->is_multivariant_available = $request->is_multivariant_available;
                $photoframe->is_notification = $request->is_notification;
                $photoframe->selected_variants = $request->selected_variants;

                //primary detail validation start

                if (!empty($request->primary_variant_details)) {

                    $primary_variant_details_insert = $this->validateSelectedVarients($request->primary_variant_details);

                    if ($primary_variant_details_insert == "true") {

                        $photoframe->primary_variant_details = $request->primary_variant_details;
                    } else {

                        return response()->json([
                            'keyword' => 'failed',
                            'message' => $primary_variant_details_insert,
                            'data' => [],
                        ]);
                    }
                }
                //primary detail validation end

                //Variant detail validation start

                // if ($photoframe->is_multivariant_available == 1) {

                $variant_type_validation = $this->varientAttributeValidate($request->variant_details);

                if ($variant_type_validation) {

                    return response()->json(['keyword' => 'failed', 'message' => $variant_type_validation, 'data' => []]);
                } else {

                    $variant_details_validation = $this->varientDetailsValidation($request->variant_details);

                    if ($variant_details_validation) {

                        return response()->json(['keyword' => 'failed', 'message' => $variant_details_validation, 'data' => []]);
                    } else {

                        $varientDetails = $request->variant_details;
                    }
                }
                // }

                //Variant detail validation end

                $photoframe->created_on = Server::getDateTime();
                $photoframe->created_by = JwtHelper::getSesUserId();

                $relatedProductJsonString = $request->related_products;

                Log::channel("photoframe")->info("request value :: $photoframe->photoframe");

                if ($photoframe->save()) {
                    $serviceDetailsCount = ProductCatalogue::where('service_id', $photoframe->service_id)->get();
                    $service_count = $serviceDetailsCount->count();
                    $product_code = env('PREFIX') . '_' . str_pad($photoframe->product_id, 3, '0', STR_PAD_LEFT) . '_' . str_pad($service_count, 3, '0', STR_PAD_LEFT);
                    $update_productdetails = ProductCatalogue::find($photoframe->product_id);
                    $update_productdetails->product_code = $product_code;
                    $update_productdetails->save();

                    // insert product variant
                    if (!empty($varientDetails)) {

                        $product_variant = $this->productVariantInsert($varientDetails, $photoframe->product_id);

                        if ($product_variant == true) {

                            $product_variant_message = "Product varinants Successfullly inserted in sub table";
                            Log::channel("photoframe")->info($product_variant_message);
                        } else {

                            $product_variant_message = "Product Varient Sub table records not inserted correctly";
                            Log::channel("photoframe")->info($product_variant_message);
                        }
                    }

                    $photoframe_details = ProductCatalogue::where('product_id', $photoframe->product_id)
                        ->select('product.*')
                        ->first();

                    // save related products
                    if ($photoframe->is_related_product_available == 1) {
                        $relatedArr = json_decode($relatedProductJsonString, true);
                        $saveRelatedProduct = $this->saverelatedProducts($relatedArr, $photoframe_details->product_id);

                        if ($saveRelatedProduct == true) {

                            $related_product_message = "Related products Successfullly inserted in sub table";
                            Log::channel("photoframe")->info($related_product_message);
                        } else {

                            $related_product_message = "PrRelated products Sub table records not inserted correctly";
                            Log::channel("photoframe")->info($related_product_message);
                        }
                    }
                    Log::channel("photoframe")->info("** photoframe save details : $photoframe_details **");

                    // log activity
                    $desc = 'Photo frame ' . $photoframe_details->product_name . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Photo Frame');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);



                    Log::channel("photoframe")->info("save value :: $photoframe_details");
                    Log::channel("photoframe")->info('** end the photoframe create method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product created successfully'),
                        'data' => [$photoframe_details],
                        'product_varient_message' => $product_variant_message,
                        'related_product_message' => $related_product_message,
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('Product creation failed'),
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('Product name already exist'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("photoframe")->error($exception);
            Log::channel("photoframe")->error('** end the photoframe create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function productCreatePushNotification($proId)
    {
        $productDetails = ProductCatalogue::where('product_id', $proId)->leftjoin('category', 'category.category_id', '=', 'product.category_id')->select('product.*', 'category.category_name')->first();


        // $emp_info = [
        //     'first_name' => $order->customer_first_name,
        //     'last_name' => $order->customer_last_name,
        //     'product_name' => $order->product_name
        // ];

        $title = "New Product Created"." - ".$productDetails->product_name;
        $body = "New product $productDetails->product_name is added by admin. Ready to Buy!";
        // $body = GlobalHelper::mergeFields($body, $emp_info);
        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'New Product Created';
        $portal = "website";
        $portal2 = "mobile";
        $page = 'new_product';
        // $titlemod = "Rating & Review !";
        $data = [
            'product_name' => $productDetails->product_name,
            'service_id' => $productDetails->service_id,
            'product_id' => $productDetails->product_id,
            'category_name' => $productDetails->category_name,
            'random_id' => $random_id,
            'page' => $page
        ];

        $data2 = [
            'product_name' => $productDetails->product_name,
            'service_id' => $productDetails->service_id,
            'product_id' => $productDetails->product_id,
            'category_name' => $productDetails->category_name,
            'random_id' => $random_id2,
            'page' => $page
        ];

        $token = Customer::where('token', '!=', NULL)->orwhere('mbl_token', '!=', NULL)->select('token', 'mbl_token', 'customer_id')->get();
        if (!empty($token)) {
            $tokens = [];
            foreach ($token as $tk) {
                $tokens[] = $tk['token'];
            }

            $mbl_tokens = [];
            foreach ($token as $tks) {
                $mbl_tokens[] = $tks['mbl_token'];
            }
            $customerId = [];
            foreach ($token as $tk) {
                $customerId[] = $tk['customer_id'];
            }
        }
        if (!empty($tokens)) {
            foreach (array_chunk($tokens, 500) as $tok) {
                $key = $tok;
                if (!empty($key)) {
                    $message = [
                        'title' => $title,
                        'body' => $body,
                        'page' => $page,
                        'data' => $data,
                        'portal' => $portal,
                        'module' => $module
                    ];
                    $push = Firebase::sendMultiple($key, $message);
                }
            }

            if (!empty($customerId)) {
                $prod = array_chunk($customerId, 500);
                if (!empty($prod)) {
                    for ($i = 0; $i < count($prod); $i++) {
                        $sizeOfArrayChunk = sizeof($prod[$i]);
                        for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                            $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $prod[$i][$j], $module, $page, "website", $data, $random_id);
                        }
                    }
                }
            }
        }

        if (!empty($mbl_tokens)) {
            foreach (array_chunk($mbl_tokens, 500) as $mbl_tok) {
                $key_mbl = $mbl_tok;
                if (!empty($key_mbl)) {
                    $message = [
                        'title' => $title,
                        'body' => $body,
                        'page' => $page,
                        'data' => $data2,
                        'portal' => $portal2,
                        'module' => $module
                    ];
                    $push2 = Firebase::sendMultipleMbl($key_mbl, $message);
                }
            }

            if (!empty($customerId)) {
                $prod = array_chunk($customerId, 500);
                if (!empty($prod)) {
                    for ($i = 0; $i < count($prod); $i++) {
                        $sizeOfArrayChunk = sizeof($prod[$i]);
                        for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                            $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $prod[$i][$j], $module, $page, "mobile", $data, $random_id2);
                        }
                    }
                }
            }
        }
    }

    public function photoframe_update(PhotoFrameRequest $request)
    {
        try {

            $product_variant_message = "";
            $related_product_message = "";

            Log::channel("photoframe")->info('** started the photoframe update method **');
            $exist = ProductCatalogue::where([['product_id', '!=', $request->product_id], ['product_name', $request->product_name], ['service_id', 3], ['status', '!=', 2]])->first();

            if (empty($exist)) {
                $id = $request->product_id;
                $photoframe = ProductCatalogue::find($id);
                $photoframe->product_name = $request->product_name;
                $photoframe->service_id = 3;
                $photoframe->gst_percentage = $request->gst_percentage;
                $photoframe->help_url = $request->help_url;
                $photoframe->frame_details = $request->frame_details;

                //Thumbnail Image validation starts
                $thumnailValidation = $this->thumbnailValidation($request->thumbnail_image);
                if ($thumnailValidation == "true") {

                    $photoframe->thumbnail_image = $request->thumbnail_image;
                } else {

                    return response()->json([
                        'keyword' => 'failed',
                        'message' => $thumnailValidation,
                        'data' => [],
                    ]);
                }
                //Thumbnail Image validation end

                $photoframe->customer_description = $request->customer_description;
                $photoframe->designer_description = $request->designer_description;

                $photoframe->is_cod_available = $request->is_cod_available;
                $photoframe->is_related_product_available = $request->is_related_product_available;
                $photoframe->is_multivariant_available = $request->is_multivariant_available;
                $photoframe->is_notification = $request->is_notification;
                $photoframe->selected_variants = $request->selected_variants;

                //primary detail validation start

                if (!empty($request->primary_variant_details)) {

                    $primary_variant_details_insert = $this->validateSelectedVarients($request->primary_variant_details);

                    if ($primary_variant_details_insert == "true") {

                        $photoframe->primary_variant_details = $request->primary_variant_details;
                    } else {

                        return response()->json([
                            'keyword' => 'failed',
                            'message' => $primary_variant_details_insert,
                            'data' => [],
                        ]);
                    }
                }
                //primary detail validation end

                //Variant detail validation start

                // if ($photoframe->is_multivariant_available == 1) {

                $variant_type_validation = $this->varientAttributeValidate($request->variant_details);

                if ($variant_type_validation) {

                    return response()->json(['keyword' => 'failed', 'message' => $variant_type_validation, 'data' => []]);
                } else {

                    $variant_details_validation = $this->varientDetailsValidation($request->variant_details);

                    if ($variant_details_validation) {

                        return response()->json(['keyword' => 'failed', 'message' => $variant_details_validation, 'data' => []]);
                    } else {

                        $varientDetails = $request->variant_details;
                    }
                }
                // }

                //Variant detail validation end

                $photoframe->updated_on = Server::getDateTime();
                $photoframe->updated_by = JwtHelper::getSesUserId();

                $relatedProductJsonString = $request->related_products;

                Log::channel("photoframe")->info("request value :: $photoframe->photoframe");

                if ($photoframe->save()) {

                    // insert product variant
                    if (!empty($varientDetails)) {

                        $product_variant = $this->productVariantUpdate($varientDetails, $photoframe->product_id);

                        if ($product_variant == true) {

                            $product_variant_message = "Product varinants Successfullly inserted in sub table";
                            Log::channel("photoframe")->info($product_variant_message);
                        } else {

                            $product_variant_message = "Product Varient Sub table records not inserted correctly";
                            Log::channel("photoframe")->info($product_variant_message);
                        }
                    }

                    $photoframe_details = ProductCatalogue::where('product_id', $photoframe->product_id)
                        ->select('product.*')
                        ->first();

                    // save related products
                    if ($photoframe->is_related_product_available == 1) {
                        $delete = RelatedProduct::where('product_id', $photoframe_details->product_id)->delete();
                        $relatedArr = json_decode($relatedProductJsonString, true);
                        $saveRelatedProduct = $this->saverelatedProducts($relatedArr, $photoframe_details->product_id);

                        if ($saveRelatedProduct == true) {

                            $related_product_message = "Related products Successfullly inserted in sub table";
                            Log::channel("photoframe")->info($related_product_message);
                        } else {

                            $related_product_message = "PrRelated products Sub table records not inserted correctly";
                            Log::channel("photoframe")->info($related_product_message);
                        }
                    }

                    //delete related products
                    if ($photoframe->is_related_product_available == 0) {
                        $delete = RelatedProduct::where('product_id', $photoframe_details->product_id)->delete();
                    }

                    //Delete product variant id
                    if ($request->del_productvariantId != '') {
                        $deleteIds = json_decode($request->del_productvariantId, TRUE);
                        if (!empty($deleteIds)) {
                            ProductVariant::whereIn('product_variant_id', $deleteIds)->delete();
                        }
                    }

                    if ($photoframe_details->is_publish == 1 &&  $photoframe_details->is_notification == 1) {
                        $pushNotification = $this->productCreatePushNotification($photoframe_details->product_id);
                    }

                    Log::channel("photoframe")->info("** photoframe save details : $photoframe_details **");

                    // log activity
                    $desc = $photoframe_details->product_name . ' product was updated by ' . JwtHelper::getSesUserNameWithType() . ' ';
                    $activitytype = Config('activitytype.Photo Frame');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("photoframe")->info("save value :: $photoframe_details");
                    Log::channel("photoframe")->info('** end the photoframe update method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product updated successfully'),
                        'data' => [$photoframe_details],
                        'product_varient_message' => $product_variant_message,
                        'related_product_message' => $related_product_message,
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('Product update failed'),
                        'data' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('Product name already exist'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("photoframe")->error($exception);
            Log::channel("photoframe")->error('** end the photoframe update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function photoframe_list(Request $request)
    {
        try {
            Log::channel("photoframe")->info('** started the photoframe list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';


            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                // 'product_name' => 'product.product_name',
                // 'passport_size_photo' => 'passport_size_photo.passport_size_photo',
                'created_on' => 'product.created_on',
                // 'product_id' => 'product.product_id',
                'product_code' => 'product.product_code',
                'product_name' => 'product.product_name',
                'selling_price' => 'product.selling_price',
                'mrp' => 'product.mrp',

            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "product.product_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");



            $column_search = array(
                'product.created_on', 'product.product_code',
                'product.product_name', 'product.selling_price',
                'product.mrp'
            );



            $photoframe = ProductCatalogue::select(
                'product.product_id',
                'product.created_on',
                'product.product_code',
                'product.product_name',
                'product_variant.selling_price',
                'product_variant.mrp',
                'product.is_publish',
                'product.status',
            )->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', '=', 1);
            })
                ->where('product.service_id', 3)
                ->where('product.status', '!=', '2');

            $photoframe->where(function ($query) use (
                $searchval,
                $column_search,
                $photoframe
            ) {
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
                $photoframe->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $photoframe->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $photoframe->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $photoframe->where('is_publish', $filterByStatus);
            }


            $count = $photoframe->count();

            if ($offset) {
                $offset = $offset * $limit;
                $photoframe->offset($offset);
            }
            if ($limit) {
                $photoframe->limit($limit);
            }
            $photoframe->orderBy('product.product_id', 'desc');
            $photoframe = $photoframe->get();
            $final = [];

            if ($count > 0) {
                foreach ($photoframe as $value) {
                    $ary = [];
                    $ary['product_id'] = $value['product_id'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['product_name'] = $value['product_name'];
                    $ary['mrp'] = $value['mrp'];
                    $ary['selling_price'] = $value['selling_price'];
                    $ary['is_publish'] = $value['is_publish'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }


            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("photoframe")->info("list value :: $log");
                Log::channel("photoframe")->info('** end the photoframe list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Products listed successfully'),
                    'data' => $final,
                    'count' => $count
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
            Log::channel("photoframe")->error($exception);
            Log::channel("photoframe")->error('** end the photoframe list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function photoframe_view($id)
    {
        try {
            Log::channel("photoframe")->info('** started the photoframe view method **');
            if ($id != '' && $id > 0) {
                $photoprint = ProductCatalogue::where('product.product_id', $id)
                    ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->select('product.*', 'gst_percentage.gst_percentage as gst')->first();

                Log::channel("photoframe")->info("request value product_id:: $id");

                if (!empty($photoprint)) {
                    $final = [];
                    $ary = [];
                    $ary['product_id'] = $photoprint['product_id'];
                    $ary['product_code'] = $photoprint['product_code'];
                    $ary['product_name'] = $photoprint['product_name'];
                    $ary['gst_percentage_id'] = $photoprint['gst_percentage'];
                    $ary['gst'] = $photoprint['gst'];
                    $ary['help_url'] = $photoprint['help_url'];
                    $ary['frame_details'] = json_decode($photoprint['frame_details'], true);
                    $ary['primary_variant_details'] = $this->getPrimaryVariantDetails($photoprint['primary_variant_details']);
                    $ary['variant_details'] = $this->getVariantDetails($photoprint['product_id']);
                    $ary['selected_variants'] = $this->getGlobelVariantDetails(json_decode($photoprint['selected_variants'], true));
                    $ary['thumbnail_url'] = ($photoprint['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $photoprint['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $photoprint['thumbnail_image'];
                    $ary['customer_description'] = $photoprint['customer_description'];
                    $ary['designer_description'] = $photoprint['designer_description'];
                    $ary['is_cod_available'] = $photoprint['is_cod_available'];
                    $ary['is_notification'] = $photoprint['is_notification'];
                    $ary['is_multivariant_available'] = $photoprint['is_multivariant_available'];
                    $ary['is_related_product_available'] = $photoprint['is_related_product_available'];
                    $ary['created_on'] = $photoprint['created_on'];
                    $ary['created_by'] = $photoprint['created_by'];
                    $ary['updated_on'] = $photoprint['updated_on'];
                    $ary['updated_by'] = $photoprint['updated_by'];
                    $ary['status'] = $photoprint['status'];
                    $ary['related_products'] = $this->getrelated_products($photoprint->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("photoframe")->info("view value :: $log");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product viewed successfully'),
                        'data' => $final,
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => [],
                    ]);
                }
            }
        } catch (\Exception $exception) {
            Log::channel("photoframe")->error($exception);
            Log::channel("photoframe")->info('** end the photoframe view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function photoframePublish_status(Request $request)
    {
        try {
            Log::channel("photoframe")->info('** started the photoframe publish status method **');
            $id = $request->product_id;
            $id = json_decode($id, true);

            if (!empty($id)) {

                $update = ProductCatalogue::where('product_id', $id)->update(array(
                    'is_publish' => $request->is_publish,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));
                $productdetails = ProductCatalogue::where('product_id', $id)->first();

                if ($request->is_publish == 1) {
                    $activity_status = 'published';
                    $update = RelatedProduct::where('product_id_related', $id)->update(array(
                        'status' => 1,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId(),
                    ));

                    $photoFrameDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($photoFrameDetails)){
                        foreach($photoFrameDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $photoFrameDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($photoFrameDetailsCount > 0){
                                    $update = ProductCatalogue::where('product_id', $productid)->update(array(
                                        'is_related_product_available' => 1,
                                        'updated_on' => Server::getDateTime(),
                                        'updated_by' => JwtHelper::getSesUserId()
                                    ));
                                } else {
                                    $update = ProductCatalogue::where('product_id', $productid)->update(array(
                                        'is_related_product_available' => 0,
                                        'updated_on' => Server::getDateTime(),
                                        'updated_by' => JwtHelper::getSesUserId()
                                    ));
                                }

                            }
                        }
                    }

                } else if ($request->is_publish == 2) {
                    $activity_status = 'unpublished';
                    $update = RelatedProduct::where('product_id_related', $id)->update(array(
                        'status' => 2,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId(),
                    ));

                    $photoFrameDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($photoFrameDetails)){
                        foreach($photoFrameDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $photoFrameDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($photoFrameDetailsCount > 0){
                                    $update = ProductCatalogue::where('product_id', $productid)->update(array(
                                        'is_related_product_available' => 1,
                                        'updated_on' => Server::getDateTime(),
                                        'updated_by' => JwtHelper::getSesUserId()
                                    ));
                                } else {
                                    $update = ProductCatalogue::where('product_id', $productid)->update(array(
                                        'is_related_product_available' => 0,
                                        'updated_on' => Server::getDateTime(),
                                        'updated_by' => JwtHelper::getSesUserId()
                                    ));
                                }

                            }
                        }
                    }
                }
                // log activity
                // $activity_status = ($request->publish) ? 'published' : 'unpublished';
                $desc = $productdetails->product_name . ' product was ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . ' ';
                $activitytype = Config('activitytype.Photo Frame');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                if ($request->is_publish == 2) {
                    Log::channel("photoframe")->info("Photo frame unpublished successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product unpublished successfully'),
                        'data' => []
                    ]);
                } else if ($request->is_publish == 1) {
                    if ($productdetails->is_publish == 1 &&  $productdetails->is_notification == 1) {
                        $pushNotification = $this->productCreatePushNotification($productdetails->product_id);
                    }
                    Log::channel("photoframe")->info("Photo frame published successfull");
                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  __('Product published successfully'),
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
        } catch (\Exception $exception) {
            Log::channel("photoframe")->error($exception);
            Log::channel("photoframe")->info('** end the photoframe publish status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function photoframe_status(Request $request)
    {
        try {
            Log::channel("photoframe")->info('** started the photoframe status method **');
            // if (!empty($request)) {
            $ids = $request->id;
            if (!empty($ids)) {

                $exist = RelatedProduct::where('product_id_related', $ids)->where('status', '!=', 2)->first();
                if (empty($exist)) {

                    Log::channel("photoframe")->info('** started the photoframe status method **');



                    $photoframe = ProductCatalogue::where('product_id', $ids)->first();
                    $update = ProductCatalogue::where('product_id', $ids)->update(array(
                        'status' => $request->status,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));

                    $update = RelatedProduct::where('product_id_related', $ids)->update(array(
                        'status' => $request->status,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId(),
                    ));
                    $update = ProductVariant::where('product_id', $ids)->delete();

                    // log activity
                    if ($request->status == 0) {
                        $activity_status = 'inactivated';
                    } else if ($request->status == 1) {
                        $activity_status = 'activated';
                    } else if ($request->status == 2) {
                        $activity_status = 'deleted';
                    }
                    // $implode = implode(",", $ids);
                    $desc = $photoframe->product_name . ' Photo frame ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Photo Frame');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {
                        Log::channel("photoframe")->info("Photo frame inactive successfull");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Product inactivated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 1) {
                        Log::channel("photoframe")->info("Photo frame active successfull");
                        return response()->json([
                            'keyword' => 'success',
                            'message' =>  __('Product activated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 2) {
                        Log::channel("photoframe")->info("Photo frame delete successfull");
                        return response()->json([
                            'keyword' => 'success',
                            'message' =>  __('Product deleted successfully'),
                            'data' => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' =>  __('The product is used in related products cannot delete.'),
                        'data' => []
                    ]);
                }
            } else {
                return response()
                    ->json([
                        'keyword' => 'failed',
                        'message' => __('Product status update failed'),
                        'data' => []
                    ]);
            }
        } catch (\Exception $exception) {
            Log::channel("photoframe")->error($exception);
            Log::channel("photoframe")->info('** end the photoframe status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function photoframe_excel(Request $request)
    {
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $all = $request->all;

        $getPhotoFrame = ProductCatalogue::select('product.product_id', 'product.created_on', 'product.product_code', 'product.product_name', 'product_variant.selling_price', 'product_variant.mrp',         'product.is_publish', 'product.status')
            ->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', '=', 1);
            })->where('product.service_id', 3)->where('product.status', '!=', '2');

        if (!empty($from_date)) {
            $getPhotoFrame->where(function ($query) use ($from_date) {
                $query->whereDate('created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $getPhotoFrame->where(function ($query) use ($to_date) {
                $query->whereDate('created_on', '<=', $to_date);
            });
        }
        if (!empty($filterByStatus)) {
            $getPhotoFrame->where('is_publish', $filterByStatus);
        }

        $getPhotoFrame = $getPhotoFrame->get();

        $count = count($getPhotoFrame);

        $s = 1;
        if ($count > 0) {
            $overll = [];
            foreach ($getPhotoFrame as $photoframe) {
                $ary = [];
                $ary['date'] = date('d-m-Y', strtotime($photoframe['created_on']));
                $ary['product_code'] = $photoframe['product_code'];
                $ary['product_name'] = $photoframe['product_name'];
                $ary['mrp'] = $photoframe['mrp'];
                $ary['selling_price'] = $photoframe['selling_price'];
                if ($photoframe['is_publish'] == 1) {
                    $ary['is_publish'] = "Publish";
                } else {
                    $ary['is_publish'] = "Unpublish";
                }
                $overll[] = $ary;
            }
            $s++;
            // return response()->json([
            //     'keyword' => 'success',
            //     'message' => 'Sales listed successfully',
            //     'data' => $overll,
            // ]);
            $excel_report_title = "Photo Print Report";

            $spreadsheet = new Spreadsheet();

            //Set document properties
            $spreadsheet->getProperties()->setCreator("Technogenesis")
                ->setLastModifiedBy("Technogenesis")
                ->setTitle("Credit Accepted Data")
                ->setSubject("Credit Accepted Data")
                ->setDescription("Credit Accepted Data")
                ->setKeywords("Credit Accepted Data")
                ->setCategory("Credit Accepted Data");

            $spreadsheet->getProperties()->setCreator("technogenesis.in")
                ->setLastModifiedBy("Technognesis");

            $spreadsheet->setActiveSheetIndex(0);

            $sheet = $spreadsheet->getActiveSheet();

            //name the worksheet
            $sheet->setTitle($excel_report_title);

            $sheet->setCellValue('A1', 'Date');
            $sheet->setCellValue('B1', 'Product ID');
            $sheet->setCellValue('C1', 'Product Name');
            $sheet->setCellValue('D1', 'MRP(₹)');
            $sheet->setCellValue('E1', 'Selling Price (₹)');
            $sheet->setCellValue('F1', 'Publish');

            $conditional1 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
            $conditional1->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
            $conditional1->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_LESSTHAN);
            $conditional1->addCondition('0');
            $conditional1->getStyle()->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
            $conditional1->getStyle()->getFont()->setBold(true);

            $conditionalStyles = $spreadsheet->getActiveSheet()->getStyle('B2')->getConditionalStyles();
            $conditional1->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            //make the font become bold
            $conditional1->getStyle('A2')->getFont()->setBold(true);
            $conditional1->getStyle('A1')->getFont()->setBold(true);
            $conditional1->getStyle('B1')->getFont()->setBold(true);
            $conditional1->getStyle('C3')->getFont()->setBold(true);
            $conditional1->getStyle('D3')->getFont()->setBold(true);
            $conditional1->getStyle('E3')->getFont()->setBold(true);
            $conditional1->getStyle('F3')->getFont()->setBold(true);
            $conditional1->getStyle('A3')->getFont()->setSize(16);
            $conditional1->getStyle('A3')->getFill()->getStartColor()->setARGB('#333');

            //make the font become bold
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);
            // $sheet->getStyle('A1')->getFont()->setSize(16);
            $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('#333');

            for ($col = ord('A'); $col <= ord('Q'); $col++) { //set column dimension
                $sheet->getColumnDimension(chr($col))->setAutoSize(true);
            }

            //retrieve  table data
            $overll[] = array('', '',  '', '');

            //Fill data
            $sheet->fromArray($overll, null, 'A2');
            $writer = new Xls($spreadsheet);
            $file_name = "photoframe-product-data.xls";
            $fullpath =  storage_path() . '/app/photoframe_product' . $file_name;
            // print_r($fullpath);exit;
            $writer->save($fullpath); // download file
            // return $file_name;
            // return response()->download($fullpath, "sales_product.xls");
            return response()->download(storage_path('app/photoframe_productphotoframe-product-data.xls'), "photoframe_product.xls");
        }
    }
}
