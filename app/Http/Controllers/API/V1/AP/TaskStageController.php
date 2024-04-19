<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Helpers\GlobalHelper;
use App\Models\Service;
use App\Models\Taskstage;
use App\Models\Department;
use App\Models\Stage;
use Illuminate\Http\Request;
use App\Http\Requests\TaskStageRequest;

class TaskStageController extends Controller
{
    public function taskstage_create(TaskStageRequest $request)
    {
        try {
            Log::channel("taskstage")->info('** started the taskstage create method **');
            
            $exist = Taskstage::where([['service_id', $request->service_id], ['status', '!=', 2]])->first();

            if (empty($exist)) {

                $taskstage = new Taskstage();
                $taskstage->service_id = $request->service_id;
                $taskstage->no_of_stage = $request->no_of_stage;
                $taskstage->stage_details = $request->stage_details;
                $taskstage->created_on = Server::getDateTime();
                $taskstage->created_by = JwtHelper::getSesUserId();

                Log::channel("taskstage")->info("request value :: $request");

                if ($taskstage->save()) {
                   
                    $taskstages = Taskstage::where('taskstage.taskstage_id', $taskstage->taskstage_id)->leftjoin('service', 'service.service_id', '=', 'taskstage.service_id')->select('taskstage.*', 'service.service_name')->first();
             
                    // log activity
                    $desc =  'Task Stage ' . '(' . $taskstages->service_name . ')' . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Task Stage');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("taskstage")->info("save value :: $taskstages");
                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => 'Task stage created successfully',
                        'data'        => [$taskstages]
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => 'task stage creation failed',
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => 'Task stage for this service already exist',
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("taskstage")->error($exception);
            Log::channel("taskstage")->error('** end the taskstage create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function taskstage_list(Request $request)
    {
        try {
            Log::channel("taskstage")->info('** started the taskstage list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'service_name' => 'service_name',
                'no_of_stage' =>  'no_of_stage',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "taskstage_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('service_name');
            $taskstage = Taskstage::where('taskstage.status', '!=', 2)
                ->leftjoin('service', 'service.service_id', '=', 'taskstage.service_id')
                ->select('taskstage.*','service.service_name');
            $count = $taskstage->count();

            $taskstage->where(function ($query) use ($searchval, $column_search, $taskstage) {
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
                $taskstage->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if ($offset) {
                $offset = $offset * $limit;
                $taskstage->offset($offset);
            }
            if ($limit) {
                $taskstage->limit($limit);
            }
            Log::channel("taskstage")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $taskstage->orderBy('taskstage_id', 'desc');
            $taskstage = $taskstage->get();
            if ($count > 0) {
                $final = [];
                foreach ($taskstage as $value) {
                    $ary = [];
                    $ary['taskstage_id'] = $value['taskstage_id'];
                    $ary['service_id'] = $value['service_id'];
                    $ary['service_name'] = $value['service_name'];
                    $ary['no_of_stage'] = $value['no_of_stage'];
                    $ary['stage_details'] = $value['stage_details'];
                    $ary['department_name'] = $this->getDepartment($value['stage_details']);
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
                Log::channel("taskstage")->info("list value :: $log");
                Log::channel("taskstage")->info('** end the taskstage list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Taskstage listed successfully'),
                    'data' => $final,
                    'count' => count($taskstage)
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                    'count' => count($taskstage)
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("taskstage")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


 public function getDepartment($stage_details){

        $stage_detail = json_decode($stage_details,true);

        $taskstage = [];
        $detail = [];


        if(!empty($stage_detail)){

        foreach($stage_detail as $det) {

            $department_ids[] = $det['department_id'];

             $taskstage[] = Department::where('department_id',$det['department_id'])
            ->select('department_name')->first();


        }

//        if(!empty($department_ids)){

//            $taskstage = Department::whereIn('department_id',$department_ids)
//            ->select('department_name')->get()->all();

//            $res = $taskstage->implode('department_name', ', ');

//        }
    }
        return $taskstage;
        
     }


    public function taskstage_update(TaskStageRequest $request)
    {
        try {
            Log::channel("taskstage")->info('** started the taskstage update method **');

            $exist = Taskstage::where([['service_id', $request->service_id],
            ['taskstage_id','!=', $request->taskstage_id],['status', '!=', 2]])->first();

            if (empty($exist)) {
                $ids = $request->taskstage_id;
                $taskstage = Taskstage::find($ids);
                $taskstage->service_id = $request->service_id;
                $taskstage->no_of_stage = $request->no_of_stage;
                $taskstage->stage_details = $request->stage_details;
                $taskstage->updated_on = Server::getDateTime();
                $taskstage->updated_by = JwtHelper::getSesUserId();
                Log::channel("taskstage")->info("request value :: $taskstage");

                if ($taskstage->save()) {
                    $taskstages = Taskstage::where('taskstage.taskstage_id', $taskstage->taskstage_id)->leftjoin('service', 'service.service_id', '=', 'taskstage.service_id')->select('taskstage.*', 'service.service_name')->first();

                    // log activity
                    $desc =  'Task Stage ' . '(' . $taskstages->service_name . ')' . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Task Stage');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("taskstage")->info("save value :: $taskstages");
                    Log::channel("taskstage")->info('** end the taskstage update method **');

                    return response()->json([
                        'keyword'      => 'success',
                        'data'        => [$taskstages],
                        'message'      => 'Task stage updated successfully'
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'data'        => [],
                        'message'      => 'Task stage update failed'
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => 'Task stage for this service already exist',
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("taskstage")->error($exception);
            Log::channel("taskstage")->error('** end the taskstage update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function taskstage_view($id)
    {
        try {
            Log::channel("taskstage")->info('** started the taskstage view method **');
            if ($id != '' && $id > 0) {

                $taskstage = Taskstage::where('taskstage_id', $id)->where('taskstage.status', '!=', 2)
                ->leftjoin('service', 'service.service_id', '=', 'taskstage.service_id')
                    ->select('taskstage.*','service.service_name')->get();

                Log::channel("taskstage")->info("request value taskstage_id:: $id");
                $count = $taskstage->count();
                if ($count > 0) {
                    $final = [];
                    foreach ($taskstage as $value) {
                        $ary = [];
                        $ary['taskstage_id'] = $value['taskstage_id'];
                        $ary['service_id'] = $value['service_id'];
                        $ary['service_name'] = $value['service_name'];
                        $ary['no_of_stage'] = $value['no_of_stage'];
                        $ary['stage_details'] = $value['stage_details'];
                        $ary['department_name'] = $this->getDepartment($value['stage_details']);
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
                    Log::channel("taskstage")->info("view value :: $log");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Task stage viewed successfully'),
                        'data' => $final
                    ]);
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
                    'message' => __('No data found'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("taskstage")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function taskstage_status(Request $request)
    {
        try {

                $ids = $request->id;

                if (!empty($ids)) {
                    Log::channel("taskstage")->info("request value taskstage_id:: $ids :: status :: $request->status");

                    $taskstage = Taskstage::where('taskstage_id', $ids)->leftjoin('service', 'service.service_id', '=', 'taskstage.service_id')->select('taskstage.*', 'service.service_name')->first();
                    $update = Taskstage::where('taskstage_id', $ids)->update(array(
                        'status' => $request->status,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));
                    //   log activity
                    if ($request->status == 0) {
                        $activity_status = 'inactivated';
                    } else if ($request->status == 1) {
                        $activity_status = 'activated';
                    } else if ($request->status == 2) {
                        $activity_status = 'deleted';
                    }
                    // $implode = implode(",", $ids);
                    $desc =  'Task Stage ' . '(' . $taskstage->service_name . ')' . ' is ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Task Stage');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {
                        Log::channel("taskstage")->info("save value :: taskstage_id  :: $ids :: taskstage inactive successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Task stage inactivated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 1) {
                        Log::channel("taskstage")->info("save value :: taskstage_id  :: $ids :: taskstage active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Task stage activated successfully'),
                            'data' => []
                        ]);
                    }
                  else if ($request->status == 2) {
                    Log::channel("taskstage")->info("save value :: taskstage_id  :: $ids :: taskstage deleted successfully");
                     return response()->json([
                        'keyword' => 'success',
                        'message' => __('Task stage deleted successfully'),
                        'data' => []
                    ]);
                }
            else {
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
            Log::channel("taskstage")->error($exception);
            Log::channel("taskstage")->info('** end the taskstage status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function serviceListForTaskStage(Request $request)
    {
        
            $service = Service::select('service_id','service_name')->get();
            
            if (!empty($service)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Service listed successfully'),
                    'data' => $service

                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
       
    }

    public function departmentListForTaskStage(Request $request)
    {

            $department = Department::where('status', '!=', 2)
                ->select('department_id','department_name')->get();
            
            if (!empty($department)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Department listed successfully'),
                    'data' => $department

                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
       
    }


}
