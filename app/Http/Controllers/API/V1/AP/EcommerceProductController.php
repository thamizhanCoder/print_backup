<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Requests\EcommerceRequest;
use App\Http\Traits\EcommerceProductTrait;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\RelatedProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class EcommerceProductController extends Controller
{

    use EcommerceProductTrait;

    public function countSummaryEcommerce()
    {
        //Total Products
        $total_count = ProductCatalogue::where('service_id', 5)->whereIn('status', [0, 1])->get();
        $total_count = $total_count->count();
        //Published Products
        $published_count = ProductCatalogue::where('service_id', 5)->where('is_publish', 1)->whereIn('status', [0, 1])->get();
        $published_count = $published_count->count();
        //UnPublished products
        $unpublished_count = ProductCatalogue::where('service_id', 5)->where('is_publish', 2)->whereIn('status', [0, 1])->get();
        $unpublished_count = $unpublished_count->count();
        $count = ['total_count' => $total_count, 'published_count' => $published_count, 'unpublished_count' => $unpublished_count];
        return response()->json([
            'keyword' => 'success',
            'message' => __('Count showed successfully'),
            'data' => [$count],
        ]);
    }

    public function ecommerce_create(EcommerceRequest $request)
    {
        try {

            $product_variant_message = "";
            $related_product_message = "";

            Log::channel("ecommerce")->info('** started the ecommerce create method **');
            $exist = ProductCatalogue::where([['product_name', $request->product_name], ['service_id', 5], ['status', '!=', 2]])->first();

            if (empty($exist)) {

                $ecommerce = new ProductCatalogue();
                $ecommerce->product_name = $request->product_name;
                $ecommerce->service_id = 5;
                $ecommerce->category_id = $request->category_id;
                $ecommerce->gst_percentage = $request->gst_percentage;

                //Thumbnail Image validation starts
                $thumnailValidation = $this->thumbnailValidation($request->thumbnail_image);
                if ($thumnailValidation == "true") {

                    $ecommerce->thumbnail_image = $request->thumbnail_image;
                } else {

                    return response()->json([
                        'keyword' => 'failed',
                        'message' => $thumnailValidation,
                        'data' => [],
                    ]);
                }
                //Thumbnail Image validation end

                $ecommerce->product_description = $request->product_description;
                $ecommerce->product_specification = $request->product_specification;

                $ecommerce->is_cod_available = $request->is_cod_available;
                $ecommerce->is_related_product_available = $request->is_related_product_available;
                $ecommerce->is_multivariant_available = $request->is_multivariant_available;
                $ecommerce->is_notification = $request->is_notification;
                $ecommerce->selected_variants = $request->selected_variants;

                //image validation start

                if (!empty($request->product_image)) {

                    $product_image_insert = $this->productImageValidation($request->product_image);

                    if ($product_image_insert == "true") {

                        $ecommerce->product_image = $request->product_image;
                    } else {

                        return response()->json([
                            'keyword' => 'failed',
                            'message' => $product_image_insert,
                            'data' => [],
                        ]);
                    }
                }
                //image validation end

                //Variant detail validation start

                // if ($ecommerce->is_multivariant_available == 1) {

                $variant_type_validation = $this->varientAttributeValidate($request->variant_details);

                if ($variant_type_validation) {

                    return response()->json(['keyword' => 'failed', 'message' => $variant_type_validation, 'data' => []]);
                } else {

                    $variant_details_validation = $this->varientDetailsValidation($request->variant_details);

                    if ($variant_details_validation) {

                        return response()->json(['keyword' => 'failed', 'message' => $variant_details_validation, 'data' => []]);
                    } else {

                        $variantCodeAlreadyExistCheck = $this->variantCodeExistCheck($request->variant_details);

                        if ($variantCodeAlreadyExistCheck == "true") {

                            $varientDetails = $request->variant_details;
                        } else {

                            return response()->json(['keyword' => 'failed', 'message' => $variantCodeAlreadyExistCheck, 'data' => []]);
                        }
                    }
                }
                // }

                //Variant detail validation end

                $ecommerce->created_on = Server::getDateTime();
                $ecommerce->created_by = JwtHelper::getSesUserId();

                $relatedProductJsonString = $request->related_products;

                Log::channel("ecommerce")->info("request value :: $ecommerce->ecommerce");

                if ($ecommerce->save()) {
                    $serviceDetailsCount = ProductCatalogue::where('service_id', $ecommerce->service_id)->get();
                    $service_count = $serviceDetailsCount->count();
                    $product_code = env('PREFIX') . '_' . str_pad($ecommerce->product_id, 3, '0', STR_PAD_LEFT) . '_' . str_pad($service_count, 3, '0', STR_PAD_LEFT);
                    $update_productdetails = ProductCatalogue::find($ecommerce->product_id);
                    $update_productdetails->product_code = $product_code;
                    $update_productdetails->save();

                    // insert product variant
                    if (!empty($varientDetails)) {

                        $product_variant = $this->productVariantInsert($varientDetails, $ecommerce->product_id);

                        if ($product_variant == true) {

                            $product_variant_message = "Product variants Successfullly inserted in sub table";
                            Log::channel("ecommerce")->info($product_variant_message);
                        } else {

                            $product_variant_message = "Product variants Sub table records not inserted correctly";
                            Log::channel("ecommerce")->info($product_variant_message);
                        }
                    }

                    $ecommerce_details = ProductCatalogue::where('product_id', $ecommerce->product_id)
                        ->select('product.*')
                        ->first();

                    // save related products
                    if ($ecommerce->is_related_product_available == 1) {
                        $relatedArr = json_decode($relatedProductJsonString, true);
                        $saveRelatedProduct = $this->saverelatedProducts($relatedArr, $ecommerce_details->product_id);

                        if ($saveRelatedProduct == true) {

                            $related_product_message = "Related products Successfullly inserted in sub table";
                            Log::channel("ecommerce")->info($related_product_message);
                        } else {

                            $related_product_message = "PrRelated products Sub table records not inserted correctly";
                            Log::channel("ecommerce")->info($related_product_message);
                        }
                    }
                    Log::channel("ecommerce")->info("** ecommerce save details : $ecommerce_details **");

                    // log activity
                    $desc = 'Ecommerce products ' . $ecommerce_details->product_name . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Ecommerce Products');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);



                    Log::channel("ecommerce")->info("save value :: $ecommerce_details");
                    Log::channel("ecommerce")->info('** end the ecommerce create method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product created successfully'),
                        'data' => [$ecommerce_details],
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
            Log::channel("ecommerce")->error($exception);
            Log::channel("ecommerce")->error('** end the ecommerce create method **');

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

    public function ecommerce_update(EcommerceRequest $request)
    {
        try {

            $product_variant_message = "";
            $related_product_message = "";

            Log::channel("ecommerce")->info('** started the ecommerce update method **');
            $exist = ProductCatalogue::where([['product_id', '!=', $request->product_id], ['product_name', $request->product_name], ['service_id', 5], ['status', '!=', 2]])->first();

            if (empty($exist)) {
                $id = $request->product_id;
                $ecommerce = ProductCatalogue::find($id);
                $ecommerce->product_name = $request->product_name;
                $ecommerce->service_id = 5;
                $ecommerce->category_id = $request->category_id;
                $ecommerce->gst_percentage = $request->gst_percentage;

                //Thumbnail Image validation starts
                $thumnailValidation = $this->thumbnailValidation($request->thumbnail_image);
                if ($thumnailValidation == "true") {

                    $ecommerce->thumbnail_image = $request->thumbnail_image;
                } else {

                    return response()->json([
                        'keyword' => 'failed',
                        'message' => $thumnailValidation,
                        'data' => [],
                    ]);
                }
                //Thumbnail Image validation end

                $ecommerce->product_description = $request->product_description;
                $ecommerce->product_specification = $request->product_specification;

                $ecommerce->is_cod_available = $request->is_cod_available;
                $ecommerce->is_related_product_available = $request->is_related_product_available;
                $ecommerce->is_multivariant_available = $request->is_multivariant_available;
                $ecommerce->is_notification = $request->is_notification;
                $ecommerce->selected_variants = $request->selected_variants;

                //image validation start

                if (!empty($request->product_image)) {

                    $product_image_insert = $this->productImageValidation($request->product_image);

                    if ($product_image_insert == "true") {

                        $ecommerce->product_image = $request->product_image;
                    } else {

                        return response()->json([
                            'keyword' => 'failed',
                            'message' => $product_image_insert,
                            'data' => [],
                        ]);
                    }
                }
                //image validation end

                //Variant detail validation start

                // if ($ecommerce->is_multivariant_available == 1) {

                $variant_type_validation = $this->varientAttributeValidate($request->variant_details);

                if ($variant_type_validation) {

                    return response()->json(['keyword' => 'failed', 'message' => $variant_type_validation, 'data' => []]);
                } else {

                    $variant_details_validation = $this->varientDetailsValidation($request->variant_details);

                    if ($variant_details_validation) {

                        return response()->json(['keyword' => 'failed', 'message' => $variant_details_validation, 'data' => []]);
                    } else {

                        $variantCodeAlreadyExistCheck = $this->variantCodeExistCheckUpdate($request->variant_details, $id);

                        if ($variantCodeAlreadyExistCheck == "true") {

                            $varientDetails = $request->variant_details;
                        } else {

                            return response()->json(['keyword' => 'failed', 'message' => $variantCodeAlreadyExistCheck, 'data' => []]);
                        }
                    }
                }
                // }

                //Variant detail validation end

                $ecommerce->updated_on = Server::getDateTime();
                $ecommerce->updated_by = JwtHelper::getSesUserId();

                $relatedProductJsonString = $request->related_products;

                Log::channel("ecommerce")->info("request value :: $ecommerce->ecommerce");

                if ($ecommerce->save()) {

                    // insert product variant
                    if (!empty($varientDetails)) {

                        $product_variant = $this->productVariantUpdate($varientDetails, $ecommerce->product_id, $ecommerce->product_name, $ecommerce->product_description);

                        if ($product_variant == true) {

                            $product_variant_message = "Product variants Successfullly inserted in sub table";
                            Log::channel("ecommerce")->info($product_variant_message);
                        } else {

                            $product_variant_message = "Product variants Sub table records not inserted correctly";
                            Log::channel("ecommerce")->info($product_variant_message);
                        }
                    }

                    $ecommerce_details = ProductCatalogue::where('product_id', $ecommerce->product_id)
                        ->select('product.*')
                        ->first();

                    // save related products
                    if ($ecommerce->is_related_product_available == 1) {
                        $delete = RelatedProduct::where('product_id', $ecommerce_details->product_id)->delete();
                        $relatedArr = json_decode($relatedProductJsonString, true);
                        $saveRelatedProduct = $this->saverelatedProducts($relatedArr, $ecommerce_details->product_id);

                        if ($saveRelatedProduct == true) {

                            $related_product_message = "Related products Successfullly inserted in sub table";
                            Log::channel("ecommerce")->info($related_product_message);
                        } else {

                            $related_product_message = "PrRelated products Sub table records not inserted correctly";
                            Log::channel("ecommerce")->info($related_product_message);
                        }
                    }

                    //delete related products
                    if ($ecommerce->is_related_product_available == 0) {
                        $delete = RelatedProduct::where('product_id', $ecommerce_details->product_id)->delete();
                    }

                    //Delete product variant id
                    if ($request->del_productvariantId != '') {
                        $deleteIds = json_decode($request->del_productvariantId, TRUE);
                        if (!empty($deleteIds)) {
                            ProductVariant::whereIn('product_variant_id', $deleteIds)->delete();
                        }
                    }

                    if ($ecommerce_details->is_publish == 1 &&  $ecommerce_details->is_notification == 1) {
                        $pushNotification = $this->productCreatePushNotification($ecommerce_details->product_id);
                    }

                    Log::channel("ecommerce")->info("** ecommerce save details : $ecommerce_details **");

                    // log activity
                    $desc = $ecommerce_details->product_name . ' product was updated by ' . JwtHelper::getSesUserNameWithType() . ' ';
                    $activitytype = Config('activitytype.Ecommerce Products');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("ecommerce")->info("save value :: $ecommerce_details");
                    Log::channel("ecommerce")->info('** end the ecommerce update method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product updated successfully'),
                        'data' => [$ecommerce_details],
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
            Log::channel("ecommerce")->error($exception);
            Log::channel("ecommerce")->error('** end the ecommerce update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function ecommerce_list(Request $request)
    {
        try {
            Log::channel("ecommerce")->info('** started the ecommerce list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
            $filterByCategory = ($request->filterByCategory) ? $request->filterByCategory : '';

            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'product_id' => 'product.product_id',
                'category_name' => 'category.category_name',
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
                'product_variant.mrp', 'category.category_name',
            );

            $ecommerce = ProductCatalogue::select(
                'product.product_id',
                'product.created_on',
                'product.product_code',
                'product.product_name',
                'product.category_id',
                'category.category_name',
                'product_variant.selling_price',
                'product_variant.mrp',
                'product_variant.quantity',
                'product.is_publish',
                'product.status',
            )->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', '=', 1);
            })->leftjoin('category', 'category.category_id', '=', 'product.category_id')
                ->where('product.service_id', 5)
                ->where('product.status', '!=', '2')
                ->groupBy('product.product_id');

            $ecommerce->where(function ($query) use (
                $searchval,
                $column_search,
                $ecommerce
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
                $ecommerce->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $ecommerce->where(function ($query) use ($from_date) {
                    $query->whereDate('product.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $ecommerce->where(function ($query) use ($to_date) {
                    $query->whereDate('product.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $ecommerce->where('product.is_publish', $filterByStatus);
            }

            if (!empty($filterByCategory)) {
                $ecommerce->where('product.category_id', $filterByCategory);
            }
            $count = count($ecommerce->get());

            if ($offset) {
                $offset = $offset * $limit;
                $ecommerce->offset($offset);
            }
            if ($limit) {
                $ecommerce->limit($limit);
            }

            $ecommerce->orderBy('product.product_id', 'desc');

            $ecommerce = $ecommerce->get();
            $final = [];

            if ($count > 0) {
                foreach ($ecommerce as $value) {
                    $ary = [];
                    $ary['product_id'] = $value['product_id'];
                    $ary['created_on'] = $value['created_on'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['category_id'] = $value['category_id'];
                    $ary['category_name'] = $value['category_name'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['product_name'] = $value['product_name'];
                    $ary['mrp'] = $value['mrp'];
                    $ary['quantity'] = $value['quantity'];
                    $ary['selling_price'] = $value['selling_price'];
                    $ary['is_publish'] = $value['is_publish'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("ecommerce")->info("list value :: $log");
                Log::channel("ecommerce")->info('** end the ecommerce list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Products listed successfully'),
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
            Log::channel("ecommerce")->error($exception);
            Log::channel("ecommerce")->error('** end the ecommerce list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function ecommerce_view($id)
    {
        try {
            Log::channel("ecommerce")->info('** started the ecommerce view method **');
            if ($id != '' && $id > 0) {
                $ecommerce = ProductCatalogue::where('product.product_id', $id)->where('product.service_id', 5)
                    ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
                    ->select('product.*', 'gst_percentage.gst_percentage as gst', 'category.category_name')->first();

                Log::channel("ecommerce")->info("request value product_id:: $id");

                if (!empty($ecommerce)) {
                    $final = [];
                    $ary = [];
                    $ary['product_id'] = $ecommerce['product_id'];
                    $ary['product_code'] = $ecommerce['product_code'];
                    $ary['product_name'] = $ecommerce['product_name'];
                    $ary['category_id'] = $ecommerce['category_id'];
                    $ary['category_name'] = $ecommerce['category_name'];
                    $ary['gst_percentage_id'] = $ecommerce['gst_percentage'];
                    $ary['gst'] = $ecommerce['gst'];
                    $gTImage = json_decode($ecommerce['product_image'], true);
                    $ary['product_image'] = $this->getdefaultImages_allImages($gTImage);
                    $ary['variant_details'] = $this->getVariantDetails($ecommerce['product_id']);
                    $ary['selected_variants'] = $this->getGlobelVariantDetails(json_decode($ecommerce['selected_variants'], true));
                    $ary['thumbnail_url'] = ($ecommerce['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $ecommerce['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $ecommerce['thumbnail_image'];
                    $ary['product_description'] = $ecommerce['product_description'];
                    $ary['product_specification'] = $ecommerce['product_specification'];
                    $ary['is_cod_available'] = $ecommerce['is_cod_available'];
                    $ary['is_notification'] = $ecommerce['is_notification'];
                    $ary['is_multivariant_available'] = $ecommerce['is_multivariant_available'];
                    $ary['is_related_product_available'] = $ecommerce['is_related_product_available'];
                    $ary['created_on'] = $ecommerce['created_on'];
                    $ary['created_by'] = $ecommerce['created_by'];
                    $ary['updated_on'] = $ecommerce['updated_on'];
                    $ary['updated_by'] = $ecommerce['updated_by'];
                    $ary['status'] = $ecommerce['status'];
                    $ary['related_products'] = $this->getrelated_products($ecommerce->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("ecommerce")->info("view value :: $log");
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
            Log::channel("ecommerce")->error($exception);
            Log::channel("ecommerce")->info('** end the ecommerce view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function ecommercePublish_status(Request $request)
    {
        try {
            Log::channel("ecommerce")->info('** started the ecommerce publish status method **');
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

                    $ecommerceDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($ecommerceDetails)){
                        foreach($ecommerceDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $ecommerceDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($ecommerceDetailsCount > 0){
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

                    $ecommerceDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($ecommerceDetails)){
                        foreach($ecommerceDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $ecommerceDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($ecommerceDetailsCount > 0){
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
                $desc = $productdetails->product_name . ' product was ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '  ';
                $activitytype = Config('activitytype.Ecommerce Products');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                if ($request->is_publish == 2) {
                    Log::channel("ecommerce")->info("Photo frame unpublished successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product unpublished successfully'),
                        'data' => []
                    ]);
                } else if ($request->is_publish == 1) {
                    if ($productdetails->is_publish == 1 &&  $productdetails->is_notification == 1) {
                        $pushNotification = $this->productCreatePushNotification($productdetails->product_id);
                    }
                    Log::channel("ecommerce")->info("Ecommerce products published successfull");
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
            Log::channel("ecommerce")->error($exception);
            Log::channel("ecommerce")->info('** end the ecommerce publish status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function ecommerce_status(Request $request)
    {
        try {
            Log::channel("ecommerce")->info('** started the ecommerce status method **');
            // if (!empty($request)) {
            $ids = $request->id;
            if (!empty($ids)) {

                $exist = RelatedProduct::where('product_id_related', $ids)->where('status', '!=', 2)->first();
                if (empty($exist)) {

                    Log::channel("ecommerce")->info('** started the ecommerce status method **');



                    $ecommerce = ProductCatalogue::where('product_id', $ids)->first();
                    $update = ProductCatalogue::where('product_id', $ids)->update(array(
                        'status' => $request->status,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
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
                    // $implode = implode(",", $ids);
                    $desc = $ecommerce->product_name . ' product was ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . ' ';
                    $activitytype = Config('activitytype.Ecommerce Products');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {
                        Log::channel("ecommerce")->info("Ecommerce products inactive successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Product inactivated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 1) {
                        Log::channel("ecommerce")->info("Ecommerce products active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' =>  __('Product activated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 2) {
                        Log::channel("ecommerce")->info("Ecommerce products delete successfully");
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
            Log::channel("ecommerce")->error($exception);
            Log::channel("ecommerce")->info('** end the ecommerce status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function ecommerce_excel(Request $request)
    {
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $filterByCategory = ($request->filterByCategory) ? $request->filterByCategory : '';
        $all = $request->all;

        $getEcommerce = ProductCatalogue::select('category.category_name', 'product.product_id', 'product.created_on', 'product.product_code', 'product.product_name', 'product_variant.selling_price', 'product_variant.mrp', 'product.is_publish', 'product.status')
            ->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', '=', 1);
            })->leftjoin('category', 'category.category_id', '=', 'product.category_id')
            ->where('product.service_id', 5)->where('product.status', '!=', '2')->groupBy('product.product_id');

        if (!empty($from_date)) {
            $getEcommerce->where(function ($query) use ($from_date) {
                $query->whereDate('product.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $getEcommerce->where(function ($query) use ($to_date) {
                $query->whereDate('product.created_on', '<=', $to_date);
            });
        }
        if (!empty($filterByStatus)) {
            $getEcommerce->where('product.is_publish', $filterByStatus);
        }
        if (!empty($filterByCategory)) {
            $getEcommerce->where('product.category_id', $filterByCategory);
        }

        $getEcommerce = $getEcommerce->get();

        $count = count($getEcommerce);

        $s = 1;
        if (!empty($getEcommerce)) {
            $overll = [];
            foreach ($getEcommerce as $ecommerce) {
                $ary = [];
                $ary['date'] = date('d-m-Y', strtotime($ecommerce['created_on']));
                $ary['product_code'] = $ecommerce['product_code'];
                $ary['product_name'] = $ecommerce['product_name'];
                $ary['category_name'] = $ecommerce['category_name'];
                $ary['mrp'] = $ecommerce['mrp'];
                $ary['selling_price'] = $ecommerce['selling_price'];
                if ($ecommerce['is_publish'] == 1) {
                    $ary['is_publish'] = "Publish";
                } else {
                    $ary['is_publish'] = "Unpublish";
                }
                $overll[] = $ary;
            }
            $s++;

            $excel_report_title = "Ecommerce Product List";

            $spreadsheet = new Spreadsheet();

            //Set document properties
            $spreadsheet->getProperties()->setCreator("Technogenesis")
                ->setLastModifiedBy("Technogenesis")
                ->setTitle("Ecommerce Product List")
                ->setSubject("Ecommerce Product List")
                ->setDescription("Ecommerce Product List")
                ->setKeywords("Ecommerce Product List")
                ->setCategory("Ecommerce Product List");

            $spreadsheet->getProperties()->setCreator("technogenesis.in")
                ->setLastModifiedBy("Technognesis");

            $spreadsheet->setActiveSheetIndex(0);

            $sheet = $spreadsheet->getActiveSheet();

            //name the worksheet
            $sheet->setTitle($excel_report_title);

            $sheet->setCellValue('A1', 'Date');
            $sheet->setCellValue('B1', 'Product ID');
            $sheet->setCellValue('C1', 'Product Name');
            $sheet->setCellValue('D1', 'Category');
            $sheet->setCellValue('E1', 'MRP(₹)');
            $sheet->setCellValue('F1', 'Selling Price (₹)');
            $sheet->setCellValue('G1', 'Publish');

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
            $file_name = "ecommerce-report-data.xls";
            $fullpath = storage_path() . '/app/ecommerce_report' . $file_name;
            $writer->save($fullpath); // download file
            return response()->download(storage_path('app/ecommerce_reportecommerce-report-data.xls'), "ecommerce_report.xls");
        }
    }
}
