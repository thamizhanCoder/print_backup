<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Helpers\GlobalHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Http\Requests\PhotoPrintAddToCartRequest;
use App\Http\Requests\PhotoprintWebsite;
use App\Models\AddToCart;
use App\Models\GstPercentage;
use App\Models\ProductCatalogue;
use App\Models\ProductVisitHistory;
use App\Models\RelatedProduct;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class PhotoprintWebsiteController extends Controller
{


    public function photoprintaddtocart_create(PhotoPrintAddToCartRequest $request)
    {
        try {
            Log::channel("photoprintaddtocart")->info('** started the photoprintaddtocart create method **');

            if ($request->cart_type == 2) {
                $typeChange = AddToCart::where('customer_id', JwtHelper::getSesUserId())->where('cart_type', 2)->get();
                if (!empty($typeChange)) {
                    foreach ($typeChange as $tc) {
                        $id[] =  $tc->add_to_cart_id;
                    }
                }
                if (!empty($id)) {
                AddToCart::whereIn('add_to_cart_id', $id)->update(array(
                    'cart_type' => 1,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));
            }
            }
            
            if (!empty($request->photoprint_variant)) {
                $gTImage = json_decode($request->photoprint_variant, true);
                if (!empty($gTImage)) {
                    foreach ($gTImage as $d) {

                        $validator = Validator::make($d, [
                            'image' => 'required',
                            'quantity' => 'required|numeric|min:1'

                        ]);

                        if ($validator->fails()) {
                            return response()->json([
                                "keyword"    => 'failed',
                                "message"   => $validator->errors()->first(),
                                "data"  => []
                            ]);
                        }
                    }
                    foreach ($gTImage as $im) {
                        $ary[] = pathinfo($im['image'], PATHINFO_EXTENSION);
                    }
                    $extension_array = ['jpeg', 'png', 'jpg'];
                    if (!array_diff($ary, $extension_array)) {
                        $request->photoprint_variant;
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                            'data'        => []
                        ]);
                    }
                }
            }

            $photoprintaddtocart = new AddToCart();
            $photoprintaddtocart->customer_id = JwtHelper::getSesUserId();
            $photoprintaddtocart->service_id = 2;
            $photoprintaddtocart->cart_type = $request->cart_type;
            $photoprintaddtocart->product_id = $request->product_id;
            $photoprintaddtocart->quantity = $this->getPhotoPrintVariant(json_decode($request->photoprint_variant, true));
            $photoprintaddtocart->photoprint_variant = $request->photoprint_variant;
            $photoprintaddtocart->created_on = Server::getDateTime();
            $photoprintaddtocart->created_by = JwtHelper::getSesUserId();
            // Log::channel("photoprintaddtocart")->info("request value :: $photoprintaddtocart->photoprintaddtocart_name");

            if ($photoprintaddtocart->save()) {
                $photoprintaddtocarts = AddtoCart::where('add_to_cart_id', $photoprintaddtocart->add_to_cart_id)->first();

                Log::channel("photoprintaddtocart")->info("save value :: $photoprintaddtocarts");
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Product added to cart successfully'),
                    'data'        => [$photoprintaddtocarts]
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Product add to cart failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("photoprintaddtocart")->error($exception);
            Log::channel("photoprintaddtocart")->error('** end the photoprintaddtocart create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function getPhotoPrintVariant($photoprintDetails)
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

    public function photoprintwebsite_list(Request $request)
    {
        try {
            $data = new ProductVisitHistory();
        $data->service_id = 2;
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
        $data->save();
            Log::channel("photoprintwebsite")->info("** started the photoprintwebsite list method **");
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';

            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'date' => 'product.created_on',
                'product_code' => 'product.product_code',
                'product_name' => 'product.product_name',
                'thumbnail_image' => 'product.thumbnail_image',
                'mrp' => 'product.mrp',
                'first_copy_selling_price' => 'product.first_copy_selling_price',
                'offer_percentage' =>  'product.offer_percentage'

            ];

            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "product_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

            $column_search = array(
                'product.created_on', 'product.product_code', 'product.product_name',
                'product.thumbnail_image', 'product.mrp', 'product.first_copy_selling_price', 'product.offer_percentage'
            );

            $getphotoprintwebsite = ProductCatalogue::where('service_id', 2)->where('product.status', 1)->where('is_publish', 1)
                ->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'product.print_size')
                ->select('product.*', 'photo_print_setting.width', 'photo_print_setting.height');



            $getphotoprintwebsite->where(function ($query) use ($searchval, $column_search, $getphotoprintwebsite) {
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
                $getphotoprintwebsite->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $getphotoprintwebsite->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $getphotoprintwebsite->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $getphotoprintwebsite->where('is_publish', $filterByStatus);
            }

            $count = count($getphotoprintwebsite->get());

            if ($offset) {
                $offset = $offset * $limit;
                $getphotoprintwebsite->offset($offset);
            }

            if ($limit) {
                $getphotoprintwebsite->limit($limit);
            }

            $getphotoprintwebsite->orderBy('product_id', 'desc');
            $getphotoprintwebsite = $getphotoprintwebsite->get();


            $final = [];
            if ($count > 0) {
                foreach ($getphotoprintwebsite as $photoprintwebsite) {
                    $ary = [];

                    $ary['product_id'] = $photoprintwebsite['product_id'];
                    $ary['service_id'] = $photoprintwebsite['service_id'];
                    $ary['product_code'] = $photoprintwebsite['product_code'];
                    $ary['date'] = date('d-m-Y', strtotime($photoprintwebsite['created_on']));
                    $ary['product_name'] = $photoprintwebsite['product_name'];
                    $ary['photo_print_id'] = $photoprintwebsite['print_size'];
                    $ary['print_width'] = $photoprintwebsite['width'];
                    $ary['print_height'] = $photoprintwebsite['height'];
                    $ary['print_size'] = $photoprintwebsite['width'] . '*' . $photoprintwebsite['height'];
                    $ary['thumbnail_url'] = ($photoprintwebsite['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $photoprintwebsite['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $photoprintwebsite['thumbnail_image'];
                    $ary['mrp'] = $photoprintwebsite['mrp'];
                    $ary['first_copy_selling_price'] = $photoprintwebsite['first_copy_selling_price'];
                    $ary['additional_copy_selling_price'] = $photoprintwebsite['additional_copy_selling_price'];
                    $amunt = (($ary['mrp'] - $ary['first_copy_selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                    $ary['is_publish'] = $photoprintwebsite['is_publish'];
                    $ary['status'] = $photoprintwebsite['status'];
                    $final[] = $ary;
                }
            }

            if (!empty($getphotoprintwebsite)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Photo print Website listed successfully',
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
            Log::channel("photoprintwebsite")->error($exception);
            Log::channel("photoprintwebsite")->error('** end the photoprintwebsite **');
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

            $sortedImages = collect($gTImage)->sortBy('index')->values()->all();

            foreach ($sortedImages as $im) {
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
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                }
                if ($rp['service_id'] == 2) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $rp['mrp'];
                    $ary['selling_price'] = $rp['first_copy_selling_price'];
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                }
                if ($rp['service_id'] == 3) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                }
                if ($rp['service_id'] == 4) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                }
                if ($rp['service_id'] == 5) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                }
                if ($rp['service_id'] == 6) {
                    $ary['thumbnail_url'] = ($rp['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $rp['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['mrp'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($rp['product_id_related'], "selling");
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
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

    public function photoprintwebsite_view($id)
    {
        try {
            Log::channel("photoprintwebsite")->info('** started the photoprintwebsite view method **');
            if ($id != '' && $id > 0) {
                $photoprintwebsite = ProductCatalogue::where('product.product_id', $id)
                    ->leftjoin('photo_print_setting', 'photo_print_setting.photo_print_settings_id', '=', 'product.print_size')
                    ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->select('product.*', 'photo_print_setting.width', 'photo_print_setting.height', 
                    'photo_print_setting.min_resolution_width','photo_print_setting.min_resolution_height','photo_print_setting.max_resolution_width','photo_print_setting.max_resolution_height','gst_percentage.gst_percentage as gst')
                    ->first();
                Log::channel("photoprintwebsite")->info("request value photoprintwebsite_id:: $id");
                $count = $photoprintwebsite->count();
                if ($count > 0) {
                    $final = [];
                    $ary = [];
                    $ary['product_id'] = $photoprintwebsite['product_id'];
                    $ary['product_name'] = $photoprintwebsite['product_name'];
                    $gTImage = json_decode($photoprintwebsite['product_image'], true);
                    $ary['product_image'] = $this->getdefaultImages_allImages($gTImage);
                    $ary['photo_print_id'] = $photoprintwebsite['print_size'];
                    $ary['print_width'] = $photoprintwebsite['width'];
                    $ary['print_height'] = $photoprintwebsite['height'];
                    $ary['min_resolution_width'] = $photoprintwebsite['min_resolution_width'];
                    $ary['min_resolution_height'] = $photoprintwebsite['min_resolution_height'];
                    $ary['max_resolution_width'] = $photoprintwebsite['max_resolution_width'];
                    $ary['max_resolution_height'] = $photoprintwebsite['max_resolution_height'];
                    $ary['print_size'] = $photoprintwebsite['width'] . '*' . $photoprintwebsite['height'];
                    $ary['mrp'] = $photoprintwebsite['mrp'];
                    $ary['first_copy_selling_price'] = $photoprintwebsite['first_copy_selling_price'];
                    $ary['additional_copy_selling_price'] = $photoprintwebsite['additional_copy_selling_price'];
                    $amunt = (($ary['mrp'] - $ary['first_copy_selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                    $gTImage = json_decode($photoprintwebsite['product_image'], true);
                    $ary['product_image'] = $this->getdefaultImages_allImages($gTImage);
                    $ary['thumbnail_url'] =  ($photoprintwebsite['thumbnail_image'] != '') ?
                        env('APP_URL') . env('PHOTOPRINT_URL') . $photoprintwebsite['thumbnail_image'] :
                        env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $photoprintwebsite['thumbnail_image'];
                    $ary['gst_percentage_id'] = $photoprintwebsite['gst_percentage'];
                    $ary['gst'] = $photoprintwebsite['gst'];
                    $ary['help_url'] = $photoprintwebsite['help_url'];
                    $ary['thumbnail_url'] = ($photoprintwebsite['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $photoprintwebsite['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $photoprintwebsite['thumbnail_image'];
                    $ary['customer_description'] = $photoprintwebsite['customer_description'];
                    $ary['designer_description'] = $photoprintwebsite['designer_description'];
                    $ary['is_cod_available'] = $photoprintwebsite['is_cod_available'];
                    $ary['is_notification'] = $photoprintwebsite['is_notification'];
                    $ary['is_related_product_available'] = $photoprintwebsite['is_related_product_available'];
                    $ary['created_on'] = $photoprintwebsite['created_on'];
                    $ary['created_by'] = $photoprintwebsite['created_by'];
                    $ary['updated_on'] = $photoprintwebsite['updated_on'];
                    $ary['updated_by'] = $photoprintwebsite['updated_by'];
                    $ary['status'] = $photoprintwebsite['status'];
                    $ary['related_products'] = $this->getrelated_products($photoprintwebsite->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("photoprintwebsite")->info("view value :: $log");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Photo print website viewed successfully'),
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
            Log::channel("photoprintwebsite")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
