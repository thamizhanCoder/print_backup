<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Requests\PersonalizedRequest;
use App\Http\Traits\PersonalizedProductTrait;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\RelatedProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class PersonalizedProductController extends Controller
{

    use PersonalizedProductTrait;

    public function countSummaryPersonalized()
    {
        //Total Products
        $total_count = ProductCatalogue::where('service_id', 4)->whereIn('status', [0, 1])->get();
        $total_count = $total_count->count();
        //Published Products
        $published_count = ProductCatalogue::where('service_id', 4)->where('is_publish', 1)->whereIn('status', [0, 1])->get();
        $published_count = $published_count->count();
        //UnPublished products
        $unpublished_count = ProductCatalogue::where('service_id', 4)->where('is_publish', 2)->whereIn('status', [0, 1])->get();
        $unpublished_count = $unpublished_count->count();
        $count = ['total_count' => $total_count, 'published_count' => $published_count, 'unpublished_count' => $unpublished_count];
        return response()->json([
            'keyword' => 'success',
            'message' => __('Count showed successfully'),
            'data' => [$count],
        ]);
    }

    public function personalizedCreate(PersonalizedRequest $request)
    {
        try {

            $product_variant_message = "";
            $related_product_message = "";

            Log::channel("personalized")->info('** started the personalized create method **');
            $exist = ProductCatalogue::where([['product_name', $request->product_name], ['service_id', 4], ['status', '!=', 2]])->first();

            if (empty($exist)) {

                $personalized = new ProductCatalogue();
                $personalized->product_name = $request->product_name;
                $personalized->service_id = 4;
                $personalized->category_id = $request->category_id;
                $personalized->gst_percentage = $request->gst_percentage;
                $personalized->help_url = $request->help_url;
                $personalized->label_name_details = $request->label_name_details;

                //Thumbnail Image validation starts
                $thumnailValidation = $this->thumbnailValidation($request->thumbnail_image);
                if ($thumnailValidation == "true") {

                    $personalized->thumbnail_image = $request->thumbnail_image;
                } else {

                    return response()->json([
                        'keyword' => 'failed',
                        'message' => $thumnailValidation,
                        'data' => [],
                    ]);
                }
                //Thumbnail Image validation end

                $personalized->customer_description = $request->customer_description;
                $personalized->designer_description = $request->designer_description;

                $personalized->is_cod_available = $request->is_cod_available;
                $personalized->is_related_product_available = $request->is_related_product_available;
                $personalized->is_multivariant_available = $request->is_multivariant_available;
                $personalized->is_customized = $request->is_customized;
                $personalized->is_notification = $request->is_notification;
                $personalized->is_colour = $request->is_colour;
                $personalized->selected_variants = $request->selected_variants;

                //primary detail validation start

                if (!empty($request->primary_variant_details)) {

                    $primary_variant_details_insert = $this->validateSelectedVarients($request->primary_variant_details);

                    if ($primary_variant_details_insert == "true") {

                        $personalized->primary_variant_details = $request->primary_variant_details;
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

                // if ($personalized->is_multivariant_available == 1) {

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

                $personalized->created_on = Server::getDateTime();
                $personalized->created_by = JwtHelper::getSesUserId();

                $relatedProductJsonString = $request->related_products;

                Log::channel("personalized")->info("request value :: $personalized->personalized");

                if ($personalized->save()) {
                    $serviceDetailsCount = ProductCatalogue::where('service_id', $personalized->service_id)->get();
                    $service_count = $serviceDetailsCount->count();
                    $product_code = env('PREFIX') . '_' . str_pad($personalized->product_id, 3, '0', STR_PAD_LEFT) . '_' . str_pad($service_count, 3, '0', STR_PAD_LEFT);
                    $update_productdetails = ProductCatalogue::find($personalized->product_id);
                    $update_productdetails->product_code = $product_code;
                    $update_productdetails->save();

                    // insert product variant
                    if (!empty($varientDetails)) {

                        $product_variant = $this->productVariantInsert($varientDetails, $personalized->product_id);

                        if ($product_variant == true) {

                            $product_variant_message = "Prodcut varinants Successfullly inserted in sub table";
                            Log::channel("personalized")->info($product_variant_message);
                        } else {

                            $product_variant_message = "Product Varient Sub table records not inserted correctly";
                            Log::channel("personalized")->info($product_variant_message);
                        }
                    }

                    $personalized_details = ProductCatalogue::where('product_id', $personalized->product_id)
                        ->select('product.*')
                        ->first();

                    // save related products
                    if ($personalized->is_related_product_available == 1) {
                        $relatedArr = json_decode($relatedProductJsonString, true);
                        $saveRelatedProduct = $this->saverelatedProducts($relatedArr, $personalized_details->product_id);

                        if ($saveRelatedProduct == true) {

                            $related_product_message = "Related products Successfullly inserted in sub table";
                            Log::channel("personalized")->info($related_product_message);
                        } else {

                            $related_product_message = "Related products Sub table records not inserted correctly";
                            Log::channel("personalized")->info($related_product_message);
                        }
                    }
                    Log::channel("personalized")->info("** personalized save details : $personalized_details **");

                    // log activity
                    $desc = 'personalized ' . $personalized->personalized . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Personalized Products');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);



                    Log::channel("personalized")->info("save value :: $personalized_details");
                    Log::channel("personalized")->info('** end the passportsizephoto create method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product created successfully'),
                        'data' => [$personalized_details],
                        'product_varient_message' => $product_variant_message,
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
            Log::channel("personalized")->error($exception);
            Log::channel("personalized")->error('** end the personalized create method **');

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

    public function personalizedUpdate(PersonalizedRequest $request)
    {
        try {

            $product_variant_message = "";
            $related_product_message = "";

            Log::channel("personalized")->info('** started the personalized update method **');
            $exist = ProductCatalogue::where([['product_id', '!=', $request->product_id], ['product_name', $request->product_name], ['service_id', 4], ['status', '!=', 2]])->first();

            if (empty($exist)) {
                $id = $request->product_id;
                $personalized = ProductCatalogue::find($id);
                $personalized->product_name = $request->product_name;
                $personalized->service_id = 4;
                $personalized->gst_percentage = $request->gst_percentage;
                $personalized->category_id = $request->category_id;
                $personalized->help_url = $request->help_url;
                $personalized->label_name_details = $request->label_name_details;

                //Thumbnail Image validation starts
                $thumnailValidation = $this->thumbnailValidation($request->thumbnail_image);
                if ($thumnailValidation == "true") {

                    $personalized->thumbnail_image = $request->thumbnail_image;
                } else {

                    return response()->json([
                        'keyword' => 'failed',
                        'message' => $thumnailValidation,
                        'data' => [],
                    ]);
                }
                //Thumbnail Image validation end

                $personalized->customer_description = $request->customer_description;
                $personalized->designer_description = $request->designer_description;

                $personalized->is_cod_available = $request->is_cod_available;
                $personalized->is_related_product_available = $request->is_related_product_available;
                $personalized->is_multivariant_available = $request->is_multivariant_available;
                $personalized->is_customized = $request->is_customized;
                $personalized->is_notification = $request->is_notification;
                $personalized->is_colour = $request->is_colour;
                $personalized->selected_variants = $request->selected_variants;

                //primary detail validation start

                if (!empty($request->primary_variant_details)) {

                    $primary_variant_details_insert = $this->validateSelectedVarients($request->primary_variant_details);

                    if ($primary_variant_details_insert == "true") {

                        $personalized->primary_variant_details = $request->primary_variant_details;
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

                // if ($personalized->is_multivariant_available == 1) {

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

                $personalized->created_on = Server::getDateTime();
                $personalized->created_by = JwtHelper::getSesUserId();

                $relatedProductJsonString = $request->related_products;

                Log::channel("personalized")->info("request value :: $personalized->personalized");

                if ($personalized->save()) {

                    // insert product variant
                    if (!empty($varientDetails)) {

                        $product_variant = $this->productVariantUpdate($varientDetails, $personalized->product_id, $personalized->product_name, $personalized->customer_description);

                        if ($product_variant == true) {

                            $product_variant_message = "Product varinants Successfullly updated in sub table";
                            Log::channel("personalized")->info($product_variant_message);
                        } else {

                            $product_variant_message = "Product Variant Sub table records not updated correctly";
                            Log::channel("personalized")->info($product_variant_message);
                        }
                    }

                    $personalized_details = ProductCatalogue::where('product_id', $personalized->product_id)
                        ->select('product.*')
                        ->first();

                    // save related products
                    if ($personalized->is_related_product_available == 1) {
                        $delete = RelatedProduct::where('product_id', $personalized_details->product_id)->delete();
                        $relatedArr = json_decode($relatedProductJsonString, true);
                        $saveRelatedProduct = $this->saverelatedProducts($relatedArr, $personalized_details->product_id);

                        if ($saveRelatedProduct == true) {

                            $related_product_message = "Related products Successfullly updated in sub table";
                            Log::channel("personalized")->info($related_product_message);
                        } else {

                            $related_product_message = "Related products Sub table records not updated correctly";
                            Log::channel("personalized")->info($related_product_message);
                        }
                    }

                    //delete related products
                    if ($personalized_details->is_related_product_available == 0) {
                        $delete = RelatedProduct::where('product_id', $personalized_details->product_id)->delete();
                    }

                    //Delete product variant id
                    if ($request->del_productvariantId != '') {
                        $deleteIds = json_decode($request->del_productvariantId, true);
                        if (!empty($deleteIds)) {
                            ProductVariant::whereIn('product_variant_id', $deleteIds)->delete();
                        }
                    }

                    if ($personalized_details->is_publish == 1 && $personalized_details->is_notification == 1) {
                        $pushNotification = $this->productCreatePushNotification($personalized_details->product_id);
                    }

                    Log::channel("personalized")->info("** personalized save details : $personalized_details **");

                    // log activity
                    $desc = $personalized->product_name . ' product was updated by ' . JwtHelper::getSesUserNameWithType() . ' ';
                    $activitytype = Config('activitytype.Personalized Products');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("personalized")->info("save value :: $personalized_details");
                    Log::channel("personalized")->info('** end the personalized update method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product updated successfully'),
                        'data' => [$personalized_details],
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
            Log::channel("personalized")->error($exception);
            Log::channel("personalized")->error('** end the personalized update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function personalizedView($id)
    {
        try {
            Log::channel("personalized")->info('** started the personalized view method **');
            if ($id != '' && $id > 0) {
                $personalized = ProductCatalogue::where('product.product_id', $id)
                    ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
                    ->select('product.*', 'gst_percentage.gst_percentage as gst', 'category.category_name')->first();

                Log::channel("personalized")->info("request value product_id:: $id");

                if (!empty($personalized)) {
                    $final = [];
                    $ary = [];
                    $ary['product_id'] = $personalized['product_id'];
                    $ary['product_name'] = $personalized['product_name'];
                    $ary['category_id'] = $personalized['category_id'];
                    $ary['category_name'] = $personalized['category_name'];
                    $ary['label_name_details'] = $personalized['label_name_details'];
                    $ary['gst_percentage_id'] = $personalized['gst_percentage'];
                    $ary['gst'] = $personalized['gst'];
                    $ary['help_url'] = $personalized['help_url'];
                    $ary['primary_variant_details'] = $this->getPrimaryVariantDetails($personalized['primary_variant_details']);
                    $ary['variant_details'] = $this->getVariantDetails($personalized['product_id']);
                    $ary['selected_variants'] = $this->getGlobelVariantDetails(json_decode($personalized['selected_variants'], true));
                    $ary['thumbnail_url'] = ($personalized['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $personalized['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $personalized['thumbnail_image'];
                    $ary['customer_description'] = $personalized['customer_description'];
                    $ary['designer_description'] = $personalized['designer_description'];
                    $ary['is_cod_available'] = $personalized['is_cod_available'];
                    $ary['is_customized'] = $personalized['is_customized'];
                    $ary['is_colour'] = $personalized['is_colour'];
                    $ary['is_multivariant_available'] = $personalized['is_multivariant_available'];
                    $ary['is_related_product_available'] = $personalized['is_related_product_available'];
                    $ary['is_notification'] = $personalized['is_notification'];
                    $ary['created_on'] = $personalized['created_on'];
                    $ary['created_by'] = $personalized['created_by'];
                    $ary['updated_on'] = $personalized['updated_on'];
                    $ary['updated_by'] = $personalized['updated_by'];
                    $ary['status'] = $personalized['status'];
                    $ary['related_products'] = $this->getrelated_products($personalized->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("personalized")->info("view value :: $log");
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
            Log::channel("Personalized")->error($exception);
            Log::channel("Personalized")->info('** end the Personalized view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function personalizedList(Request $request)
    {
        try {
            Log::channel("Personalized")->info('** started the Personalized list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByCategory = ($request->filterByCategory) ? $request->filterByCategory : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';

            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'product_id' => 'product.product_id',
                'category_name' => 'category.category_name',
                'category_name' => 'category.category_name',
                'created_on' => 'product.created_on',
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
                'product.mrp', 'category.category_name',
            );

            $Personalized = ProductCatalogue::select(
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
                ->where('product.service_id', 4)
                ->where('product.status', '!=', '2')
                ->groupBy('product.product_id');

            $Personalized->where(function ($query) use (
                $searchval,
                $column_search,
                $Personalized
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
                $Personalized->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $Personalized->where(function ($query) use ($from_date) {
                    $query->whereDate('product.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $Personalized->where(function ($query) use ($to_date) {
                    $query->whereDate('product.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $Personalized->where('product.is_publish', $filterByStatus);
            }

            if (!empty($filterByCategory)) {
                $Personalized->where('product.category_id', $filterByCategory);
            }
            $count = count($Personalized->get());

            if ($offset) {
                $offset = $offset * $limit;
                $Personalized->offset($offset);
            }
            if ($limit) {
                $Personalized->limit($limit);
            }

            $Personalized->orderBy('product.product_id', 'desc');

            $Personalized = $Personalized->get();
            $final = [];

            if ($count > 0) {
                foreach ($Personalized as $value) {
                    $ary = [];
                    $ary['product_id'] = $value['product_id'];
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
                Log::channel("Personalized")->info("list value :: $log");
                Log::channel("Personalized")->info('** end the Personalized list method **');
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
            Log::channel("Personalized")->error($exception);
            Log::channel("Personalized")->error('** end the Personalized list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function personlaizedPublishStatus(Request $request)
    {
        try {
            Log::channel("Personalized")->info('** started the Personalized publish status method **');
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

                    $personalizedDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($personalizedDetails)){
                        foreach($personalizedDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $personalizedDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($personalizedDetailsCount > 0){
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

                    $personalizedDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($personalizedDetails)){
                        foreach($personalizedDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $personalizedDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($personalizedDetailsCount > 0){
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
                $desc = $productdetails->product_name . ' product was ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . ' ';
                $activitytype = Config('activitytype.Personalized Products');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                if ($request->is_publish == 2) {
                    Log::channel("Personalized")->info("Personalized product unpublished successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product unpublished successfully'),
                        'data' => [],
                    ]);
                } else if ($request->is_publish == 1) {
                    if ($productdetails->is_publish == 1 && $productdetails->is_notification == 1) {
                        $pushNotification = $this->productCreatePushNotification($productdetails->product_id);
                    }
                    Log::channel("Personalized")->info("Personalized product published successfull");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product published successfully'),
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
            Log::channel("Personalized")->error($exception);
            Log::channel("Personalized")->info('** end the Personalized publish status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function personalizedStatus(Request $request)
    {
        try {
            Log::channel("Personalized")->info('** started the Personalized status method **');

            $ids = $request->id;

            if (!empty($ids)) {

                $exist = RelatedProduct::where('product_id_related', $ids)->where('status', '!=', 2)->first();
                if (empty($exist)) {

                    Log::channel("Personalized")->info('** started the Personalized status method **');

                    $Personalized = ProductCatalogue::where('product_id', $ids)->first();
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

                    $desc = $Personalized->product_name . ' product was ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . ' ';
                    $activitytype = Config('activitytype.Personalized Products');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {
                        Log::channel("Personalized")->info("Personalized product inactive successfull");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Product inactivated successfully'),
                            'data' => [],
                        ]);
                    } else if ($request->status == 1) {
                        Log::channel("Personalized")->info("Personalized product active successfull");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Product activated successfully'),
                            'data' => [],
                        ]);
                    } else if ($request->status == 2) {
                        Log::channel("Personalized")->info("Personalized product delete successfull");
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
            Log::channel("Personalized")->error($exception);
            Log::channel("Personalized")->info('** end the Personalized status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function personalizedExcel(Request $request)
    {
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $all = $request->all;

        $getPersonalized = ProductCatalogue::select('category.category_name', 'product.product_id', 'product.created_on', 'product.product_code', 'product.product_name', 'product_variant.selling_price', 'product_variant.mrp', 'product.is_publish', 'product.status')
            ->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', '=', 1);
            })->leftjoin('category', 'category.category_id', '=', 'product.category_id')
            ->where('product.service_id', 4)->where('product.status', '!=', '2')->groupBy('product.product_id');

        if (!empty($from_date)) {
            $getPersonalized->where(function ($query) use ($from_date) {
                $query->whereDate('product.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $getPersonalized->where(function ($query) use ($to_date) {
                $query->whereDate('product.created_on', '<=', $to_date);
            });
        }
        if (!empty($filterByStatus)) {
            $getPersonalized->where('product.is_publish', $filterByStatus);
        }

        $getPersonalized = $getPersonalized->get();

        $count = count($getPersonalized);

        $s = 1;
        if (!empty($getPersonalized)) {
            $overll = [];
            foreach ($getPersonalized as $personalized) {
                $ary = [];
                $ary['date'] = date('d-m-Y', strtotime($personalized['created_on']));
                $ary['product_code'] = $personalized['product_code'];
                $ary['product_name'] = $personalized['product_name'];
                $ary['category_name'] = $personalized['category_name'];
                $ary['mrp'] = $personalized['mrp'];
                $ary['selling_price'] = $personalized['selling_price'];
                if ($personalized['is_publish'] == 1) {
                    $ary['is_publish'] = "Publish";
                } else {
                    $ary['is_publish'] = "Unpublish";
                }
                $overll[] = $ary;
            }
            $s++;

            $excel_report_title = "Personalized Product List";

            $spreadsheet = new Spreadsheet();

            //Set document properties
            $spreadsheet->getProperties()->setCreator("Technogenesis")
                ->setLastModifiedBy("Technogenesis")
                ->setTitle("Personalized Product List")
                ->setSubject("Personalized Product List")
                ->setDescription("Personalized Product List")
                ->setKeywords("Personalized Product List")
                ->setCategory("Personalized Product List");

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
            $file_name = "photoframe-report-data.xls";
            $fullpath = storage_path() . '/app/photoframe_report' . $file_name;
            $writer->save($fullpath); // download file
            return response()->download(storage_path('app/photoframe_reportphotoframe-report-data.xls'), "photoframe_report.xls");
        }
    }
}
