<?php

namespace App\Http\Controllers\API\V1\AP;

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
use App\Models\OrderItems;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use File;
use Illuminate\Http\File as HttpFile;

class AdminChatViewController extends Controller
{

    public function chatConversation(Request $request)
    {
        try {
            $toUserId = ($request->toUserId) ? $request->toUserId : '';
            $order_item_id = ($request->order_item_id) ? $request->order_item_id : '';
            // $chat_user_id = ($request->chat_user_id) ? $request->chat_user_id : '';
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $get_chat = Messages::leftjoin('chat_user', 'chat_user.chat_user_id', '=', 'messages.toUserId')
                ->where('messages.order_items_id', '=', $order_item_id)
                ->select('messages.*','chat_user.user_type','chat_user.online', 'chat_user.customer_name', 'chat_user.profile_image', 'chat_user.user_type');
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
                    $ary['user_type'] = $value['user_type'];
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
                    $final[] = $ary;
                }
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Employee & Customer chat conversation listed successfully'),
                    'data' => !empty($final) ? $final : [],
                    //  'to_customer_details' => $to_customer,
                    'count' => $count
                ]);
            }
 
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

    public function getfromtoUserId($orderItemId,$employee_id)
    {
        // $emp_id = JwtHelper::getSesEmployeeId();
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
            // $ary['employee_chat_user_id'] = $this->getemployeeId($orderItemId);
            $employee_details = $this->getemployeeId($employee_id);
            $ary['employee_id'] = !empty($employee_details->employee_id) ? $employee_details->employee_id : null;
            $ary['employee_chat_user_id'] = !empty($employee_details->chat_user_id) ? $employee_details->chat_user_id : null;
            $ary['employee_name'] = !empty($employee_details->employee_name) ? $employee_details->employee_name : null;
            $employee_image = !empty($employee_details->employee_image) ? $employee_details->employee_image : null;
            $ary['employee_image_url'] = ($employee_image != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $employee_image : env('APP_URL') . "avatar.jpg";
            $final[] = $ary;
        }
        return $final;
    }

    public function getemployeeId($employee_id)
    {
        
        // $employee_id = Messages::where('messages.order_items_id',$orderItemId)->where('user_type','=','employee')->leftJoin('chat_user','messages.fromUserId','=','chat_user_id')->leftJoin('employee','employee.employee_id','=','chat_user.table_unique_id')
        // ->select('chat_user.chat_user_id','employee.employee_name','employee.employee_image')->orderBy('messages.created_at','desc')->first();
        // return $employee_id;
        $employee_details = ChatUser::where('chat_user.table_unique_id',$employee_id)->where('user_type','=','employee')->leftJoin('employee','employee.employee_id','=','chat_user.table_unique_id')->select('chat_user.chat_user_id','employee.employee_name','employee.employee_image','employee.employee_id')->first();
        return $employee_details;
    }
}
