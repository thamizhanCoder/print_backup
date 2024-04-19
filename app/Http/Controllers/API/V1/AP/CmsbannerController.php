<?php

namespace App\Http\Controllers\API\V1\AP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Models\Cmsbanner;
use App\Helpers\JwtHelper;
use App\Helpers\GlobalHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\CmsbannerRequest;

class CmsbannerController extends Controller
{
    public function cmsBanner_create(CmsbannerRequest $request)
    {
        try {

            $getExtension =  pathinfo($request->input('banner_image'), PATHINFO_EXTENSION);

            $extension_array = ['jpeg', 'png', 'jpg'];

            if (in_array($getExtension, $extension_array)) {

                $cmsBanner = new Cmsbanner();
                $cmsBanner->banner_image = $request->input('banner_image');
                $cmsBanner->banner_url = $request->input('banner_url');
                $cmsBanner->created_on = Server::getDateTime();
                $cmsBanner->created_by = JwtHelper::getSesUserId();

                if ($cmsBanner->save()) {
                    $cmsBanner = Cmsbanner::where('cms_banner_id', $cmsBanner->cms_banner_id)->select('*')->first();

                    // log activity
                    $desc =  'Cms Banner  '  . $cmsBanner->banner_url  . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Cms Banner');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                    // log end ***********
                    Log::channel("cmsbanner")->info("Cms Banner Controller end:: save values :: $cmsBanner::::end");
                    Log::channel("cmsbanner")->info("******* Cms Banner Insert Method End *******");
                    Log::channel("cmsbanner")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => 'Banner created successfully',
                        'data'        => $cmsBanner
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Banner creation failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("cmsbanner")->error($exception);
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }



    public function cmsBanner_update(CmsbannerRequest $request)
    {
        try {
            $getExtension =  pathinfo($request->input('banner_image'), PATHINFO_EXTENSION);

            $extension_array = ['jpeg', 'png', 'jpg'];

            if (in_array($getExtension, $extension_array)) {

                $ids = $request->input('cms_banner_id');
                $cmsBanner = Cmsbanner::find($ids);
                $cmsBanner->banner_image = $request->input('banner_image');
                $cmsBanner->banner_url = $request->input('banner_url');
                $cmsBanner->updated_on = Server::getDateTime();
                $cmsBanner->updated_by = JwtHelper::getSesUserId();

                // log start *********
                Log::channel("cmsbanner")->info("******* Cms Banner Update Method Start *******");
                Log::channel("cmsbanner")->info("Cms Banner Controller start:: find ID : $ids, Request values :: $cmsBanner");
                // log start *********


                if ($cmsBanner->save()) {
                    $cmsBanner = Cmsbanner::where('cms_banner_id', $cmsBanner->cms_banner_id)->select('*')->first();

                    // log activity
                    $desc =  ' Cms Banner ' . $cmsBanner->banner_url  . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Cms Banner');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);



                    // log end ***********
                    Log::channel("cmsbanner")->info("Cms Banner Controller end:: save values :: $cmsBanner::::end");
                    Log::channel("cmsbanner")->info("******* Cms Banner Update Method End *******");
                    Log::channel("cmsbanner")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********


                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => 'Banner updated successfully',
                        'data'        => $cmsBanner
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Banner update failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("cmsbanner")->error("******* Cms Banner Update Method Error Start *******");
            Log::channel("cmsbanner")->error($exception);
            Log::channel("cmsbanner")->error("*******  Cms Banner Update Method Error End *******");
            Log::channel("cmsbanner")->error("********************************END !!!!!!!!!!!!!!******************************************** ");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function cmsBanner_list(Request $request)
    {
        try {
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

    public function cmsBanner_view(Request $request, $id)
    {
        try {
            // log start *********
            Log::channel("cmsbanner")->info("******* Cms Banner View Method Start *******");
            Log::channel("cmsbanner")->info("Cms Banner Controller start:: find ID : $id");
            // log start *********

            if ($id != '' && $id > 0) {
                $data = Cmsbanner::where('cms_banner_id', $id)->select('*')->first();

                // log end ***********
                Log::channel("cmsbanner")->info("Cms Banner Controller end:: save values :: $id ::::end");
                Log::channel("cmsbanner")->info("******* Cms Banner View Method End *******");
                Log::channel("cmsbanner")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                // log end ***********

                if (!empty($data)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'Banner viewed successfully',
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
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("cmsbanner")->error("******* Cms Banner View Method Error Start *******");
            Log::channel("cmsbanner")->error($exception);
            Log::channel("cmsbanner")->error("******* Cms Banner View Method Error End *******");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function cmsBanner_status(Request $request)
    {
        try {

            if (!empty($request)) {
                $ids = $request->id;
                $ids = json_decode($ids, true);

                if (!empty($ids)) {

                    // log start *********
                    $impl = implode(",", $ids);
                    Log::channel("cmsbanner")->info("******* Cms Banner Status Method Start *******");
                    Log::channel("cmsbanner")->info("Cms Banner Controller start ::: Request IDS == $impl :::: Request status === $request->status");
                    // log start *********
                    $cmsBanner = Cmsbanner::where('cms_banner_id', $ids)->first();
                    $update = Cmsbanner::whereIn('cms_banner_id', $ids)->update(array(
                        'status' => $request->status,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));

                    //   log activity
                    $activity_status = ($request->status) ? 'activated' : 'inactivated';
                    // $implode = implode(",", $ids);
                    $desc =  'Cms Banner '  . $cmsBanner->banner_url  . ' is ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Cms Banner');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {

                        // log end ***********
                        $impl = implode(",", $ids);
                        Log::channel("cmsbanner")->info("Cms Banner Controller end:: save values :: $impl :: Status == Inactive ::::end");
                        Log::channel("cmsbanner")->info("******* Cms Banner Status Method End *******");
                        // log end ***********

                        return response()->json([
                            'keyword' => 'success',
                            'message' => 'Banner inactivated successfully',
                            'data' => []
                        ]);
                    } else if ($request->status == 1) {

                        // log end ***********
                        $impl = implode(",", $ids);
                        Log::channel("cmsbanner")->info("Cms Banner Controller end:: save values :: $impl :: Status == Active ::::end");
                        Log::channel("cmsbanner")->info("******* Cms Banner Status Method End *******");
                        // log end ***********

                        return response()->json([
                            'keyword' => 'success',
                            'message' =>  'Banner activated successfully',
                            'data' => []
                        ]);
                    }
                } else {
                    return response()
                        ->json([
                            'keyword' => 'failed',
                            'message' => __('Banner failed'),
                            'data' => []
                        ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('Banner failed'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("cmsbanner")->error("******* Cms Banner Stauts Method Error Start *******");
            Log::channel("cmsbanner")->error($exception);
            Log::channel("cmsbanner")->error("******* Cms Banner Stauts Method Error End *******");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function OrderUpdateBannerItems(Request $request)
    {
        $sort_order = json_decode($request->sort_order, true);
        if (!empty($sort_order)) {
            for ($i = 0; $i < count($sort_order); $i++) {
                $order_data = Cmsbanner::find($sort_order[$i]);
                $order_data->banner_order_id = $i;
                $order_data->save();
            }
        }

        // log activity
        $desc =  'Cms Banner is Reordered by ' . JwtHelper::getSesUserNameWithType() . '';
        $activitytype = Config('activitytype.Cms Banner');
        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
        return response()->json([
            'keyword' => 'success',
            'message' => __('Banner reordered successfully'),
            'data'   => []
        ]);
    }

    public function cmsBanner_delete(Request $request)
    {
        try {
            if (!empty($request)) {
                $ids = $request->id;
                $ids = json_decode($ids, true);

                if (!empty($ids)) {

                    // log start *********
                    Log::channel("cmsbanner")->info("******* Cms Banner Delete Method Start *******");
                    Log::channel("cmsbanner")->info("Cms Banner Controller start ::: Request IDS == $ids ::::");
                    // log start *********
                    $cmsBanner = Cmsbanner::where('cms_banner_id', $ids)->first();
                    $update = Cmsbanner::where('cms_banner_id', $ids)->update(array(
                        'status' => 2,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));

                    // log activity
                    // $implode = implode(",", $ids);
                    $desc =  ' Cms Banner '  . $cmsBanner->banner_url . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Cms Banner');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    // log end ***********
                    Log::channel("cmsbanner")->info("Cms Banner Controller end:: save values :: $ids :: Status == Deleted ::::end");
                    Log::channel("cmsbanner")->info("******* Cms Banner Delete Method End *******");
                    Log::channel("cmsbanner")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  'Banner deleted successfully',
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
