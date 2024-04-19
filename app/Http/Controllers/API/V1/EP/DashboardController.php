<?php

namespace App\Http\Controllers\API\V1\EP;

use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Models\ChatUser;
use App\Models\Communication;
use App\Models\CommunicationInbox;
use App\Models\EmployeeTaskHistory;
use App\Models\Messages;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\TaskManager;
use App\Models\TaskManagerHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function chat_conversation_list(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $userid = JwtHelper::getSesEmployeeId();

        $my_chat = ChatUser::where('table_unique_id', $userid)->where('user_type', '=', 'employee')
            ->select('chat_user_id')
            ->first();

        $get_chatlist = "SELECT DISTINCT `messages`.*, `chat_user`.`customer_name`, `u2`.`customer_name` as `to_username`,`u2`.`profile_image`,`u2`.`user_type`,
        CASE WHEN toUserId = $my_chat->chat_user_id
           THEN `chat_user`.`customer_name`
           ELSE u2.customer_name
        END as participant
        FROM `messages` 
        LEFT JOIN `chat_user` ON `messages`.`fromUserId`=`chat_user`.`chat_user_id` 
        LEFT JOIN `chat_user` as `u2` ON `messages`.`toUserId`=`u2`.`chat_user_id` 
        INNER JOIN (SELECT max(id) as lastmsgId FROM messages where toUserId = $my_chat->chat_user_id or fromUserId = $my_chat->chat_user_id GROUP BY toUserId) m2 ON `messages`.`id`=`m2`.`lastmsgId` 
        GROUP BY participant,order_items_id  
        ORDER BY `messages`.`created_at` DESC";


        $total_count = DB::select(DB::raw($get_chatlist));
        $count = Count($total_count);
        if ($limit) {
            $get_chatlist .= " LIMIT $limit";
        }
        if ($offset) {
            $offset = $limit * $offset;
            $get_chatlist .= " OFFSET $offset";
        }
        $get_chatlist = DB::select(DB::raw($get_chatlist));
        $get_chatlist = json_decode(json_encode($get_chatlist), true);
        if ($count > 0) {
            $final = [];
            foreach ($get_chatlist as $value) {
                $ary = [];
                $ary['id'] = $value['id'];
                $ary['fromUserId'] = $value['fromUserId'];
                $ary['toUserId'] = $value['toUserId'];
                $ary['order_items_id'] = $value['order_items_id'];
                $ary['customer_name'] = $value['customer_name'];
                $ary['user_type'] = $value['user_type'];
                $last_created_message = $this->lastChatMessageData($value['order_items_id']);
                $order_code = OrderItems::where('order_items_id', $last_created_message->order_items_id)->select('order_id')->first();
                $order_id_get = Orders::where('order_id', $order_code->order_id)->select('order_code')->first();
                $ary['order_id'] =  $order_code->order_id;
                $ary['order_code'] = $order_id_get->order_code . '_' . sprintf("%02d", $last_created_message->order_items_id);
                $ary['isread'] = $last_created_message->isread;
                $ary['read_at'] = $last_created_message->read_at;
                $ary['delivered_at'] = $last_created_message->delivered_at;
                $ary['soft_delete'] = $last_created_message->soft_delete;
                $ary['created_at'] = $last_created_message->created_at;
                $ary['status'] =  $last_created_message->status;
                $ary['message'] = $last_created_message->message;
                $ary['profile_images'] = $value['profile_image'];
                if ($value['user_type'] == "customer") {
                    $ary['profile_image_url'] = ($value['profile_image'] != '') ? env('APP_URL') . env('PROFILE_URL') . $value['profile_image'] : env('APP_URL') . "avatar.jpg";
                } else {
                    $ary['profile_image_url'] = ($value['profile_image'] != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $value['profile_image'] : env('APP_URL') . "avatar.jpg";
                }
                $ary['date'] = date('d-m-Y', strtotime($value['created_at']));
                $ary['time'] = date('h:i a', strtotime($value['created_at']));
                $ary['my_id'] = $my_chat->chat_user_id;

                if ($value['fromUserId'] == $my_chat->chat_user_id) {
                    $unread_count = $this->getUnreadCount($value['toUserId'], $ary['my_id']);
                    if (!empty($unread_count)) {
                        $ary['unread_count'] = count($unread_count);
                    } else {
                        $ary['unread_count'] = 0;
                    }
                }
                if ($value['fromUserId'] != $my_chat->chat_user_id) {
                    $unread_count = $this->getUnreadCountForOther($value['fromUserId'], $ary['my_id']);
                    if (!empty($unread_count)) {
                        $ary['unread_count'] = count($unread_count);
                    } else {
                        $ary['unread_count'] = 0;
                    }
                }
                $taskManagerDetails = TaskManager::where('task_manager.order_items_id', $last_created_message->order_items_id)->leftjoin('task_manager_history','task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->where('production_status', 1)->select('task_manager.*','task_manager_history.task_manager_history_id')->first();
                if(!empty($taskManagerDetails)){
                $ary['taskManagerId'] = $taskManagerDetails->task_manager_history_id;
                }

                $final[] = $ary;
            }
        }

        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Chats listed successfully'),
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
            ]);
        }
    }

    public function lastChatMessageData($fromUserId)
    {

        $message = Messages::where('messages.order_items_id', $fromUserId)->orderBy('created_at', 'desc')->limit(1)->first();

        return $message;
    }


    public function getUnreadCount($toUserId, $fromId)
    {
        $get_unread = Messages::where('messages.fromUserId', $toUserId)->where('messages.toUserId', $fromId)->where('isread', '=', 0)->select('messages.id', 'messages.isread', 'messages.fromUserId')->get();

        return $get_unread;
    }

    public function getUnreadCountForOther($fromUserId, $toUserId)
    {
        $get_unread = Messages::where('messages.fromUserId', $fromUserId)->where('messages.toUserId', $toUserId)->where('isread', '=', 0)->select('messages.id', 'messages.isread', 'messages.fromUserId')->get();

        return $get_unread;
    }


    public function management_communication(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';

        $userId = JwtHelper::getSesEmployeeId();

        $my_communication_list = Communication::where('communication.created_by', $userId)
            // ->leftjoin('order_items', 'communication_inbox.communication_id','=', 'communication.communication_id', )
            ->leftJoin('communication_inbox', function ($leftJoin) {
                $leftJoin->on('communication_inbox.communication_id', '=', 'communication.communication_id')
                    ->orderby('communication_inbox.communication_inbox_id', 'DESC')
                    ->groupby('communication_inbox.communication_inbox_id');
            })
            ->select('communication_inbox.*', 'communication.task_manager_id', 'communication.orderitem_stage_id')
            ->groupBy('communication.communication_id');

        $count = count($my_communication_list->get());

        if ($offset) {
            $offset = $offset * $limit;
            $my_communication_list->offset($offset);
        }
        if ($limit) {
            $my_communication_list->limit($limit);
        }
        $my_communication_list = $my_communication_list->get();

        if ($count > 0) {
            $final = [];

            foreach ($my_communication_list as $value) {
                if ($value['communication_inbox_id'] != NULL) {
                    $ary = [];
                    $ary['communication_inbox_id'] = $value['communication_inbox_id'];
                    $ary['communication_id'] = $value['communication_id'];
                    $ary['task_manager_id'] = $value['task_manager_id'];
                    $ary['orderitem_stage_id'] = $value['orderitem_stage_id'];
                    $ary['isCheckChatHistory'] = !empty($value['orderitem_stage_id']) ? "order" : "customtask";
                    if ($value['orderitem_stage_id'] != '') {
                        $task_manager = TaskManager::where('task_manager_id', $value['task_manager_id'])->select('order_items_id')->first();
                        $order_code = OrderItems::where('order_items_id', $task_manager->order_items_id)->select('order_id')->first();
                        $order_id_get = Orders::where('order_id', $order_code->order_id)->select('order_code')->first();
                        $ary['order_code'] = $order_id_get->order_code . '_' . sprintf("%02d", $task_manager->order_items_id);
                    } else {
                        $task_manager = TaskManager::where('task_manager_id', $value['task_manager_id'])->select('task_code')->first();
                        $ary['order_code'] = $task_manager->task_code;
                    }
                    $last_created_message = $this->lastMessageData($value['communication_id']);
                    $ary['messages'] = $last_created_message->messages;
                    $ary['is_read'] = $last_created_message->is_read;
                    $ary['attachments'] = json_decode($last_created_message->attachments, true);
                    $ary['mime_type'] = $last_created_message->mime_type;
                    $ary['employee_id'] = $last_created_message->employee_id;
                    $ary['acl_user_id'] = $last_created_message->acl_user_id;
                    $ary['reply_on'] = $last_created_message->reply_on;
                    $ary['ratings'] = $last_created_message->ratings;
                    $ary['date'] = date('d-m-Y', strtotime($last_created_message->reply_on));
                    $ary['time'] = date('h:i a', strtotime($last_created_message->reply_on));

                    $orderUnReadCount = Communication::where('orderitem_stage_id', $value['orderitem_stage_id'])->first();
                    $orderUnReadCount = CommunicationInbox::where('communication_id', $orderUnReadCount->communication_id)->where('is_read', 0)->count();

                    $directUnReadCount = Communication::where('task_manager_id', $value['task_manager_id'])->first();
                    $directUnReadCount = CommunicationInbox::where('communication_id', $directUnReadCount->communication_id)->where('is_read', 0)->count();

                    $ary['un_read_count'] = !empty($value['orderitem_stage_id']) ? $orderUnReadCount : $directUnReadCount;
                    $final[] = $ary;
                }
            }
        }

        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => 'Management communication listed successfully',
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failure',
                'message' => __('No data found'),
                'data' => [],
            ]);
        }
    }

    public function conversationhistory_view_dashboard(Request $request)
    {
        try {
            $id = $request->id;
            $type = $request->type;
            Log::channel("communication")->info('** started the communication view method **');
            Log::channel("communication")->info("request value communication_id:: $id");
            if ($type == "order") {
                $converslist = Communication::where('communication.orderitem_stage_id', $id)->first();
            } else if ($type == "customtask") {
                $converslist = Communication::where('communication.task_manager_id', $id)->first();
            }

            $final = [];
            if (!empty($converslist)) {
                $ary = [];
                $ary['communication_id'] = $converslist['communication_id'];
                $ary['subject'] = $converslist['subject'];
                $ary['history'] = $this->getCommunicationHistoryview($converslist['communication_id']);
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("communication")->info("view value :: $log");
                Log::channel("communication")->info('** end the communication view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Employee conversation listed successfully'),
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
            Log::channel("communication")->error($exception);
            Log::channel("communication")->info('** end the communication view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function getCommunicationHistoryview($communicationId)
    {

        $converslisthistory = CommunicationInbox::where('communication_id', $communicationId)->leftjoin('employee', 'employee.employee_id', '=', 'communication_inbox.employee_id')
            ->select('communication_inbox_id', 'communication_id', 'messages', 'attachments', 'communication_inbox.employee_id', 'acl_user_id', 'folder', 'reply_on', 'employee.employee_name', 'employee.employee_image')->get();

        $frameArray = [];
        $resultArray = [];

        if (!empty($converslisthistory)) {

            foreach ($converslisthistory as $im) {

                $frameArray['communication_inbox_id'] = $im['communication_inbox_id'];
                $frameArray['communication_id'] = $im['communication_id'];
                $frameArray['messages'] = $im['messages'];
                $frameArray['folder'] = $im['folder'];
                $frameArray['employee_name'] = $im['employee_name'];
                $gTImage = json_decode($im['attachments'], true);
                $frameArray['attachments'] = $this->getdefaultImages_allImages($gTImage, $im['folder']);
                if (!empty($im['employee_id'])) {
                    $frameArray['profile_image'] = ($im['employee_image'] != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $im['employee_image'] : env('APP_URL') . "avatar.jpg";
                } else {
                    $frameArray['profile_image'] = env('APP_URL') . "avatar.jpg";
                }
                $frameArray['employee_id'] = $im['employee_id'];
                $frameArray['acl_user_id'] = $im['acl_user_id'];
                $frameArray['reply_on'] = $im['reply_on'];
                $update = CommunicationInbox::where('communication_id', $im['communication_id'])->update(array(
                    'is_read' => 1
                ));
                $resultArray[] = $frameArray;
            }
        }


        return $resultArray;
    }


    public function getdefaultImages_allImages($gTImage, $folder)
    {
        $imG = [];
        if (!empty($gTImage)) {
            foreach ($gTImage as $im) {
                $ary = [];
                $ary['image'] = $im['image'];
                $ary['image_url'] = ($im['image'] != '') ? env('APP_URL') . env('MANAGEMENT_URL') . $folder . '/' . $im['image'] : env('APP_URL') . "avatar.jpg";

                $imG[] = $ary;
            }
        }
        return $imG;
    }

    public function lastMessageData($comm_id)
    {

        return CommunicationInbox::where('communication_id', $comm_id)
            ->orderBy('reply_on', 'desc')->limit(1)->first();
    }

    public function current_month_task()
    {
        $userId = JwtHelper::getSesEmployeeId();

        //Total Order
        $total_order_count = EmployeeTaskHistory::where('employee_task_history.employee_id', $userId)->where('task_manager_history.production_status', 1)->whereMonth('employee_task_history.created_on', date('m'))
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->count();

        //Order Task Completed Count
        $total_order_completed_count = EmployeeTaskHistory::where('employee_task_history.employee_id', $userId)->where('task_manager_history.production_status', 1)->whereMonth('employee_task_history.created_on', date('m'))->where('employee_task_history.status', 4)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->count();

        //Order Task Inprogress Count
        $total_order_Inprogress_count = EmployeeTaskHistory::where('employee_task_history.employee_id', $userId)->where('task_manager_history.production_status', 1)->whereMonth('employee_task_history.created_on', date('m'))->whereIn('employee_task_history.status', [1, 2, 3])
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->count();

        //Total Direct Task
        $total_Dtask_count = EmployeeTaskHistory::where('employee_task_history.employee_id', $userId)->where('task_manager_history.production_status', 1)->whereMonth('employee_task_history.created_on', date('m'))
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.task_code', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->groupBy('orders.order_id')->count();

        //Direct Task Completed Count
        $total_Dtask_completed_count =  EmployeeTaskHistory::where('employee_task_history.employee_id', $userId)->where('task_manager_history.production_status', 1)->whereMonth('employee_task_history.created_on', date('m'))->where('employee_task_history.status', 4)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.task_code', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->groupBy('orders.order_id')->count();

        //Direct Task Inprogress Count
        $total_Dtask_Inprogress_count = EmployeeTaskHistory::where('employee_task_history.employee_id', $userId)->where('task_manager_history.production_status', 1)->whereMonth('employee_task_history.created_on', date('m'))->whereIn('employee_task_history.status', [1, 2, 3])
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.task_code', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->groupBy('orders.order_id')->count();

        $count = [
            //Total task count
            'total_tasks' => $total_Dtask_count + $total_order_count,
            'orders' => $total_order_count,
            'direct_task' => $total_Dtask_count,
            //Total Orders
            'total_orders_count' => $total_order_count,
            'total_order_completed_count' => $total_order_completed_count,
            'total_order_inprogress_count' => $total_order_Inprogress_count,
            //Total Direct Task
            'total_direct_task_count' => $total_Dtask_count,
            'total_direct_task_completed_count' => $total_Dtask_completed_count,
            'total_direct_task_inprogress_count' => $total_Dtask_Inprogress_count,
        ];
        return response()->json([
            'keyword' => 'success',
            'message' => __('Current month listed successfully'),
            'data' => [$count],
        ]);
    }


    public function task_analysis()
    {
        $userId = JwtHelper::getSesEmployeeId();

        //Total Task
        $year = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
        foreach ($year as $yr) {
            $dt = [
                "assigned_date" => $yr,
                "monthly_tasks" => 0
            ];
            $arry[] = $dt;
        }
        $data = [];

        $data = EmployeeTaskHistory::where('employee_task_history.employee_id', $userId)->where('task_manager_history.production_status', 1)->whereMonth('employee_task_history.created_on', date('m'))
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.task_code', '!=', '')->select(DB::raw('month(employee_task_history.created_on) as assigned_date'), DB::raw('count(task_manager.task_manager_id) as monthly_tasks'))->groupBy('orders.order_id')
            ->groupBy(DB::raw('MONTH(employee_task_history.created_on)'))->get();

        $collection = collect($data);
        $union = $collection->merge($arry);
        $data = $union->sortBy('assigned_date')->values();
        $datas = $data->unique('assigned_date')->values();

        //Total order 
        $years = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
        foreach ($years as $yrs) {
            $dts = [
                "assigned_date" => $yrs,
                "monthly_orders" => 0
            ];
            $ordarry[] = $dts;
        }
        $orderdata = [];

        $orderdata = EmployeeTaskHistory::where('employee_task_history.employee_id', $userId)->where('task_manager_history.production_status', 1)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '!=', '')->select(DB::raw('month(employee_task_history.created_on) as assigned_date'), DB::raw('count(task_manager.task_manager_id) as monthly_orders'))
            ->groupBy(DB::raw('MONTH(employee_task_history.created_on)'))
            ->get();

        $ordcollection = collect($orderdata);
        $ordunion = $ordcollection->merge($ordarry);
        $orderdata = $ordunion->sortBy('assigned_date')->values();
        $orderdata = $orderdata->unique('assigned_date')->values();
        if (!empty($data) && !empty($orderdata)) {
            return response()->json([
                'keyword' => 'success',
                'message' => 'Task analysis listed successfully',
                'month_wise_tasks' => $datas,
                'month_wise_orders' => $orderdata
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'data'        => [],
                'message'      => __('message.failed')
            ]);
        }
    }

    public function chat_message_read_update(Request $request)
    {

        if (!empty($request)) {

            $userid = JwtHelper::getSesEmployeeId();

            $my_chat = ChatUser::where('table_unique_id', $userid)->where('user_type', '=', 'employee')
                ->select('chat_user_id')
                ->first();

            $toUser_id = $request->id;


            $update = Messages::where('messages.fromUserId', $my_chat->chat_user_id)->where('messages.toUserId', $toUser_id)->update(array(
                'isread' => 2,
                'read_at' => Server::getDateTime()
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
            ]);
        }
    }

    public function communication_message_read_update(Request $request)
    {

        if (!empty($request)) {

            $comm_id = $request->id;

            $update = CommunicationInbox::where('communication_id', $comm_id)->update(array(
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
            ]);
        }
    }
}
