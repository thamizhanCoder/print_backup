<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Helpers\Firebase;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\EmployeeTaskHistory;
use App\Models\OrderItemStage;
use App\Models\TaskDuration;
use App\Models\TaskManager;
use App\Models\TaskManagerHistory;
use Illuminate\Support\Facades\Log;

class CronController extends Controller
{
    public function revokeCron(Request $request)
    {
        $taskDuration = TaskDuration::select('*')->first();
        $today = date('Y-m-d');
        $details = TaskManagerHistory::where('task_manager_history.production_status', '!=', 2)->where('task_manager_history.extra_expected_on', '<', $today)->select('task_manager_history_id', 'task_manager_id', 'orderitem_stage_id');
        // $details->where(function ($query) use ($taskDuration, $details) {
        //     $ary = json_decode($taskDuration->revert_status, true);
        //     for ($i = 0; $i < count($ary); $i++) {
        //         if ($i == 0) {
        //             $query->whereJsonContains('task_manager_history.work_stage', $ary[$i]);
        //         } else {
        //             $query->orWhereJsonContains('task_manager_history.work_stage', $ary[$i]);
        //         }
        //     }
        // });
        $filterByName = json_decode($taskDuration->revert_status, true);
        $details->whereIn('task_manager_history.work_stage', $filterByName);
        $details = $details->get();

        if (!empty($details)) {
            foreach ($details as $det) {

                if(!empty($det['orderitem_stage_id'])){
                $orderItemStage = OrderItemStage::where('orderitem_stage_id', $det['orderitem_stage_id'])->update(array(
                    'status' => 1,
                    'qc_status' => 0
                ));
            }
                $taskManagerHistory = TaskManagerHistory::where('task_manager_history_id', $det['task_manager_history_id'])->update(array(
                    'revoked_on' => Server::getDateTime(),
                    //'revoked_by' => JwtHelper::getSesUserId(),
                    'production_status' => 2
                ));
                $employeeTaskHistory = EmployeeTaskHistory::where('task_manager_history_id', $det['task_manager_history_id'])->update(array(
                    'employee_status' => 2
                ));
                $taskManager = TaskManager::where('task_manager_id', $det['task_manager_id'])->update(array(
                    'current_task_stage' => 2,
                    'updated_on' => Server::getDateTime(),
                    //'updated_by' => JwtHelper::getSesUserId()
                ));
            }

            if (!empty($details)) {
                return response()->json([
                    'keyword' => 'success',
                    'data'   => $details,
                    'message' => __('Reverted successfully')
                ]);
            }
        }
    }

    // public function greet($check, $today, $key)
    // {
    //     $body = Config('fcm_msg.body.greetings');
    //     $title = 'Greetings';
    //     $module = 'cms_greeting';
    //     $page = 'greeting_create';
    //     $portal = 'admin';
    //     $data = [];
    //     foreach ($check as $value) {
    //         $ary = [];
    //         $ary['cms_greeting_id'] = $value['cms_greeting_id'];
    //         $ary['image'] = $value['greeting_image'];
    //         $ary['greeting_image'] = ($ary['image'] != '') ? env('APP_URL') . env('GREETINGS_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
    //         $ary['from_date'] = date("Y-m-d H:i", strtotime($value['from_date']));
    //         $ary['to_date'] = date("Y-m-d H:i:s", strtotime($value['to_date']));
    //         $ary['page'] = 'greeting_create';
    //         $data[] = $ary;
    //         if ($ary['from_date'] == $today) {
    //             $message = [
    //                 'title' => $title,
    //                 'body' => $body,
    //                 'page' => $page,
    //                 'data' => $data,
    //                 'portal' => $portal,
    //                 'module' => $module
    //             ];
    //             $push = Firebase::sendMultipleGreeting($key, $message);
    //         }
    //     }
    //     return $data;
    // }

    public function list(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'title' => 'title',
            'body' => 'body',
        ];

        $sort_dir = ['ASC', 'DESC'];

        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "notification_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

        $column_search = array('title', 'body');

        //$user_id = JwtHelper::getSesUserId();
        //$user = UserModel::where('acl_user_id',$user_id)->select('created_on')->first();

        $schedule_detail = Notification::where([['portal', "admin"]]);

        $count = $schedule_detail->count();

        $schedule_detail->where(function ($query) use ($searchval, $column_search, $schedule_detail) {

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
            $schedule_detail->orderBy($order_by_key[$sortByKey], $sortType);
        }
        if ($offset) {
            $offset = $offset * $limit;
            $schedule_detail->offset($offset);
        }

        if ($limit) {
            $schedule_detail->limit($limit);
        }
        $schedule_detail->orderBy('msg_read', 'asc');
        $schedule_detail->orderBy('notification_id', 'desc');

        $schedule_detail = $schedule_detail->get();
        $final = [];
        if (!empty($schedule_detail)) {
            foreach ($schedule_detail as $row) {
                $ary = [];
                $ary['notification_id'] = $row->notification_id;
                $ary['random_id'] = $row->random_id;
                $ary['title'] = $row->title;
                $ary['body'] = $row->body;
                $ary['user_type'] = $row->user_type;
                $ary['page'] = $row->page;
                $ary['portal'] = $row->portal;
                $ary['data'] = json_decode($row->data);
                $ary['msg_read'] = $row->msg_read;
                $ary['created_on'] = $row->created_on;
                $ary['status'] = $row->status;
                $final[] = $ary;
            }
        }

        $count = count($final);

        if ($count > 0) {

            return response()->json([
                'keyword' => 'success',
                'message' => "Notifications listed successfully",
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => "Data not found",
                'data' => []
            ]);
        }
    }

    public function update_notification(Request $request)
    {
        if (!empty($request)) {

            $ids = $request->id;
            $ids = json_decode($ids, true);


            if (!empty($ids)) {

                $update = Notification::whereIn('notification_id', $ids)->update(array(
                    'msg_read' => 1,
                ));

                return response()->json([
                    'keyword' => 'success',
                    'message' =>  "Message read successfully",
                    'data' => []
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => "No unread message",
                    'data' => []
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => "No unread message",
                'data' => []
            ]);
        }
    }
}
