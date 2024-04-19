<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Requests\SelfieAlbumRequest;
use App\Http\Traits\SelfieAlbumTrait;
use App\Models\Customer;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\RelatedProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class SelfieAlbumController extends Controller
{

    use SelfieAlbumTrait;

    public function countSummaryselfiealbum()
    {
        //Total Products
        $total_count = ProductCatalogue::where('service_id', 6)->whereIn('status', [0, 1])->get();
        $total_count = $total_count->count();
        //Published Products
        $published_count = ProductCatalogue::where('service_id', 6)->where('is_publish', 1)->whereIn('status', [0, 1])->get();
        $published_count = $published_count->count();
        //UnPublished products
        $unpublished_count = ProductCatalogue::where('service_id', 6)->where('is_publish', 2)->whereIn('status', [0, 1])->get();
        $unpublished_count = $unpublished_count->count();
        $count = ['total_count' => $total_count, 'published_count' => $published_count, 'unpublished_count' => $unpublished_count];
        return response()->json([
            'keyword' => 'success',
            'message' => __('Count showed successfully'),
            'data' => [$count],
        ]);
    }

    public function selfiealbumCreate(SelfieAlbumRequest $request)
    {
        try {

            $product_variant_message = "";
            $related_product_message = "";

            Log::channel("selfiealbum")->info('** started the selfiealbum create method **');
            $exist = ProductCatalogue::where([['product_name', $request->product_name], ['service_id', 6], ['status', '!=', 2]])->first();

            if (empty($exist)) {

                $selfiealbum = new ProductCatalogue();
                $selfiealbum->product_name = $request->product_name;
                $selfiealbum->no_of_images = $request->no_of_images;
                $selfiealbum->service_id = 6;
                $selfiealbum->gst_percentage = $request->gst_percentage;
                $selfiealbum->help_url = $request->help_url;


                //Thumbnail Image validation starts
                $thumbnailValidation = $this->thumbnailValidation($request->thumbnail_image);
                if ($thumbnailValidation == "true") {

                    $selfiealbum->thumbnail_image = $request->thumbnail_image;
                } else {

                    return response()->json([
                        'keyword' => 'failed',
                        'message' => $thumbnailValidation,
                        'data' => [],
                    ]);
                }
                //Thumbnail Image validation end

                $selfiealbum->customer_description = $request->customer_description;
                $selfiealbum->designer_description = $request->designer_description;
                $selfiealbum->is_cod_available = $request->is_cod_available;
                $selfiealbum->is_related_product_available = $request->is_related_product_available;
                $selfiealbum->is_multivariant_available = $request->is_multivariant_available;
                // $selfiealbum->is_customized = $request->is_customized;
                $selfiealbum->is_notification = $request->is_notification;
                $selfiealbum->selected_variants = $request->selected_variants;

                //primary detail validation start

                if (!empty($request->primary_variant_details)) {

                    $primary_variant_details_insert = $this->validateSelectedVarients($request->primary_variant_details);

                    if ($primary_variant_details_insert == "true") {

                        $selfiealbum->primary_variant_details = $request->primary_variant_details;
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

                //Variant detail validation end

                $selfiealbum->created_on = Server::getDateTime();
                $selfiealbum->created_by = JwtHelper::getSesUserId();
                $relatedProductJsonString = $request->related_products;

                Log::channel("selfiealbum")->info("request value :: $selfiealbum->selfiealbum");

                if ($selfiealbum->save()) {
                    $serviceDetailsCount = ProductCatalogue::where('service_id', $selfiealbum->service_id)->get();
                    $service_count = $serviceDetailsCount->count();
                    $product_code = env('PREFIX') . '_' . str_pad($selfiealbum->product_id, 3, '0', STR_PAD_LEFT) . '_' . str_pad($service_count, 3, '0', STR_PAD_LEFT);
                    $update_productdetails = ProductCatalogue::find($selfiealbum->product_id);
                    $update_productdetails->product_code = $product_code;
                    $update_productdetails->save();

                    // insert product variant
                    if (!empty($varientDetails)) {

                        $product_variant = $this->productVariantInsert($varientDetails, $selfiealbum->product_id);

                        if ($product_variant == true) {

                            $product_variant_message = "Product Variants Successfullly inserted in sub table";
                            Log::channel("selfiealbum")->info($product_variant_message);
                        } else {

                            $product_variant_message = "Product Varient Sub table records not inserted correctly";
                            Log::channel("selfiealbum")->info($product_variant_message);
                        }
                    }

                    $selfiealbum_details = ProductCatalogue::where('product_id', $selfiealbum->product_id)
                        ->select('product.*')
                        ->first();

                    // save related products
                    if ($selfiealbum->is_related_product_available == 1) {
                        $relatedArr = json_decode($relatedProductJsonString, true);
                        $saveRelatedProduct = $this->saverelatedProducts($relatedArr, $selfiealbum_details->product_id);

                        if ($saveRelatedProduct == true) {

                            $related_product_message = "Related products Successfullly inserted in sub table";
                            Log::channel("selfiealbum")->info($related_product_message);
                        } else {

                            $related_product_message = "Related products Sub table records not inserted correctly";
                            Log::channel("selfiealbum")->info($related_product_message);
                        }
                    }
                    Log::channel("selfiealbum")->info("** selfiealbum save details : $selfiealbum_details **");

                    // log activity
                    $desc = 'Selfie Album' . $selfiealbum_details->product_name . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Selfie Album');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);



                    Log::channel("selfiealbum")->info("save value :: $selfiealbum_details");
                    Log::channel("selfiealbum")->info('** end the selfiealbum create method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product created successfully'),
                        'data' => [$selfiealbum_details],
                        'product_variant_message' => $product_variant_message,
                        'related_product_message' => $related_product_message,
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('Product created failed'),
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
            Log::channel("selfiealbum")->error($exception);
            Log::channel("selfiealbum")->error('** end the selfiealbum create method **');

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

    public function selfiealbumUpdate(SelfieAlbumRequest $request)
    {
        try {

            $product_variant_message = "";
            $related_product_message = "";

            Log::channel("selfiealbum")->info('** started the selfiealbum update method **');
            $exist = ProductCatalogue::where([
                ['product_id', '!=', $request->product_id], ['product_name', $request->product_name],
                ['service_id', 6], ['status', '!=', 2]
            ])->first();

            if (empty($exist)) {
                $id = $request->product_id;
                $selfiealbum = ProductCatalogue::find($id);
                $selfiealbum->product_name = $request->product_name;
                $selfiealbum->no_of_images = $request->no_of_images;
                $selfiealbum->gst_percentage = $request->gst_percentage;
                $selfiealbum->help_url = $request->help_url;


                //Thumbnail Image validation starts
                $thumbnailValidation = $this->thumbnailValidation($request->thumbnail_image);
                if ($thumbnailValidation == "true") {

                    $selfiealbum->thumbnail_image = $request->thumbnail_image;
                } else {

                    return response()->json([
                        'keyword' => 'failed',
                        'message' => $thumbnailValidation,
                        'data' => [],
                    ]);
                }
                //Thumbnail Image validation end

                $selfiealbum->customer_description = $request->customer_description;
                $selfiealbum->designer_description = $request->designer_description;
                $selfiealbum->is_cod_available = $request->is_cod_available;
                $selfiealbum->is_related_product_available = $request->is_related_product_available;
                $selfiealbum->is_multivariant_available = $request->is_multivariant_available;
                // $selfiealbum->is_customized = $request->is_customized;
                $selfiealbum->is_notification = $request->is_notification;
                $selfiealbum->selected_variants = $request->selected_variants;

                //primary detail validation start

                if (!empty($request->primary_variant_details)) {

                    $primary_variant_details_insert = $this->validateSelectedVarients($request->primary_variant_details);

                    if ($primary_variant_details_insert == "true") {

                        $selfiealbum->primary_variant_details = $request->primary_variant_details;
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

                //Variant detail validation end

                // $selfiealbum->created_on = Server::getDateTime();
                // $selfiealbum->created_by = JwtHelper::getSesUserId();
                $selfiealbum->updated_on = Server::getDateTime();
                $selfiealbum->updated_by = JwtHelper::getSesUserId();

                $relatedProductJsonString = $request->related_products;

                Log::channel("selfiealbum")->info("request value :: $selfiealbum->selfiealbum");

                if ($selfiealbum->save()) {

                    // insert product variant
                    if (!empty($varientDetails)) {

                        $product_variant = $this->productVariantUpdate($varientDetails, $selfiealbum->product_id);

                        if ($product_variant == true) {

                            $product_variant_message = "Product Variants Successfullly updated in sub table";
                            Log::channel("selfiealbum")->info($product_variant_message);
                        } else {

                            $product_variant_message = "Product Variant Sub table records not updated correctly";
                            Log::channel("selfiealbum")->info($product_variant_message);
                        }
                    }

                    $selfiealbum_details = ProductCatalogue::where('product_id', $selfiealbum->product_id)
                        ->select('product.*')
                        ->first();

                    // save related products
                    if ($selfiealbum->is_related_product_available == 1) {
                        $delete = RelatedProduct::where('product_id', $selfiealbum_details->product_id)->delete();
                        $relatedArr = json_decode($relatedProductJsonString, true);
                        $saveRelatedProduct = $this->saverelatedProducts($relatedArr, $selfiealbum_details->product_id);

                        if ($saveRelatedProduct == true) {

                            $related_product_message = "Related products Successfullly updated in sub table";
                            Log::channel("selfiealbum")->info($related_product_message);
                        } else {

                            $related_product_message = "Related products Sub table records not updated correctly";
                            Log::channel("selfiealbum")->info($related_product_message);
                        }
                    }

                    //delete related products
                    if ($selfiealbum_details->is_related_product_available == 0) {
                        $delete = RelatedProduct::where('product_id', $selfiealbum_details->product_id)->delete();
                    }

                    //Delete product variant id
                    if ($request->del_productvariantId != '') {
                        $deleteIds = json_decode($request->del_productvariantId, true);
                        if (!empty($deleteIds)) {
                            ProductVariant::whereIn('product_variant_id', $deleteIds)->delete();
                        }
                    }

                    if ($selfiealbum_details->is_publish == 1 && $selfiealbum_details->is_notification == 1) {
                        $pushNotification = $this->productCreatePushNotification($selfiealbum_details->product_id);
                    }

                    Log::channel("selfiealbum")->info("** selfiealbum save details : $selfiealbum_details **");

                    // log activity
                    $desc = $selfiealbum_details->product_name . ' product was updated by ' . JwtHelper::getSesUserNameWithType() . ' ';
                    $activitytype = Config('activitytype.Selfie Album');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("selfiealbum")->info("save value :: $selfiealbum_details");
                    Log::channel("selfiealbum")->info('** end the selfiealbum update method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product updated successfully'),
                        'data' => [$selfiealbum_details],
                        'product_variant_message' => $product_variant_message,
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
            Log::channel("selfiealbum")->error($exception);
            Log::channel("selfiealbum")->error('** end the selfiealbum update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function selfiealbumView($id)
    {
        try {
            Log::channel("selfiealbum")->info('** started the selfiealbum view method **');
            if ($id != '' && $id > 0) {
                $selfiealbum = ProductCatalogue::where('product.product_id', $id)->where('service_id', 6)
                    ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->select('product.*', 'gst_percentage.gst_percentage as gst')->first();

                Log::channel("selfiealbum")->info("request value product_id:: $id");

                if (!empty($selfiealbum)) {
                    $final = [];
                    $ary = [];
                    $ary['product_id'] = $selfiealbum['product_id'];
                    $ary['product_name'] = $selfiealbum['product_name'];
                    $ary['no_of_images'] = $selfiealbum['no_of_images'];
                    $ary['gst_percentage_id'] = $selfiealbum['gst_percentage'];
                    $ary['gst'] = $selfiealbum['gst'];
                    $ary['help_url'] = $selfiealbum['help_url'];
                    $ary['primary_variant_details'] = $this->getPrimaryVariantDetails($selfiealbum['primary_variant_details']);
                    $ary['variant_details'] = $this->getVariantDetails($selfiealbum['product_id']);
                    $ary['selected_variants'] = $this->getGlobelVariantDetails(json_decode($selfiealbum['selected_variants'], true));
                    $ary['thumbnail_url'] = ($selfiealbum['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $selfiealbum['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $selfiealbum['thumbnail_image'];
                    $ary['customer_description'] = $selfiealbum['customer_description'];
                    $ary['designer_description'] = $selfiealbum['designer_description'];
                    $ary['is_cod_available'] = $selfiealbum['is_cod_available'];
                    // $ary['is_customized'] = $selfiealbum['is_customized'];
                    $ary['is_multivariant_available'] = $selfiealbum['is_multivariant_available'];
                    $ary['is_related_product_available'] = $selfiealbum['is_related_product_available'];
                    $ary['is_notification'] = $selfiealbum['is_notification'];
                    $ary['created_on'] = $selfiealbum['created_on'];
                    $ary['created_by'] = $selfiealbum['created_by'];
                    $ary['updated_on'] = $selfiealbum['updated_on'];
                    $ary['updated_by'] = $selfiealbum['updated_by'];
                    $ary['status'] = $selfiealbum['status'];
                    $ary['related_products'] = $this->getrelated_products($selfiealbum->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("selfiealbum")->info("view value :: $log");
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
            Log::channel("selfiealbum")->error($exception);
            Log::channel("selfiealbum")->info('** end the selfiealbum view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function selfiealbumList(Request $request)
    {
        try {
            Log::channel("selfiealbum")->info('** started the selfiealbum list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';

            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'product_id' => 'product.product_id',
                // 'category_name' => 'category.category_name',
                // 'category_name' => 'category.category_name',
                'created_on' => 'product.created_on',
                'product_code' => 'product.product_code',
                'product_name' => 'product.product_name',
                'selling_price' => 'product_variant.selling_price',
                'mrp' => 'product_variant.mrp',

            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "product.product_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

            $column_search = array(
                'product.created_on', 'product.product_code',
                'product.product_name', 'product_variant.selling_price',
                'product_variant.mrp',
                // 'category.category_name',
            );

            $selfiealbum = ProductCatalogue::select(
                'product.product_id',
                'product.created_on',
                'product.product_code',
                'product.product_name',
                // 'product.category_id',
                // 'category.category_name',
                'product_variant.selling_price',
                'product_variant.mrp',
                'product_variant.quantity',
                'product.is_publish',
                'product.status',
            )->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', '=', 1);
            })
                // ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
                ->where('product.service_id', 6)
                ->where('product.status', '!=', '2')
                ->groupBy('product.product_id');

            $selfiealbum->where(function ($query) use (
                $searchval,
                $column_search,
                $selfiealbum
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
                $selfiealbum->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $selfiealbum->where(function ($query) use ($from_date) {
                    $query->whereDate('product.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $selfiealbum->where(function ($query) use ($to_date) {
                    $query->whereDate('product.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $selfiealbum->where('product.is_publish', $filterByStatus);
            }

            // if (!empty($filterByCategory)) {
            //     $selfiealbum->where('product.category_id', $filterByStatus);
            // }

            $count = count($selfiealbum->get());

            if ($offset) {
                $offset = $offset * $limit;
                $selfiealbum->offset($offset);
            }
            if ($limit) {
                $selfiealbum->limit($limit);
            }

            $selfiealbum->orderBy('product.product_id', 'desc');

            $selfiealbum = $selfiealbum->get();
            $final = [];

            if ($count > 0) {
                foreach ($selfiealbum as $value) {
                    $ary = [];
                    $ary['product_id'] = $value['product_id'];
                    $ary['product_code'] = $value['product_code'];
                    // $ary['category_id'] = $value['category_id'];
                    // $ary['category_name'] = $value['category_name'];
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
                Log::channel("selfiealbum")->info("list value :: $log");
                Log::channel("selfiealbum")->info('** end the selfiealbum list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Product listed successfully'),
                    'data' => $final,
                    'count' => $count,
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                    'count' => $count,
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("selfiealbum")->error($exception);
            Log::channel("selfiealbum")->error('** end the selfiealbum list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function selfiealbumPublishStatus(Request $request)
    {
        try {
            Log::channel("selfiealbum")->info('** started the selfiealbum publish status method **');
            $id = $request->product_id;
            $id = json_decode($id, true);

            if (!empty($id)) {

                $update = ProductCatalogue::where('product_id', $id)->update(array(
                    'is_publish' => $request->is_publish,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId(),
                ));
                $productdetails = ProductCatalogue::where('product_id', $id)->first();

                if ($request->is_publish == 1) {
                    $activity_status = 'published';
                    $update = RelatedProduct::where('product_id_related', $id)->update(array(
                        'status' => 1,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId(),
                    ));

                    $selfieDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($selfieDetails)){
                        foreach($selfieDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $selfieDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($selfieDetailsCount > 0){
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

                    $selfieDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($selfieDetails)){
                        foreach($selfieDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $selfieDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($selfieDetailsCount > 0){
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
                $activity_status = ( $request->is_publish== 1) ? 'published' : 'unpublished';
                $desc = $productdetails->product_name . ' product was ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . ' ';
                $activitytype = Config('activitytype.Selfie Album');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);





                if ($request->is_publish == 2) {
                    Log::channel("selfiealbum")->info("Selfie Album Unpublished successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __(' Product unpublished successfully'),
                        'data' => [],
                    ]);
                } else if ($request->is_publish == 1) {
                    if ($productdetails->is_publish == 1 && $productdetails->is_notification == 1) {
                        $pushNotification = $this->productCreatePushNotification($productdetails->product_id);
                    }
                    Log::channel("selfiealbum")->info("Selfie Album Published successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __(' Product published successfully'),
                        'data' => [],
                    ]);
                }
            } else {
                return response()
                    ->json([
                        'keyword' => 'failed',
                        'message' => __('failed'),
                        'data' => [],
                    ]);
            }
        } catch (\Exception $exception) {
            Log::channel("selfiealbum")->error($exception);
            Log::channel("selfiealbum")->info('** end the selfiealbum publish status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function selfiealbumStatus(Request $request)
    {
        try {
            Log::channel("selfiealbum")->info('** started the selfiealbum status method **');

            $ids = $request->id;

            if (!empty($ids)) {

                $exist = RelatedProduct::where('product_id_related', $ids)->where('status', '!=', 2)->first();
                if (empty($exist)) {

                    Log::channel("selfiealbum")->info('** started the selfiealbum status method **');

                    $selfiealbum = ProductCatalogue::where('product_id', $ids)->first();
                    $update = ProductCatalogue::where('product_id', $ids)->update(array(
                        'status' => $request->status,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId(),
                    ));
                    $updated_on = ProductCatalogue::where('product_id',$ids)->first();
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

                    $desc = $selfiealbum->product_name . ' product was ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . ' ';
                    $activitytype = Config('activitytype.Selfie Album');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {
                        Log::channel("selfiealbum")->info("Selfie Album Inactive successfull");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Product inactivated successfully'),
                            'data' => [],
                        ]);
                    } else if ($request->status == 1) {
                        Log::channel("selfiealbum")->info("Selfie Album Active successfull");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Product activated successfully'),
                            'data' => [],
                        ]);
                    } else if ($request->status == 2) {
                        Log::channel("selfiealbum")->info("Selfie Album Delete successfull");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Product deleted successfully'),
                            'data' => [],
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
                        'message' => __('failed'),
                        'data' => [],
                    ]);
            }
        } catch (\Exception $exception) {
            Log::channel("selfiealbum")->error($exception);
            Log::channel("selfiealbum")->info('** end the selfiealbum status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function selfiealbumExcel(Request $request)
    {
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $all = $request->all;

        $getselfiealbum = ProductCatalogue::select('product.product_id', 'product.created_on', 'product.product_code', 'product.product_name', 'product_variant.selling_price', 'product_variant.mrp', 'product.is_publish', 'product.status')
            ->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', '=', 1);
            })
            // ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
            ->where('product.service_id', 6)->where('product.status', '!=', '2')->groupBy('product.product_id');

        if (!empty($from_date)) {
            $getselfiealbum->where(function ($query) use ($from_date) {
                $query->whereDate('product.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $getselfiealbum->where(function ($query) use ($to_date) {
                $query->whereDate('product.created_on', '<=', $to_date);
            });
        }
        if (!empty($filterByStatus)) {
            $getselfiealbum->where('product.is_publish', $filterByStatus);
        }

        $getselfiealbum = $getselfiealbum->get();

        $count = count($getselfiealbum);

        $s = 1;
        if (!empty($getselfiealbum)) {
            $overll = [];
            foreach ($getselfiealbum as $selfiealbum) {
                $ary = [];
                $ary['date'] = date('d-m-Y', strtotime($selfiealbum['created_on']));
                $ary['product_code'] = $selfiealbum['product_code'];
                $ary['product_name'] = $selfiealbum['product_name'];
                // $ary['category_name'] = $selfiealbum['category_name'];
                $ary['mrp'] = $selfiealbum['mrp'];
                $ary['selling_price'] = $selfiealbum['selling_price'];
                if ($selfiealbum['is_publish'] == 1) {
                    $ary['is_publish'] = "Publish";
                } else {
                    $ary['is_publish'] = "Unpublish";
                }
                $overll[] = $ary;
            }
            $s++;

            $excel_report_title = "Selfie Album Product List";

            $spreadsheet = new Spreadsheet();

            //Set document properties
            $spreadsheet->getProperties()->setCreator("Technogenesis")
                ->setLastModifiedBy("Technogenesis")
                ->setTitle("Selfie Album Product List")
                ->setSubject("Selfie Album Product List")
                ->setDescription("Selfie Album Product List")
                ->setKeywords("Selfie Album Product List")
                ->setCategory("Selfie Album Product List");

            $spreadsheet->getProperties()->setCreator("technogenesis.in")
                ->setLastModifiedBy("Technognesis");

            $spreadsheet->setActiveSheetIndex(0);

            $sheet = $spreadsheet->getActiveSheet();

            //name the worksheet
            $sheet->setTitle($excel_report_title);

            $sheet->setCellValue('A1', 'Date');
            $sheet->setCellValue('B1', 'Product ID');
            $sheet->setCellValue('C1', 'Product Name');
            // $sheet->setCellValue('D1', 'Category');
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
            $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('#333');

            for ($col = ord('A'); $col <= ord('Q'); $col++) { //set column dimension
                $sheet->getColumnDimension(chr($col))->setAutoSize(true);
            }

            //retrieve  table data
            $overll[] = array('', '', '', '');

            //Fill data
            $sheet->fromArray($overll, null, 'A2');
            $writer = new Xls($spreadsheet);
            $file_name = "selfiealbum-report-data.xls";
            $fullpath = storage_path() . '/app/selfiealbum_report' . $file_name;
            $writer->save($fullpath); // download file
            return response()->download(storage_path('app/selfiealbum_reportselfiealbum-report-data.xls'), "selfiealbum_report.xls");
        }
    }
}
