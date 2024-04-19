<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\GlobalHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Models\CmsVideo;
use App\Helpers\Firebase;
use App\Models\FcmToken;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\CmsVideoRequest;

class CmsVideoController extends Controller
{
    public function cmsvideo_create(CmsVideoRequest $request)
    {
        try {     


            $cmsvideo = new CmsVideo();
            $url_exist = CmsVideo::where([['video_url', $request->input('video_url')], ['status', '!=', 2]])->first();
            if (empty($url_exist)) {
                $cmsvideo->video_url = $request->input('video_url');
                $cmsvideo->video_description = $request->input('video_description');
                $cmsvideo->created_on = Server::getDateTime();
                $cmsvideo->created_by = JwtHelper::getSesUserId();

                // log start *********
                Log::channel("cmsvideo")->info("******* Cms Video Insert Method Start *******");
                Log::channel("cmsvideo")->info("Cms Video Controller start:: Request values :: $cmsvideo");
                // log start *********

                if ($cmsvideo->save()) {

                    $cmsvideo = CmsVideo::where('cms_video_id', $cmsvideo->cms_video_id)->select('*')->first();

                     // log activity
                     $desc =  'Cms Video '  . $cmsvideo->video_url  . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                     $activitytype = Config('activitytype.Cms Video');
                     GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    // log end ***********
                    Log::channel("cmsvideo")->info("Cms Video Controller end:: save values :: $cmsvideo::::end");
                    Log::channel("cmsvideo")->info("******* Cms Video Insert Method End *******");
                    Log::channel("cmsvideo")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    // $videodata = CmsVideo::leftjoin('acl_user', 'acl_user.acl_user_id', '=', 'cms_video.created_by')->select('acl_user.acl_user_id', 'acl_user.name', 'cms_video.cms_video_id', 'cms_video.video_url')->where('cms_video.cms_video_id', $cmsvideo->cms_video_id)->first();
                    // $emp_info = [
                    //     'videourl' => $videodata->video_url,
                    //     'acl_name' => $videodata->name
                    // ];

                    

                    // $title = Config('fcm_msg.title.video_create_title');
                    // $body = Config('fcm_msg.body.video_create');
                    // $body = GlobalHelper::mergeFields($body, $emp_info);
                    // $module = 'youtube';
                    // $portal = 'admin';
                    // $page = 'youtube';
                    // $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                    // $data = [
                    //     'cms_video_id' => $videodata->cms_video_id,
                    //     'video_url' => $videodata->video_url,
                    //     'page' => 'youtube',
                    //     'random_id' => $random_id
                    // ];
                    // $token = Customer::where('token', '!=', NULL)->orwhere('mbl_token', '!=', NULL)->select('token', 'mbl_token')->get();
                    // if (!empty($token)) {
                    //     $tokens = [];
                    //     foreach ($token as $tk) {
                    //         $tokens[] = $tk['token'];
                    //     }

                    //     $mbl_tokens = [];
                    //     foreach ($token as $tks) {
                    //         $mbl_tokens[] = $tks['mbl_token'];
                    //     }
                    // }
                    // if (!empty($tokens)) {
                    //     foreach (array_chunk($tokens, 500) as $tok) {
                    //         for ($i = 0; $i < count($tok); $i++) {
                    //             $key = ($tok[$i]) ? $tok[$i] : " ";
                    //             if (!empty($key)) {

                    //                 $message = [
                    //                     'title' => $title,
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

                    // //mobile app push
                    // if (!empty($mbl_tokens)) {
                    //     foreach (array_chunk($mbl_tokens, 500) as $mbl_tok) {
                    //         for ($i = 0; $i < count($mbl_tok); $i++) {
                    //             $key_mbl = ($mbl_tok[$i]) ? $mbl_tok[$i] : " ";
                    //             if (!empty($key_mbl)) {

                    //                 $message = [
                    //                     'title' => $title,
                    //                     'body' => $body,
                    //                     'page' => $page,
                    //                     'data' => $data,
                    //                     'portal' => $portal,
                    //                     'module' => $module
                    //                 ];
                    //                 $push2 = Firebase::sendMultipleMbl($key_mbl, $message);
                    //             }
                    //         }
                    //     }
                    // }

                    // $getdata = GlobalHelper::notification_create($title, $body, 1, 1, 2, $module, $page, "userview", $data, $random_id);

                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => 'Video created successfully',
                        'data'        => $cmsvideo
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Video creation failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => __('Video URL already exist'),
                ]);
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("cmsvideo")->error("******* Cms Video Insert Method Error Start *******");
            Log::channel("cmsvideo")->error($exception);
            Log::channel("cmsvideo")->error("*******  Cms Video Insert Method Error End *******");
            Log::channel("cmsvideo")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function cmsvideo_update(CmsVideoRequest $request)
    {
        try {

            $ids = $request->input('cms_video_id');
            $cmsvideo = CmsVideo::find($ids);
            $cmsvideo->video_description = $request->input('video_description');
            $cmsvideo->video_url = $request->input('video_url');
            // $cmsvideo->video_order_id = $request->input('video_order_id');
            $cmsvideo->updated_on = Server::getDateTime();
            $cmsvideo->updated_by = JwtHelper::getSesUserId();

            // log start *********
            Log::channel("cmsvideo")->info("******* Cms Video Update Method Start *******");
            Log::channel("cmsvideo")->info("Cms Video Controller start:: find ID : $ids, Request values :: $cmsvideo");
            // log start *********



            if ($cmsvideo->save()) {
                $cmsvideo = CmsVideo::where('cms_video_id', $cmsvideo->cms_video_id)->select('*')->first();
                
                 // log activity
                 $desc =  ' Cms Video ' . $cmsvideo->video_url  . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                 $activitytype = Config('activitytype.Cms Video');
                 GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                // log end ***********
                Log::channel("cmsvideo")->info("Cms Video Controller end:: save values :: $cmsvideo::::end");
                Log::channel("cmsvideo")->info("******* Cms Video Update Method End *******");
                Log::channel("cmsvideo")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                // log end ***********

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => 'Video updated successfully',
                    'data'        => $cmsvideo
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Video update failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("cmsvideo")->error("******* Cms Video Update Method Error Start *******");
            Log::channel("cmsvideo")->error($exception);
            Log::channel("cmsvideo")->error("*******  Cms Video Update Method Error End *******");
            Log::channel("cmsvideo")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
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
    public function cmsvideo_view(Request $request, $id)
    {  
        try {

        // log start *********
        Log::channel("cmsvideo")->info("******* Cms Video View Method Start *******");
        Log::channel("cmsvideo")->info("Cms Video Controller start:: find ID : $id");
        // log start *********

        if ($id != '' && $id > 0) {
            $data = CmsVideo::where('cms_video_id', $id)->select('*')->first();

             // log end ***********
             Log::channel("cmsvideo")->info("Cms Video Controller end:: save values :: $id ::::end");
             Log::channel("cmsvideo")->info("******* Cms Video View Method End *******");
             Log::channel("cmsvideo")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
             // log end ***********

            if (!empty($data)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Video viewed successfully',
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failure',
                    'message' =>  __('No data found'), 'data' => $data
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' =>  __('No data found'), 'data' => []
            ]);
        }
      }catch (\Exception $exception) {

        // log for error start
        Log::channel("cmsvideo")->error("******* Cms Video View Method Error Start *******");
        Log::channel("cmsvideo")->error($exception);
        Log::channel("cmsvideo")->error("******* Cms Video View Method Error End *******");
        Log::channel("cmsvideo")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
        // log for error end

        return response()->json([
            'error' => 'Internal server error.',
            'message' => $exception->getMessage()
        ], 500);
    }
    }
    public function cmsvideo_status(Request $request)
    {
        try{
            
        if (!empty($request)) {
            $ids = $request->id;
            $ids = json_decode($ids, true);
            if (!empty($ids)) {

                // log start *********
                $impl = implode(",", $ids);
                Log::channel("cmsvideo")->info("******* Cms Video Status Method Start *******");
                Log::channel("cmsvideo")->info("Cms Video Controller start ::: Request IDS == $impl :::: Request status === $request->status");
                // log start *********

                $cmsvideo = CmsVideo::where('cms_video_id', $ids)->first();
                $update = CmsVideo::whereIn('cms_video_id', $ids)->update(array(
                    'status' => $request->status,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));
                
                //   log activity
                $activity_status = ($request->status) ? 'activated' : 'inactivated';
                // $implode = implode(",", $ids);
                $desc =  'Cms Video '  . $cmsvideo->video_url  . ' is ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Cms Video');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                if ($request->status == 0) {

                    // log end ***********
                    $impl = implode(",", $ids);
                    Log::channel("cmsvideo")->info("Cms Video Controller end:: save values :: $impl :: Status == Inactive ::::end");
                    Log::channel("cmsvideo")->info("******* Cms Video Status Method End *******");
                    Log::channel("cmsvideo")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'Video inactivated successfully',
                        'data' => []
                    ]);
                } else if ($request->status == 1) {

                    // log end ***********
                    $impl = implode(",", $ids);
                    Log::channel("cmsvideo")->info("Cms Video Controller end:: save values :: $impl :: Status == Active ::::end");
                    Log::channel("cmsvideo")->info("******* Cms Video Status Method End *******");
                    Log::channel("cmsvideo")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  'Video activated successfully',
                        'data' => []
                    ]);
                }
            } else {
                return response()
                    ->json([
                        'keyword' => 'failed',
                        'message' => __('Video failed'),
                        'data' => []
                    ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('Video failed'), 'data' => []
            ]);
        }
      }catch (\Exception $exception) {

        // log for error start
        Log::channel("cmsvideo")->error("******* Cms Video Stauts Method Error Start *******");
        Log::channel("cmsvideo")->error($exception);
        Log::channel("cmsvideo")->error("******* Cms Video Stauts Method Error End *******");
        Log::channel("cmsvideo")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
        // log for error end

        return response()->json([
            'error' => 'Internal server error.',
            'message' => $exception->getMessage()
        ], 500);
      }
    }
    public function cmsvideo_delete(Request $request)
    {
        try {
            if (!empty($request)) {
                $ids = $request->id;
                $ids = json_decode($ids, true);

                if (!empty($ids)) {

                    // log start *********
                    Log::channel("cmsvideo")->info("******* Cms Video Delete Method Start *******");
                    Log::channel("cmsvideo")->info("Cms Video Controller start ::: Request IDS == $ids ::::");
                    // log start *********

                    $cmsvideo = CmsVideo::where('cms_video_id', $ids)->first();
                    $update = CmsVideo::where('cms_video_id', $ids)->update(array(
                        'status' => 2,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));

                    // log activity
                    // $implode = implode(",", $ids);
                    $desc =  ' Cms Video '  . $cmsvideo->video_url  . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Cms Video');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    // log end ***********
                    Log::channel("cmsvideo")->info("Cms Video Controller end:: save values :: $ids :: Status == Deleted ::::end");
                    Log::channel("cmsvideo")->info("******* Cms Video Delete Method End *******");
                    Log::channel("cmsvideo")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  'Video deleted successfully',
                        'data' => []
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('message.failed'),
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.failed'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("cmsvideo")->error("******* Cms Video Delete Method Error Start *******");
            Log::channel("cmsvideo")->error($exception);
            Log::channel("cmsvideo")->error("******* Cms Video Delete Method Error End *******");
            Log::channel("cmsvideo")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
