<?php

namespace App\Http\Controllers\API\V1\AP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Models\Cmsgreetings;
use App\Models\FcmToken;
use App\Helpers\JwtHelper;
use App\Helpers\Firebase;
use DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\GlobalHelper;
use App\Http\Requests\CmsgreetingsRequest;
use App\Http\Requests\CmsgreetingsUpdateRequest;


class CmsgreetingsController extends Controller
{
    public function cmsGreet_create(CmsgreetingsRequest $request)
    {   
        try {

            $getExtension =  pathinfo($request->input('greeting_image'), PATHINFO_EXTENSION);

            $extension_array = ['jpeg','png','jpg'];
 
         if (in_array($getExtension, $extension_array)) {

        $cmsGreet = new Cmsgreetings();
        $cmsGreet->from_date = ($request->input('from_date')) ? date('Y-m-d H:i:s', strtotime($request->input('from_date'))) : '';
        $cmsGreet->to_date = ($request->input('to_date')) ? date('Y-m-d H:i:s', strtotime($request->input('to_date'))) : '';
        $cmsGreet->greeting_image = $request->input('greeting_image');
         $cmsGreet->greeting_url = $request->input('greeting_url');
        // $cmsGreet->from_time = $request->input('from_time');
        // $cmsGreet->to_end_time = $request->input('to_end_time');
        $cmsGreet->created_on = Server::getDateTime();
        $cmsGreet->created_by = JwtHelper::getSesUserId();

            // log start *********
            Log::channel("cmsgreeting")->info("******* Cms Greeting Insert Method Start *******");
            Log::channel("cmsgreeting")->info("Cms Greeting Controller start:: Request values :: $cmsGreet");
            // log start ********* 

        if ($cmsGreet->save()) {
            $cmsGreet = Cmsgreetings::where('cms_greeting_id', $cmsGreet->cms_greeting_id)->select('*')->first();

            // log activity
            $desc =  'Cms Greeting  '  . $cmsGreet->greeting_image  . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
            $activitytype = Config('activitytype.Cms Greeting');
            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

            // log end ***********
            Log::channel("cmsgreeting")->info("Cms Greeting Controller end:: save values :: $cmsGreet::::end");
            Log::channel("cmsgreeting")->info("******* Cms Greeting Insert Method End *******");
            Log::channel("cmsgreeting")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log end ***********

            return response()->json([
                'keyword'      => 'success',
                'message'      => 'Greetings created successfully',
                'data'        => $cmsGreet
            ]);
        } else {
            return response()->json([
                'keyword'      => 'failed',
                'message'      => __('Greetings creation failed'),
                'data'        => []
            ]);
        }
      
    }  else{
        return response()->json([
            'keyword'      => 'failed',
            'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
            'data'        => []
        ]);
    
    }
}
    catch (\Exception $exception) {

        // log for error start
        Log::channel("cmsgreeting")->error("******* Cms Greeting Insert Method Error Start *******");
        Log::channel("cmsgreeting")->error($exception);
        Log::channel("cmsgreeting")->error("*******  Cms Greeting Insert Method Error End *******");
        Log::channel("cmsgreeting")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
        // log for error end

        return response()->json([
            'error' => 'Internal server error.',
            'message' => $exception->getMessage()
        ], 500);
    }
    }


