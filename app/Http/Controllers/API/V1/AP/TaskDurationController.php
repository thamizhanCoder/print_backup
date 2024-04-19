<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Helpers\GlobalHelper;
use App\Models\TaskDuration;
use App\Models\Revertstatus;
use App\Models\Taskstage;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\TaskDurationRequest;

class TaskDurationController extends Controller
{
    public function revertstatus_getcall()
    {
        $get_revert = Revertstatus::select('revert_status.*')->get();

        if (!empty($get_revert)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Revert status listed successfully'),
                    'data' => $get_revert
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => []
                ]
            );
        }
    }
    public function taskduration_update(TaskDurationRequest $request)
    {
        try {
            Log::channel("taskduration")->info('** started the taskduration update method **');

            TaskDuration::truncate();

            $taskduration = new TaskDuration();
            $taskduration->duration = $request->duration;
            $taskduration->revert_status = $request->revert_status;
            $taskduration->updated_on = Server::getDateTime();
            $taskduration->updated_by = JwtHelper::getSesUserId();

            if ($taskduration->save()) {

                $taskdurations = TaskDuration::where('task_duration_id', $taskduration->task_duration_id)->get();
            
                // log activity
                // $desc =  ' Task Duration ' . '(' . $taskduration->duration . ')' . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                $desc =  'Over due task duration days ' . $taskduration->duration . ' as updated by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Task Duration');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                Log::channel("taskduration")->info("save value :: $taskdurations");
                Log::channel("taskduration")->info('** end the taskduration update method **');

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Task duration updated successfully'),
                    'data'        => $taskdurations

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Task duration update failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("taskduration")->error($exception);
            Log::channel("taskduration")->info('** end the taskduration update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function task_duration_list(Request $request)
    {
        try {
            Log::channel("taskduration")->info('** started the taskduration view method **');
            $exp_delivery = TaskDuration::first();

            Log::channel("taskduration")->info("result value :: $exp_delivery");
            Log::channel("taskduration")->info('** end the taskduration view method **');

            if (!empty($exp_delivery)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Task duration viewed successfully'),
                    'data' => $exp_delivery
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("taskduration")->error($exception);
            Log::channel("taskduration")->info('** error occured in taskduration view method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
