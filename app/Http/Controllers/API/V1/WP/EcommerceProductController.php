<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Requests\EcommerceAddToCartRequest;
use App\Http\Requests\EcommerceRequest;
use App\Http\Traits\EcommerceProductTrait;
use App\Models\AddToCart;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\ProductVisitHistory;
use App\Models\Rating;
use App\Models\RelatedProduct;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class EcommerceProductController extends Controller
{

    use EcommerceProductTrait;

    public function getRatingProducts($rId)
    {
        $rating = Rating::where('product_id', '=', $rId)->where('status', 2)->select(DB::raw('AVG(rating_review.rating) as ratings_average'))->value('ratings_average');
        return round($rating, 1);
    }

    public function getReviewProducts($review_Id)
    {
        $rating = Rating::where('product_id', '=', $review_Id)->where('status', 2)->select('rating_review.review')->count();
        return ($rating);
    }

    public function ecommerceList(Request $request)
    {
        try {
            $data = new ProductVisitHistory();
            $data->service_id = 5;
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
            Log::channel("ecommerce")->info('** started the ecommerce website list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $category = json_decode($request->category, true);
            $fromPrice = $request->fromPrice;
            $toPrice = $request->toPrice;
            $priceSort = $request->priceSort;

            $filerByVariant = $request->filerByVariant;

            $qry = $this->generateQuery($request);

            $qry_count = $qry;

            $qry_count .=  " group by `product`.`product_id`";

            $get_ecommerce_count = DB::select(DB::raw($qry_count));

            $get_ecommerce_count = json_decode(json_encode($get_ecommerce_count), true);

            $get_ecommerce_count = collect($get_ecommerce_count);

            $max_price = $get_ecommerce_count->max('selling_price');
            $min_price = $get_ecommerce_count->min('selling_price');

            // $count = Count($get_ecommerce_count);

            if ($fromPrice != '') {
                $qry .= " AND `
                `.`selling_price` >= $fromPrice";
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

            $get_ecommerce = DB::select(DB::raw($qry));

            $get_ecommerce = json_decode(json_encode($get_ecommerce), true);

            if (!empty($get_ecommerce)) {
                $final = [];
                foreach ($get_ecommerce as $value) {
                    $ary = [];
                    $ary['product_id'] = $value['product_id'];
                    $ary['service_id'] = $value['service_id'];
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
                    $ary['thumbnail_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['is_publish'] = $value['is_publish'];
                    $ary['status'] = $value['status'];
                    $ary['stock_status'] = "0";
                    $ary['rating'] = $this->getRatingProducts($value['product_id']);
                    $ary['reviews'] = $this->getReviewProducts($value['product_id']);
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                // $log = json_encode($final, true);
                // Log::channel("ecommerce")->info("list value :: $log");
                Log::channel("ecommerce")->info('** end the ecommerce website list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Ecommerce product listed successfully'),
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
            Log::channel("ecommerce")->error($exception);
            Log::channel("ecommerce")->error('** end the ecommerce website list method **');

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
        $ecommerce = "";

        $ecommerce .= "SELECT 
            `product`.`product_id`,
            `product`.`service_id`,
            `product_variant`.`product_variant_id`, 
            `product`.`created_on`,
            `product`.`product_code`,
            `product`.`product_name`,
            `product`.`category_id`,
            `category`.`category_name`,
            `product_variant`.`selling_price`, 
            `product_variant`.`mrp`,
            `product_variant`.`quantity`,
            `product`.`thumbnail_image`, 
            `product_variant`.`variant_attributes`, 
            `product`.`is_publish`, 
            `product`.`status` 
            FROM `product_variant` 
             LEFT JOIN `product` ON `product`.`product_id` = `product_variant`.`product_id`";
        if ($primaryVariantTypeId == "" && $primaryVariantValue == "" && $filerByVariant == "") {

            $ecommerce .= " and `product_variant`.`set_as_default` = 1 ";
        }
        $ecommerce .= " LEFT JOIN `category` ON `category`.`category_id` = `product`.`category_id`";

        $ecommerce .= " WHERE
            `product`.`service_id` = 5
            AND `product`.`status` = 1 
            AND `product`.`is_publish` = 1";

        if (!empty($category)) {
            // $category = json_encode($category, true);

            // $category_trim = trim($category, '[]');

            // $ecommerce .= " AND `product`.`category_id` IN($category_trim)";
            $ecommerce .= " AND (`product`.`category_id` = $category)";
        }

        if ($primaryVariantTypeId != '') {

            $ecommerce .= " AND (`product_variant`.`variant_type_id` = $primaryVariantTypeId)";
        }

        if ($primaryVariantValue != '') {

            $ecommerce .= " AND (`product_variant`.`label` = '$primaryVariantValue')";
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

            $ecommerce .= implode(" ", $finalary);
        }

        return $ecommerce;
    }

    public function ecommerceView($id)
    {
        // try {
        Log::channel("ecommerce")->info('** started the ecommerce website view method **');
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
                $ary['stock_status'] = "0";
                $ary['rating'] = $this->getRatingProducts($ecommerce['product_id']);
                $ary['reviews'] = $this->getReviewProducts($ecommerce['product_id']);
                $ary['related_products'] = $this->getrelated_products($ecommerce->product_id);
                $final[] = $ary;
            }
            if (!empty($final)) {
                // $log = json_encode($final, true);
                // Log::channel("ecommerce")->info("view website value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Ecommerce products viewed successfully'),
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
        // } catch (\Exception $exception) {
        //     Log::channel("ecommerce")->error($exception);
        //     Log::channel("ecommerce")->info('** end the ecommerce website view method **');
        //     return response()->json([
        //         'error' => 'Internal server error.',
        //         'message' => $exception->getMessage(),
        //     ], 500);
        // }
    }

    public function addToCartForEcommerceCreate(EcommerceAddToCartRequest $request)
    {
        try {
            Log::channel("addtocart_ecommerce_web")->info('** started the addtocart create method in selfie album**');

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

            $check = AddToCart::where([
                ['customer_id', '=', JwtHelper::getSesUserId()], ['product_id', '=', $request->product_id], ['product_variant_id', '=', $request->product_variant_id],
                ['status', '!=', 2]
            ])->first();
            if (empty($check)) {
                $cart = new AddToCart();
                $cart->customer_id = JwtHelper::getSesUserId();
                $cart->service_id = 5;
                $cart->cart_type  = $request->cart_type;
                $cart->product_id  = $request->product_id;
                $cart->product_variant_id = $request->product_variant_id;
                $cart->quantity = $request->quantity;
                $cart->variant_attributes = $request->variant_attributes;
                $cart->created_on = Server::getDateTime();
                $cart->created_by = JwtHelper::getSesUserId();

                if ($cart->save()) {

                    Log::channel("addtocart_ecommerce_web")->info("request value :: " . implode(' / ', $request->all()));
                    Log::channel("addtocart_ecommerce_web")->info('** end the addtocart create method in selfie album**');

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
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('The selected product is already present in cart, so increase the quantity in cart'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("addtocart_ecommerce_web")->error($exception);
            Log::channel("addtocart_ecommerce_web")->error('** end the addtocart create method in selfie album**');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function ratingcreate(Request $request)
    {
        $rating = new Rating();
        $rating->product_id = $request->input('product_id');
        $rating->order_id = $request->input('order_id');
        $rating->customer_id = JwtHelper::getSesUserId();
        $rating->rating = $request->input('rating');
        $rating->review = $request->input('review');
        $rating->created_on = Server::getDateTime();
        $rating->created_by = JwtHelper::getSesUserId();

        if ($rating->save()) {

            $rating_data = Rating::where('rating_review.rating_review_id', $rating->rating_review_id)->leftjoin('customer', 'customer.customer_id', '=', 'rating_review.customer_id')->leftjoin('product', 'product.product_id', '=', 'rating_review.product_id')->select('customer.*', 'rating_review.*', 'product.*')->first();

            //$ord = GlobalHelper::getRating($rating_data->rating_review_id);

            // $emp_info = [
            //     'first_name' => $rating_data->billing_customer_first_name,
            //     'last_name' => $rating_data->billing_customer_last_name,
            //     'product_name' => $rating_data->product_name
            // ];

            // $title = Config('fcm_msg.title.member_complaint_respond');
            // $body = Config('fcm_msg.body.rating');
            // $body = GlobalHelper::mergeFields($body, $emp_info);
            // $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
            // $module = 'Rating & Review';
            // $portal = "admin";
            // $page = 'rating_review';
            // $titlemod = "Rating & Review !";
            // $data = [
            //     'customer_id' => $rating_data->customer_id,
            //     'product_id' => $rating_data->product_id,
            //     'random_id' => $random_id,
            //     'rating_review_id' => $rating_data->rating_review_id,
            //     'page' => $page
            // ];

            // $token = UserModel::where('token', '!=', NULL)->select('token')->get();
            // if (!empty($token)) {
            //     $tokens = [];
            //     foreach ($token as $tk) {
            //         $tokens[] = $tk['token'];
            //     }
            //     foreach (array_chunk($tokens, 500) as $tok) {
            //         for ($i = 0; $i < count($tok); $i++) {
            //             $key = ($tok[$i]) ? $tok[$i] : " ";
            //             if (!empty($key)) {
            //                 $message = [
            //                     'title' => $titlemod,
            //                     'body' => $body,
            //                     'page' => $page,
            //                     'data' => $data,
            //                     'portal' => $portal,
            //                     'module' => $module
            //                 ];
            //                 $push = Firebase::sendMultiple($key, $message);
            //             }
            //         }
            //     }
            // }
            // $getdata = GlobalHelper::notification_create($titlemod, $body, 2, 2, 1, $module, $page, "admin", $data, $random_id);

            return response()->json([
                'keyword'      => 'success',
                'message'      => 'Rating created successfully',
                'data'        => $rating
            ]);
        } else {
            return response()->json([
                'keyword'      => 'failed',
                'message'      => 'Rating creation failed',
                'data'        => []
            ]);
        }
    }


    public function ratingReviewProductList(Request $request, $id)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "rating_review.product_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('');
        $ratingReviewProduct = Rating::where('rating_review.product_id', $id)->where('rating_review.status', 2)
            ->leftjoin('product', 'product.product_id', '=', 'rating_review.product_id')
            ->leftjoin('customer', 'customer.customer_id', '=', 'rating_review.customer_id')
            ->select('rating_review.*', 'customer.profile_image', 'customer.auth_provider', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.check_profile_image', 'rating_review.created_on', 'rating_review.rating', 'rating_review.review');
        $ratingReviewProduct->where(function ($query) use ($searchval, $column_search, $ratingReviewProduct) {
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
            $ratingReviewProduct->orderBy($order_by_key[$sortByKey], $sortType);
        }
        $count = $ratingReviewProduct->count();
        if ($offset) {
            $offset = $offset * $limit;
            $ratingReviewProduct->offset($offset);
        }
        if ($limit) {
            $ratingReviewProduct->limit($limit);
        }
        $ratingReviewProduct->orderBy('rating_review.rating', 'desc');
        $ratingReviewProduct = $ratingReviewProduct->get();
        if ($count > 0) {
            $final = [];
            foreach ($ratingReviewProduct as $value) {
                $ary = [];
                $ary['customer_id'] = $value['customer_id'];
                $ary['image'] = $value->profile_image;
                if ($value->auth_provider == "facebook" || $value->auth_provider == "google") {
                    $cus_image = $value->profile_image;
                }
                if ($value->auth_provider == "" || $value->auth_provider == "apple" || $value->check_profile_image == 1) {
                    $cus_image = ($ary['image'] != '') ? env('APP_URL') . env('PROFILE_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
                }
                $ary['profile_image'] = $cus_image;
                $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                $ary['created_on'] = $value['created_on'];
                $ary['rating'] = $value['rating'];
                $ary['review'] = $value['review'];
                $final[] = $ary;
            }
        }
        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Rating review listed successfully'),
                    'data' => $final,
                    'count' => $count
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                    'count' => $count
                ]
            );
        }
    }
}
