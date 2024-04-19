<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\GlobalHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Models\Rating;
use App\Helpers\JwtHelper;
use App\Models\Product;

class RatingReviewController extends Controller
{
    public function product_name(Request $request)
    {
        $product_name = Product::where("status", 1)->select('product_id', 'product_name')
            ->orderBy('created_on', 'desc')->get();

        if (!empty($product_name)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Product name listed successfully'),
                'data' => $product_name
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('Product name not found'),
                'data' => []
            ]);
        }
    }

    public function rating(Request $request)
    {
        $rating = Rating::select('rating')
            ->orderBy('created_on', 'desc')->distinct()->get();

        if (!empty($rating)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Rating listed successfully'),
                'data' => $rating
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('Rating not found'),
                'data' => []
            ]);
        }
    }

    public function rating_status(Request $request)
    {
        $ids = $request->id;
        $ids = json_decode($ids, true);

        if (!empty($ids)) {
            $update = Rating::where('rating_review_id', $ids)->update(array(
                'status' => $request->input('status'),
                'updated_on' => Server::getDateTime(),
                'updated_by' => JwtHelper::getSesUserId()
            ));


            if ($request->status == 1) {

                $ratingDetailsget = Rating::where('rating_review_id', $ids)->leftjoin('customer', 'customer.customer_id', '=', 'rating_review.customer_id')->leftjoin('product', 'product.product_id', '=', 'rating_review.product_id')->select('rating_review.*', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.email', 'customer.mobile_no', 'product.product_name')->first();

                $desc =  ' Rating & Review - This (' . $ratingDetailsget->customer_first_name . ' ' . $ratingDetailsget->customer_last_name . ' - ' . $ratingDetailsget->product_name . ') is pending by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Ratings & Review');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Rating review is pending',
                    'data' => []
                ]);
            }
            if ($request->status == 2) {

                $ratingDetailsget = Rating::where('rating_review_id', $ids)->leftjoin('customer', 'customer.customer_id', '=', 'rating_review.customer_id')->leftjoin('product', 'product.product_id', '=', 'rating_review.product_id')->select('rating_review.*', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.email', 'customer.mobile_no', 'product.product_name')->first();

                $desc =  ' Rating & Review - This (' . $ratingDetailsget->customer_first_name . ' ' . $ratingDetailsget->customer_last_name . ' - ' . $ratingDetailsget->product_name . ') is approved by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Ratings & Review');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Rating review approved successfully',
                    'data' => []
                ]);
            }
        } else {
            return response()
                ->json([
                    'keyword' => 'failed',
                    'message' => __('failed'),
                    'data' => []
                ]);
        }
    }

    public function rating_delete(Request $request)
    {
        $ids = $request->id;
        $ids = json_decode($ids, true);


        if (!empty($ids)) {
            $update = Rating::whereIn('rating_review_id', $ids)->delete();

            return response()->json([
                'keyword' => 'success',
                'message' => 'Rating review deleted successfully',
                'data' => []
            ]);
        } else {
            return response()
                ->json([
                    'keyword' => 'failed',
                    'message' => __('failed'),
                    'data' => []
                ]);
        }
    }

    public function rating_list(Request $request)
    {
        $id = ($request->id) ? $request->id : '';
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $filterByProduct = ($request->filterByProduct) ? $request->filterByProduct : '[]';
        $filterByRating = ($request->filterByRating) ? $request->filterByRating : '[]';

        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'rating' => 'rating_review.rating',
            'review' => 'rating_review.review',
            'customer_first_name' => 'customer.customer_first_name',
            'customer_last_name' => 'customer.customer_last_name',
            'email' => 'customer.email',
            'mobile_no' => 'customer.mobile_no',
            'product_name' => 'product.product_name'
        ];

        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "rating_review_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

        $column_search = array('rating_review.rating', 'rating_review.review', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.email', 'customer.mobile_no', 'product.product_name');

        $getRating = Rating::leftjoin('customer', 'customer.customer_id', '=', 'rating_review.customer_id')->leftjoin('product', 'product.product_id', '=', 'rating_review.product_id')->select('rating_review.*', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.email', 'customer.mobile_no', 'product.product_name');

        $getRating->where(function ($query) use ($searchval, $column_search, $getRating) {
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
            $getRating->orderBy($order_by_key[$sortByKey], $sortType);
        }

        if ($filterByProduct != '[]') {
            $filterByProduct = json_decode($filterByProduct, true);
            $getRating->whereIn('product.product_id', $filterByProduct);
        }

        if ($filterByRating != '[]') {
            $filterByRating = json_decode($filterByRating, true);
            $getRating->whereIn('rating_review.rating', $filterByRating);
        }

        if ($id) {
            $getRating->where('rating_review.rating_review_id', '=', $id);
        }

        $count = count($getRating->get());

        if ($offset) {
            $offset = $offset * $limit;
            $getRating->offset($offset);
        }

        if ($limit) {
            $getRating->limit($limit);
        }

        $getRating->orderBy('rating_review_id', 'desc');
        $getRating = $getRating->get();


        $final = [];
        if ($count > 0) {
            foreach ($getRating as $rating) {
                $ary = [];
                $ary['rating_review_id'] = $rating['rating_review_id'];
                $ary['date'] = date('d-m-Y', strtotime($rating['created_on']));
                $ary['customer_name'] = $rating['customer_first_name'] . '' . $rating['customer_last_name'];
                $ary['mobile_no'] = $rating['mobile_no'];
                $ary['email'] = $rating['email'];
                $ary['product_name'] = $rating['product_name'];
                $ary['rating'] = $rating['rating'];
                $ary['review'] = $rating['review'];
                $ary['status'] = ($rating['status'] == 1) ? "pending" : "Approved";

                $final[] = $ary;
            }
        }

        if (!empty($getRating)) {
            return response()->json([
                'keyword' => 'success',
                'message' => 'Rating review listed successfully',
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failure',
                'message' => __('No data found'),
                'data' => [],
            ]);
        }
    }
}
