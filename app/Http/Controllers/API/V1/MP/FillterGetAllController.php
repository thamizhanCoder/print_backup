<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ProductCatalogue;
use Illuminate\Http\Request;

class FillterGetAllController extends Controller
{
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
