<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Models\AddToCart;
use App\Models\Category;
use App\Models\GstPercentage;
use App\Models\OrderItems;
use App\Models\Photoprintsetting;
use App\Models\ProductCatalogue;
use App\Models\ProductVariant;
use App\Models\Service;
use App\Models\VariantType;
use Illuminate\Http\Request;

class ProductCatalogueGetCallController extends Controller
{

    public function photoprint_getcall(Request $request)
    {
        $get_photoprint = Photoprintsetting::where('status', 1)->get();

	$final = [];
        if (!empty($get_photoprint)) {

            foreach ($get_photoprint as $data) {
              $ary = [];
              $ary['photo_print_settings_id'] = $data['photo_print_settings_id'];
              $ary['size_value'] = $data['width'].' X '.$data['height'];
              $final[] = $ary;
            }
          }

        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Photo print settings listed successfully'),
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

    public function gst_getcall(Request $request)
    {
        $get_gst = GstPercentage::where('status', 1)->select('gst_percentage_id', 'gst_percentage', 'status')->get();

	$final = [];
        if (!empty($get_gst)) {

            foreach ($get_gst as $data) {
              $ary = [];
              $ary['gst_percentage_id'] = $data['gst_percentage_id'];
              $ary['gst_percentage'] = $data['gst_percentage']. ' % ';
              $ary['status'] = $data['status'];
              $final[] = $ary;
            }
          }

        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Gst percentage listed successfully'),
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
            $get_productname = ProductCatalogue::where('product.status', 1)->where('product.is_publish', 1)->where('product.service_id', $id)->where('product.product_id', '!=', $product_id)->leftjoin('service', 'service.service_id', '=', 'product.service_id')->select('product.product_id', 'product.service_id', 'product.product_name', 'product.mrp', 'product.selling_price', 'product.first_copy_selling_price', 'product.additional_copy_selling_price', 'product.status', 'service.service_name')->orderby('product.product_id', 'desc')->groupBy('product.product_id')->get();
        } else {
            $get_productname = ProductCatalogue::where('product.status', 1)->where('product.is_publish', 1)->where('product.service_id', $id)->leftjoin('service', 'service.service_id', '=', 'product.service_id')->select('product.product_id', 'product.service_id', 'product.product_name', 'product.mrp', 'product.selling_price', 'product.first_copy_selling_price', 'product.additional_copy_selling_price', 'product.status', 'service.service_name')->orderby('product.product_id', 'desc')->groupBy('product.product_id')->get();
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

    public function variant_getall(Request $request)
    {
        $slug = $request->slug;
        if ($slug == "photoframe") {
            $get_photoprint = VariantType::where('variant_type_id', '!=', 1)->where('status', 1)->select('variant_type_id', 'variant_type', 'status', 'is_default')->get();
        } else if ($slug == "personalized") {
            $get_photoprint = VariantType::where('variant_type_id', '!=', 2)->where('status', 1)->select('variant_type_id', 'variant_type', 'status', 'is_default')->get();
        } else if ($slug == "selfie") {
            $get_photoprint = VariantType::where('variant_type_id', '!=', 4)->where('status', 1)->select('variant_type_id', 'variant_type', 'status', 'is_default')->get();
        } else {
            $get_photoprint = VariantType::where('status', 1)->select('variant_type_id', 'variant_type', 'status', 'is_default')->get();
        }

        if (!empty($get_photoprint)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Variant type listed successfully'),
                    'data' => $get_photoprint,
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

    public function categorygetcall(Request $request)
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

    //product variant delete
    public function productVariantDelete(Request $request)
  {
      $id = $request->id;

      if (!empty($id)) {

        $addtocart = AddToCart::where('product_variant_id', $id)->where('status', '!=', 2)->first();
        if (empty($addtocart)) {

            $orderItems = OrderItems::where('product_variant_id', $id)->where('status', '!=', 2)->first();
        if (empty($orderItems)) {
            $update = ProductVariant::where('product_variant_id', $id)->delete();
        //   $update = ProductVariant::where('product_variant_id', $ids)->update(array(
        //     'status' => 2,
        //     'updated_at' => Server::getDateTime(),
        //     'updated_by' => JwtHelper::getSesUserId()
        //   ));   

          return response()->json([
            'keyword' => 'success',
            'message' =>  __('Product variant deleted successfully'),
            'data' => []
          ]);
        } else {
          return response()->json([
            'keyword' => 'failed',
            'message' =>  __('Product variant is already used in orders'),
            'data' => []
          ]);
        }
    } else {
        return response()->json([
          'keyword' => 'failed',
          'message' =>  __('Product variant is already used in add to cart'),
          'data' => []
        ]);
      }
      } else {
        return response()->json([
          'keyword' => 'failed',
          'message' =>  __('Role failed'),
          'data' => []
        ]);
      }
  }
}
