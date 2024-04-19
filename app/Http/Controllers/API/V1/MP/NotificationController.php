<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Helpers\JwtHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function list(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';

        $customer_id = JwtHelper::getSesUserId();
        $cus = Customer::where('customer_id', $customer_id)->select('created_on')->first();

        $notification = Notification::where('created_on', '>=', $cus->created_on)
            ->where('portal', "mobile")
            ->where('receiver', $customer_id);


        if ($offset) {
            $offset = $offset * $limit;
            $notification->offset($offset);
        }

        if ($limit) {
            $notification->limit($limit);
        }
        $notification->orderBy('notification_id', 'desc');

        $notification = $notification->get();
        $count = $notification->count();
        $final = [];
        if (!empty($notification)) {
            foreach ($notification as $row) {
                $ary = [];
                $ary['notification_id'] = $row->notification_id;
                $ary['title'] = $row->title;
                $ary['body'] = $row->body;
                $ary['user_type'] = $row->user_type;
                $ary['page'] = $row->page;
                $ary['portal'] = $row->portal;
                $ary['data'] = json_decode($row->data);
                $ary['msg_read'] = $row->msg_read;
                $ary['created_on'] = $row->created_on;
                $ary['random_id'] = $row->random_id;
                $final[] = $ary;
            }
        }

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
                'message' => "There is no data listed",
            ]);
        }
    }

    public function update_notification(Request $request)
    {
        if (!empty($request)) {

            $user_id = JwtHelper::getSesUserId();
            if ($request->notification_id) {

                $notification_id = $request->notification_id;
            }
            if ($request->random_id) {
                
                $notification_id = $request->random_id;
            }
            $type = $request->type;


            if ($request->notification_id) {
                if ($type == "single") {

                    $update = Notification::where('notification_id', $notification_id)->update(array(
                        'msg_read' => 1,
                    ));
                }
            }
            if ($request->random_id) {
                if ($type == "single") {

                    $update = Notification::where('random_id', $notification_id)->update(array(
                        'msg_read' => 1,
                    ));
                }
            }

            if ($type == "all") {

                $update = Notification::where('receiver', $user_id)->where('portal', "mobile")->update(array(
                    'msg_read' => 1,
                ));
            }

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
