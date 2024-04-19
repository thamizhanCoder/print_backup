<?php

namespace App\Http\Controllers\API\V1\EP;

use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Controller\EmployeeController;
use App\Models\Customer;
use App\Models\Messages;
use App\Models\UserModel;
use Illuminate\Http\Request;
use App\Models\ReportFriend;
use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Http\Requests\EmployeeChatRequest;
use App\Models\Chatuser;
use App\Models\Communication;
use App\Models\CommunicationInbox;
use App\Models\OrderItems;
use App\Models\TaskManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use File;
use Illuminate\Http\File as HttpFile;
use Illuminate\Support\Facades\File as FacadesFile;

class EmployeeChatController extends Controller
{
    //Employee Chat list
    public function employee_chat_list(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        // $order_id = ($request->order_id) ? $request->order_id : '';
        $order_item_id = ($request->order_item_id) ? $request->order_item_id : '';
        $chat_user_id = ($request->chat_user_id) ? $request->chat_user_id : '';

        $get_chatlist = "SELECT DISTINCT id,fromUserId,u.online,order_items_id,toUserId,customer_name,user_type,profile_image FROM 
        (SELECT c.*, CASE fromUserId WHEN $chat_user_id THEN toUserId ELSE fromUserId END AS involved FROM messages c WHERE 
        c.fromUserId = $chat_user_id OR c.toUserId = $chat_user_id)  s JOIN chat_user u ON s.involved = u.chat_user_id WHERE  order_items_id = $order_item_id GROUP BY chat_user_id 
        ORDER BY id DESC";

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
                $ary['customer_name'] = $value['customer_name'];
                $ary['user_type'] = $value['user_type'];
                $last_created_message = $this->lastMessageData($value['fromUserId'], $value['toUserId']);
                $ary['isread'] = $last_created_message->isread;
                $ary['read_at'] = $last_created_message->read_at;
                $ary['order_items_id'] = $value['order_items_id'];
                $product_code = Messages::where('order_items.order_items_id', $value['order_items_id'])->leftJoin('order_items', 'messages.order_items_id', '=', 'order_items.order_items_id')->select('order_items.product_code')->first();
                $ary['product_code'] = $product_code->product_code;
                $order_code = Messages::where('order_items.order_items_id', $value['order_items_id'])->leftJoin('order_items', 'messages.order_items_id', '=', 'order_items.order_items_id')->leftJoin('orders', 'order_items.order_id', '=', 'orders.order_id')->select('orders.order_code')->first();
                $ary['order_code'] = $order_code->order_code;
                $ary['delivered_at'] = $last_created_message->delivered_at;
                $ary['soft_delete'] = $last_created_message->soft_delete;
                $ary['created_at'] = $last_created_message->created_at;
                $ary['status'] = $last_created_message->status;
                $ary['message'] = $last_created_message->message;
                $ary['profile_image'] = $value['profile_image'];
                $ary['online'] = $value['online'];
                // $ary['UserIds'] = Messages::where('messages.order_items_id', $value['order_items_id'])->where('messages.fromUserId', $value['fromUserId'])->select('messages.toUserId')->groupBy('messages.fromUserId','messages.toUserId')->get();
                $user_id = OrderItems::where('order_items.order_items_id', $value['order_items_id'])->where('user_type', '=', 'customer')->leftJoin('chat_user', 'order_items.created_by', '=', 'chat_user.table_unique_id')->select('chat_user.chat_user_id')->first();
                $ary['user_id'] = $user_id->chat_user_id;
                $ary['profile_image_url'] = ($ary['profile_image'] != '') ? env('APP_URL') . env('PROFILE_URL') . $ary['profile_image'] : env('APP_URL') . "avatar.jpg";
                $ary['date'] = date('d-m-Y', strtotime($last_created_message->created_at));
                $ary['time'] = date('h:i a', strtotime($last_created_message->created_at));
                $unread_count  = $this->getUnreadCount($value['fromUserId']);
                if (!empty($unread_count)) {
                    $ary['unread_count'] = count($unread_count);
                } else {
                    $ary['unread_count'] = 0;
                }
                $final[] = $ary;
            }
        }

        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Chat listed successfully'),
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

    public function getfromtoUserId($orderItemId)
    {
        $get_orderItemId = OrderItems::where('order_items.order_items_id', $orderItemId)->where('user_type', '=', 'customer')->leftJoin('chat_user', 'order_items.created_by', '=', 'chat_user.table_unique_id')->leftJoin('customer', 'customer.customer_id', '=', 'chat_user.table_unique_id')->leftJoin('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->select('orders.order_code', 'chat_user.chat_user_id', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.profile_image', 'order_items.product_code');
        $get_orderItemId = $get_orderItemId->get();
        $final = [];
        foreach ($get_orderItemId as $value) {
            $ary = [];
            $ary['chat_user_id'] = $value['chat_user_id'];
            $ary['customer_first_name'] = $value['customer_first_name'] . " " . $value['customer_last_name'];
            $ary['profile_image'] = $value['profile_image'];
            $ary['profile_image_url'] = ($ary['profile_image'] != '') ? env('APP_URL') . env('PROFILE_URL') . $ary['profile_image'] : env('APP_URL') . "avatar.jpg";
            $ary['product_code'] = $value['product_code'];
            $ary['order_code'] = $value['order_code'];
            $final[] = $ary;
        }
        return $final;
    }

    public function getUnreadCount($fromId)
    {
        $get_unread = Messages::where('messages.fromUserId', $fromId)->where('isread', '!=', 2)->select('messages.isread')->get();
        return $get_unread;
    }

    public function lastMessageData($fromUserId, $toUserId)
    {

        return Messages::where('messages.fromUserId', $fromUserId)
            ->where('messages.toUserId', $toUserId)->orderBy('created_at', 'desc')->limit(1)->first();
    }

    //Chat conversation
    public function chatConversation(Request $request)
    {
        try {
            $toUserId = ($request->toUserId) ? $request->toUserId : '';
            // $order_id = ($request->order_id) ? $request->order_id : '';
            $order_item_id = ($request->order_item_id) ? $request->order_item_id : '';
            $chat_user_id = ($request->chat_user_id) ? $request->chat_user_id : '';
            // $toUserId = JwtHelper::getSesUserId();
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $get_chat = Messages::leftjoin('chat_user', 'chat_user.chat_user_id', '=', 'messages.toUserId')
                ->where('messages.order_items_id', '=', $order_item_id)
                ->whereIn('messages.fromUserId', [$toUserId, $chat_user_id])->whereIn('messages.toUserId', [$chat_user_id, $toUserId])
                ->select('messages.*', 'chat_user.online', 'chat_user.customer_name', 'chat_user.profile_image', 'chat_user.user_type');
            $count = count($get_chat->get());
            if ($offset) {
                $offset = $limit * $offset;
                $get_chat->offset($offset)->orderBy('messages.id', 'desc');
            }
            if ($limit) {
                $get_chat->limit($limit)->orderBy('messages.id', 'desc');
            }
            $get_chat->orderBy('messages.id', 'asc');
            $get_chat = $get_chat->get();
            if ($count > 0) {
                $final = [];
                foreach ($get_chat as $value) {
                    $ary = [];
                    $ary['id'] = $value['id'];
                    $ary['fromUserId'] = $value['fromUserId'];
                    $ary['toUserId'] = $value['toUserId'];
                    $ary['isread'] = $value['isread'];
                    $ary['read_at'] = $value['read_at'];
                    $ary['delivered_at'] = $value['delivered_at'];
                    $ary['created_at'] = $value['created_at'];
                    $ary['time'] = date('h:i a', strtotime($ary['created_at']));
                    $ary['status'] = $value['status'];
                    $ary['order_items_id'] = $value['order_items_id'];

                    if ($value['fromUserId'] == $chat_user_id) {
                        $update = Messages::where('fromUserId', $value['toUserId'])->where('toUserId', $chat_user_id)->where('order_items_id', $value['order_items_id'])->update(array(
                            'isread' => 2
                        ));
                    }

                    if ($value['fromUserId'] != $chat_user_id) {

                        $update = Messages::where('fromUserId', $value['fromUserId'])->where('toUserId', $chat_user_id)->where('order_items_id', $value['order_items_id'])->update(array(
                            'isread' => 2
                        ));
                    }
                    $product_code = Messages::where('order_items.order_items_id', $value['order_items_id'])->leftJoin('order_items', 'messages.order_items_id', '=', 'order_items.order_items_id')->select('order_items.product_code')->first();
                    $ary['product_code'] = $product_code->product_code;
                    $order_code = Messages::where('order_items.order_items_id', $value['order_items_id'])->leftJoin('order_items', 'messages.order_items_id', '=', 'order_items.order_items_id')->leftJoin('orders', 'order_items.order_id', '=', 'orders.order_id')->select('orders.order_code')->first();
                    $ary['order_code'] = $order_code->order_code;
                    $ary['message'] = $value['message'];
                    $ary['type'] = $value['type'];
                    $ary['file'] = $value['file'];
                    $ary['path'] = env('APP_URL') . env('CHAT_URL');
                    $ary['size'] = $value['size'];
                    $ary['online'] = $value['online'];
                    $ary['customer_name'] = $value['customer_name'];
                    $ary['profile_image'] = $value['profile_image'];
                    $ary['profile_image_url'] = ($ary['profile_image'] != '') ? env('APP_URL') . env('PROFILE_URL') . $ary['profile_image'] : env('APP_URL') . "avatar.jpg";
                    //  $last_comment = Messages::leftjoin('customer', 'customer.customer_id', '=', 'messages.toUserId')
                    //      ->whereIn('messages.fromUserId', [$fromUserId, $toUserId])->whereIn('messages.toUserId', [$toUserId, $fromUserId])
                    //      ->select('messages.*', 'customer.customer_id', 'customer.customer_name', 'customer.profile_image')->orderBy('messages.id', 'desc')->first();
                    //  $ary['last_comments'] = $last_comment->created_at;
                    $final[] = $ary;
                }
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Chat conversion listed successfully'),
                    'data' => !empty($final) ? $final : [],
                    //  'to_customer_details' => $to_customer,
                    'count' => $count
                ]);
            }
            //tocustomer
            //  if (!empty($get_chat_to_cus)) {
            //      $ary = [];
            //      $ary['online'] = "Online";
            //      $ary['customer_id'] = $get_chat_to_cus['customer_id'];
            //      $ary['last_active_at'] = "";
            //      $ary['customer_name'] = $get_chat_to_cus['customer_name'];
            //      $ary['profile_image'] = $get_chat_to_cus['profile_image'];
            //      $ary['profile_image_url'] = ($ary['profile_image'] != '') ? env('APP_URL') . env('PROFILE_URL') . $ary['profile_image'] : env('APP_URL') . "avatar.jpg";
            //      $to_customer = $ary;
            //  }
            // if (!empty($get_chat)) {
            // }
            else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////

    /////Worked By Rajesh kannan.N 02/02/2023.

    //////If Employee Starts Conversation with Admin 

    //     Employee Login
    // 1. Initial Create Call

    public function empchatwithadminbox_create(EmployeeChatRequest $request)

    {
        try {
            $taskManager = TaskManager::where('task_manager_id', $request->task_manager_id)->where('order_items_id', '!=', '')->first();

            $chatwithadmin = new Communication();
            $chatwithadmin->task_manager_id = $request->task_manager_id;
            $chatwithadmin->orderitem_stage_id = !empty($taskManager->orderitem_stage_id) ? $taskManager->orderitem_stage_id : null;
            $chatwithadmin->subject = $request->subject;
            $chatwithadmin->created_on = Server::getDateTime();
            $chatwithadmin->created_by = JwtHelper::getSesEmployeeId();
            if ($chatwithadmin->save()) {

                $chatwithadminDetails = Communication::where('communication_id', $chatwithadmin->communication_id)->first();
                $commun = new CommunicationInbox();
                $commun->communication_id = $chatwithadminDetails->communication_id;
                $commun->messages = $request->messages;
                $commun->attachments = $request->attachments;
                $commun->folder = $request->folder;
                $commun->employee_id = JwtHelper::getSesEmployeeId();
                $commun->save();

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Employee chat created successfully'),
                    'data'        => [$chatwithadminDetails]
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Employee chat creation failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("chatwithadmininbox")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //Employee Login
    //2. Reply Call

    public function employeereply_create(Request $request)
    {
        try {
            $replystatus = new CommunicationInbox();
            $replystatus->communication_id = $request->communication_id;
            $replystatus->messages = $request->messages;
            $replystatus->attachments = $request->attachments;
            $replystatus->folder = $request->folder;
            $replystatus->employee_id = JwtHelper::getSesEmployeeId();
            $replystatus->reply_on = Server::getDateTime();

            if ($replystatus->save()) {
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Employee replied successfully'),
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Employee reply failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("replystatus")->error($exception);
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    ///////////////    IF Employee start conversation with Admin.......
    ////Employee Login

    //
    ///List of Conversation History......






    public function conversationhistory_view(Request $request)
    {
        try {
            $id = $request->id;
            $type = $request->type;
            Log::channel("communication")->info('** started the communication view method **');
            Log::channel("communication")->info("request value communication_id:: $id");
            if ($type == "order") {
                $converslist = Communication::where('communication.orderitem_stage_id', $id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'communication.orderitem_stage_id')->where('orderitem_stage.status', 1)->select('communication.*')->first();
            } else if ($type == "customtask") {
                $converslist = Communication::where('communication.task_manager_id', $id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'communication.task_manager_id')->where('task_manager.qc_status', '!=', 1)->first();
            }

            $final = [];
            if (!empty($converslist)) {
                $ary = [];
                $ary['communication_id'] = $converslist->communication_id;
                $ary['subject'] = $converslist->subject;
                $ary['history'] = $this->getCommunicationHistory($converslist->communication_id);
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
                    'message' => __('No data found'),
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

    public function getCommunicationHistory($communicationId)
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


    ///Admin Login
    // 1.List

    public function admincommunication_list(Request $request)
    {
        try {
            Log::channel("admincommunication")->info('** started the admincommunication list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';


            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'

                'subject' => 'communication.subject',
                'messages' => 'communication_inbox.messages',
                'attachments' => 'communication_inbox.attachments',
                'status' => 'communication.status',

            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "task_manager_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'communication.subject', 'communication_inbox.messages', 'communication_inbox.attachments', 'communication.status'
            );

            $admincommunications = Communication::leftjoin('communication_inbox', 'communication_inbox.communication_id', '=', 'communication.communication_id')
                ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'communication.task_manager_id')
                ->select('communication.*', 'communication_inbox.messages', 'communication_inbox.attachments', 'communication_inbox.employee_id', 'communication_inbox.acl_user_id', 'communication_inbox.reply_on', 'communication_inbox.ratings')
                ->whereIn('communication.status', [0, 1]);


            $admincommunications->where(function ($query) use ($searchval, $column_search, $admincommunications) {
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
                $admincommunications->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $admincommunications->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $admincommunications->where(function ($query) use ($from_date) {
                    $query->whereDate('communication.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $admincommunications->where(function ($query) use ($to_date) {
                    $query->whereDate('communication.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
                $filterByStatus = json_decode($filterByStatus, true);
                $admincommunications->whereIn('communication.status', $filterByStatus);
            }
            // if (!empty($filterByStatus)) {
            //     $filterByStatus = json_decode($filterByStatus, true);
            //     $admincommunications->where('admincommunication.status', $filterByStatus);
            // }
            // if($filterByStatus == 0){
            //     $admincommunications->where('admincommunication.status', $filterByStatus);
            // }



            $count = $admincommunications->count();

            if ($offset) {
                $offset = $offset * $limit;
                $admincommunications->offset($offset);
            }
            if ($limit) {
                $admincommunications->limit($limit);
            }
            Log::channel("admincommunication")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $admincommunications->orderBy('communication_id', 'DESC');
            $admincommunications = $admincommunications->get();

            $inprogress = Communication::where('status', 0)->count();
            $completed = Communication::where('status', 1)->count();

            if ($count > 0) {
                $final = [];
                foreach ($admincommunications as $value) {
                    $ary = [];

                    $ary['subject'] = $value['subject'];
                    $ary['messages'] = $value['messages'];
                    // $ary['attachments'] = $value['attachments'];

                    $gTImage = json_decode($value['attachments'], true);
                    $ary['attachments'] = $this->getdefaultImages_allImages($gTImage);

                    $ary['employee_id'] = $value['employee_id'];

                    if ($value['status'] == 1) {
                        $ary['status'] = "completed";
                    }
                    if ($value['status'] == 0) {
                        $ary['status'] = "inprogress";
                    }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("admincommunications")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Administrator chat listed successfully'),
                    'data' => $final,
                    'count' => $count,
                    'completed' => $completed,
                    'inprogress' => $inprogress,

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
            Log::channel("admincommunications")->error($exception);
            Log::channel("admincommunications")->error('** end the admincommunications list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }



    //Admin  Login
    //2. Reply status Call

    public function adminreply_create(Request $request)
    {
        try {
            $replystatus = new CommunicationInbox();
            $replystatus->communication_id = $request->communication_id;
            $replystatus->messages = $request->messages;
            $replystatus->attachments = $request->attachments;
            // $replystatus->employee_id = $request->employee_id;
            $replystatus->employee_id = JwtHelper::getSesEmployeeId();
            $replystatus->acl_user_id = JwtHelper::getSesEmployeeId();
            $replystatus->reply_on = Server::getDateTime();
            // $replystatus->ratings = $request->ratings;



            if (!empty($request->status)) {
                $closedStatus = CommunicationInbox::find($request->communication_inbox_id);
                $closedStatus->status = $request->status;
                $closedStatus->save();
            }
            if ($replystatus->save()) {
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Administrator replied successfully'),
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Administrator reply failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("replystatus")->error($exception);
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function downloadZip(Request $request)
    {
        $zip = new ZipArchive;
        $folder = $request->folder;
        $fileName = $folder . '.zip';
        if ($zip->open(public_path($fileName), ZipArchive::CREATE) === TRUE) {
            $files = FacadesFile::files(public_path('public/management/' . $folder));
            foreach ($files as $key => $value) {
                $relativeNameInZipFile = basename($value);
                $zip->addFile($value, $relativeNameInZipFile);
            }
            $zip->close();
        }
        return response()->download(public_path($fileName));
    }
}
