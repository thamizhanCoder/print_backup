<?php

namespace App\Http\Controllers\API\V1\WP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Models\Cmsbanner;
use App\Helpers\JwtHelper;
use App\Helpers\GlobalHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\CmsbannerRequest;
use App\Models\Cmsgreetings;
use App\Models\CmsVideo;
use App\Models\ProductCatalogue;
use App\Models\Visitors;
use Jenssegers\Agent\Agent;

class HomePageController extends Controller
{

    public function search(Request $request)
  {
    $search_word = $request->input('search');
    $limit = ($request->limit) ? $request->limit : '';
    $offset = ($request->offset) ? $request->offset : '';

    $product = ProductCatalogue::where('product.status', 1)->where('product.is_publish', 1)->select('product.product_id', 'product.product_name', 'product.product_code', 'product.thumbnail_image', 'product.service_id')
      ->where('product.product_name', 'LIKE', '%' . $search_word . '%');

    $count = count($product->get());

    if ($offset) {
      $offset = $offset * $limit;
      $product->offset($offset);
    }

    if ($limit) {
      $product->limit($limit);
    }

    $product = $product->get();

    if ($count > 0) {
      $final = [];
      foreach ($product as $value) {
        $ary = [];
        $ary['product_id'] = $value['product_id'];
        $ary['product_code'] = $value['product_code'];
        $ary['product_name'] = $value['product_name'];
        $ary['service_id'] = $value['service_id'];
        if ($value['service_id'] == 1) {
            $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
            $ary['thumbnail_image'] = $value['thumbnail_image'];
        }
        if ($value['service_id'] == 2) {
            $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
            $ary['thumbnail_image'] = $value['thumbnail_image'];
        }
        if ($value['service_id'] == 3) {
            $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
            $ary['thumbnail_image'] = $value['thumbnail_image'];
        }
        if ($value['service_id'] == 4) {
            $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
            $ary['thumbnail_image'] = $value['thumbnail_image'];
        }
        if ($value['service_id'] == 5) {
            $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
            $ary['thumbnail_image'] = $value['thumbnail_image'];
        }
        if ($value['service_id'] == 6) {
            $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
            $ary['thumbnail_image'] = $value['thumbnail_image'];
        }
        $final[] = $ary;
      }
    }

    if (!empty($final)) {
      return response()->json([
        'keyword' => 'success',
        'message' => __('Search viewed successfully'),
        'data' => $final,
        'count' => $count
      ]);
    } else {
      return response()->json([
        'keyword' => 'failed',
        'message' => __('No data found'),
        'data' => []
      ]);
    }
  }

    public function cmsBanner_list(Request $request)
    {
        try {
            $websitevisitors = new Visitors();
        $websitevisitors->ip_address = $_SERVER['REMOTE_ADDR'];
        $websitevisitors->page_type = $request->input('page_type');
        $Agent = new Agent();
        // agent detection influences the view storage path
        if ($Agent->isMobile()) {
            // you're a mobile device
            $websitevisitors->user_agent = 'mobile';
        } else {
            // $websitevisitors->user_agent = $request->server('HTTP_USER_AGENT');
            $websitevisitors->user_agent = 'web';
        }
        $websitevisitors->visited_on = Server::getDateTime();
        $websitevisitors->visited_time = date('H');
        $websitevisitors->save();
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'banner_image' => 'cms_banner.banner_image',
                'banner_url' => 'cms_banner.banner_url',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "cms_banner_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('cms_banner.banner_image', 'cms_banner.banner_url');

            // log start *********
            Log::channel("cmsbanner")->info("******* Cms Banner List Method Start *******");
            Log::channel("cmsbanner")->info("Cms Banner Controller start ::: limit = $limit, ::: offset == $offset:::: , searchval == $searchval::: , sortByKey === $sortByKey ,:::  sortType == $sortType :::");
            // log start *********


            $getCmsbanner = Cmsbanner::where('cms_banner.status', '!=', 2)->select('*');
            $getCmsbanner->where(function ($query) use ($searchval, $column_search, $getCmsbanner) {
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
                $getCmsbanner->orderBy($order_by_key[$sortByKey], $sortType);
            }
            $count = $getCmsbanner->count();
            if ($offset) {
                $offset = $offset * $limit;
                $getCmsbanner->offset($offset);
            }
            if ($limit) {
                $getCmsbanner->limit($limit);
            }
            $getCmsbanner->orderBy('banner_order_id', 'asc');
            $getCmsbanner->orderBy('cms_banner_id', 'desc');
            $getCmsbanner = $getCmsbanner->get();
            if ($count > 0) {
                $final = [];
                foreach ($getCmsbanner as $value) {
                    $ary = [];
                    $ary['cms_banner_id'] = $value['cms_banner_id'];
                    $ary['image'] = $value['banner_image'];
                    $ary['banner_image'] = ($ary['image'] != '') ? env('APP_URL') . env('BANNER_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['banner_url'] = $value['banner_url'];
                     $ary['banner_order_id'] = $value['banner_order_id'];
                    $ary['created_on'] = $value['created_on'];
                    $ary['created_by'] = $value['created_by'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['updated_by'] = $value['updated_by'];
                    $ary['status'] = $value['status'];
                    $ary['customer_from'] = "Web";
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                // log end ***********
                $impl = json_encode($final, true);
                Log::channel("cmsbanner")->info("Cms Banner Controller end:: save values :: $impl ::::end");
                Log::channel("cmsbanner")->info("******* Cms Banner List Method End *******");
                Log::channel("cmsbanner")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                // log end ***********

                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Banner listed successfully',
                    'data' => $final,
                    'count' => $count
                ]);
            } else {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('No data found'),
                    'data' => [],
                    'count' => $count
                ]);
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("cmsbanner")->error("******* Cms banner List Method Error Start *******");
            Log::channel("cmsbanner")->error($exception);
            Log::channel("cmsbanner")->error("******* Cms Banner List Method Error End *******");
            Log::channel("cmsbanner")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end


            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
