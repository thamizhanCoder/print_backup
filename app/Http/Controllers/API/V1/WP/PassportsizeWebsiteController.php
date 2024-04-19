<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Models\ProductCatalogue;
use App\Models\RelatedProduct;
use App\Models\Customer;
use App\Helpers\GlobalHelper;
use App\Http\Requests\PassportAddToCartRequest;
use App\Models\AddToCart;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use App\Models\GstPercentage;
use App\Models\ProductVisitHistory;
use App\Models\Service;
use Jenssegers\Agent\Agent;

class PassportsizeWebsiteController extends Controller
{
    public function passportsizewebsite_list(Request $request)
    {
        try {
            $data = new ProductVisitHistory();
        $data->service_id = 1;
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
            Log::channel("passportsizewebsite")->info('** started the passportsizewebsite list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';




            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'

                'date' => 'product.created_on',
                // 'product_id' => 'product.product_id',
                'product_code' => 'product.product_code',
                'product_name' => 'product.product_name',
                'thumbnail_image' => 'product.thumbnail_image',
                'mrp' => 'product.mrp',
                'selling_price' => 'product.selling_price',
                'offer_percentage' => 'product.offer_percentage'

            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "product_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'product.created_on', 'product.product_code', 'product.product_name', 'product.thumbnail_image',
                'product.mrp', 'product.selling_price', 'product.offer_percentage'
            );
            $passportsizewebsite = ProductCatalogue::where('service_id', 1)->where('status', 1)->where('is_publish', 1);

            $passportsizewebsite->where(function ($query) use ($searchval, $column_search, $passportsizewebsite) {
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
                $passportsizewebsite->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $passportsizewebsite->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $passportsizewebsite->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $passportsizewebsite->where('is_publish', $filterByStatus);
            }


            $count = $passportsizewebsite->count();

            if ($offset) {
                $offset = $offset * $limit;
                $passportsizewebsite->offset($offset);
            }
            if ($limit) {
                $passportsizewebsite->limit($limit);
            }
            Log::channel("passportsizewebsite")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType:: $from_date:: $to_date:: $filterByStatus");
            $passportsizewebsite->orderBy('product_id', 'desc');
            $passportsizewebsite = $passportsizewebsite->get();
            $final = [];

            if ($count > 0) {
                foreach ($passportsizewebsite as $value) {

                    $ary = [];
                    $ary['product_id'] = $value['product_id'];
                    $ary['service_id'] = $value['service_id'];
                    $ary['product_code'] = $value['product_code'];
                    // $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['product_name'] = $value['product_name'];
                    $ary['product_name'] = $value['product_name'];
                    $gTImage = json_decode($value['thumbnail_image'], true);

                    $ary['thumbnail_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $value['thumbnail_image'];
                    $ary['mrp'] = $value['mrp'];
                    $ary['selling_price'] = $value['selling_price'];
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    // $offer =  $amunt / $ary['mrp'];
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                    // $ary['offer_percentage'] =  $amunt;

                    //     $amunt = $ary['mrp'] - $ary['selling_price'];
                    // $offer =  $amunt / $ary['mrp'];
                    // $ary['offer_percentage'] =  round($offer . '' . "%", 2);


                    $ary['is_cod_available'] = $value['is_cod_available'];
                    $ary['is_related_product_available'] = $value['is_related_product_available'];
                    $ary['is_publish'] = $value['is_publish'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("passportsizewebsite")->info("list value :: $log");
                Log::channel("passportsizewebsite")->info('** end the passportsizewebsite list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Passport size product listed successfully'),
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
            Log::channel("passportsizewebsite")->error($exception);
            Log::channel("passportsizewebsite")->error('** end the passportsizewebsite list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function passportsizeaddtocart_create(PassportAddToCartRequest $request)
    {
        try {
        Log::channel("passportsizeaddtocart")->info('** started the passportsizeaddtocart create method **');
        
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
        
        if ($request->image != '') {
            $Extension =  pathinfo($request->input('image'), PATHINFO_EXTENSION);
            $extension_ary = ['jpeg', 'png', 'jpg'];
            if (in_array($Extension, $extension_ary)) {
                    $request->image;
            
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                    'data'        => []
                ]);
            }
        }
        $passportsizeaddtocart = new AddToCart();
        $passportsizeaddtocart->customer_id = JwtHelper::getSesUserId();
        $passportsizeaddtocart->service_id = 1;
        $passportsizeaddtocart->product_id = $request->product_id;
        $passportsizeaddtocart->quantity = $request->quantity;
        $passportsizeaddtocart->image = $request->image;
        $passportsizeaddtocart->background_color = $request->background_color;
        $passportsizeaddtocart->cart_type = $request->cart_type;
        $passportsizeaddtocart->created_on = Server::getDateTime();
        $passportsizeaddtocart->created_by = JwtHelper::getSesUserId();
        // Log::channel("passportsizeaddtocart")->info("request value :: $passportsizeaddtocart->passportsizeaddtocart_name");



        if ($passportsizeaddtocart->save()) {
            $passportsizeaddtocarts = AddtoCart::where('add_to_cart_id', $passportsizeaddtocart->add_to_cart_id)->first();

            Log::channel("passportsizeaddtocart")->info("save value :: $passportsizeaddtocarts");
            return response()->json([
                'keyword'      => 'success',
                'message'      => __('Product added to cart successfully'),
                'data'        => [$passportsizeaddtocarts]
            ]);
        } else {
            return response()->json([
                'keyword'      => 'failed',
                'message'      => __('Product add to cart failed'),
                'data'        => []
            ]);
        }
                } catch (\Exception $exception) {
                    Log::channel("passportsizeaddtocart")->error($exception);
                    Log::channel("passportsizeaddtocart")->error('** end the passportsizeaddtocart create method **');

                    return response()->json([
                        'error' => 'Internal server error.',
                        'message' => $exception->getMessage()
                    ], 500);
                }
    }


    // public function passportsizeaddtocart_update(Request $request)
    // {
    //     try {
    //         Log::channel("passportsizeaddtocart")->info('** started the passportsizeaddtocart update method **');

    //         // $exist = AddtoCart::where([['add_cart_id', '!=', $request->add_cart_id],['passportsizeaddtocart_name', $request->passportsizeaddtocart_name], ['status', '!=', 2]])->first();

    //         // if (empty($exist)) {
    //             // $id = JwtHelper::getSesUserId();
    //             $ids = $request->add_to_cart_id;
    //             $passportsizeaddtocart = AddtoCart::find($ids); 
    //             // $passportsizeaddtocart->customer_id = $request->$id;
    //             // $passportsizeaddtocart->customer_id = JwtHelper::getSesUserId();
    //             // $passportsizeaddtocart->add_to_cart_id = $request->add_to_cart_id;
    //             $passportsizeaddtocart->product_id = $request->product_id;
    //             $passportsizeaddtocart->quantity = $request->quantity;
    //             $passportsizeaddtocart->image = $request->image;
    //             $passportsizeaddtocart->background_color = $request->background_color;
    //             $passportsizeaddtocart->updated_on = Server::getDateTime();
    //             $passportsizeaddtocart->updated_by = JwtHelper::getSesUserId();
    //             // Log::channel("passportsizeaddtocart")->info("request value :: $passportsizeaddtocart->passportsizeaddtocart_name");

    //             if ($passportsizeaddtocart->save()) {
    //                 $passportsizeaddtocarts = AddtoCart::where('add_to_cart_id', $passportsizeaddtocart->add_cart_id)->first();

    //                 // log activity
    //                 // $desc =   $passportsizeaddtocart->passportsizeaddtocart_name . ' passportsizeaddtocart ' . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
    //                 // $activitytype = Config('activitytype.passportsizeaddtocart');
    //                 // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

    //                 Log::channel("passportsizeaddtocart")->info("save value :: $passportsizeaddtocarts");
    //                 Log::channel("passportsizeaddtocart")->info('** end the passportsizeaddtocart update method **');

    //                 return response()->json([
    //                     'keyword'      => 'success',
    //                     'data'        => [$passportsizeaddtocarts],
    //                     'message'      => __('passportsizeaddtocart updated successfully')
    //                 ]);
    //             } else {
    //                 return response()->json([
    //                     'keyword'      => 'failed',
    //                     'data'        => [],
    //                     'message'      => __('passportsizeaddtocart updated failed')
    //                 ]);
    //             }
    //         // } else {
    //         //     return response()->json([
    //         //         'keyword'      => 'failed',
    //         //         'message'      => __('passportsizeaddtocart name already exist'),
    //         //         'data'        => []
    //         //     ]);
    //         // }
    //     } catch (\Exception $exception) {
    //         Log::channel("passportsizeaddtocart")->error($exception);
    //         Log::channel("passportsizeaddtocart")->error('** end the passportsizeaddtocart update method **');

    //         return response()->json([
    //             'error' => 'Internal server error.',
    //             'message' => $exception->getMessage()
    //         ], 500);
    //     }
    // }

    public function getdefaultImages_allImages($gTImage)
    {
        $imG = [];
        if (!empty($gTImage)) {

            $sortedImages = collect($gTImage)->sortBy('index')->values()->all();

            foreach ($sortedImages as $im) {
                $ary = [];
                $ary['index'] = $im['index'];
                $ary['url'] = ($im['image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $im['image'] : env('APP_URL') . "avatar.jpg";
                $ary['image'] = $im['image'];
                $imG[] = $ary;
            }
        }
        return $imG;
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


    public function passportsizewebsite_view($id)
    {
        try {
            Log::channel("passportsizewebsite")->info('** started the passportsizewebsite view method **');
            if ($id != '' && $id > 0) {

                $passportsizewebsite = ProductCatalogue::where('product.product_id', $id)

                    ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->select('product.*', 'gst_percentage.gst_percentage as gst')->first();
                Log::channel("passportsizewebsite")->info("request value product_id:: $id");
                $count = $passportsizewebsite->count();
                if ($count > 0) {
                    $final = [];
                    $ary = [];
                    $ary['product_id'] = $passportsizewebsite['product_id'];
                    $ary['product_code'] = $passportsizewebsite['product_code'];
                    $ary['product_name'] = $passportsizewebsite['product_name'];
                    $ary['mrp'] = $passportsizewebsite['mrp'];
                    $ary['selling_price'] = $passportsizewebsite['selling_price'];
                    // $amunt = $ary['mrp'] - $ary['selling_price'];
                    // $offer =  $amunt / $ary['mrp'];
                    // $ary['offer_percentage'] =  round($offer . '' . "%", 2);

                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    // $offer =  $amunt / $ary['mrp'];
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);



                    // $ary['image'] = $passportsizewebsite['image'];
                    // $ary['quantity_selection'] =$passportsizewebsite['quantity_selection'];
                    // $ary['background_colour'] =$passportsizewebsite['background_colour'];
                    // $ary['add_to_cart'] =$passportsizewebsite['add_to_cart'];
                    // $ary['buy_now'] =$passportsizewebsite['buy_now'];
                    // $ary['description'] =$passportsizewebsite['description'];
                    $ary['related_products'] = $this->getrelated_products($passportsizewebsite->product_id);
                    $ary['gst'] = $passportsizewebsite['gst'];
                    $ary['gst_percentage_id'] = $passportsizewebsite['gst_percentage'];
                    $ary['help_url'] = $passportsizewebsite['help_url'];
                    $ary['customer_description'] = $passportsizewebsite['customer_description'];
                    $ary['designer_description'] = $passportsizewebsite['designer_description'];
                    $gTImage = json_decode($passportsizewebsite['product_image'], true);
                    $ary['product_image'] = $this->getdefaultImages_allImages($gTImage);
                    $gTImage = json_decode($passportsizewebsite['thumbnail_image'], true);
                    $ary['thumbnail_url'] =  ($passportsizewebsite['thumbnail_image'] != '') ?
                        env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $passportsizewebsite['thumbnail_image'] :
                        env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $passportsizewebsite['thumbnail_image'];
                    $ary['is_cod_available'] = $passportsizewebsite['is_cod_available'];
                    $ary['is_related_product_available'] = $passportsizewebsite['is_related_product_available'];
                    $ary['is_notification'] = $passportsizewebsite['is_notification'];
                    $ary['service_id'] = $passportsizewebsite['service_id'];
                    $ary['is_publish'] = $passportsizewebsite['is_publish'];
                    $ary['created_on'] = $passportsizewebsite['created_on'];
                    $ary['created_by'] = $passportsizewebsite['created_by'];
                    $ary['updated_on'] = $passportsizewebsite['updated_on'];
                    $ary['updated_by'] = $passportsizewebsite['updated_by'];
                    $ary['status'] = $passportsizewebsite['status'];
                    $ary['related_products'] = $this->getrelated_products($passportsizewebsite->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("passportsizewebsite")->info("view value :: $log");
                    Log::channel("passportsizewebsite")->info('** end the passportsizewebsite view method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Passport size photo viewed successfully'),
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
            Log::channel("passportsizewebsite")->error($exception);
            Log::channel("passportsizewebsite")->info('** end the passportsizewebsite view method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