    public function  cmsGreet_update(CmsgreetingsUpdateRequest $request)
    {
        try {

            $getExtension =  pathinfo($request->input('greeting_image'), PATHINFO_EXTENSION);

            $extension_array = ['jpeg','png','jpg'];
 
         if (in_array($getExtension, $extension_array)) {

        $ids = $request->input('cms_greeting_id');
        $cmsGreet = Cmsgreetings::find($ids);
        $cmsGreet->from_date = ($request->input('from_date')) ? date('Y-m-d H:i:s', strtotime($request->input('from_date'))) : '';
        $cmsGreet->to_date = ($request->input('to_date')) ? date('Y-m-d H:i:s', strtotime($request->input('to_date'))) : '';
        // $cmsGreet->from_time = $request->input('from_time');
        // $cmsGreet->to_end_time = $request->input('to_end_time');
        $cmsGreet->greeting_image = $request->input('greeting_image');
         $cmsGreet->greeting_url = $request->input('greeting_url');
        $cmsGreet->updated_on = Server::getDateTime();
        $cmsGreet->updated_by = JwtHelper::getSesUserId();
        
        // log start *********
        Log::channel("cmsgreeting")->info("******* Cms Greeting Update Method Start *******");
        Log::channel("cmsgreeting")->info("Cms Greeting Controller start:: find ID : $ids, Request values :: $cmsGreet");
        // log start *********

        if ($cmsGreet->save()) {
            $cmsGreet = Cmsgreetings::where('cms_greeting_id', $cmsGreet->cms_greeting_id)->select('*')->first();

            // log activity
            $desc =  ' Cms Greeting ' . $cmsGreet->greeting_image  . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
            $activitytype = Config('activitytype.Cms Greeting');
            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
            
            // log end ***********
            Log::channel("cmsgreeting")->info("Cms Greeting Controller end:: save values :: $cmsGreet::::end");
            Log::channel("cmsgreeting")->info("******* Cms Greeting Update Method End *******");
            Log::channel("cmsgreeting")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log end ***********

            return response()->json([
                'keyword'      => 'success',
                'message'      => 'Greetings updated successfully',
                'data'        => $cmsGreet
            ]);
        } else {
            return response()->json([
                'keyword'      => 'failed',
                'message'      => __('Greetings update failed'),
                'data'        => []
            ]);
        }
        }  else{
            return response()->json([
                'keyword'      => 'failed',
                'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                'data'        => []
            ]);
        }
        }
      
    catch (\Exception $exception) {

        // log for error start
        Log::channel("cmsgreeting")->error("******* Cms Greeting Update Method Error Start *******");
        Log::channel("cmsgreeting")->error($exception);
        Log::channel("cmsgreeting")->error("*******  Cms Greeting Update Method Error End *******");
        Log::channel("cmsgreeting")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
        // log for error end

        return response()->json([
            'error' => 'Internal server error.',
            'message' => $exception->getMessage()
        ], 500);
      }
    }

