<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Requests\SelfieAddToCartRequest;
use App\Http\Traits\SelfieAlbumTrait;
use App\Models\AddToCart;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\ProductVisitHistory;
use App\Models\RelatedProduct;
use App\Models\VariantType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;

class SelfieAlbumWebsiteController extends Controller
{
    use SelfieAlbumTrait;

    public function selfiealbumwebsite_list_old(Request $request)
    {
        try {
            Log::channel("selfiealbumwebsite")->info('** started the selfiealbumwebsite list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';

            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'product_id' => 'product.product_id',
                // 'category_name' => 'category.category_name',
                // 'category_name' => 'category.category_name',
                'date' => 'product.created_on',
                'product_code' => 'product.product_code',
                'product_name' => 'product.product_name',
                'thumbnail_image' => 'product.thumbnail_image',
                'mrp' => 'product_variant.mrp',
                'selling_price' => 'product_variant.selling_price',
                // 'offer_percentage' =>  'product.offer_percentage',
                // 'filter_by' => 'product.filter_by'


            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "product.product_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

            $column_search = array(
                'product.created_on', 'product.product_code',
                'product.product_name', 'product.thumbnail_image', 'product_variant.mrp', 'product_variant.mrp', 'product_variant.selling_price',
                'product.offer_percentage', 'product.filter_by'
                // 'category.category_name',
            );

            $selfiealbumwebsite = ProductCatalogue::select(

                'product.created_on',
                'product.product_code',
                'product.product_name',
                'product.thumbnail_image',
                'product_variant.mrp',
                'product_variant.selling_price',
                // 'product.offer_percentage',
                // 'product.filter_by',
                'product.product_id',
                'product_variant.quantity',
                'product.is_publish',
                'product.status',



            )->leftJoin('product_variant', function ($leftJoin) {
                $leftJoin->on('product_variant.product_id', '=', 'product.product_id')
                    ->where('product_variant.set_as_default', '=', 1);
            })
                // ->leftjoin('category', 'category.category_id', '=', 'product.category_id')
                ->where('product.service_id', 6)
                ->where('product.status', 1)
                ->where('is_publish', 1)
                ->groupBy('product.product_id');

            $selfiealbumwebsite->where(function ($query) use (
                $searchval,
                $column_search,
                $selfiealbumwebsite
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
                $selfiealbumwebsite->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (!empty($from_date)) {
                $selfiealbumwebsite->where(function ($query) use ($from_date) {
                    $query->whereDate('product.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $selfiealbumwebsite->where(function ($query) use ($to_date) {
                    $query->whereDate('product.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus)) {
                $selfiealbumwebsite->where('product.is_publish', $filterByStatus);
            }

            // if (!empty($filterByCategory)) {
            //     $selfiealbumwebsite->where('product.category_id', $filterByStatus);
            // }

            $count = count($selfiealbumwebsite->get());

            if ($offset) {
                $offset = $offset * $limit;
                $selfiealbumwebsite->offset($offset);
            }
            if ($limit) {
                $selfiealbumwebsite->limit($limit);
            }

            $selfiealbumwebsite->orderBy('product.product_id', 'desc');

            $selfiealbumwebsite = $selfiealbumwebsite->get();
            $final = [];

            if ($count > 0) {
                foreach ($selfiealbumwebsite as $value) {
                    $ary = [];
                    $ary['product_id'] = $value['product_id'];
                    $ary['product_code'] = $value['product_code'];
                    // $ary['category_id'] = $value['category_id'];
                    // $ary['category_name'] = $value['category_name'];
                    // $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['product_name'] = $value['product_name'];
                    $ary['thumbnail_image'] = $value['thumbnail_image'];
                    $ary['mrp'] = $value['mrp'];
                    $ary['selling_price'] = $value['selling_price'];
                    // $ary['offer_percentage'] = $value['offer_percentage'];
                    // $ary['filter_By'] = $value['filter_By'];
                    // $amunt = $ary['mrp'] - $ary['selling_price'];
                    // $offer =  $amunt / $ary['mrp'];
                    // $ary['offer_percentage'] =  round($offer . '' . "%", 2);
                    $amunt = (($ary['mrp'] - $ary['selling_price']) /  $ary['mrp']) * 100;
                    // $offer =  $amunt / $ary['mrp'];
                    $ary['offer_percentage'] =  round($amunt . '' . "%", 2);
                    $ary['is_publish'] = $value['is_publish'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }


            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("selfiealbumwebsite")->info("list value :: $log");
                Log::channel("selfiealbumwebsite")->info('** end the selfiealbumwebsite list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Selfie album website listed successfully'),
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
            Log::channel("selfiealbumwebsite")->error($exception);
            Log::channel("selfiealbumwebsite")->error('** end the selfiealbumwebsite list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
    
    public function selfiealbumwebsite_list(Request $request)
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
            Log::channel("selfiealbum")->info('** started the selfiealbum website list method **');
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
                    $ary['service_id'] = $value['service_id'];
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
            Log::channel("selfiealbum")->error('** end the selfiealbum website list method **');

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
            `product`.`service_id`,
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




    public function selfiealbumwebsite_view($id)
    {
        try {
            Log::channel("selfiealbum")->info('** started the selfiealbum view method **');
            if ($id != '' && $id > 0) {
                $selfiealbumwebsite = ProductCatalogue::where('product.product_id', $id)->where('service_id', 6)
                    ->leftjoin('gst_percentage', 'gst_percentage.gst_percentage_id', '=', 'product.gst_percentage')
                    ->select('product.*', 'gst_percentage.gst_percentage as gst')->first();

                Log::channel("selfiealbum")->info("request value product_id:: $id");

                if (!empty($selfiealbumwebsite)) {
                    $final = [];
                    $ary = [];
                    $ary['product_id'] = $selfiealbumwebsite['product_id'];
                    $ary['product_name'] = $selfiealbumwebsite['product_name'];
                    $ary['no_of_images'] = $selfiealbumwebsite['no_of_images'];
                    $ary['gst_percentage_id'] = $selfiealbumwebsite['gst_percentage'];
                    $ary['gst'] = $selfiealbumwebsite['gst'];
                    $ary['help_url'] = $selfiealbumwebsite['help_url'];
                    $ary['primary_variant_details'] = $this->getPrimaryVariantDetails($selfiealbumwebsite['primary_variant_details']);
                    $ary['variant_details'] = $this->getVariantDetails($selfiealbumwebsite['product_id']);
                    $ary['selected_variants'] = $this->getGlobelVariantDetails(json_decode($selfiealbumwebsite['selected_variants'], true));
                    $ary['thumbnail_url'] = ($selfiealbumwebsite['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $selfiealbumwebsite['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $selfiealbumwebsite['thumbnail_image'];
                    $ary['customer_description'] = $selfiealbumwebsite['customer_description'];
                    $ary['designer_description'] = $selfiealbumwebsite['designer_description'];
                    $ary['is_cod_available'] = $selfiealbumwebsite['is_cod_available'];
                    // $ary['is_customized'] = $selfiealbumwebsite['is_customized'];
                    $ary['is_multivariant_available'] = $selfiealbumwebsite['is_multivariant_available'];
                    $ary['is_related_product_available'] = $selfiealbumwebsite['is_related_product_available'];
                    $ary['is_notification'] = $selfiealbumwebsite['is_notification'];
                    $ary['created_on'] = $selfiealbumwebsite['created_on'];
                    $ary['created_by'] = $selfiealbumwebsite['created_by'];
                    $ary['updated_on'] = $selfiealbumwebsite['updated_on'];
                    $ary['updated_by'] = $selfiealbumwebsite['updated_by'];
                    $ary['status'] = $selfiealbumwebsite['status'];
                    $ary['related_products'] = $this->getrelated_products($selfiealbumwebsite->product_id);
                    $final[] = $ary;
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("selfiealbum")->info("view value :: $log");
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
            Log::channel("selfiealbum")->error($exception);
            Log::channel("selfiealbum")->info('** end the selfiealbum view method **');
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

    // public function addToCartForSelfieUpdate(Request $request)
    // {
    //     try {
    //         Log::channel("addtocart_photoframe_web")->info('** started the addtocart update method in photo frame**');
    //         $id = $request->input('add_to_cart_id');
    //         $cart = AddToCart::find($id);
    //         $cart->product_id  = $request->product_id;
    //         $cart->product_variant_id = $request->product_variant_id;
    //         $cart->variant_attributes = $request->variant_attributes;
    //         $cart->frames = $request->frames;
    //         $cart->quantity = $request->quantity;
    //         $cart->customer_id = JwtHelper::getSesUserId();
    //         $cart->created_on = Server::getDateTime();
    //         $cart->created_by = JwtHelper::getSesUserId();

    //         if ($cart->save()) {

    //             Log::channel("addtocart_photoframe_web")->info("request value :: " . implode(' / ', $request->all()));
    //             Log::channel("addtocart_photoframe_web")->info('** end the addtocart update method in photo frame**');

    //             return response()->json([
    //                 'keyword'      => 'success',
    //                 'message'      => __('Product successfully added to cart'),
    //                 'data'        => [$cart]
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'keyword'      => 'failed',
    //                 'message'      => __('add to cart failed'),
    //                 'data'        => []
    //             ]);
    //         }
    //     } catch (\Exception $exception) {
    //         Log::channel("addtocartmobile")->error($exception);
    //         Log::channel("addtocartmobile")->error('** end the addtocart create method **');

    //         return response()->json([
    //             'error' => 'Internal server error.',
    //             'message' => $exception->getMessage()
    //         ], 500);
    //     }
    // }
}
