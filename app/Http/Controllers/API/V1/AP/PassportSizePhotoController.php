<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Firebase;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Models\ProductCatalogue;
use App\Models\RelatedProduct;
use App\Helpers\GlobalHelper;
use App\Http\Requests\PassportSizePhotoRequest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use App\Http\Requests\PhotoPrintRequest;
use App\Models\Customer;
use App\Models\GstPercentage;
use App\Models\Product;
use App\Models\Service;


class PassportSizePhotoController extends Controller
{
    public function getpassportsizephoto_totalcount()
    {
        //Total Products
        $total_count = ProductCatalogue::where('service_id', 1)->whereIn('status', [0, 1])->get();
        $total_count = $total_count->count();
        //total paid ads
        $published_count = ProductCatalogue::where('service_id', 1)->where('is_publish', 1)->whereIn('status', [0, 1])->get();
        $published_count = $published_count->count();
        //Wanted ads
        $unpublished_count = ProductCatalogue::where('service_id', 1)->where('is_publish', 2)->whereIn('status', [0, 1])->get();
        $unpublished_count = $unpublished_count->count();
        $count = ['total_count' => $total_count, 'published_count' => $published_count, 'unpublished_count' => $unpublished_count];
        return response()->json([
            'keyword' => 'success',
            'message' => __('Count successfully'),
            'data' => [$count]
        ]);
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

    public function passportsizephoto_publishstatus(Request $request)
    {
        try {
            Log::channel("passportsizephoto")->info('** started the passportsizephoto publish status method **');
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

                    $passportDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($passportDetails)){
                        foreach($passportDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $passportDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($passportDetailsCount > 0){
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

                    $passportDetails = RelatedProduct::where('product_id_related', $id)->get();
                    if(!empty($passportDetails)){
                        foreach($passportDetails as $passportDetail){
                            $productIds[] = $passportDetail['product_id'];
                        }

                        if(!empty($productIds)){
                            foreach($productIds as $productid){
                                $passportDetailsCount = RelatedProduct::where('product_id', $productid)->where('status', '!=', 2)->count();
                                if($passportDetailsCount > 0){
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
                $activity_status = ($request->is_publish == 1) ? 'published' : 'unpublished';
                $desc = $productdetails->product_name . ' product was ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . ' ';
                
                $activitytype = Config('activitytype.Passport Size Photo');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                if ($request->is_publish == 2) {
                    Log::channel("passportsizephoto")->info("Passport Size Photo unpublished successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Product unpublished successfully'),
                        'data' => []
                    ]);
                } else if ($request->is_publish == 1) {
                    if ($productdetails->is_publish == 1 &&  $productdetails->is_notification == 1) {
                        $pushNotification = $this->productCreatePushNotification($productdetails->product_id);
                    }
                    Log::channel("passportsizephoto")->info("Passport Size Photo published successfull");
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
            Log::channel("passportsizephoto")->error($exception);
            Log::channel("passportsizephoto")->info('** end the passportsizephoto publish status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function passportsizephoto_create(PassportSizePhotoRequest $request)

    {

        try {
            Log::channel("passportsize")->info("** started the passportsize create method **");
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
                            ['product_name', $request->product_name], ['service_id', '=', 1], ['status', '!=', 2]
                        ])->first();



                        if (empty($exits)) {

                            $passportsizephoto = new ProductCatalogue();
                            $passportsizephoto->service_id = 1;
                            $passportsizephoto->category_id = $request->category_id;
                            $passportsizephoto->product_name = $request->product_name;
                            $passportsizephoto->mrp = $request->mrp;
                            $passportsizephoto->selling_price = $request->selling_price;
                            $passportsizephoto->gst_percentage = $request->gst_percentage;
                            $passportsizephoto->help_url = $request->help_url;
                            $passportsizephoto->customer_description = $request->customer_description;
                            $passportsizephoto->designer_description = $request->designer_description;
                            $passportsizephoto->product_image = $request->product_image;
                            $passportsizephoto->thumbnail_image = $request->thumbnail_image;
                            $passportsizephoto->is_cod_available = $request->is_cod_available;
                            $passportsizephoto->is_related_product_available = $request->is_related_product_available;
                            $passportsizephoto->weight = $request->weight;
                            $passportsizephoto->created_on = Server::getDateTime();
                            $passportsizephoto->created_by = JwtHelper::getSesUserId();
                            $relatedProductJsonString = $request->input('related_products');

                            Log::channel("passportsizephoto")->info("request value :: $passportsizephoto->passportsizephoto");
                            if ($passportsizephoto->save()) {
                                $serviceDetailsCount = ProductCatalogue::where('service_id', $passportsizephoto->service_id)->get();
                                $service_count = $serviceDetailsCount->count();
                                $product_code = env('PREFIX') . '_' . str_pad($passportsizephoto->product_id, 3, '0', STR_PAD_LEFT) . '_' . str_pad($service_count, 3, '0', STR_PAD_LEFT);
                                $update_productdetails = ProductCatalogue::find($passportsizephoto->product_id);
                                $update_productdetails->product_code = $product_code;
                                $update_productdetails->save();

                                $passportsizephotos = ProductCatalogue::where('product_id', $passportsizephoto->product_id)
                                    ->select('product.*')
                                    ->first();

                                // save related products
                                if ($passportsizephotos->is_related_product_available == 1) {
                                    $relatedArr = json_decode($relatedProductJsonString, TRUE);
                                    $this->saverelatedProducts($relatedArr, $passportsizephotos->product_id);
                                }
                                Log::channel("passportsizephoto")->info("** passportsizephoto save details : $passportsizephotos **");

                                // log activity
                                $desc =  'Passport Size Photo '  . $passportsizephoto->passport_size_photo . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                                $activitytype = Config('activitytype.Passport Size Photo');
                                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                                Log::channel("passportsizephoto")->info("save value :: $passportsizephotos");
                                Log::channel("passportsizephoto")->info('** end the passportsizephoto create method **');
                                return response()->json([
                                    'keyword'      => 'success',
                                    'message'      => __('Product created successfully'),
                                    'data'        => [$passportsizephotos]
                                ]);
                            } else {
                                return response()->json([
                                    'keyword'      => 'failed',
                                    'message'      => __('Product creation failed'),
                                    'data'        => []
                                ]);
                            }
                        } else {
                            return response()->json([
                                'keyword'      => 'failed',
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
            Log::channel("passportsizephoto")->error($exception);
            Log::channel("passportsizephoto")->error('** end the passportsizephoto create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
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

    public function passportsizephoto_list(Request $request)
    {
        try {
            Log::channel("passportsizephoto")->info('** started the passportsizephoto list method **');
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
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "product_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('product.created_on', 'product.product_code', 'product.product_name', 'product.selling_price', 'product.mrp');
            $passportsizephoto = ProductCatalogue::where('service_id', 1)->where('status', '!=', '2');

            $passportsizephoto->where(function ($query) use ($searchval, $column_search, $passportsizephoto) {
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
                $passportsizephoto->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $passportsizephoto->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $passportsizephoto->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $passportsizephoto->where('is_publish', $filterByStatus);
            }


            $count = $passportsizephoto->count();

            if ($offset) {
                $offset = $offset * $limit;
                $passportsizephoto->offset($offset);
            }
            if ($limit) {
                $passportsizephoto->limit($limit);
            }
            Log::channel("passportsizephoto")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType:: $from_date:: $to_date:: $filterByStatus");
            $passportsizephoto->orderBy('product_id', 'desc');
            $passportsizephoto = $passportsizephoto->get();
            $final = [];

            if ($count > 0) {
                foreach ($passportsizephoto as $value) {
                    $ary = [];
                    $ary['product_id'] = $value['product_id'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['product_name'] = $value['product_name'];
                    $ary['mrp'] = $value['mrp'];
                    $ary['selling_price'] = $value['selling_price'];
                    $ary['is_cod_available'] = $value['is_cod_available'];
                    $ary['is_related_product_available'] = $value['is_related_product_available'];
                    $ary['is_publish'] = $value['is_publish'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("passportsizephoto")->info("list value :: $log");
                Log::channel("passportsizephoto")->info('** end the passportsizephoto list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Product listed successfully'),
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
            Log::channel("passportsizephoto")->error($exception);
            Log::channel("passportsizephoto")->error('** end the passportsizephoto list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function passportsizephoto_update(PassportSizePhotoRequest $request)
    {

        try {
            Log::channel("passportsize")->info("** started the passportsize update method **");
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
                            ['service_id', '=', 1],
                            ['status', '!=', 2]
                        ])->first();




                        Log::channel("passportsizephoto")->info('** started the passportsizephoto update method **');


                        $exits = ProductCatalogue::where([
                            ['product_id', '!=', $request->product_id],
                            ['product_name', $request->product_name], ['status', '!=', 2]
                        ])->first();

                        // $exist = ProductCatalogue::where([['product_id', '!=', $request->product_id],['product_id', '!=', 
                        // $request->product_id],
                        // ['product_name', $request->product_name],['product_name', $request->product_name],  
                        //         ['status', '!=', 2],['product_id', '!=', $request->product_id]])->first();

                        if (empty($exits)) {
                            $ids = $request->product_id;
                            $passportsizephoto = ProductCatalogue::find($ids);
                            $passportsizephoto->product_id = $request->product_id;
                            $passportsizephoto->product_name = $request->product_name;
                            $passportsizephoto->mrp = $request->mrp;
                            $passportsizephoto->selling_price = $request->selling_price;
                            $passportsizephoto->gst_percentage = $request->gst_percentage;
                            $passportsizephoto->help_url = $request->help_url;
                            $passportsizephoto->customer_description = $request->customer_description;
                            $passportsizephoto->designer_description = $request->designer_description;
                            $passportsizephoto->product_image = $request->product_image;
                            $passportsizephoto->thumbnail_image = $request->thumbnail_image;
                            $passportsizephoto->is_cod_available = $request->is_cod_available;
                            $passportsizephoto->is_related_product_available = $request->is_related_product_available;
                            $passportsizephoto->weight = $request->weight;
                            $passportsizephoto->is_notification  = $request->is_notification;
                            $passportsizephoto->service_id = 1;
                            $relatedProductJsonString = $request->input('related_products');
                            $passportsizephoto->updated_on = Server::getDateTime();
                            $passportsizephoto->updated_by = JwtHelper::getSesUserId();
                            Log::channel("passportsizephoto")->info("request value :: $passportsizephoto->passportsizephoto");

                            if ($passportsizephoto->save()) {
                                $passportsizephotos = ProductCatalogue::where('product_id', $passportsizephoto->product_id)
                                    ->select('product.*')
                                    ->first();



                                // save related products
                                if ($passportsizephotos->is_related_product_available == 1) {
                                    $delete = RelatedProduct::where('product_id', $passportsizephotos->product_id)->delete();
                                    $relatedArr = json_decode($relatedProductJsonString, TRUE);
                                    $this->saverelatedProducts($relatedArr, $passportsizephotos->product_id);
                                }
                                if ($passportsizephotos->is_related_product_available == 0) {
                                    $delete = RelatedProduct::where('product_id', $passportsizephotos->product_id)->delete();
                                }

                                if ($passportsizephotos->is_publish == 1 &&  $passportsizephotos->is_notification == 1) {
                                    $pushNotification = $this->productCreatePushNotification($passportsizephotos->product_id);
                                }

                                Log::channel("passportsizephoto")->info("** passportsizephoto save details : $passportsizephotos **");
                                // log activity
                                $desc =  'Product ' . '(' . $passportsizephotos->passportsizephotos . ')' . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                                $activitytype = Config('activitytype.Passport size photo');
                                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                                Log::channel("passportsizephoto")->info("** end the passportsizephoto update method **");

                                // log activity
                                $desc =  $passportsizephoto->product_name  . ' product was updated by ' . JwtHelper::getSesUserNameWithType() . ' ';
                                $activitytype = Config('activitytype.Passport Size Photo');
                                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);




                                Log::channel("passportsizephoto")->info("save value :: $passportsizephotos");
                                Log::channel("passportsizephoto")->info('** end the passportsizephoto update method **');

                                return response()->json([
                                    'keyword'      => 'success',
                                    'data'        => [$passportsizephotos],
                                    'message'      => __('Product updated successfully')
                                ]);
                            } else {
                                return response()->json([
                                    'keyword'      => 'failed',
                                    'data'        => [],
                                    'message'      => __('Product update failed')
                                ]);
                            }
                        } else {
                            return response()->json([
                                'keyword'      => 'failed',
                                'message'      => __('Product name already exist'),
                                'data'        => []
                            ]);
                        }
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'message'      => 'Only JPG,JPEG,PNG,WEBP formats allowed for image',
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
            Log::channel("passportsizephoto")->error($exception);
            Log::channel("passportsizephoto")->error('** end the passportsizephoto update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function passportsizephoto_view($id)
    {
        try {
            Log::channel("passportsizephoto")->info('** started the passportsizephoto view method **');
            if ($id != '' && $id > 0) {

                $passportsizephoto = ProductCatalogue::where('product.product_id', $id)

                    ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->select('product.*', 'gst_percentage.gst_percentage as gst')->first();
                Log::channel("passportsizephoto")->info("request value product_id:: $id");
                $count = $passportsizephoto->count();
                if ($count > 0) {
                    $final = [];
                    $ary = [];
                    $ary['product_id'] = $passportsizephoto['product_id'];
                    $ary['product_code'] = $passportsizephoto['product_code'];
                    $ary['product_name'] = $passportsizephoto['product_name'];
                    $ary['mrp'] = $passportsizephoto['mrp'];
                    $ary['selling_price'] = $passportsizephoto['selling_price'];
                    $ary['gst'] = $passportsizephoto['gst'];
                    $ary['gst_percentage_id'] = $passportsizephoto['gst_percentage'];
                    $ary['help_url'] = $passportsizephoto['help_url'];
                    $ary['customer_description'] = $passportsizephoto['customer_description'];
                    $ary['designer_description'] = $passportsizephoto['designer_description'];
                    $gTImage = json_decode($passportsizephoto['product_image'], true);
                    $ary['product_image'] = $this->getdefaultImages_allImages($gTImage);
                    $gTImage = json_decode($passportsizephoto['thumbnail_image'], true);
                    $ary['thumbnail_url'] =  ($passportsizephoto['thumbnail_image'] != '') ?
                        env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $passportsizephoto['thumbnail_image'] :
                        env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $passportsizephoto['thumbnail_image'];
                    $ary['is_cod_available'] = $passportsizephoto['is_cod_available'];
                    $ary['is_related_product_available'] = $passportsizephoto['is_related_product_available'];
                    $ary['is_notification'] = $passportsizephoto['is_notification'];
                    $ary['service_id'] = $passportsizephoto['service_id'];
                    $ary['is_publish'] = $passportsizephoto['is_publish'];
                    $ary['created_on'] = $passportsizephoto['created_on'];
                    $ary['created_by'] = $passportsizephoto['created_by'];
                    $ary['updated_on'] = $passportsizephoto['updated_on'];
                    $ary['updated_by'] = $passportsizephoto['updated_by'];
                    $ary['status'] = $passportsizephoto['status'];
                    $ary['weight'] = $passportsizephoto['weight'];
                    $ary['related_products'] = $this->getrelated_products($passportsizephoto->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("passportsizephoto")->info("view value :: $log");
                    Log::channel("passportsizephoto")->info('** end the passportsizephoto view method **');
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
            Log::channel("passportsizephoto")->error($exception);
            Log::channel("passportsizephoto")->info('** end the passportsizephoto view method **');

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
                $ary['url'] = ($im['image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $im['image'] : env('APP_URL') . "avatar.jpg";
                $ary['image'] = $im['image'];
                $imG[] = $ary;
            }
        }
        return $imG;
    }

    public function getrelated_products($proId)
    {
        $related = RelatedProduct::where('p.is_related_product_available', 1)->where('related_products.product_id', $proId)->where('related_products.status', '!=', 2)
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


    //     public function passportsizephoto_delete(Request $request)
    //     {
    //         try {
    //             if (!empty($request)) {
    //                 $ids = $request->id;

    //                 if (empty($exist)) {
    //                     Log::channel("passportsizephoto")->info('** started the passportsizephoto delete method **');
    //                     Log::channel("passportsizephoto")->info("request value product_id:: $ids :: ");
    //                     $passportsizephoto = ProductCatalogue::where('product_id', $ids)->first();
    //                     $update = ProductCatalogue::where('product_id', $ids)->update(array(
    //                         'status' => 2,
    //                         'updated_on' => Server::getDateTime(),
    //                         'updated_by' => JwtHelper::getSesUserId()
    //                     ));

    //                     // log activity
    //     $desc =  'Passport Size Photo '  . $passportsizephoto->passport_size_photo  . ' is deleted by ' . JwtHelper::getSesUserNameWithType() . '';
    //     $activitytype = Config('activitytype.Passport Size Photo');
    //     GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);



    // Log::channel("passportsizephoto")->info("save value :: product_id :: $ids :: passportsizephoto deleted successfully");
    // Log::channel("passportsizephoto")->info('** end the passportsizephoto delete method **');
    //                     return response()->json([
    //                         'keyword' => 'success',
    //                         'message' =>  __('Passport Size Photo deleted successfully'),
    //                         'data' => []
    //                     ]);
    //                 } else {
    //                     return response()->json([
    //                         'keyword' => 'failed',
    //                         'message' => __('message.failed'),
    //                         'data' => []
    //                     ]);
    //                 }
    //             } else {
    //                 return response()->json([
    //                     'keyword' => 'failed',
    //                     'message' => __('message.failed'),
    //                     'data' => []
    //                 ]);
    //             }
    //         } catch (\Exception $exception) {
    //             Log::channel("passportsizephoto")->error($exception);
    //             Log::channel("passportsizephoto")->info('** end the passportsizephoto delete method **');

    //             return response()->json([
    //                 'error' => 'Internal server error.',
    //                 'message' => $exception->getMessage()
    //             ], 500);
    //         }
    //     }

    public function passportsizephoto_excel(Request $request)
    {
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $all = $request->all;

        $passportsizephoto = ProductCatalogue::where('service_id', 1)->where('status', '!=', 2);


        if (!empty($from_date)) {
            $passportsizephoto->where(function ($query) use ($from_date) {
                $query->whereDate('created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $passportsizephoto->where(function ($query) use ($to_date) {
                $query->whereDate('created_on', '<=', $to_date);
            });
        }
        if (!empty($filterByStatus)) {
            $passportsizephoto->where('is_publish', $filterByStatus);
        }

        $passportsizephoto = $passportsizephoto->get();

        $count = count($passportsizephoto);

        $s = 1;
        if ($count > 0) {
            $overll = [];
            foreach ($passportsizephoto as $value) {
                $ary = [];
                $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                $ary['product_code'] = $value['product_code'];
                $ary['product_name'] = $value['product_name'];
                $ary['mrp'] = $value['mrp'];
                $ary['selling_price'] = $value['selling_price'];
                if ($value['is_publish'] == 1) {
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
            $excel_report_title = "Passport Size Photos Report";

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
            $file_name = "passportsizephoto-report-data.xls";
            $fullpath =  storage_path() . '/app/passportsizephoto_report' . $file_name;
            // print_r($fullpath);exit;
            $writer->save($fullpath); // download file
            // return $file_name;
            // return response()->download($fullpath, "sales_report.xls");
            return response()->download(storage_path('app/passportsizephoto_reportpassportsizephoto-report-data.xls'), "passportsizephoto_reportpassportsizephoto-report-data.xls");
        }
    }


    public function passportsizephoto_status(Request $request)
    {
        try {
            Log::channel("passportsizephoto")->info('** started the passportsizephoto status method **');
            // if (!empty($request)) {
            $ids = $request->id;
            if (!empty($ids)) {

                $exist = RelatedProduct::where('product_id_related', $ids)->where('status', '!=', 2)->first();
                if (empty($exist)) {

                    Log::channel("passportsizephoto")->info('** started the passportsizephoto status method **');



                    $passportsizephoto = ProductCatalogue::where('product_id', $ids)->first();
             
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
                    $desc = $passportsizephoto->product_name . ' product was ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . ' at '.$updated_on->updated_on;

                    $activitytype = Config('activitytype.Passport Size Photo');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {
                        Log::channel("passportsizephoto")->info("Passport Size Photo inactive successfull");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Product inactivated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 1) {
                        Log::channel("passportsizephoto")->info("Passport Size Photo active successfull");
                        return response()->json([
                            'keyword' => 'success',
                            'message' =>  __('Product activated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 2) {
                        Log::channel("passportsizephoto")->info("Passport Size Photo delete successfull");
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
                        'message' => __('Product status updated failed'),
                        'data' => []
                    ]);
            }
        } catch (\Exception $exception) {
            Log::channel("passportsizephoto")->error($exception);
            Log::channel("passportsizephoto")->info('** end the passportsizephoto status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
