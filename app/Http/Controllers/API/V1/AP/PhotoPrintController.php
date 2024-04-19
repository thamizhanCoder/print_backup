<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Http\Requests\PhotoPrintRequest;
use App\Models\Customer;
use App\Models\GstPercentage;
use App\Models\Product;
use App\Models\ProductCatalogue;
use App\Models\RelatedProduct;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class PhotoPrintController extends Controller
{
    public function getphotoprint_totalcount()
    {
        //Total Products
        $total_count = ProductCatalogue::where('service_id', 2)->whereIn('status', [0, 1])->get();
        $total_count = $total_count->count();
        //total paid ads
        $published_count = ProductCatalogue::where('service_id', 2)->where('is_publish', 1)->whereIn('status', [0, 1])->get();
        $published_count = $published_count->count();
        //Wanted ads
        $unpublished_count = ProductCatalogue::where('service_id', 2)->where('is_publish', 2)->whereIn('status', [0, 1])->get();
        $unpublished_count = $unpublished_count->count();
        $count = ['total_count' => $total_count, 'published_count' => $published_count, 'unpublished_count' => $unpublished_count];
        return response()->json([
            'keyword' => 'success',
            'message' => __('Count successfully'),
            'data' => [$count]
        ]);
    }
    public function photoprint_create(PhotoPrintRequest $request)
    {

        try {
            Log::channel("photoprint")->info("** started the photoprint create method **");

            //thumbnail_image
            if ($request->input('thumbnail_image') != '') {


                $Extension =  pathinfo($request->input('thumbnail_image'), PATHINFO_EXTENSION);

                $extension_ary = ['jpeg', 'png', 'jpg', 'webp'];

                if (in_array($Extension, $extension_ary)) {

                    //product_image
                    $gTImage = json_decode($request->product_image, true);

                    if (!empty($gTImage)) {
                        foreach ($gTImage as $im) {
                            $ary[] = pathinfo($im['image'], PATHINFO_EXTENSION);
                        }
                    }
                    $extension_array = ['jpeg', 'png', 'jpg', 'webp'];

                    if (!array_diff($ary, $extension_array)) {
                        // print_r($ary);exit;
                        $exits = ProductCatalogue::where([
                            ['product_name', $request->product_name],['service_id', '=', 2], ['status', '!=', 2]
                        ])->first();

                        if (empty($exits)) {

                            $photoprint = new ProductCatalogue();
                            $photoprint->service_id = 2;
                            // $photoprint->category_id = $request->category_id;
                            $photoprint->product_name = $request->product_name;
                            $photoprint->print_size = $request->print_size;
                            $photoprint->mrp = $request->mrp;
                            $photoprint->first_copy_selling_price = $request->first_copy_selling_price;
                            $photoprint->additional_copy_selling_price = $request->additional_copy_selling_price;
                            $photoprint->gst_percentage = $request->gst_percentage;
                            $photoprint->help_url = $request->help_url;
                            $photoprint->product_image = $request->product_image;
                            $photoprint->thumbnail_image = $request->thumbnail_image;
                            $photoprint->customer_description = $request->customer_description;
                            $photoprint->designer_description = $request->designer_description;
                            $photoprint->is_cod_available = $request->is_cod_available;
                            $photoprint->is_notification = $request->is_notification;
                            $photoprint->is_related_product_available = $request->is_related_product_available;
                            $photoprint->weight = $request->weight;
                            $photoprint->created_on = Server::getDateTime();
                            $photoprint->created_by = JwtHelper::getSesUserId();
                            $relatedProductJsonString = $request->input('related_products');
                            if ($photoprint->save()) {
                                $serviceDetailsCount = ProductCatalogue::where('service_id', $photoprint->service_id)->get();
                                $service_count = $serviceDetailsCount->count();
                                $product_code = env('PREFIX').'_' . str_pad($photoprint->product_id, 3, '0', STR_PAD_LEFT) . '_' . str_pad($service_count, 3, '0', STR_PAD_LEFT);
                                $update_productdetails = ProductCatalogue::find($photoprint->product_id);
                                $update_productdetails->product_code = $product_code;
                                $update_productdetails->save();

                                $photoprints = ProductCatalogue::where('product_id', $photoprint->product_id)
                                    ->select('product.*')
                                    ->first();

                                // save related products
                                if ($photoprints->is_related_product_available == 1) {
                                    $relatedArr = json_decode($relatedProductJsonString, TRUE);
                                    $this->saverelatedProducts($relatedArr, $photoprints->product_id);
                                }
                                Log::channel("photoprint")->info("** photoprint save details : $photoprints **");
                                // log activity
                                $desc =  'Product print ' . '(' . $photoprints->product_name . ')' . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                                $activitytype = Config('activitytype.Photo Print');
                                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                                Log::channel("photoprint")->info("** end the category create method **");

                               

                                return response()->json([
                                    'keyword'      => 'success',
                                    'message'      => __('Product created successfully'),
                                    'data'        => [$photoprints]

                                ]);
                            } else {
                                return response()->json([
                                    'keyword'      => 'failure',
                                    'message'      => __('Product creation failed'),
                                    'data'        => []
                                ]);
                            }
                        } else {
                            return response()->json([
                                'keyword' => 'failed',
                                'message'      => __('Product name already exist'),
                                'data'        => []
                            ]);
                        }
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'message'      => ('Only JPG,JPEG,PNG,WEBP formats allowed for image'),
                            'data'        => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG,WEBP formats allowed for image'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => 'Image field is required',
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("photoprint")->error($exception);
            Log::channel("photoprint")->error('** end the photoprint create method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function saverelatedProducts($relatedpro, $product_id)
    {

        foreach ($relatedpro as $proId) {

            $newRelatedPro = new RelatedProduct();
            $newRelatedPro->product_id = $product_id;
            $newRelatedPro->service_id = $proId['service_id'];
            $newRelatedPro->product_id_related = $proId['product_id_related'];
            $newRelatedPro->created_on = Server::getDateTime();
            $newRelatedPro->created_by = JwtHelper::getSesUserId();
            $newRelatedPro->save();
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

    public function photoprint_update(PhotoPrintRequest $request)
    {

        try {
            Log::channel("photoprint")->info("** started the photoprint update method **");
            //thumbnail_image
            if ($request->input('thumbnail_image') != '') {


                $Extension =  pathinfo($request->input('thumbnail_image'), PATHINFO_EXTENSION);

                $extension_ary = ['jpeg', 'png', 'jpg', 'webp'];

                if (in_array($Extension, $extension_ary)) {

                    //product_image
                    $gTImage = json_decode($request->product_image, true);

                    if (!empty($gTImage)) {
                        foreach ($gTImage as $im) {
                            $ary[] = pathinfo($im['image'], PATHINFO_EXTENSION);
                        }
                    }
                    $extension_array = ['jpeg', 'png', 'jpg', 'webp'];

                    if (!array_diff($ary, $extension_array)) {

                        $exits = ProductCatalogue::where([
                            ['product_id', '!=', $request->product_id],
                            ['product_name', $request->product_name],
                            ['service_id', '=', 2],
                            ['status', '!=', 2]
                        ])->first();

                        if (empty($exits)) {
                            $id = $request->product_id;
                            $photoprint = ProductCatalogue::find($id);
                            $photoprint->service_id = 2;
                            // $photoprint->category_id = $request->category_id;
                            $photoprint->product_name = $request->product_name;
                            $photoprint->print_size = $request->print_size;
                            $photoprint->mrp = $request->mrp;
                            $photoprint->first_copy_selling_price = $request->first_copy_selling_price;
                            $photoprint->additional_copy_selling_price = $request->additional_copy_selling_price;
                            $photoprint->gst_percentage = $request->gst_percentage;
                            $photoprint->help_url = $request->help_url;
                            $photoprint->product_image = $request->product_image;
                            $photoprint->thumbnail_image = $request->thumbnail_image;
                            $photoprint->customer_description = $request->customer_description;
                            $photoprint->designer_description = $request->designer_description;
                            $photoprint->is_cod_available = $request->is_cod_available;
                            $photoprint->is_notification = $request->is_notification;
                            $photoprint->is_related_product_available = $request->is_related_product_available;
                            $photoprint->weight = $request->weight;
                            $photoprint->created_on = Server::getDateTime();
                            $photoprint->created_by = JwtHelper::getSesUserId();
                            $relatedProductJsonString = $request->input('related_products');
                            if ($photoprint->save()) {

                                $photoprints = ProductCatalogue::where('product_id', $photoprint->product_id)
                                    ->select('product.*')
                                    ->first();

                                // save related products
                                if ($photoprints->is_related_product_available == 1) {
                                    $delete = RelatedProduct::where('product_id', $photoprints->product_id)->delete();
                                    $relatedArr = json_decode($relatedProductJsonString, TRUE);
                                    $this->saverelatedProducts($relatedArr, $photoprints->product_id);
                                }

                                if ($photoprints->is_related_product_available == 0) {
                                    $delete = RelatedProduct::where('product_id', $photoprints->product_id)->delete();
                                }
                                Log::channel("photoprint")->info("** photoprint save details : $photoprints **");
                                // log activity
                                $desc =  $photoprints->product_name . ' product was updated by ' . JwtHelper::getSesUserNameWithType() . ' ';
                                $activitytype = Config('activitytype.Photo Print');
                                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                                Log::channel("photoprint")->info("** end the photoprint update method **");

                                if ($photoprints->is_publish == 1 &&  $photoprints->is_notification == 1) {
                                    $pushNotification = $this->productCreatePushNotification($photoprints->product_id);
                                    }

                                return response()->json([
                                    'keyword'      => 'success',
                                    'message'      => __('Product updated successfully'),
                                    'data'        => [$photoprints]

                                ]);
                            } else {
                                return response()->json([
                                    'keyword'      => 'failure',
                                    'message'      => __('Product update failed'),
                                    'data'        => []
                                ]);
                            }
                        } else {
                            return response()->json([
                                'keyword' => 'failed',
                                'message'      => __('Product name already exist'),
                                'data'        => []
                            ]);
                        }
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'message'      => ('Only JPG,JPEG,PNG,WEBP formats allowed for image'),
                            'data'        => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG,WEBP formats allowed for image'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => 'Image field is required',
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("photoprint")->error($exception);
            Log::channel("photoprint")->error('** end the photoprint create method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function photoprint_list(Request $request)
    {
        try {
            Log::channel("photoprint")->info("** started the photoprint list method **");
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';

            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'created_on' => 'product.created_on',
                'product_code' => 'product.product_code',
                'product_name' => 'product.product_name',
                'first_copy_selling_price' => 'product.first_copy_selling_price',
                'additional_copy_selling_price' => 'product.additional_copy_selling_price',
                'mrp' => 'product.mrp',
            ];

            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "product_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

            $column_search = array('product.created_on', 'product.product_code', 'product.product_name', 'product.first_copy_selling_price', 'product.additional_copy_selling_price', 'product.mrp');

            $getPhotoPrint = ProductCatalogue::where('service_id', 2)->where('status', '!=', 2);

            $getPhotoPrint->where(function ($query) use ($searchval, $column_search, $getPhotoPrint) {
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
                $getPhotoPrint->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $getPhotoPrint->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $getPhotoPrint->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $getPhotoPrint->where('is_publish', $filterByStatus);
            }

            $count = count($getPhotoPrint->get());

            if ($offset) {
                $offset = $offset * $limit;
                $getPhotoPrint->offset($offset);
            }

            if ($limit) {
                $getPhotoPrint->limit($limit);
            }

            $getPhotoPrint->orderBy('product_id', 'desc');
            $getPhotoPrint = $getPhotoPrint->get();


            $final = [];
            if ($count > 0) {
                foreach ($getPhotoPrint as $photoprint) {
                    $ary = [];
                    $ary['product_id'] = $photoprint['product_id'];
                    $ary['product_code'] = $photoprint['product_code'];
                    $ary['date'] = date('d-m-Y', strtotime($photoprint['created_on']));
                    $ary['product_name'] = $photoprint['product_name'];
                    $ary['first_copy_selling_price'] = $photoprint['first_copy_selling_price'];
                    $ary['additional_copy_selling_price'] = $photoprint['additional_copy_selling_price'];
                    $ary['mrp'] = $photoprint['mrp'];
                    $ary['is_publish'] = $photoprint['is_publish'];
                    $ary['status'] = $photoprint['status'];
                    $final[] = $ary;
                }
            }

            if (!empty($getPhotoPrint)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Product listed successfully',
                    'data' => $final,
                    'count' => $count
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("photoprint")->error($exception);
            Log::channel("photoprint")->error('** end the photoprint list method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function photoprint_view($id)
    {
        try {
            Log::channel("photoprint")->info('** started the photoprint view method **');
            if ($id != '' && $id > 0) {
                $photoprint = ProductCatalogue::where('product.product_id', $id)->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'product.print_size')->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')->select('product.*', 'photo_print_setting.width', 'photo_print_setting.height', 'gst_percentage.gst_percentage as gst')->first();
                Log::channel("photoprint")->info("request value photoprint_id:: $id");
                $count = $photoprint->count();
                if ($count > 0) {
                    $final = [];
                    $ary = [];
                    $ary['product_id'] = $photoprint['product_id'];
                    $ary['product_name'] = $photoprint['product_name'];
                    $ary['photo_print_id'] = $photoprint['print_size'];
                    $ary['print_size'] = $photoprint['width'] . '*' . $photoprint['height'];
                    $ary['mrp'] = $photoprint['mrp'];
                    $ary['first_copy_selling_price'] = $photoprint['first_copy_selling_price'];
                    $ary['additional_copy_selling_price'] = $photoprint['additional_copy_selling_price'];
                    $ary['gst_percentage_id'] = $photoprint['gst_percentage'];
                    $ary['gst'] = $photoprint['gst'];
                    $ary['help_url'] = $photoprint['help_url'];
                    $gTImage = json_decode($photoprint['product_image'], true);
                    $ary['product_image'] = $this->getdefaultImages_allImages($gTImage);
                    $ary['thumbnail_url'] = ($photoprint['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $photoprint['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $photoprint['thumbnail_image'];
                    $ary['customer_description'] = $photoprint['customer_description'];
                    $ary['designer_description'] = $photoprint['designer_description'];
                    $ary['is_cod_available'] = $photoprint['is_cod_available'];
                    $ary['is_notification'] = $photoprint['is_notification'];
                    $ary['is_related_product_available'] = $photoprint['is_related_product_available'];

                    $ary['created_on'] = $photoprint['created_on'];
                    $ary['created_by'] = $photoprint['created_by'];
                    $ary['updated_on'] = $photoprint['updated_on'];
                    $ary['updated_by'] = $photoprint['updated_by'];
                    $ary['status'] = $photoprint['status'];
                    $ary['weight'] = $photoprint['weight'];
                    $ary['related_products'] = $this->getrelated_products($photoprint->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("photoprint")->info("view value :: $log");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product viewed successfully'),
                        'data' => $final
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {
            Log::channel("photoprint")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function getdefaultImages_allImages($gTImage)
    {
        $imG = [];
        if (!empty($gTImage)) {
            foreach ($gTImage as $im) {
                $ary = [];
                $ary['index'] = $im['index'];
                $ary['url'] = ($im['image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $im['image'] : env('APP_URL') . "avatar.jpg";
                $ary['image'] = $im['image'];
                $imG[] = $ary;
            }
        }
        return $imG;
    }

    public function getrelated_products($proId)
    {
        $related = RelatedProduct::where('p.is_related_product_available', 1)->where('related_products.product_id', $proId)->where('related_products.status','!=', 2)
            ->select('service.service_name', 'product.product_name', 'product.mrp', 'product.selling_price', 'product.first_copy_selling_price', 'related_products.service_id', 'related_products.product_id_related', 'product.thumbnail_image')
            ->leftjoin('service', 'service.service_id', '=', 'related_products.service_id')
            ->leftjoin('product as p', 'p.product_id', '=', 'related_products.product_id')
            ->leftjoin('product', 'product.product_id', '=', 'related_products.product_id_related')->get();
        $RelatedPro = [];
        if (!empty($related)) {
            foreach ($related as $rp) {
                $ary = [];
                $ary['service_name'] = $rp['service_name'];
                $ary['product_name'] = $rp['product_name'];
                $ary['service_id'] = $rp['service_id'];
                $ary['product_id_related'] = $rp['product_id_related'];
                if ($rp['service_id'] == 1) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $rp['mrp'];
                    $ary['selling_price'] = $rp['selling_price'];
                }
                if ($rp['service_id'] == 2) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $rp['mrp'];
                    $ary['selling_price'] = $rp['first_copy_selling_price'];
                }
                if ($rp['service_id'] == 3) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                }
                if ($rp['service_id'] == 4) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                }
                if ($rp['service_id'] == 5) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                }
                if ($rp['service_id'] == 6) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                }
                $ary['thumbnail_image'] = $rp['thumbnail_image'];
                $RelatedPro[] = $ary;
            }
        }
        return $RelatedPro;
    }

    public function photoframeProductAmountDetails($id, $slug)
    {

        $value = "";

        $amountDetails = ProductCatalogue::where('product.product_id', $id)->select('product.product_id', 'product.created_on', 'product.product_code', 'product.product_name', 'product_variant.selling_price', 'product_variant.mrp', 'product.is_publish', 'product.status')
            ->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', '=', 1);
            })->where('product.status', 1)->first();

        if (!empty($amountDetails)) {

            if ($slug == "mrp") {

                $value = $amountDetails->mrp;
            }

            if ($slug == "selling") {

                $value = $amountDetails->selling_price;
            }
        } else {

            $value = 0;
        }

        return $value;
    }

    public function photoprint_status(Request $request)
    {
        try {
            Log::channel("photoprint")->info('** started the photoprint status method **');
            // if (!empty($request)) {
            $ids = $request->id;
            if (!empty($ids)) {
                
                $exist = RelatedProduct::where('product_id_related', $ids)->where('status', '!=', 2)->first();
                if (empty($exist)) {

                Log::channel("photoprint")->info('** started the photoprint status method **');



                $photoprint = ProductCatalogue::where('product_id', $ids)->first();
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

                // log activity
                if ($request->status == 0) {
                    $activity_status = 'inactivated';
                } else if ($request->status == 1) {
                    $activity_status = 'activated';
                } else if ($request->status == 2) {
                    $activity_status = 'deleted';
                }
                // $implode = implode(",", $ids);
                $desc = $photoprint->product_name . ' product was ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . ' ';

                $activitytype = Config('activitytype.Photo Print');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                if ($request->status == 0) {
                    Log::channel("photoprint")->info("Photo Print inactive successfull");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product inactivated successfully'),
                        'data' => []
                    ]);
                } else if ($request->status == 1) {
                    Log::channel("photoprint")->info("Photo Print active successfull");
                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  __('Product activated successfully'),
                        'data' => []
                    ]);
                } else if ($request->status == 2) {
                        Log::channel("photoprint")->info("Photo Print delete successfull");
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
            Log::channel("photoprint")->error($exception);
            Log::channel("photoprint")->info('** end the photoprint status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function photoprintPublish_status(Request $request)
    {
        try {
            Log::channel("photoprint")->info('** started the photoprint publish status method **');
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

                    $photoprintDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($photoprintDetails)){
                        foreach($photoprintDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $photoprintDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($photoprintDetailsCount > 0){
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

                    $photoprintDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($photoprintDetails)){
                        foreach($photoprintDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $photoprintDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($photoprintDetailsCount > 0){
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
                $activitytype = Config('activitytype.Photo Print');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                if ($request->is_publish == 2) {
                    Log::channel("photoprint")->info("Photo Print unpublished successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product unpublished successfully'),
                        'data' => []
                    ]);
                } else if ($request->is_publish == 1) {
                    if ($productdetails->is_publish == 1 &&  $productdetails->is_notification == 1) {
                    $pushNotification = $this->productCreatePushNotification($productdetails->product_id);
                    }
                    Log::channel("photoprint")->info("Photo Print published successfull");
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
            Log::channel("photoprint")->error($exception);
            Log::channel("photoprint")->info('** end the photoprint publish status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function photoprint_excel(Request $request)
    {
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $all = $request->all;

        $getPhotoPrint = ProductCatalogue::where('service_id', 2)->where('status', '!=', 2);


        if (!empty($from_date)) {
            $getPhotoPrint->where(function ($query) use ($from_date) {
                $query->whereDate('created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $getPhotoPrint->where(function ($query) use ($to_date) {
                $query->whereDate('created_on', '<=', $to_date);
            });
        }
        if (!empty($filterByStatus)) {
            $getPhotoPrint->where('is_publish', $filterByStatus);
        }

        $getPhotoPrint = $getPhotoPrint->get();

        $count = count($getPhotoPrint);

        $s = 1;
        if ($count > 0) {
            $overll = [];
            foreach ($getPhotoPrint as $photoprint) {
                $ary = [];
                $ary['date'] = date('d-m-Y', strtotime($photoprint['created_on']));
                $ary['product_code'] = $photoprint['product_code'];
                $ary['product_name'] = $photoprint['product_name'];
                $ary['mrp'] = $photoprint['mrp'];
                $ary['first_copy_selling_price'] = $photoprint['first_copy_selling_price'];
                $ary['additional_copy_selling_price'] = $photoprint['additional_copy_selling_price'];
                if ($photoprint['is_publish'] == 1) {
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
            $sheet->setCellValue('E1', '1st Copy Selling Price (₹)');
            $sheet->setCellValue('F1', 'Additional Copy Selling Price (₹)');
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
            $file_name = "photoprint-report-data.xls";
            $fullpath =  storage_path() . '/app/photoprint_report' . $file_name;
            // print_r($fullpath);exit;
            $writer->save($fullpath); // download file
            // return $file_name;
            // return response()->download($fullpath, "sales_report.xls");
            return response()->download(storage_path('app/photoprint_reportphotoprint-report-data.xls'), "photoprint_reportphotoprint-report-data.xls");
        }
    }
}
