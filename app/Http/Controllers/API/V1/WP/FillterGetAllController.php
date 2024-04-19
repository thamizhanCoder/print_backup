<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\GstPercentage;
use App\Models\Photoprintsetting;
use App\Models\ProductCatalogue;
use App\Models\Service;
use App\Models\VariantType;
use Illuminate\Http\Request;

class FillterGetAllController extends Controller
{

    public function servicetype_getcall(Request $request)
    {
        $get_servicetype = Service::select('service_id', 'service_name')->get();

        if (!empty($get_servicetype)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Service type listed successfully'),
                    'data' => $get_servicetype,
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

    public function productname_getcall(Request $request, $id)
    {
        $product_id = ($request->product_id) ? $request->product_id : '';
        if (!empty($product_id)) {
            $get_productname = ProductCatalogue::where('product.status', 1)->where('product.service_id', $id)->where('product.product_id', '!=', $product_id)->leftjoin('service', 'service.service_id', '=', 'product.service_id')->select('product.product_id', 'product.service_id', 'product.product_name', 'product.mrp', 'product.selling_price', 'product.first_copy_selling_price', 'product.additional_copy_selling_price', 'product.status', 'service.service_name')->orderby('product.product_id', 'desc')->groupBy('product.product_id')->get();
        } else {
            $get_productname = ProductCatalogue::where('product.status', 1)->where('product.service_id', $id)->leftjoin('service', 'service.service_id', '=', 'product.service_id')->select('product.product_id', 'product.service_id', 'product.product_name', 'product.mrp', 'product.selling_price', 'product.first_copy_selling_price', 'product.additional_copy_selling_price', 'product.status', 'service.service_name')->orderby('product.product_id', 'desc')->groupBy('product.product_id')->get();
        }
        $count = $get_productname->count();

        $final = [];
        if ($count > 0) {
            foreach ($get_productname as $proname) {
                $ary = [];
                $ary['product_id'] = $proname['product_id'];
                $ary['product_name'] = $proname['product_name'];
                if ($proname['service_id'] == 1) {
                    $ary['mrp'] = $proname['mrp'];
                    $ary['selling_price'] = $proname['selling_price'];
                }
                if ($proname['service_id'] == 2) {
                    $ary['mrp'] = $proname['mrp'];
                    $ary['selling_price'] = $proname['first_copy_selling_price'];
                }
                if ($proname['service_id'] == 3) {

                    $ary['mrp'] = $this->photoframeProductAmountDetails($proname['product_id'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($proname['product_id'], "selling");
                }
                if ($proname['service_id'] == 4) {

                    $ary['mrp'] = $this->photoframeProductAmountDetails($proname['product_id'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($proname['product_id'], "selling");
                }
                if ($proname['service_id'] == 5) {

                    $ary['mrp'] = $this->photoframeProductAmountDetails($proname['product_id'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($proname['product_id'], "selling");
                }
                if ($proname['service_id'] == 6) {

                    $ary['mrp'] = $this->photoframeProductAmountDetails($proname['product_id'], "mrp");
                    $ary['selling_price'] = $this->photoframeProductAmountDetails($proname['product_id'], "selling");
                }
                $ary['status'] = $proname['status'];
                $ary['service_name'] = $proname['service_name'];
                $final[] = $ary;
            }
        }

        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Product name listed successfully'),
                    'data' => $final,
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

    public function categorygetall_old(Request $request)
    {
        $Category = Category::select('category_id', 'category_name')->where('status', 1)->get();

        if (!empty($Category)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Category listed successfully'),
                    'data' => $Category,
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

    public function categorygetall(Request $request)
    {
        $service_id = ($request->service_id) ? $request->service_id : '';
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $Category = ProductCatalogue::where('product.service_id', $service_id)->leftjoin('category', 'category.category_id', '=', 'product.category_id')->select('product.category_id', 'category.category_name', 'category.category_image')->where('product.status', 1)->where('product.is_publish', 1)->groupby('product.category_id');
        $count = count($Category->get());
        if ($offset) {
            $offset = $offset * $limit;
            $Category->offset($offset);
        }
        if ($limit) {
            $Category->limit($limit);
        }

        $Category = $Category->get();

        if (!empty($Category)) {
            $final = [];
            foreach ($Category as $value) {
                $ary = [];
                $ary['category_id'] = $value['category_id'];
                $ary['category_name'] = $value['category_name'];
                $ary['category_image'] = ($value['category_image'] != '') ? env('APP_URL') . env('CATEGORY_URL') . $value['category_image'] : env('APP_URL') . "avatar.jpg";
                $final[] = $ary;
            }
        }

        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Category listed successfully'),
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
                ]
            );
        }
    }
}