    public function cmsGreet_list(Request $request)
    {
        try{

        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'from_date' => 'cms_greeting.from_date',
            'to_date' => 'cms_greeting.to_date'
        ];

        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "cms_certificate_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

        $column_search = array('cms_greeting.from_date', 'cms_greeting.to_date');

        // log start *********
        Log::channel("cmsgreeting")->info("******* Cms Greeting List Method Start *******");
        Log::channel("cmsgreeting")->info("Cms Greeting Controller start ::: limit = $limit, ::: offset == $offset:::: , searchval == $searchval::: , sortByKey === $sortByKey ,:::  sortType == $sortType :::");
        // log start *********

        $getCmsgreetings = Cmsgreetings::where('cms_greeting.status', '!=', 2)->select('*');

        $getCmsgreetings->where(function ($query) use ($searchval, $column_search, $getCmsgreetings) {
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
            $getCmsgreetings->orderBy($order_by_key[$sortByKey], $sortType);
        }

        $count = $getCmsgreetings->count();

        if ($offset) {
            $offset = $offset * $limit;
            $getCmsgreetings->offset($offset);
        }

        if ($limit) {
            $getCmsgreetings->limit($limit);
        }

        $getCmsgreetings->orderBy('cms_greeting_id', 'desc');
        $getCmsgreetings = $getCmsgreetings->get();

        if (!empty($getCmsgreetings)) {
            $final = [];
            foreach ($getCmsgreetings as $value) {
                $ary = [];
                $ary['cms_greeting_id'] = $value['cms_greeting_id'];
                $ary['image'] = $value['greeting_image'];
                $ary['greeting_url'] = $value['greeting_url'];
                $ary['greeting_image'] = ($ary['image'] != '') ? env('APP_URL') . env('GREETINGS_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
                $ary['from_date'] = date("d-m-Y H:i:s", strtotime($value['from_date']));
                $ary['to_date'] = date("d-m-Y H:i:s", strtotime($value['to_date']));
                $ary['from_time'] = date("H:i:s", strtotime($value['from_date']));
                $ary['to_time'] = date("H:i:s", strtotime($value['to_date']));

                if (strtotime($ary['from_date']) > strtotime(date('Y-m-d H:i:s'))) {
                    $ary['status'] = 'UPCOMING';
                } elseif (strtotime($ary['to_date'])  < strtotime(date('Y-m-d H:i:s'))) {
                    $ary['status'] = 'EXPIRED';
                } else {
                    $ary['status'] = 'ONGOING';
                }

                $final[] = $ary;
            }
        
        if(!empty($final)){

            // log end ***********
            $impl = json_encode($final, true);
            Log::channel("cmsgreeting")->info("Cms Greeting Controller end:: save values :: $impl ::::end");
            Log::channel("cmsgreeting")->info("******* Cms Greeting List Method End *******");
            Log::channel("cmsgreeting")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log end ***********

            return response()->json([
                'keyword' => 'success',
                'message' => 'Greetings listed successfully',
                'data' => $final,
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
        }
     }catch (\Exception $exception) {

        // log for error start
        Log::channel("cmsgreeting")->error("******* Cms Greeting List Method Error Start *******");
        Log::channel("cmsgreeting")->error($exception);
        Log::channel("cmsgreeting")->error("******* Cms Greeting List Method Error End *******");
        Log::channel("cmsgreeting")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
        // log for error end


        return response()->json([
            'error' => 'Internal server error.',
            'message' => $exception->getMessage()
        ], 500);
    }
    }


    public function cmsGreet_view(Request $request, $id)
    { 
        try{

        // log start *********
        Log::channel("cmsgreeting")->info("******* Cms Greeting View Method Start *******");
        Log::channel("cmsgreeting")->info("Cms Greeting Controller start:: find ID : $id");
        // log start *********

        if ($id != '' && $id > 0) {
            $data = Cmsgreetings::where('cms_greeting_id', $id)->select('*')->first();

            // log end ***********
            Log::channel("cmsgreeting")->info("Cms Greeting Controller end:: save values :: $id ::::end");
            Log::channel("cmsgreeting")->info("******* Cms Greeting View Method End *******");
            Log::channel("cmsgreeting")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log end ***********

            $ary = [];
            $ary['cms_greeting_id'] = $data->cms_greeting_id;
            $ary['image'] = $data->greeting_image;
            $ary['greeting_url'] = $data['greeting_url'];
            $ary['greeting_image'] = ($ary['image'] != '') ? env('APP_URL') . env('GREETINGS_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
            $ary['from_date'] = date("Y-m-d", strtotime($data->from_date));
            $ary['to_date'] = date("Y-m-d", strtotime($data->to_date));
            $ary['from_time'] = date("H:i:s", strtotime($data->from_date));
            $ary['to_time'] = date("H:i:s", strtotime($data->to_date));

            if (strtotime($ary['from_date']) > strtotime(date('Y-m-d H:i:s'))) {
                $ary['status'] = 'UPCOMING';
            } elseif (strtotime($ary['to_date'])  < strtotime(date('Y-m-d H:i:s'))) {
                $ary['status'] = 'EXPIRED';
            } else {
                $ary['status'] = 'ONGOING';
            }

            if (!empty($ary)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Greetings viewed successfully',
                    'data' => $ary
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
        Log::channel("cmsgreeting")->error("******* Cms Greeting View Method Error Start *******");
        Log::channel("cmsgreeting")->error($exception);
        Log::channel("cmsgreeting")->error("******* Cms Greeting View Method Error End *******");
        Log::channel("cmsgreeting")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
        // log for error end

        return response()->json([
            'error' => 'Internal server error.',
            'message' => $exception->getMessage()
        ], 500);
    }
    }

    public function cmsGreet_status(Request $request)
    {
        try{

        if (!empty($request)) {
            $ids = $request->id;
            $ids = json_decode($ids, true);

            if (!empty($ids)) {

                // log start *********
                $impl = implode(",", $ids);
                Log::channel("cmsgreeting")->info("******* Cms Greeting Status Method Start *******");
                Log::channel("cmsgreeting")->info("Cms Greeting Controller start ::: Request IDS == $impl :::: Request status === $request->status");
                // log start *********

                $cmsGreet = Cmsgreetings::where('cms_greeting_id', $ids)->first();
                $update = Cmsgreetings::whereIn('cms_greeting_id', $ids)->update(array(
                    'status' => $request->status,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));

                //   log activity
                $activity_status = ($request->status) ? 'activated' : 'inactivated';
                // $implode = implode(",", $ids);
                $desc =  'Cms Greeting '  . $cmsGreet->greeting_image  . ' is ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Cms Greeting');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                if ($request->status == 0) {

                    // log end ***********
                    $impl = implode(",", $ids);
                    Log::channel("cmsgreeting")->info("Cms Greeting Controller end:: save values :: $impl :: Status == Inactive ::::end");
                    Log::channel("cmsgreeting")->info("******* Cms Greeting Status Method End *******");
                    Log::channel("cmsgreeting")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'Greetings inactivated successfully',
                        'data' => []
                    ]);
                } else if ($request->status == 1) {

                    // log end ***********
                    $impl = implode(",", $ids);
                    Log::channel("cmsgreeting")->info("Cms Greeting Controller end:: save values :: $impl :: Status == Active ::::end");
                    Log::channel("cmsgreeting")->info("******* Cms Greeting Status Method End *******");
                    Log::channel("cmsgreeting")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  'Greetings activated successfully',
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
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('failed'), 'data' => []
            ]);
        }
     }catch (\Exception $exception) {

        // log for error start
        Log::channel("cmsgreeting")->error("******* Cms Greeting Stauts Method Error Start *******");
        Log::channel("cmsgreeting")->error($exception);
        Log::channel("cmsgreeting")->error("******* Cms Greeting Stauts Method Error End *******");
        Log::channel("cmsgreeting")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
        // log for error end

        return response()->json([
            'error' => 'Internal server error.',
            'message' => $exception->getMessage()
        ], 500);
    }
    }

    public function cmsGreet_delete(Request $request)
    {
        try {
            if (!empty($request)) {
                $ids = $request->id;
                $ids = json_decode($ids, true);

                if (!empty($ids)) {

                    // log start *********
                    Log::channel("cmsgreeting")->info("******* Cms Greeting Delete Method Start *******");
                    Log::channel("cmsgreeting")->info("Cms Greeting Controller start ::: Request IDS == $ids ::::");
                    // log start *********

                    $cmsGreet = Cmsgreetings::where('cms_greeting_id', $ids)->first();
                    $update = Cmsgreetings::where('cms_greeting_id', $ids)->update(array(
                        'status' => 2,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));

                    // log activity
                    // $implode = implode(",", $ids);
                    $desc =  ' Cms Greeting '  . $cmsGreet->greeting_image  . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Cms Greeting');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);



                    // log end ***********
                    Log::channel("cmsgreeting")->info("Cms Greeting Controller end:: save values :: $ids :: Status == Deleted ::::end");
                    Log::channel("cmsgreeting")->info("******* Cms Greeting Delete Method End *******");
                    Log::channel("cmsgreeting")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  'Greetings deleted successfully',
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
            Log::channel("cmsbanner")->error("******* Cms Banner Delete Method Error Start *******");
            Log::channel("cmsbanner")->error($exception);
            Log::channel("cmsbanner")->error("******* Cms Banner Delete Method Error End *******");
            Log::channel("cmsbanner")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }   
    
}