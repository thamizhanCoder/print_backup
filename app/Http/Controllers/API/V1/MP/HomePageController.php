<?php

namespace App\Http\Controllers\API\V1\MP;

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
use App\Models\Visitors;
use Jenssegers\Agent\Agent;

class HomePageController extends Controller
{

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
                    $ary['customer_from'] = "Mobile";
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


public function cmsvideo_list(Request $request)
{ 
    try {
    $limit = ($request->limit) ? $request->limit : '';
    $offset = ($request->offset) ? $request->offset : '';
    $searchval = ($request->searchWith) ? $request->searchWith : "";
    $order_by_key = [
        // 'mention the api side' => 'mention the mysql side column'
        'video_description' => 'cms_video.video_description',
        'video_url' => 'cms_video.video_url',
    ];
    $sort_dir = ['ASC', 'DESC'];
    $sortByKey = ($request->sortByKey) ? $request->sortByKey : "cms_video_id";
    $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
    $column_search = array('cms_video.video_description', 'cms_video.video_url');

    
    // log start *********
    Log::channel("cmsvideo")->info("******* Cms Video List Method Start *******");
    Log::channel("cmsvideo")->info("Cms Video Controller start ::: limit = $limit, ::: offset == $offset:::: , searchval == $searchval::: , sortByKey === $sortByKey ,:::  sortType == $sortType :::");
    // log start *********


    $getcmsvideo = CmsVideo::where('cms_video.status', '!=', 2)->select('*');
    $getcmsvideo->where(function ($query) use ($searchval, $column_search, $getcmsvideo) {
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
        $getcmsvideo->orderBy($order_by_key[$sortByKey], $sortType);
    }
    $count = $getcmsvideo->count();
    if ($offset) {
        $offset = $offset * $limit;
        $getcmsvideo->offset($offset);
    }
    if ($limit) {
        $getcmsvideo->limit($limit);
    }
    $getcmsvideo->orderBy('cms_video_id', 'desc');
    $getcmsvideo = $getcmsvideo->get();
    $count = $getcmsvideo->count();

    if ($count > 0) {
        $final = [];
        foreach ($getcmsvideo as $value) {
            $ary = [];
            $ary['cms_video_id'] = $value['cms_video_id'];
            $ary['video_url'] = $value['video_url'];
            $ary['video_description'] = $value['video_description'];
            $ary['updated_by'] = $value['updated_by'];
            $ary['status'] = $value['status'];
            $ary['created_on'] = $value['created_on'];
            $ary['created_by'] = $value['created_by'];
            $ary['updated_on'] = $value['updated_on'];
            $final[] = $ary;
        }
    }
    if (!empty($final)) {

         // log end ***********
         $impl = json_encode($final, true);
         Log::channel("cmsvideo")->info("Cms Video Controller end:: save values :: $impl ::::end");
         Log::channel("cmsvideo")->info("******* Cms Video List Method End *******");
         Log::channel("cmsvideo")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
         // log end ***********

        return response()->json([
            'keyword' => 'success',
            'message' => 'Video listed successfully',
            'data' => $getcmsvideo,
            'count' => $count
        ]);
    } else {
        return response()->json([
            'keyword' => 'failure',
            'message' => __('No data found'),
            'data' => [],
            'count' => $count
        ]);
    }
  }catch (\Exception $exception) {

    // log for error start
    Log::channel("cmsvideo")->error("******* Cms Video List Method Error Start *******");
    Log::channel("cmsvideo")->error($exception);
    Log::channel("cmsvideo")->error("******* Cms Video List Method Error End *******");
    Log::channel("cmsvideo")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
    // log for error end


    return response()->json([
        'error' => 'Internal server error.',
        'message' => $exception->getMessage()
    ], 500);
}
}


public function cmsGreet_list(Request $request)
{
        //$currentDateTime = new DateTime('now');
        $date = date('Y-m-d H:i:s');
        $getGreetings = Cmsgreetings::select('cms_greeting.*')->where('cms_greeting.status',1)
        //->where('cms_greeting.from_date','<=', DB::raw('NOW()'))
        //->where('cms_greeting.to_date','>=',DB::raw('NOW()'))
        ->where('cms_greeting.from_date', '<=', $date)
        ->where('cms_greeting.to_date', '>=', $date)
         ->get();

        if ($getGreetings->count() > 0) {

            $final = [];
         foreach ($getGreetings as $value) {
             $ary = [];
             $ary['cms_greeting_id'] = $value['cms_greeting_id'];
             $ary['image'] = $value['greeting_image'];
             $ary['greeting_image'] = ($ary['image'] != '') ? env('APP_URL') . env('GREETINGS_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
             $ary['from_date'] = date("d-m-Y H:i:s", strtotime($value['from_date']));
             $ary['to_date'] = date("d-m-Y H:i:s", strtotime($value['to_date']));

             $final[] = $ary;
         }
    
            return response()->json([
                'keyword' => 'success',
                'message' => 'Greetings listed succcessfully',
                'data' => $final,
                'currentDateTime' => $date,
            ]);
        } else {
            return response()->json([
                'keyword' => 'failure',
                'message' => 'No data found',
                'data' => []
            ]);
        }
}

}
