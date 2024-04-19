<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Http\Requests\SelfieAddToCartRequest;
use App\Http\Traits\SelfieAlbumTrait;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\RelatedProduct;
use App\Models\VariantType;
use App\Models\AddToCart;
use App\Models\ProductVisitHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;

class SelfieAlbumMobileController extends Controller
{
    use SelfieAlbumTrait;

    public function selfiealbummobile_list(Request $request)
    {
        try {
            $data = new ProductVisitHistory();
        $data->service_id = 6;
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
            Log::channel("selfiealbum")->info('** started the selfiealbum mobile list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $fromPrice = $request->fromPrice;
            $toPrice = $request->toPrice;
            $priceSort = $request->priceSort;

            $filerByVariant = $request->filerByVariant;

            $qry = $this->generateQuery($request);

            $qry_count = $qry;

            $qry_count .=  " group by `product`.`product_id`";

            $get_selfiealbum_count = DB::select(DB::raw($qry_count));

            $get_selfiealbum_count = json_decode(json_encode($get_selfiealbum_count), true);

            $get_selfiealbum_count = collect($get_selfiealbum_count);

            $max_price = $get_selfiealbum_count->max('selling_price');
            $min_price = $get_selfiealbum_count->min('selling_price');

            // $count = Count($get_selfiealbum_count);

            if ($fromPrice != '') {
                $qry .= " AND `product_variant`.`selling_price` >= $fromPrice";
            }

            if ($toPrice != '') {
                $qry .= " AND `product_variant`.`selling_price` <= $toPrice";
            }

            $qry .=  " group by `product`.`product_id`";

            $qry .= " ORDER BY";

            if ($priceSort == 1) {
                $qry .= "`product_variant`.`selling_price` ASC,";
            }

            if ($priceSort == 2) {
                $qry .= "`product_variant`.`selling_price` DESC,";
            }

            $qry .= " `product_id` DESC";

            $total_count = DB::select(DB::raw($qry));

            $count = Count($total_count);

            if ($limit) {
                $qry .= " LIMIT $limit";
            }

            if ($offset) {
                $offset = $limit * $offset;
                $qry .= " OFFSET $offset";
            }

            $get_selfiealbum = DB::select(DB::raw($qry));

            $get_selfiealbum = json_decode(json_encode($get_selfiealbum), true);

            if (!empty($get_selfiealbum)) {
                $final = [];
                foreach ($get_selfiealbum as $value) {
                    $ary = [];
                    $ary['product_id'] = $value['product_id'];
                    $ary['product_variant_id'] = $value['product_variant_id'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['product_name'] = $value['product_name'];
                    $ary['mrp'] = $value['mrp'];
                    $ary['selling_price'] = $value['selling_price'];
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                    $ary['thumbnail_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['is_publish'] = $value['is_publish'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                // $log = json_encode($final, true);
                // Log::channel("Personalized")->info("list value :: $log");
                Log::channel("selfiealbum")->info('** end the selfiealbum website list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Selfie album product listed successfully'),
                    'data' => $final,
                    'count' => $count,
                    'min_price' => $min_price,
                    'max_price' => $max_price,
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
            Log::channel("selfiealbum")->error('** end the selfiealbum mobile list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function generateQuery($request)
    {
        $fromPrice = $request->fromPrice;
        $toPrice = $request->toPrice;
        $filerByVariant = $request->filerByVariant;
        $primaryVariantTypeId = $request->primaryVariantTypeId;
        $primaryVariantValue = $request->primaryVariantValue;

        $filerByVariant = json_decode($filerByVariant, true);

        $i = 0;
        $qry = "";
        $finalary = [];
        $photoframe = "";

        $photoframe .= "SELECT 
            `product`.`product_id`,
            `product`.`created_on`,
            `product`.`product_code`,
            `product`.`product_name`,
            `product`.`thumbnail_image`,
            `product_variant`.`product_variant_id`, 
            `product_variant`.`selling_price`, 
            `product_variant`.`mrp`,
            `product_variant`.`image`, 
            `product_variant`.`variant_attributes`, 
            `product`.`is_publish`, 
            `product`.`status` 
            FROM `product_variant` 
             LEFT JOIN `product` ON `product`.`product_id` = `product_variant`.`product_id`";
        if ($primaryVariantTypeId == "" && $primaryVariantValue == "" && $filerByVariant == "") {

            $photoframe .= " and `product_variant`.`set_as_default` = 1 ";
        }
        $photoframe .= " LEFT JOIN `category` ON `category`.`category_id` = `product`.`category_id`";

        $photoframe .= " WHERE
            `product`.`service_id` = 6
            AND `product`.`status` = 1 
            AND `product`.`is_publish` = 1";

        if ($primaryVariantTypeId != '') {

            $photoframe .= " AND (`product_variant`.`variant_type_id` = $primaryVariantTypeId)";
        }

        if ($primaryVariantValue != '') {

            $photoframe .= " AND (`product_variant`.`label` = '$primaryVariantValue')";
        }

        if (!empty($filerByVariant)) {

            $where = "";
            $finalary = [];

            foreach ($filerByVariant as $varient) {

                $value =  addslashes($varient['value']);

                // $variantValue = mysql_real_escape_string($value);
                // $value =  '"' . $varient['value'] . '"';

                $where .= " AND json_search(json_extract(variant_attributes, '$[*].value'), 'one', '$value')";

                $finalary[] = $where;
            }

            $photoframe .= implode(" ", $finalary);
        }

        return $photoframe;
    }


    public function getProductImage($productImageData)
    {

        $imageArray = [];
        $resultArray = [];

        $productImageData = json_decode(json_encode($productImageData), true);

        if (!empty($productImageData)) {

            foreach ($productImageData as $data) {

                $imageArray['image'] = $data['image'];
                $imageArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
                $imageArray['index'] = $data['index'];
                $resultArray[] = $imageArray;
            }
        }

        return $resultArray;
    }

    public function getVariantTypeName($variantTypeId)
    {

        $VariantType = VariantType::where('variant_type_id', $variantTypeId)->first();

        if (!empty($VariantType)) {

            return $VariantType->variant_type;
        } else {

            $value = "";

            return $value;
        }
    }




    public function selfiealbummobile_view($id)
    {
        try {
            Log::channel("selfiealbummobile")->info('** started the selfiealbummobile view method **');
            if ($id != '' && $id > 0) {
                $selfiealbummobile = ProductCatalogue::where('product.product_id', $id)->where('service_id', 6)
                    ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->select('product.*', 'gst_percentage.gst_percentage as gst')->first();

                Log::channel("selfiealbummobile")->info("request value product_id:: $id");

                if (!empty($selfiealbummobile)) {
                    $final = [];
                    $ary = [];
                    $ary['product_id'] = $selfiealbummobile['product_id'];
                    $ary['product_name'] = $selfiealbummobile['product_name'];
                    $ary['no_of_images'] = $selfiealbummobile['no_of_images'];
                    $ary['gst_percentage_id'] = $selfiealbummobile['gst_percentage'];
                    $ary['gst'] = $selfiealbummobile['gst'];
                    $ary['help_url'] = $selfiealbummobile['help_url'];
                    $ary['primary_variant_details'] = $this->getPrimaryVariantDetails($selfiealbummobile['primary_variant_details']);
                    $ary['variant_details'] = $this->getVariantDetails($selfiealbummobile['product_id']);
                    $ary['selected_variants'] = $this->getGlobelVariantDetails(json_decode($selfiealbummobile['selected_variants'], true));
                    $ary['thumbnail_url'] = ($selfiealbummobile['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $selfiealbummobile['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $selfiealbummobile['thumbnail_image'];
                    $ary['customer_description'] = $selfiealbummobile['customer_description'];
                    $ary['designer_description'] = $selfiealbummobile['designer_description'];
                    $ary['is_cod_available'] = $selfiealbummobile['is_cod_available'];
                    // $ary['is_customized'] = $selfiealbummobile['is_customized'];
                    $ary['is_multivariant_available'] = $selfiealbummobile['is_multivariant_available'];
                    $ary['is_related_product_available'] = $selfiealbummobile['is_related_product_available'];
                    $ary['is_notification'] = $selfiealbummobile['is_notification'];
                    $ary['created_on'] = $selfiealbummobile['created_on'];
                    $ary['created_by'] = $selfiealbummobile['created_by'];
                    $ary['updated_on'] = $selfiealbummobile['updated_on'];
                    $ary['updated_by'] = $selfiealbummobile['updated_by'];
                    $ary['status'] = $selfiealbummobile['status'];
                    $ary['related_products'] = $this->getrelated_products($selfiealbummobile->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("selfiealbummobile")->info("view value :: $log");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Selfie album viewed successfully'),
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
            Log::channel("selfiealbummobile")->error($exception);
            Log::channel("selfiealbummobile")->info('** end the selfiealbummobile view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }


    public function getVariantDetails($productId)
    {

        $variantArray = [];
        $resultArray = [];

        $productVariant = ProductVariant::where('product_id', $productId)->get();

        $variantDetails = json_decode($productVariant, true);

        if (!empty($variantDetails)) {

            foreach ($variantDetails as $data) {

                $variantArray['variant_attributes'] = $this->getGlobelVariantDetails(json_decode($data['variant_attributes'], true));
                $variantArray['product_variant_id'] = $data['product_variant_id'];
                // $variantArray['variant_code'] = $data['variant_code'];
                $variantArray['mrp'] = $data['mrp'];
                $variantArray['selling_price'] = $data['selling_price'];
                $amunt = (($variantArray['mrp'] - $variantArray['selling_price']) /  $variantArray['mrp']) * 100;
                $variantArray['offer_percentage'] =  round($amunt . '' . "%", 2);
                // $variantArray['quantity'] = $data['quantity'];
                $variantArray['set_as_default'] = $data['set_as_default'];
                // $variantArray['customized_price'] = $data['customized_price'];
                $variantArray['variant_type_id'] = $data['variant_type_id'];
                $variantArray['variant_type'] = $this->getVariantTypeName($data['variant_type_id']);
                $variantArray['image'] = $data['image'];
                $variantArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
                $variantArray['label'] = $data['label'];
                $variantArray['index'] = $data['internal_variant_id'];
                $variantArray['variant_options'] = json_decode($data['variant_options'], true);
                $resultArray[] = $variantArray;
            }
        }

        return $resultArray;
    }

    public function getGlobelVariantDetails($VariantAttributeData)
    {

        $variantArray = [];
        $resultArray = [];

        if (!empty($VariantAttributeData)) {

            foreach ($VariantAttributeData as $data) {

                $variantArray['variant_type_id'] = $data['variant_type_id'];
                $variantArray['variant_type'] = $this->getVariantTypeName($data['variant_type_id']);
                $variantArray['value'] = $data['value'];
                $resultArray[] = $variantArray;
            }
        }

        return $resultArray;
    }


    public function getPrimaryVariantDetails($primaryVariantDetails)
    {

        $primaryDataArray = [];
        $resultArray = [];

        $primaryData = json_decode($primaryVariantDetails, true);

        if (!empty($primaryData)) {

            foreach ($primaryData as $data) {

                $primaryDataArray['product_image'] = $this->getProductImage($data['product_image']);
                // $primaryDataArray['variant'] = $this->getVariant($data['variant']);
                $primaryDataArray['variant_type_id'] = $data['variant_type_id'];
                $primaryDataArray['variant_type'] = $this->getVariantTypeName($data['variant_type_id']);
                $primaryDataArray['image'] = $data['image'];
                $primaryDataArray['image_url'] = ($data['image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $data['image'] : env('APP_URL') . "avatar.jpg";
                $primaryDataArray['label'] = $data['label'];
                $resultArray[] = $primaryDataArray;
            }
        }

        return $resultArray;
    }

    public function getdefaultImages_allImages($gTImage)
    {
        $imG = [];
        if (!empty($gTImage)) {
            foreach ($gTImage as $im) {
                $ary = [];
                $ary['index'] = $im['index'];
                $ary['url'] = ($im['image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $im['image'] : env('APP_URL') . "avatar.jpg";
                $ary['image'] = $im['image'];
                $imG[] = $ary;
            }
        }
        return $imG;
    }


    public function getrelated_products($proId)
    {
        $related = RelatedProduct::where('p.is_related_product_available', 1)->where('related_products.product_id', $proId)
            ->select('service.service_name', 'product.product_name', 'product.mrp', 'product.selling_price', 'product.first_copy_selling_price', 'related_products.service_id', 'related_products.product_id_related', 'product.thumbnail_image', 'product_variant.quantity')
            ->leftjoin('service', 'service.service_id', '=', 'related_products.service_id')
            ->leftjoin('product as p', 'p.product_id', '=', 'related_products.product_id')
            ->leftjoin('product', 'product.product_id', '=', 'related_products.product_id_related')
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


    public function addToCartForSelfieCreate(SelfieAddToCartRequest $request)
    {
        try {
            Log::channel("addtocart_selfie_web")->info('** started the addtocart create method in selfie album**');

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
            
            if (!empty($request->images)) {
                $gTImage = json_decode($request->images, true);
                if (!empty($gTImage)) {
                    foreach ($gTImage as $d) {

                        $validator = Validator::make($d, [
                            'image' => 'required'
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
                    $request->images;
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data'        => []
                    ]);
                }
            }
            }

            $cart = new AddToCart();
            $cart->customer_id = JwtHelper::getSesUserId();
            $cart->service_id = 6;
            $cart->cart_type  = $request->cart_type;
            $cart->product_id  = $request->product_id;
            $cart->product_variant_id = $request->product_variant_id;
            $cart->quantity = $request->quantity;
            $cart->variant_attributes = $request->variant_attributes;
            $cart->images = $request->images;
            $cart->created_on = Server::getDateTime();
            $cart->created_by = JwtHelper::getSesUserId();

            if ($cart->save()) {

                Log::channel("addtocart_selfie_web")->info("request value :: " . implode(' / ', $request->all()));
                Log::channel("addtocart_selfie_web")->info('** end the addtocart create method in selfie album**');

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Product added to cart successfully'),
                    'data'        => [$cart]
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Product add to cart failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("addtocart_selfie_web")->error($exception);
            Log::channel("addtocart_selfie_web")->error('** end the addtocart create method in selfie album**');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
