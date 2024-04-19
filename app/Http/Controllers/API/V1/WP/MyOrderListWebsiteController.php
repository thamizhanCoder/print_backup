<?php

namespace App\Http\Controllers\API\V1\WP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\myorderlistwebsite;
use App\Models\Department;
use App\Helpers\GlobalHelper;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Http\Requests\myorderlistwebsiteRequest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class MyOrderListWebsiteController extends Controller
{

public function myorderlistwebsite_list(Request $request)
    {
        try {
        // Log::channel("myorderlistwebsite")->info('** started the myorderlistwebsite list method **');
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';




        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'date' => 'myorderlistwebsite.created_on',
            'myorderlistwebsite_name' => 'myorderlistwebsite.myorderlistwebsite_name',
            'myorderlistwebsite_code' => 'myorderlistwebsite.myorderlistwebsite_code',
            'department_name' => 'department.department_name',
            'myorderlistwebsite_type' => 'myorderlistwebsite.myorderlistwebsite_type',
            // 'work_status' => 'myorderlistwebsite.work_status'
        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "myorderlistwebsite.myorderlistwebsite_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('myorderlistwebsite.created_on',
            'myorderlistwebsite.myorderlistwebsite_name', 'myorderlistwebsite.myorderlistwebsite_code', 'department.department_name',
            'myorderlistwebsite.myorderlistwebsite_type'
        );

        $myorderlistwebsites = myorderlistwebsite::leftjoin('department', 'department.department_id', '=', 'myorderlistwebsite.department_id')
            ->select('myorderlistwebsite.*', 'department.department_id', 'department.department_name')
            ->where('myorderlistwebsite.status', '!=', '2');

        $myorderlistwebsites->where(function ($query) use ($searchval, $column_search, $myorderlistwebsites) {
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
            $myorderlistwebsites->orderBy($order_by_key[$sortByKey], $sortType);
        }
        if (!empty($from_date)) {
            $myorderlistwebsites->where(function ($query) use ($from_date) {
                $query->whereDate('myorderlistwebsite.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $myorderlistwebsites->where(function ($query) use ($to_date) {
                $query->whereDate('myorderlistwebsite.created_on', '<=', $to_date);
            });
        }

        if (!empty($filterByStatus)) {
            if ($filterByStatus == "inactive") {
                $myorderlistwebsites->where('myorderlistwebsite.status', 0);
               
            }

            if ($filterByStatus == "active") {
                $myorderlistwebsites->where('myorderlistwebsite.status', 1);
            }
        }

        $count = $myorderlistwebsites->count();

        if ($offset) {
            $offset = $offset * $limit;
            $myorderlistwebsites->offset($offset);
        }
        if ($limit) {
            $myorderlistwebsites->limit($limit);
        }
        Log::channel("myorderlistwebsite")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
        $myorderlistwebsites->orderBy('myorderlistwebsite.myorderlistwebsite_id', 'desc');
        $myorderlistwebsites = $myorderlistwebsites->get();
        if ($count > 0) {
            $final = [];
            foreach ($myorderlistwebsites as $value) {
                $ary = [];
                $ary['myorderlistwebsite_id'] = $value['myorderlistwebsite_id'];
                $ary['myorderlistwebsite_code'] = $value['myorderlistwebsite_code'];
                $ary['myorderlistwebsite_name'] = $value['myorderlistwebsite_name'];
                //$ary['image'] = $value['myorderlistwebsite_image'];
                //$ary['myorderlistwebsite_image'] = ($ary['image'] != '') ? env('APP_URL') . env('myorderlistwebsite_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
                       
             
                                
                if ($value['myorderlistwebsite_type'] == 1) {
                    $ary['myorderlistwebsite_type'] = "In-House";
                }
                if ($value['myorderlistwebsite_type'] == 2) {
                    $ary['myorderlistwebsite_type'] = "Vendor";
                }
                $ary['department_id'] = $value['department_id'];
                $ary['department_name'] = $value['department_name'];
                $ary['mobile_no'] = $value['mobile_no'];
                $ary['email'] = $value['email'];
                $ary['work_status'] = 0;
                // $ary['created_on'] = $value['created_on'];
                $ary['created_on'] = date('d-m-Y', strtotime($value['created_on']));
                $ary['created_by'] = date('d-m-Y', strtotime($value['created_by']));
                $ary['updated_on'] = date('d-m-Y', strtotime($value['updated_on']));
                $ary['updated_by'] = date('d-m-Y', strtotime($value['updated_by']));
                // $ary['created_by'] = $value['created_by'];
                // $ary['updated_on'] = $value['updated_on'];
                // $ary['updated_by'] = $value['updated_by'];
                $ary['status'] = $value['status'];
                $final[] = $ary;
            }
        }
        if (!empty($final)) {
            $log = json_encode($final, true);
            Log::channel("myorderlistwebsite")->info("list value :: $log");
            Log::channel("myorderlistwebsite")->info('** end the myorderlistwebsite list method **');
            return response()->json([
                'keyword' => 'success',
                'message' => __('Myorder website listed successfully'),
                'data' => $final,
                'count' => count($myorderlistwebsites)
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => [],
                'count' => count($myorderlistwebsites)
            ]);
        }
        } catch (\Exception $exception) {
            Log::channel("myorderlistwebsite")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function getdefaultImages_allImages($gTImage)
    {
        $imG = [];
        if (!empty($gTImage)) {
            foreach ($gTImage as $im) {
                $ary = [];
                $ary['index'] = $im['index'];
                $ary['url'] = ($im['image'] != '') ? env('APP_URL') . env('myorderlistwebsite_URL') . $im['image'] : env('APP_URL') . "avatar.jpg";
                $ary['image'] = $im['image'];
                $imG[] = $ary;
            }
        }
        return $imG;
    }

    public function myorderlistwebsite_update(myorderlistwebsiteRequest $request)
    {
        try {

            if (!empty($request->myorderlistwebsite_image)) {
                $gTImage = json_decode($request->myorderlistwebsite_image, true);
                if (!empty($gTImage)) {
                    foreach ($gTImage as $im) {
                        $ary[] = pathinfo($im['image'], PATHINFO_EXTENSION);
                    }
                }
                $extension_array = ['jpeg', 'png', 'jpg'];
                if (!array_diff($ary, $extension_array)) {
                    $request->myorderlistwebsite_image;
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data'        => []
                    ]);
                }
            }


            $emailexist = myorderlistwebsite::where([
                ['myorderlistwebsite_id', '!=', $request->myorderlistwebsite_id],
                ['email', '=', $request->email],
                ['status', '!=', 2]
            ])->first();
            if (empty($emailexist)) {

                $mobileexist = myorderlistwebsite::where([
                    ['myorderlistwebsite_id', '!=', $request->myorderlistwebsite_id],
                    ['mobile_no', '=', $request->mobile_no],
                    ['status', '!=', 2]
                ])->first();

                if (empty($mobileexist)) {
                    $ids = $request->input('myorderlistwebsite_id');
                    $myorderlistwebsite = myorderlistwebsite::find($ids);

                    $myorderlistwebsite->myorderlistwebsite_type = $request->myorderlistwebsite_type;
                    $myorderlistwebsite->myorderlistwebsite_name = $request->myorderlistwebsite_name;
                    $myorderlistwebsite->mobile_no = $request->mobile_no;
                    $myorderlistwebsite->email = $request->email;
                    $myorderlistwebsite->department_id = $request->department_id;
                    $myorderlistwebsite->myorderlistwebsite_image = $request->myorderlistwebsite_image;
                    $myorderlistwebsite->updated_on = Server::getDateTime();
                    // $myorderlistwebsite->updated_by = JwtHelper::getSesUserId();

                    if ($myorderlistwebsite->save()) {
                        $myorderlistwebsites = myorderlistwebsite::where('myorderlistwebsite_id', $myorderlistwebsite->myorderlistwebsite_id)->first();
                        // log activity
                        // $desc =  'myorderlistwebsite' . $myorderlistwebsite->myorderlistwebsite_name  . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                        // $activitytype = Config('activitytype.myorderlistwebsite');
                        // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        Log::channel("myorderlistwebsite")->info("save value :: $myorderlistwebsites");



                        return response()->json([
                            'keyword'      => 'success',
                            'data'        => [$myorderlistwebsites],
                            'message'      => __('Myorder webiste updated successfully')
                        ]);
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'data'        => [],
                            'message'      => __('Myorder webiste update failed'),
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Mobile number already exist'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Email-ID already exist'),
                    'data'        => []
                ]);

                // } else {
                //     return response()->json([
                //         'keyword'      => 'failed',
                //         'message'      => __('myorderlistwebsite name already exist'),
                //         'data'        => []
                //     ]);
            }
        } catch (\Exception $exception) {
            Log::channel("myorderlistwebsite")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }



    public function myorderlistwebsite_view($id)
    {
        try {
            Log::channel("myorderlistwebsite")->info('** started the myorderlistwebsite view method **');
            $myorderlistwebsite_view = myorderlistwebsite::where('myorderlistwebsite_id', $id)
                ->leftjoin('department', 'department.department_id', '=', 'myorderlistwebsite.department_id')
                ->select('myorderlistwebsite.*', 'department.department_id', 'department.department_name')->first();

            Log::channel("myorderlistwebsite")->info("request value myorderlistwebsite_id:: $id");



            $final = [];

            if (!empty($myorderlistwebsite_view)) {
                $ary = [];
                $ary['myorderlistwebsite_id'] = $myorderlistwebsite_view['myorderlistwebsite_id'];
                $ary['myorderlistwebsite_code'] = $myorderlistwebsite_view['myorderlistwebsite_code'];
                $ary['myorderlistwebsite_name'] = $myorderlistwebsite_view['myorderlistwebsite_name'];
                $ary['image'] = $myorderlistwebsite_view['myorderlistwebsite_image'];
                $ary['myorderlistwebsite_image'] = ($ary['image'] != '') ? env('APP_URL') . env('myorderlistwebsite_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
                if ($myorderlistwebsite_view['myorderlistwebsite_type'] == 1) {
                    $ary['myorderlistwebsite_type'] = "In-House";
                }
                if ($myorderlistwebsite_view['myorderlistwebsite_type'] == 2) {
                    $ary['myorderlistwebsite_type'] = "Vendor";
                }
                if ($myorderlistwebsite_view['myorderlistwebsite_type'] == 1) {
                $ary['department_id'] = $myorderlistwebsite_view['department_id'];
                $ary['department_name'] = $myorderlistwebsite_view['department_name'];
                }
                $ary['mobile_no'] = $myorderlistwebsite_view['mobile_no'];
                $ary['email'] = $myorderlistwebsite_view['email'];
                $gTImage = json_decode($myorderlistwebsite_view['myorderlistwebsite_image'], true);
                $ary['myorderlistwebsite_image'] = $this->getdefaultImages_allImages($gTImage);
                // $ary['created_on'] = $myorderlistwebsite_view['created_on'];
                // $ary['created_on'] = date('d-m-Y', strtotime($myorderlistwebsite_view['created_on']));

                $ary['created_on'] = date('d-m-Y', strtotime($myorderlistwebsite_view['created_on']));
                $ary['created_by'] = date('d-m-Y', strtotime($myorderlistwebsite_view['created_by']));
                $ary['updated_on'] = date('d-m-Y', strtotime($myorderlistwebsite_view['updated_on']));
                $ary['updated_by'] = date('d-m-Y', strtotime($myorderlistwebsite_view['updated_by']));

                // $ary['created_by'] = $myorderlistwebsite_view['created_by'];
                // $ary['updated_on'] = $myorderlistwebsite_view['updated_on'];
                // $ary['updated_by'] = $myorderlistwebsite_view['updated_by'];
                $ary['status'] = $myorderlistwebsite_view['status'];
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("myorderlistwebsite")->info("view value :: $log");
                Log::channel("myorderlistwebsite")->info('** end the myorderlistwebsite view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Myorder webiste viewed successfully'),
                    'data' => $final
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No Data Found'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("myorderlistwebsite")->error($exception);
            Log::channel("myorderlistwebsite")->info('** end the myorderlistwebsite view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }