<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\myorderlistmobile;
use App\Models\Department;
use App\Helpers\GlobalHelper;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Http\Requests\myorderlistmobileRequest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class myorderlistmobileController extends Controller
{

public function myorderlistmobile_list(Request $request)
    {
        try {
        // Log::channel("myorderlistmobile")->info('** started the myorderlistmobile list method **');
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';




        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'date' => 'myorderlistmobile.created_on',
            'myorderlistmobile_name' => 'myorderlistmobile.myorderlistmobile_name',
            'myorderlistmobile_code' => 'myorderlistmobile.myorderlistmobile_code',
            'department_name' => 'department.department_name',
            'myorderlistmobile_type' => 'myorderlistmobile.myorderlistmobile_type',
            // 'work_status' => 'myorderlistmobile.work_status'
        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "myorderlistmobile.myorderlistmobile_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('myorderlistmobile.created_on',
            'myorderlistmobile.myorderlistmobile_name', 'myorderlistmobile.myorderlistmobile_code', 'department.department_name',
            'myorderlistmobile.myorderlistmobile_type'
        );

        $myorderlistmobiles = myorderlistmobile::leftjoin('department', 'department.department_id', '=', 'myorderlistmobile.department_id')
            ->select('myorderlistmobile.*', 'department.department_id', 'department.department_name')
            ->where('myorderlistmobile.status', '!=', '2');

        $myorderlistmobiles->where(function ($query) use ($searchval, $column_search, $myorderlistmobiles) {
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
            $myorderlistmobiles->orderBy($order_by_key[$sortByKey], $sortType);
        }
        if (!empty($from_date)) {
            $myorderlistmobiles->where(function ($query) use ($from_date) {
                $query->whereDate('myorderlistmobile.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $myorderlistmobiles->where(function ($query) use ($to_date) {
                $query->whereDate('myorderlistmobile.created_on', '<=', $to_date);
            });
        }

        if (!empty($filterByStatus)) {
            if ($filterByStatus == "inactive") {
                $myorderlistmobiles->where('myorderlistmobile.status', 0);
               
            }

            if ($filterByStatus == "active") {
                $myorderlistmobiles->where('myorderlistmobile.status', 1);
            }
        }

        $count = $myorderlistmobiles->count();

        if ($offset) {
            $offset = $offset * $limit;
            $myorderlistmobiles->offset($offset);
        }
        if ($limit) {
            $myorderlistmobiles->limit($limit);
        }
        Log::channel("myorderlistmobile")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
        $myorderlistmobiles->orderBy('myorderlistmobile.myorderlistmobile_id', 'desc');
        $myorderlistmobiles = $myorderlistmobiles->get();
        if ($count > 0) {
            $final = [];
            foreach ($myorderlistmobiles as $value) {
                $ary = [];
                $ary['myorderlistmobile_id'] = $value['myorderlistmobile_id'];
                $ary['myorderlistmobile_code'] = $value['myorderlistmobile_code'];
                $ary['myorderlistmobile_name'] = $value['myorderlistmobile_name'];
                //$ary['image'] = $value['myorderlistmobile_image'];
                //$ary['myorderlistmobile_image'] = ($ary['image'] != '') ? env('APP_URL') . env('myorderlistmobile_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
                       
             
                                
                if ($value['myorderlistmobile_type'] == 1) {
                    $ary['myorderlistmobile_type'] = "In-House";
                }
                if ($value['myorderlistmobile_type'] == 2) {
                    $ary['myorderlistmobile_type'] = "Vendor";
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
            Log::channel("myorderlistmobile")->info("list value :: $log");
            Log::channel("myorderlistmobile")->info('** end the myorderlistmobile list method **');
            return response()->json([
                'keyword' => 'success',
                'message' => __('Myorder mobile listed successfully'),
                'data' => $final,
                'count' => count($myorderlistmobiles)
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => [],
                'count' => count($myorderlistmobiles)
            ]);
        }
        } catch (\Exception $exception) {
            Log::channel("myorderlistmobile")->error($exception);

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
                $ary['url'] = ($im['image'] != '') ? env('APP_URL') . env('myorderlistmobile_URL') . $im['image'] : env('APP_URL') . "avatar.jpg";
                $ary['image'] = $im['image'];
                $imG[] = $ary;
            }
        }
        return $imG;
    }

    public function myorderlistmobile_update(myorderlistmobileRequest $request)
    {
        try {

            if (!empty($request->myorderlistmobile_image)) {
                $gTImage = json_decode($request->myorderlistmobile_image, true);
                if (!empty($gTImage)) {
                    foreach ($gTImage as $im) {
                        $ary[] = pathinfo($im['image'], PATHINFO_EXTENSION);
                    }
                }
                $extension_array = ['jpeg', 'png', 'jpg'];
                if (!array_diff($ary, $extension_array)) {
                    $request->myorderlistmobile_image;
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data'        => []
                    ]);
                }
            }


            $emailexist = myorderlistmobile::where([
                ['myorderlistmobile_id', '!=', $request->myorderlistmobile_id],
                ['email', '=', $request->email],
                ['status', '!=', 2]
            ])->first();
            if (empty($emailexist)) {

                $mobileexist = myorderlistmobile::where([
                    ['myorderlistmobile_id', '!=', $request->myorderlistmobile_id],
                    ['mobile_no', '=', $request->mobile_no],
                    ['status', '!=', 2]
                ])->first();

                if (empty($mobileexist)) {
                    $ids = $request->input('myorderlistmobile_id');
                    $myorderlistmobile = myorderlistmobile::find($ids);

                    $myorderlistmobile->myorderlistmobile_type = $request->myorderlistmobile_type;
                    $myorderlistmobile->myorderlistmobile_name = $request->myorderlistmobile_name;
                    $myorderlistmobile->mobile_no = $request->mobile_no;
                    $myorderlistmobile->email = $request->email;
                    $myorderlistmobile->department_id = $request->department_id;
                    $myorderlistmobile->myorderlistmobile_image = $request->myorderlistmobile_image;
                    $myorderlistmobile->updated_on = Server::getDateTime();
                    // $myorderlistmobile->updated_by = JwtHelper::getSesUserId();

                    if ($myorderlistmobile->save()) {
                        $myorderlistmobiles = myorderlistmobile::where('myorderlistmobile_id', $myorderlistmobile->myorderlistmobile_id)->first();
                        // log activity
                        // $desc =  'myorderlistmobile' . $myorderlistmobile->myorderlistmobile_name  . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                        // $activitytype = Config('activitytype.myorderlistmobile');
                        // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        Log::channel("myorderlistmobile")->info("save value :: $myorderlistmobiles");



                        return response()->json([
                            'keyword'      => 'success',
                            'data'        => [$myorderlistmobiles],
                            'message'      => __('Myorder mobile updated successfully')
                        ]);
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'data'        => [],
                            'message'      => __('Myorder mobile update failed'),
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
                //         'message'      => __('myorderlistmobile name already exist'),
                //         'data'        => []
                //     ]);
            }
        } catch (\Exception $exception) {
            Log::channel("myorderlistmobile")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }



    public function myorderlistmobile_view($id)
    {
        try {
            Log::channel("myorderlistmobile")->info('** started the myorderlistmobile view method **');
            $myorderlistmobile_view = myorderlistmobile::where('myorderlistmobile_id', $id)
                ->leftjoin('department', 'department.department_id', '=', 'myorderlistmobile.department_id')
                ->select('myorderlistmobile.*', 'department.department_id', 'department.department_name')->first();

            Log::channel("myorderlistmobile")->info("request value myorderlistmobile_id:: $id");



            $final = [];

            if (!empty($myorderlistmobile_view)) {
                $ary = [];
                $ary['myorderlistmobile_id'] = $myorderlistmobile_view['myorderlistmobile_id'];
                $ary['myorderlistmobile_code'] = $myorderlistmobile_view['myorderlistmobile_code'];
                $ary['myorderlistmobile_name'] = $myorderlistmobile_view['myorderlistmobile_name'];
                $ary['image'] = $myorderlistmobile_view['myorderlistmobile_image'];
                $ary['myorderlistmobile_image'] = ($ary['image'] != '') ? env('APP_URL') . env('myorderlistmobile_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
                if ($myorderlistmobile_view['myorderlistmobile_type'] == 1) {
                    $ary['myorderlistmobile_type'] = "In-House";
                }
                if ($myorderlistmobile_view['myorderlistmobile_type'] == 2) {
                    $ary['myorderlistmobile_type'] = "Vendor";
                }
                if ($myorderlistmobile_view['myorderlistmobile_type'] == 1) {
                $ary['department_id'] = $myorderlistmobile_view['department_id'];
                $ary['department_name'] = $myorderlistmobile_view['department_name'];
                }
                $ary['mobile_no'] = $myorderlistmobile_view['mobile_no'];
                $ary['email'] = $myorderlistmobile_view['email'];
                $gTImage = json_decode($myorderlistmobile_view['myorderlistmobile_image'], true);
                $ary['myorderlistmobile_image'] = $this->getdefaultImages_allImages($gTImage);
                // $ary['created_on'] = $myorderlistmobile_view['created_on'];
                // $ary['created_on'] = date('d-m-Y', strtotime($myorderlistmobile_view['created_on']));

                $ary['created_on'] = date('d-m-Y', strtotime($myorderlistmobile_view['created_on']));
                $ary['created_by'] = date('d-m-Y', strtotime($myorderlistmobile_view['created_by']));
                $ary['updated_on'] = date('d-m-Y', strtotime($myorderlistmobile_view['updated_on']));
                $ary['updated_by'] = date('d-m-Y', strtotime($myorderlistmobile_view['updated_by']));

                // $ary['created_by'] = $myorderlistmobile_view['created_by'];
                // $ary['updated_on'] = $myorderlistmobile_view['updated_on'];
                // $ary['updated_by'] = $myorderlistmobile_view['updated_by'];
                $ary['status'] = $myorderlistmobile_view['status'];
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("myorderlistmobile")->info("view value :: $log");
                Log::channel("myorderlistmobile")->info('** end the myorderlistmobile view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Myorder mobile viewed successfully'),
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
            Log::channel("myorderlistmobile")->error($exception);
            Log::channel("myorderlistmobile")->info('** end the myorderlistmobile view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }