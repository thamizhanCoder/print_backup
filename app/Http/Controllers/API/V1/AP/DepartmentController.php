<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use Illuminate\Support\Facades\Log;
use App\Models\Department;
use App\Models\Employee;
use App\Helpers\GlobalHelper;
use App\Http\Requests\DepartmentRequest;
use App\Models\Designation;

class DepartmentController extends Controller
{
    public function dept_create(DepartmentRequest $request)
    {
        try {
            Log::channel("department")->info('** started the department create method **');
            $department = new Department();
            $exist = Department::where([['department_name', $request->department_name], ['status', '!=', 2]])->first();

            if (empty($exist)) {
                $department->department_name = $request->department_name;
                $department->created_on = Server::getDateTime();
                $department->created_by = JwtHelper::getSesUserId();
                Log::channel("department")->info("request value :: $department->department_name");



                if ($department->save()) {
                    $departments = Department::where('department_id', $department->department_id)->first();

                    // log activity
                    $desc =  'Department ' . $department->department_name . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Department');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("department")->info("save value :: $departments");
                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('Department created successfully'),
                        'data'        => [$departments]
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Department creation failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Department name already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("department")->error($exception);
            Log::channel("department")->error('** end the department create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function dept_list(Request $request)
    {
        try {
            Log::channel("department")->info('** started the department list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'department_name' => 'department_name',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "department_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('department_name');
            $departments = Department::where([
                ['status', '!=', '2']
            ]);

            $departments->where(function ($query) use ($searchval, $column_search, $departments) {
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
                $departments->orderBy($order_by_key[$sortByKey], $sortType);
            }

            $count = $departments->count();

            if ($offset) {
                $offset = $offset * $limit;
                $departments->offset($offset);
            }
            if ($limit) {
                $departments->limit($limit);
            }
            Log::channel("department")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $departments->orderBy('department_id', 'desc');
            $departments = $departments->get();
            if ($count > 0) {
                $final = [];
                foreach ($departments as $value) {
                    $ary = [];
                    $ary['department_id'] = $value['department_id'];
                    $ary['department_name'] = $value['department_name'];
                    $ary['created_on'] = $value['created_on'];
                    $ary['created_by'] = $value['created_by'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['updated_by'] = $value['updated_by'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("department")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Department listed successfully'),
                    'data' => $final,
                    'count' => $count
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                    'count' => $count
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("department")->error($exception);
            Log::channel("department")->error('** end the department list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function dept_update(DepartmentRequest $request)
    {
        try {
            Log::channel("department")->info('** started the department update method **');

            $exist = Department::where([['department_id', '!=', $request->department_id],['department_name', $request->department_name], ['status', '!=', 2]])->first();

            if (empty($exist)) {
                $departmentsOldDetails = Department::where('department_id', $request->department_id)->first();

                $ids = $request->department_id;
                $department = Department::find($ids);
                $department->department_name = $request->department_name;
                $department->updated_on = Server::getDateTime();
                $department->updated_by = JwtHelper::getSesUserId();
                Log::channel("department")->info("request value :: $department->department_name");

                if ($department->save()) {
                    $departments = Department::where('department_id', $department->department_id)->first();

                    // log activity
                    $desc =  'Department ' . '(' . $departmentsOldDetails->department_name . ')' . ' is updated as ' . '(' . $department->department_name . ')' . ' by ' .JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Department');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("department")->info("save value :: $departments");
                    Log::channel("department")->info('** end the department update method **');

                    return response()->json([
                        'keyword'      => 'success',
                        'data'        => [$departments],
                        'message'      => __('Department updated successfully')
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'data'        => [],
                        'message'      => __('Department update failed')
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Department name already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("department")->error($exception);
            Log::channel("department")->error('** end the department update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function dept_view($id)
    {
        try {
            Log::channel("department")->info('** started the department view method **');
            if ($id != '' && $id > 0) {
                $get_department = Department::where('department_id', $id)->get();
                Log::channel("department")->info("request value department_id:: $id");
                $count = $get_department->count();
                if ($count > 0) {
                    $final = [];
                    foreach ($get_department as $value) {
                        $ary = [];
                        $ary['department_id'] = $value['department_id'];
                        $ary['department_name'] = $value['department_name'];
                        $ary['created_on'] = $value['created_on'];
                        $ary['created_by'] = $value['created_by'];
                        $ary['updated_on'] = $value['updated_on'];
                        $ary['updated_by'] = $value['updated_by'];
                        $ary['status'] = $value['status'];
                        $final[] = $ary;
                    }
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("department")->info("view value :: $log");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Department viewed successfully'),
                        'data' => $final
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {
            Log::channel("department")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function dept_status(Request $request)
    {
        try {
            if (!empty($request)) {

                $ids = $request->id;

                if (!empty($ids)) {
                    Log::channel("department")->info("request value department_id:: $ids :: status :: $request->status");

                    $department = Department::where('department_id', $ids)->first();
                    $update = Department::where('department_id', $ids)->update(array(
                        'status' => $request->status,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));
                    //   log activity
                    $activity_status = ($request->status) ? 'activated' : 'inactivated';
                    // $implode = implode(",", $ids);
                    $desc =  'Department ' . $department->department_name . ' is ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Department');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {
                        Log::channel("department")->info("save value :: department_id :: $ids :: department inactive successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Department inactivated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 1) {
                        Log::channel("department")->info("save value :: department_id :: $ids :: department active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Department activated successfully'),
                            'data' => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.no_data'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("department")->error($exception);
            Log::channel("department")->info('** end the department status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function dept_delete(Request $request)
    {
        try {
            if (!empty($request)) {
                $ids = $request->id;


                if (!empty($ids)) {

                    $exist = Employee::where('department_id', $ids)->where('status', '!=', 2)->first();
                        if (empty($exist)) {


                Log::channel("department")->info("request value department_id:: $ids :: ");
                $department = Department::where('department_id', $ids)->first();
                $update = Department::where('department_id', $ids)->update(array(
                    'status' => 2,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));
                // log activity
                // $implode = implode(",", $ids);
                $desc =  'Department ' . $department->department_name .' is'.' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Department');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                Log::channel("department")->info("save value :: department_id :: $ids :: department deleted successfully");

                return response()->json([
                    'keyword' => 'success',
                    'message' =>  __('Department deleted successfully'),
                    'data' => []
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('The department is used in employee cannot delete.'),
                    'data' => []
                ]);
            }  
            
        } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' =>  __('message.failed'),
                    'data' => []
                ]);
            }
        }
    
        } catch (\Exception $exception) {
            Log::channel("department")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    
    }
}


