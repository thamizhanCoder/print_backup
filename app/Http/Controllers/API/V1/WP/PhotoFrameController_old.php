<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Http\Controllers\Controller;
use App\Http\Traits\PhotoFrameTrait;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\Service;
use App\Models\VariantType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhotoFrameController_old extends Controller
{
    use PhotoFrameTrait;

    public function photoframeList(Request $request)
    {
        try {
            Log::channel("photoframe")->info('** started the photoframe website list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $fromPrice = $request->fromPrice;
            $toPrice = $request->toPrice;
            $priceSort = $request->priceSort;

            $filerByVariant = $request->filerByVariant;

            $qry = $this->generateQuery($filerByVariant, $fromPrice, $toPrice);

            $get_photoframe_count = DB::select(DB::raw($qry));

            $get_photoframe_count = json_decode(json_encode($get_photoframe_count), true);

            $count = Count($get_photoframe_count);

            $qry .= " ORDER BY";

            if ($priceSort == 1) {

                $qry .= "`selling_price` ASC,";
            }

            if ($priceSort == 2) {

                $qry .= "`selling_price` DESC,";
            }

            $qry .= " `product_id` DESC";

            if ($limit) {
                $qry .= " LIMIT $limit";
            }

            if ($offset) {
                $offset = $limit * $offset;
                $qry .= " OFFSET $offset";
            }

            $get_photoframe = DB::select(DB::raw($qry));

            $get_photoframe = json_decode(json_encode($get_photoframe), true);

            $get_photoframe = collect($get_photoframe);

            $max_price = $get_photoframe->max('selling_price');
            $min_price = $get_photoframe->min('selling_price');

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
                    $ary['offer_percentage'] =  round($amunt);
                    $ary['thumbnail_url'] = ($value['image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['is_publish'] = $value['is_publish'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                // $log = json_encode($final, true);
                // Log::channel("photoframe")->info("list value :: $log");
                Log::channel("photoframe")->info('** end the photoframe website list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Photo frame product listed successfully'),
                    'data' => $final,
                    'count' => $count,
                    'min_price' => $min_price,
                    'max_price' => $max_price
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
            Log::channel("photoframe")->error('** end the photoframe website list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }



    public function generateQuery($filerByVariant, $fromPrice, $toPrice)
    {

        $filerByVariant = json_decode($filerByVariant, true);

        $i = 0;
        $qry = "";
        $finalary = [];
        $photoframe = "";

        if (!empty($filerByVariant)) {

            foreach ($filerByVariant as $varient) {

                $variant_type_id = $varient['variant_type_id'];
                $type = $varient['type'];

                if (gettype($varient['value']) == "string") {

                    $value =  '"' . $varient['value'] . '"';
                } else {

                    $value =  $varient['value'];
                }

                if ($i > 0) {

                    $photoframe = " UNION ";
                }

                $photoframe .= "SELECT 
            `product`.`product_id`,
            `product`.`created_on`,
            `product`.`product_code`,
            `product`.`product_name`,
            `product_variant`.`selling_price`, 
            `product_variant`.`mrp`,
            `product_variant`.`image`, 
            `product_variant`.`variant_attributes`, 
            `product_variant`.`product_variant_id`, 
            `product`.`is_publish`, 
            `product`.`status` 
            FROM `product_variant` 
             LEFT JOIN `product` ON `product`.`product_id` = `product_variant`.`product_id`";

                // if ($type == "other_variant") {
                    $photoframe .=       ",JSON_TABLE(
                variant_attributes,
                '$[*]' COLUMNS(
                    `variant_type_id` VARCHAR(11) PATH '$.variant_type_id',
                    `variant_type` VARCHAR(11) PATH '$.variant_type',
                    `value` VARCHAR(11) PATH '$.value'
                )
            ) `get_value`";
                // }

                $photoframe .= " WHERE
            `product`.`service_id` = 3
            AND `product`.`status` = 1 
            AND `product`.`is_publish` = 1";

                if ($fromPrice != '') {

                    $photoframe .= " AND (`product_variant`.`selling_price` >= $fromPrice)";
                }

                if ($toPrice != '') {

                    $photoframe .= " AND (`product_variant`.`selling_price` <= $toPrice)";
                }

                if ($variant_type_id != '') {

                    if ($type == "primary_variant") {

                        $photoframe .= " AND `product_variant`.`variant_type_id` = $variant_type_id";
                    }

                    if ($type == "other_variant") {

                        $photoframe .= " AND `get_value`.`variant_type_id` = $variant_type_id";
                    }
                }

                if ($value != '') {

                    if ($type == "primary_variant") {

                        $photoframe .= " AND `product_variant`.`label` = $value";
                    }

                    if ($type == "other_variant") {

                        $photoframe .= " AND `get_value`.`value` = $value";
                    }
                }

                $photoframe .=  " group by `product`.`product_id`";

                $finalary[] = $photoframe;

                $i++;
            }

            $qry = implode(" ", $finalary);

            return $qry;
        } else {

            $photoframe = "SELECT 
            `product`.`product_id`,
            `product`.`created_on`,
            `product`.`product_code`,
            `product`.`product_name`,
            `product_variant`.`selling_price`, 
            `product_variant`.`mrp`,
            `product_variant`.`image`, 
            `product_variant`.`variant_attributes`, 
            `product_variant`.`product_variant_id`, 
            `product`.`is_publish`, 
            `product`.`status` 
            FROM `product_variant` 
             LEFT JOIN `product` ON `product`.`product_id` = `product_variant`.`product_id` 
             and `product_variant`.`set_as_default` = 1";

            $photoframe .= " WHERE
            `product`.`service_id` = 3
            AND `product`.`status` = 1 
            AND `product`.`is_publish` = 1";

            if ($fromPrice != '') {

                $photoframe .= " AND (`product_variant`.`selling_price` >= $fromPrice)";
            }

            if ($toPrice != '') {

                $photoframe .= " AND (`product_variant`.`selling_price` <= $toPrice)";
            }

            $photoframe .=  " group by `product`.`product_id`";

            return $photoframe;
        }
    }
    public function photoframeView($id)
    {
        try {
            Log::channel("photoframe")->info('** started the photoframe website view method **');
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
                    // Log::channel("photoframe")->info("view website value :: $log");
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
            Log::channel("photoframe")->info('** end the photoframe website view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function variantDetailsList(Request $request, $id)
    {
        $get_variantdetails = ProductCatalogue::where('service_id', $id)->where('product.status', 1)->leftjoin('product_variant', 'product_variant.product_id', '=', 'product.product_id')->select('product_variant_id', 'product_variant.product_id', 'variant_attributes')->get();
        $getPrimaryvariantdetails = ProductCatalogue::where('product.service_id', $id)->where('product.status', 1)
            ->select('product_variant.variant_type_id', 'product_variant.label as value', 'variant_type.variant_type')
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
                $pmArray['value'] = $this->getPrimaryValues($pm['variant_type_id']);
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

    public function getPrimaryValues($variant_type_id)
    {
        $prodcutVariant = ProductVariant::select('label')->where('variant_type_id', $variant_type_id)->get();

        $final = [];
        $ary = [];

        if (!empty($prodcutVariant)) {

            foreach ($prodcutVariant  as $variant) {

                if ($variant['label'] != null && $variant['label'] != "") {

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
}
