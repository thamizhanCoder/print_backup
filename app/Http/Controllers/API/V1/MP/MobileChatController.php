<?php

namespace App\Http\Controllers\API\V1\MP;

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
use App\Models\ChatUser;
use App\Models\Communication;
use App\Models\CommunicationInbox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use File;
use Illuminate\Http\File as HttpFile;

class MobileChatController extends Controller
{
    //Employee Chat list
    public function employee_chat_list(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        // $order_id = ($request->order_id) ? $request->order_id : '';
        $order_item_id = ($request->order_item_id) ? $request->order_item_id : '';
        // $chat_user_id = ($request->chat_user_id) ? $request->chat_user_id : '';
        $cus_id = JwtHelper::getSesUserId();
        $getchat_user_id = ChatUser::where('table_unique_id',$cus_id)->where('user_type','=','customer')->select('chat_user_id')->first();
        if(!empty($order_item_id))
        {
            $get_chatlist = "SELECT DISTINCT id,fromUserId,u.online,order_items_id,toUserId,customer_name,user_type,profile_image FROM 
            (SELECT c.*, CASE fromUserId WHEN $getchat_user_id->chat_user_id THEN toUserId ELSE fromUserId END AS involved FROM messages c WHERE  
            c.fromUserId = $getchat_user_id->chat_user_id OR c.toUserId = $getchat_user_id->chat_user_id)  s JOIN chat_user u ON s.involved = u.chat_user_id WHERE  order_items_id = $order_item_id GROUP BY chat_user_id 
            ORDER BY id DESC";
        }
       
        else
        {
            $get_chatlist = "SELECT DISTINCT id,fromUserId,u.online,order_items_id,toUserId,customer_name,user_type,profile_image FROM 
        (SELECT c.*, CASE fromUserId WHEN $getchat_user_id->chat_user_id THEN toUserId ELSE fromUserId END AS involved FROM messages c WHERE 
        c.fromUserId = $getchat_user_id->chat_user_id OR c.toUserId = $getchat_user_id->chat_user_id)  s JOIN chat_user u ON s.involved = u.chat_user_id GROUP BY chat_user_id 
        ORDER BY id DESC";
        }
        

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
                $product_code = Messages::where('order_items.order_items_id',$value['order_items_id'])->leftJoin('order_items','messages.order_items_id','=','order_items.order_items_id')->select('order_items.product_code')->first();
                $ary['product_code'] = $product_code->product_code;
                $order_code = Messages::where('order_items.order_items_id',$value['order_items_id'])->leftJoin('order_items','messages.order_items_id','=','order_items.order_items_id')->leftJoin('orders','order_items.order_id','=','orders.order_id')->select('orders.order_code')->first();
                $ary['order_code'] = $order_code->order_code;
                $ary['delivered_at'] = $last_created_message->delivered_at;
                $ary['soft_delete'] = $last_created_message->soft_delete;
                $ary['created_at'] = $last_created_message->created_at;
                $ary['status'] = $last_created_message->status;
                $ary['message'] = $last_created_message->message;
                $ary['profile_image'] = $value['profile_image'];
                $ary['online'] = $value['online'];
                $ary['profile_image_url'] = ($ary['profile_image'] != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $ary['profile_image'] : env('APP_URL') . "avatar.jpg";
                $ary['date'] = date('d-m-Y', strtotime($last_created_message->created_at));
                $ary['time'] = date('h:i a', strtotime($last_created_message->created_at));
                $unread_count  = $this->getUnreadCount($value['fromUserId'],$value['order_items_id']);
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

    public function getUnreadCount($fromId,$ord_id)
    {
        $get_unread = Messages::where('isread', '!=', 2)->where('messages.order_items_id', $ord_id)->where('messages.fromUserId', $fromId)->select('messages.isread')->get();
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
            $cus_id = JwtHelper::getSesUserId();
            $getchat_user_id = ChatUser::where('table_unique_id',$cus_id)->where('user_type','=','customer')->select('chat_user_id')->first();
            $chat_user_id = $getchat_user_id->chat_user_id;
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $get_chat = Messages::leftjoin('chat_user', 'chat_user.chat_user_id', '=', 'messages.toUserId')
                ->where('messages.order_items_id', '=', $order_item_id)
                ->whereIn('messages.fromUserId', [$toUserId, $chat_user_id])->whereIn('messages.toUserId', [$chat_user_id, $toUserId])
                ->select('messages.*', 'chat_user.online', 'chat_user.customer_name', 'chat_user.profile_image', 'chat_user.user_type');
            $count = count($get_chat->get());
            if ($offset) {
                $offset = $limit * $offset;
                $get_chat->offset($offset)->orderBy('messages.id','desc');
            }
            if ($limit) {
                $get_chat->limit($limit)->orderBy('messages.id','desc');
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
                    $product_code = Messages::where('order_items.order_items_id',$value['order_items_id'])->leftJoin('order_items','messages.order_items_id','=','order_items.order_items_id')->select('order_items.product_code')->first();
                    $ary['product_code'] = $product_code->product_code;
                    $order_code = Messages::where('order_items.order_items_id',$value['order_items_id'])->leftJoin('order_items','messages.order_items_id','=','order_items.order_items_id')->leftJoin('orders','order_items.order_id','=','orders.order_id')->select('orders.order_code')->first();
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
    public function downloadSingle_file(Request $request)
    {
        $attachment = $request->attachment;
        $module = $request->module;

        if (!empty($attachment)) {

            $fileName = $attachment;

            $myFile = base_path('public/public' . '/' . $module . '/') . $attachment;
            $headers = [
                'Content-Description: File Transfer',
                'Content-Type: application/octet-stream',
                'Content-Disposition: attachment; filename="' . basename($myFile) . '"',
                'Expires: 0',
                'Cache-Control: must-revalidate',
                'Pragma: public',
                'Content-Length: ' . filesize($myFile)
            ];

            $newName = $fileName;
            return response()->download($myFile, $newName, $headers);
        } else {
            return response()->json([
                'keyword'      => 'failed',
                'message'      => __('message.failed'),
                'data'        => [],

            ]);
        }
    }
}