<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Http\Controllers\Controller;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Requests\PersonalizedAddToCartRequest;
use App\Http\Traits\PersonalizedProductTrait;
use App\Models\ProductCatalogue;
use App\Models\AddToCart;
use App\Models\ProductVisitHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;

class PersonalizedProductController extends Controller
{

    use PersonalizedProductTrait;

    public function personalizedList(Request $request)
    {
        try {
            $data = new ProductVisitHistory();
        $data->service_id = 4;
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
            Log::channel("Personalized")->info('** started the Personalized mobile list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $fromPrice = $request->fromPrice;
            $toPrice = $request->toPrice;
            $priceSort = $request->priceSort;

            $qry = $this->generateQuery($request);

            $qry_count = $qry;

            $qry_count .=  " group by `product`.`product_id`";

            $get_personalized_count = DB::select(DB::raw($qry_count));

            $get_personalized_count = json_decode(json_encode($get_personalized_count), true);

            $get_personalized_count = collect($get_personalized_count);

            $max_price = $get_personalized_count->max('selling_price');
            $min_price = $get_personalized_count->min('selling_price');

            // $count = Count($get_personalized_count);

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

            $get_personalized = DB::select(DB::raw($qry));
            
            $get_personalized = json_decode(json_encode($get_personalized), true);

            if (!empty($get_personalized)) {
                $final = [];
                foreach ($get_personalized as $value) {
                    $ary = [];
                    $ary['product_id'] = $value['product_id'];
                    $ary['product_variant_id'] = $value['product_variant_id'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['category_id'] = $value['category_id'];
                    $ary['category_name'] = $value['category_name'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['product_name'] = $value['product_name'];
                    $ary['mrp'] = $value['mrp'];
                    $ary['quantity'] = $value['quantity'];
                    $ary['selling_price'] = $value['selling_price'];
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                    $ary['thumbnail_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['is_publish'] = $value['is_publish'];
                    $ary['status'] = $value['status'];
                    $ary['stock_status'] = "0";
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                // $log = json_encode($final, true);
                // Log::channel("Personalized")->info("list value :: $log");
                Log::channel("Personalized")->info('** end the Personalized mobile list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Personalized product listed successfully'),
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
            Log::channel("Personalized")->error($exception);
            Log::channel("Personalized")->error('** end the Personalized mobile list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function generateQuery($request)
    {

        $category = json_decode($request->category, true);
        $fromPrice = $request->fromPrice;
        $toPrice = $request->toPrice;
        $filerByVariant = $request->filerByVariant;
        $primaryVariantTypeId = $request->primaryVariantTypeId;
        $primaryVariantValue = $request->primaryVariantValue;

        $filerByVariant = json_decode($filerByVariant, true);

        $i = 0;
        $qry = "";
        $finalary = [];
        $personalized = "";

        $personalized .= "SELECT 
            `product`.`product_id`,
            `product_variant`.`product_variant_id`, 
            `product`.`created_on`,
            `product`.`product_code`,
            `product`.`is_customized`,
            `product`.`product_name`,
            `product`.`category_id`,
            `product`.`thumbnail_image`,
            `category`.`category_name`,
            `product_variant`.`customized_price`, 
            `product_variant`.`selling_price`, 
            `product_variant`.`mrp`,
            `product_variant`.`quantity`,
            `product_variant`.`image`, 
            `product_variant`.`variant_attributes`, 
            `product`.`is_publish`, 
            `product`.`status` 
            FROM `product_variant` 
             LEFT JOIN `product` ON `product`.`product_id` = `product_variant`.`product_id`";
        if ($primaryVariantTypeId == "" && $primaryVariantValue == "" && $filerByVariant == "") {

            $personalized .= " and `product_variant`.`set_as_default` = 1 ";
        }
        $personalized .= " LEFT JOIN `category` ON `category`.`category_id` = `product`.`category_id`";

        $personalized .= " WHERE
            `product`.`service_id` = 4
            AND `product`.`status` = 1 
            AND `product`.`is_publish` = 1";

        if (!empty($category)) {
            // $category = json_encode($category, true);

            // $category_trim = trim($category, '[]');

            // $personalized .= " AND `product`.`category_id` IN($category_trim)";
            $personalized .= " AND (`product`.`category_id` = $category)";
        }

        if ($primaryVariantTypeId != '') {

            $personalized .= " AND (`product_variant`.`variant_type_id` = $primaryVariantTypeId)";
        }

        if ($primaryVariantValue != '') {

            $personalized .= " AND (`product_variant`.`label` = '$primaryVariantValue')";
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

            $personalized .= implode(" ", $finalary);
        }

        return $personalized;
    }


    public function personalizedView($id)
    {
        try {
            Log::channel("personalized")->info('** started the personalized mobile view method **');
            if ($id != '' && $id > 0) {
                $personalized = ProductCatalogue::where('product.product_id', $id)->where('product.service_id', 4)
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
                    $ary['stock_status'] = "0";
                    $ary['related_products'] = $this->getrelated_products($personalized->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    // $log = json_encode($final, true);
                    // Log::channel("personalized")->info("view mobile value :: $log");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Personalized product viewed successfully'),
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
            Log::channel("Personalized")->info('** end the Personalized mobile view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }


    public function addToCartForPersonalized(PersonalizedAddToCartRequest $request)
    {
        try {
            Log::channel("addtocart_personalized_web")->info('** started the addtocart create method in photo frame**');

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
            
            if (!empty($request->variant_attributes)) {
                $gTImage = json_decode($request->variant_attributes, true);
                if (!empty($gTImage)) {
                    foreach ($gTImage as $im) {
                        if (isset($im['reference_image'])) {
                            $reference_image = $im['reference_image'];
                        }
                        if (isset($im['image'])) {
                            $image = $im['image'];
                        }
                        if (isset($im['labels'])) {
                            $labels = $im['labels'];
                        }
                    }
                    //reference_image
                    if (!empty($reference_image)) {
                        foreach ($reference_image as $d) {

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
                        foreach ($reference_image as $img) {
                            $ary[] = pathinfo($img['image'], PATHINFO_EXTENSION);
                        }
                        $extension_array = ['jpeg', 'png', 'jpg'];
                        if (!array_diff($ary, $extension_array)) {
                            $request->variant_attributes;
                        } else {
                            return response()->json([
                                'keyword'      => 'failed',
                                'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                                'data'        => []
                            ]);
                        }
                    }

                    //image
                    if (!empty($image)) {
                        foreach ($image as $d) {

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
                        foreach ($image as $img) {
                            $ary[] = pathinfo($img['image'], PATHINFO_EXTENSION);
                        }
                        $extension_array = ['jpeg', 'png', 'jpg'];
                        if (!array_diff($ary, $extension_array)) {
                            $request->variant_attributes;
                        } else {
                            return response()->json([
                                'keyword'      => 'failed',
                                'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                                'data'        => []
                            ]);
                        }
                    }

                    //label
                    if (!empty($labels)) {
                        foreach ($labels as $d) {

                            $validator = Validator::make($d, [
                                'content' => 'required'
                            ]);

                            if ($validator->fails()) {
                                return response()->json([
                                    "keyword"    => 'failed',
                                    "message"   => $validator->errors()->first(),
                                    "data"  => []
                                ]);
                            }
                        }
                    }
                }
            }

            $cart = new AddToCart();
            $cart->service_id = 4;
            $cart->product_id  = $request->product_id;
            $cart->product_variant_id = $request->product_variant_id;
            $cart->variant_attributes = $request->variant_attributes;
            $cart->is_customized = $request->is_customized;
            $cart->quantity = $request->quantity;
            $cart->cart_type = $request->cart_type;
            $cart->customer_id = JwtHelper::getSesUserId();
            $cart->created_on = Server::getDateTime();
            $cart->created_by = JwtHelper::getSesUserId();

            if ($cart->save()) {

                Log::channel("addtocart_personalized_web")->info("request value :: " . implode(' / ', $request->all()));
                Log::channel("addtocart_personalized_web")->info('** end the addtocart create method in photo frame**');

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
            Log::channel("addtocart_personalized_web")->error($exception);
            Log::channel("addtocart_personalized_web")->error('** end the addtocart create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
