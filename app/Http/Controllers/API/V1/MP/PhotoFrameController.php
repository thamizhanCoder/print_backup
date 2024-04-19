<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Http\Requests\PhotoFrameAddToCartRequest;
use App\Http\Traits\PhotoFrameTrait;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\AddToCart;
use App\Models\ProductVisitHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;

class PhotoFrameController extends Controller
{
    use PhotoFrameTrait;

    public function photoframeList(Request $request)
    {
        try {
        $data = new ProductVisitHistory();
        $data->service_id = 3;
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
            Log::channel("photoframe")->info('** started the photoframe mobile list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $fromPrice = $request->fromPrice;
            $toPrice = $request->toPrice;
            $priceSort = $request->priceSort;

            $filerByVariant = $request->filerByVariant;

            $qry = $this->generateQuery($request);

            $qry_count = $qry;

            $qry_count .=  " group by `product`.`product_id`";

            $get_photoframe_count = DB::select(DB::raw($qry_count));

            $get_photoframe_count = json_decode(json_encode($get_photoframe_count), true);

            $get_photoframe_count = collect($get_photoframe_count);

            $max_price = $get_photoframe_count->max('selling_price');
            $min_price = $get_photoframe_count->min('selling_price');

            // $count = Count($get_photoframe_count);

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

            $get_photoframe = DB::select(DB::raw($qry));

            $get_photoframe = json_decode(json_encode($get_photoframe), true);

            if (!empty($get_photoframe)) {
                $final = [];
                foreach ($get_photoframe as $value) {
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
                    $ary['thumbnail_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['is_publish'] = $value['is_publish'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                // $log = json_encode($final, true);
                // Log::channel("Personalized")->info("list value :: $log");
                Log::channel("photoframe")->info('** end the photoframe website list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Photo frame product listed successfully'),
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
            Log::channel("photoframe")->error($exception);
            Log::channel("photoframe")->error('** end the photoframe mobile list method **');

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
            `product`.`service_id` = 3
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

    public function photoframeView($id)
    {
        try {
            Log::channel("photoframe")->info('** started the photoframe mobile view method **');
            if ($id != '' && $id > 0) {
                $photoprint = ProductCatalogue::where('product.product_id', $id)->where('product.service_id', 3)
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
                    // $log = json_encode($final, true);
                    // Log::channel("photoframe")->info("view mobile value :: $log");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Photo frame viewed successfully'),
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
            Log::channel("photoframe")->info('** end the photoframe mobile view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function variantDetailsList(Request $request, $id)
    {
        $get_variantdetails = ProductCatalogue::where('service_id', $id)->where('product.status', 1)->where('product.is_publish', 1)->leftjoin('product_variant', 'product_variant.product_id', '=', 'product.product_id')->select('product_variant_id', 'product_variant.product_id', 'variant_attributes')->get();
        $getPrimaryvariantdetails = ProductCatalogue::where('product.service_id', $id)->where('product.status', 1)->where('product.is_publish', 1)
            ->select('product_variant.variant_type_id', 'product_variant.label as value', 'variant_type.variant_type', 'product.service_id')
            ->leftjoin('product_variant', 'product_variant.product_id', '=', 'product.product_id')
            ->leftjoin('variant_type', 'variant_type.variant_type_id', '=', 'product_variant.variant_type_id')->groupby('product_variant.variant_type_id')->get();

        $count = $get_variantdetails->count();

        $final = [];
        if ($count > 0) {
            foreach ($get_variantdetails as $vd) {

                if ($vd['variant_attributes'] != null) {

                    $value = $this->getVariantAttributesList($vd['variant_attributes']);

                    if (!empty($value)) {

                        foreach ($value as $v) {

                            $finalary['variant_type_id']  = $v['variant_type_id'];
                            $finalary['variant_type']  = $v['variant_type'];
                            $final[] = $finalary;
                        }
                    }
                }
            }
        }

        $result = collect($final);
        $resfinal = $result->unique()->all();

        $varArray = [];
        $varFinal = [];

        if (count($resfinal) > 0) {

            foreach ($resfinal as $rs) {

                $varArray['variant_type_id'] = $rs['variant_type_id'];
                $varArray['variant_type'] = $rs['variant_type'];
                $varArray['value'] = $this->getvaluesFromVarient($rs['variant_type_id'], $get_variantdetails);
                $varArray['type'] = "other_variant";
                $varFinal[] = $varArray;
            }
        }


        $pmArray = [];
        $pmFinal = [];

        if (!empty($getPrimaryvariantdetails)) {

            foreach ($getPrimaryvariantdetails as $pm) {

                $pmArray['variant_type_id'] = $pm['variant_type_id'];
                $pmArray['variant_type'] = $pm['variant_type'];
                $pmArray['value'] = $this->getPrimaryValues($pm['variant_type_id'], $pm['service_id']);
                $pmArray['type'] = "primary_variant";
                $pmFinal[] = $pmArray;
            }
        }

        $finallist = array_merge($pmFinal, $varFinal);

        if (!empty($finallist)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Variant details listed successfully'),
                    'data' => $finallist,
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                ]
            );
        }
    }

    public function getPrimaryValues($variant_type_id, $service_id)
    {
        // $prodcutVariant = ProductVariant::select('label')->where('variant_type_id', $variant_type_id)->get();
        $prodcutVariant = ProductCatalogue::where('product.service_id', $service_id)
            ->where('product.status', 1)
            ->where('product.is_publish', 1)
            ->where('product_variant.variant_type_id', $variant_type_id)
            ->select('product_variant.label')
            ->leftjoin('product_variant', 'product_variant.product_id', '=', 'product.product_id')
            ->get();

        $final = [];
        $ary = [];

        if (!empty($prodcutVariant)) {

            foreach ($prodcutVariant  as $variant) {

                if ($variant['label'] != null && $variant['label'] != "" && $variant['label'] != "Non-Color") {

                    $final[]  = $variant['label'];
                }
            }
        }

        $result = collect($final);
        $resfinal = $result->unique()->values();

        return $resfinal;
    }


    public function getVariantAttributesList($variant_attributes)
    {

        $details = json_decode($variant_attributes, true);

        $final = [];
        $ary = [];

        if (!empty($details)) {
            foreach ($details as $det) {

                $ary['variant_type_id'] = $det['variant_type_id'];
                $ary['variant_type'] = $det['variant_type'];
                $final[] = $ary;
            }
        }
        return $final;
    }

    public function getvaluesFromVarient($variantTypeID, $get_variantdetails)
    {

        $final = [];
        $ary = [];

        if (!empty($get_variantdetails)) {

            foreach ($get_variantdetails  as $variant) {

                if ($variant['variant_attributes'] != null) {

                    $value =  $this->getFinalValues($variant['variant_attributes'], $variantTypeID);

                    if (!empty($value)) {

                        $final[]  = $value;
                    }
                }
            }
        }

        $result = collect($final);
        $resfinal = $result->unique();

        if (!empty($resfinal)) {

            foreach ($resfinal as $fn) {

                $ary = array_merge($ary, $fn);
            }
        }

        $result1 = collect($ary);
        $resfinal1 = $result1->unique();

        return $resfinal1;
    }

    public function getFinalValues($variant_attributes, $variantTypeID)
    {

        $final = [];
        $variant_attributes = json_decode($variant_attributes, true);

        if (!empty($variant_attributes)) {

            foreach ($variant_attributes  as $variant) {

                if ($variant['variant_type_id'] == $variantTypeID) {

                    $final[]  = $variant['value'];
                }
            }
        }
        return $final;
    }

    public function addToCartForPhotoFrame(PhotoFrameAddToCartRequest $request)
    {
        try {
            Log::channel("addtocart_photoframe_web")->info('** started the addtocart create method in photo frame**');

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
            
            if (!empty($request->frames)) {
                $gTImage = json_decode($request->frames, true);
                if (!empty($gTImage)) {
                    foreach ($gTImage as $im) {
                        $imageArray = $im['images'];
                    }
                    foreach ($imageArray as $d) {

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
                    if (!empty($imageArray)) {
                        foreach ($imageArray as $img) {
                            $ary[] = pathinfo($img['image'], PATHINFO_EXTENSION);
                        }
                    }
                    $extension_array = ['jpeg', 'png', 'jpg'];
                    if (!array_diff($ary, $extension_array)) {
                        $request->frames;
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
            $cart->service_id = 3;
            $cart->product_id  = $request->product_id;
            $cart->product_variant_id = $request->product_variant_id;
            $cart->variant_attributes = $request->variant_attributes;
            $cart->frames = $request->frames;
            $cart->quantity = $request->quantity;
            $cart->cart_type = $request->cart_type;
            $cart->customer_id = JwtHelper::getSesUserId();
            $cart->created_on = Server::getDateTime();
            $cart->created_by = JwtHelper::getSesUserId();

            if ($cart->save()) {

                Log::channel("addtocart_photoframe_web")->info("request value :: " . implode(' / ', $request->all()));
                Log::channel("addtocart_photoframe_web")->info('** end the addtocart create method in photo frame**');

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
            Log::channel("addtocart_photoframe_web")->error($exception);
            Log::channel("addtocart_photoframe_web")->error('** end the addtocart create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
