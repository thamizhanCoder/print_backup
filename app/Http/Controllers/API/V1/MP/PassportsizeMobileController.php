<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Models\ProductCatalogue;
use App\Models\RelatedProduct;
use App\Models\AddToCart;
use App\Helpers\GlobalHelper;
use App\Http\Requests\PassportAddToCartRequest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use App\Models\GstPercentage;
use App\Models\ProductVisitHistory;
use App\Models\Service;
use Jenssegers\Agent\Agent;

class PassportsizeMobileController extends Controller
{
    public function passportsizemobile_list(Request $request)
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
            Log::channel("passportsizemobile")->info('** started the passportsizemobile list method **');
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
            $passportsizemobile = ProductCatalogue::where('service_id', 1)->where('status', 1)->where('is_publish', 1);

            $passportsizemobile->where(function ($query) use ($searchval, $column_search, $passportsizemobile) {
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
                $passportsizemobile->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $passportsizemobile->where(function ($query) use ($from_date) {
                    $query->whereDate('created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $passportsizemobile->where(function ($query) use ($to_date) {
                    $query->whereDate('created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $passportsizemobile->where('is_publish', $filterByStatus);
            }


            $count = $passportsizemobile->count();

            if ($offset) {
                $offset = $offset * $limit;
                $passportsizemobile->offset($offset);
            }
            if ($limit) {
                $passportsizemobile->limit($limit);
            }
            Log::channel("passportsizemobile")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType:: $from_date:: $to_date:: $filterByStatus");
            $passportsizemobile->orderBy('product_id', 'desc');
            $passportsizemobile = $passportsizemobile->get();
            $final = [];

            if ($count > 0) {
                foreach ($passportsizemobile as $value) {

                    $ary = [];
                    $ary['product_id'] = $value['product_id'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
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
                Log::channel("passportsizemobile")->info("list value :: $log");
                Log::channel("passportsizemobile")->info('** end the passportsizemobile list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Passport size product mobile listed successfully'),
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
            Log::channel("passportsizemobile")->error($exception);
            Log::channel("passportsizemobile")->error('** end the passportsizemobile list method **');

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
            ->select('service.service_name', 'product.product_name', 'product.mrp', 'product.selling_price', 'product.first_copy_selling_price', 'related_products.service_id', 'related_products.product_id_related', 'product.thumbnail_image', 'c.category_name', 'product_variant.quantity')
            ->leftjoin('service', 'service.service_id', '=', 'related_products.service_id')
            ->leftjoin('product as p', 'p.product_id', '=', 'related_products.product_id')
            ->leftjoin('product', 'product.product_id', '=', 'related_products.product_id_related')
            ->leftjoin('category as c', 'c.category_id', '=', 'product.category_id')
            ->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', 1);
            })->get();
        $RelatedPro = [];
        if (!empty($related)) {
            foreach ($related as $rp) {
                $ary = [];
                $ary['service_name'] = $rp['service_name'];
                $ary['product_name'] = $rp['product_name'];
                $ary['category_name'] = $rp['category_name'];
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
                $ary['quantity'] = $rp['quantity'];
                $RelatedPro[] = $ary;
            }
        }
        return $RelatedPro;
    }


    public function passportsizemobile_view($id)
    {
        try {
            Log::channel("passportsizemobile")->info('** started the passportsizemobile view method **');
            if ($id != '' && $id > 0) {

                $passportsizemobile = ProductCatalogue::where('product.product_id', $id)

                    ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->select('product.*', 'gst_percentage.gst_percentage as gst')->first();
                Log::channel("passportsizemobile")->info("request value product_id:: $id");
                $count = $passportsizemobile->count();
                if ($count > 0) {
                    $final = [];
                    $ary = [];
                    $ary['product_id'] = $passportsizemobile['product_id'];
                    $ary['product_code'] = $passportsizemobile['product_code'];
                    $ary['product_name'] = $passportsizemobile['product_name'];
                    $ary['mrp'] = $passportsizemobile['mrp'];
                    $ary['selling_price'] = $passportsizemobile['selling_price'];
                    // $amunt = $ary['mrp'] - $ary['selling_price'];
                    // $offer =  $amunt / $ary['mrp'];
                    // $ary['offer_percentage'] =  round($offer . '' . "%", 2);

                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    // $offer =  $amunt / $ary['mrp'];
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                    // $ary['image'] = $passportsizemobile['image'];
                    // $ary['quantity_selection'] =$passportsizemobile['quantity_selection'];
                    // $ary['background_colour'] =$passportsizemobile['background_colour'];
                    // $ary['add_to_cart'] =$passportsizemobile['add_to_cart'];
                    // $ary['buy_now'] =$passportsizemobile['buy_now'];
                    // $ary['description'] =$passportsizemobile['description'];
                    //$ary['related_products'] = $this->getrelated_products($passportsizemobile->product_id);
                    $ary['gst'] = $passportsizemobile['gst'];
                    $ary['gst_percentage_id'] = $passportsizemobile['gst_percentage'];
                    $ary['help_url'] = $passportsizemobile['help_url'];
                    $ary['customer_description'] = $passportsizemobile['customer_description'];
                    $ary['designer_description'] = $passportsizemobile['designer_description'];
                    $gTImage = json_decode($passportsizemobile['product_image'], true);
                    $ary['product_image'] = $this->getdefaultImages_allImages($gTImage);
                    $gTImage = json_decode($passportsizemobile['thumbnail_image'], true);
                    $ary['thumbnail_url'] =  ($passportsizemobile['thumbnail_image'] != '') ?
                        env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $passportsizemobile['thumbnail_image'] :
                        env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $passportsizemobile['thumbnail_image'];
                    $ary['is_cod_available'] = $passportsizemobile['is_cod_available'];
                    $ary['is_related_product_available'] = $passportsizemobile['is_related_product_available'];
                    $ary['is_notification'] = $passportsizemobile['is_notification'];
                    $ary['service_id'] = $passportsizemobile['service_id'];
                    $ary['is_publish'] = $passportsizemobile['is_publish'];
                    $ary['created_on'] = $passportsizemobile['created_on'];
                    $ary['created_by'] = $passportsizemobile['created_by'];
                    $ary['updated_on'] = $passportsizemobile['updated_on'];
                    $ary['updated_by'] = $passportsizemobile['updated_by'];
                    $ary['status'] = $passportsizemobile['status'];
                    $ary['related_products'] = $this->getrelated_products($passportsizemobile->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("passportsizemobile")->info("view value :: $log");
                    Log::channel("passportsizemobile")->info('** end the passportsizemobile view method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Passport size photo-mobile viewed successfully'),
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
            Log::channel("passportsizemobile")->error($exception);
            Log::channel("passportsizemobile")->info('** end the passportsizemobile view method **');

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
}
