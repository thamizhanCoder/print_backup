<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\ExpectedDays;
use App\Models\Tickets;
use App\Models\TicketInbox;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\PhotoFrameUploadModel;
use App\Models\ProductVariant;
use App\Models\SelfieUploadModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\myorderRequest;
class MyOrderComplaintController extends Controller
{
    public function myordercomplaint_create(myorderRequest $request)
    {

        try {
        Log::channel("myordercomplaint")->info("** started the myordercomplaint create method **");          

        $myordercomplaint = new Tickets();
        $myordercomplaint->subject = $request->subject;
        $myordercomplaint->priority = $request->priority;
        $myordercomplaint->order_items_id = $request->order_items_id;
        $myordercomplaint->created_on = Server::getDateTime();
        $myordercomplaint->created_by = JwtHelper::getSesUserId();

        if ($myordercomplaint->save()) {


            $ticket_no = env('TICKETPREFIX') . str_pad($myordercomplaint->tickets_id, 3, '0', STR_PAD_LEFT);
            $update_ticketdetails = Tickets::find($myordercomplaint->tickets_id);
            $update_ticketdetails->ticket_no = $ticket_no;
            $update_ticketdetails->save();

            $ticket_message = new TicketInbox();
            $ticket_message->tickets_id = $myordercomplaint->tickets_id;
            $ticket_message->messages = $request->message;
            $ticket_message->customer_id = JwtHelper::getSesUserId();
            $ticket_message->reply_on = Server::getDateTime();
            $ticket_message->save();

            $myordercomplaints = Tickets::where('tickets_id', $myordercomplaint->tickets_id)->first();


            Log::channel("myordercomplaint")->info("** myordercomplaint save details : $myordercomplaints **");


            // log activity
            // $desc = 'myordercomplaint ' . '(' . $myordercomplaint->myordercomplaint_name . ')' . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
            // $activitytype = Config('activitytype.myordercomplaint');
            // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

            Log::channel("myordercomplaint")->info("** end the myordercomplaint create method **");

            return response()->json([
                'keyword' => 'success',
                'message' => __('My order complaint created successfully'),
                'data' => [$myordercomplaints],

            ]);
        } else {
            return response()->json([
                'keyword' => 'failure',
                'message' => __('My order complaint created failed'),
                'data' => [],
            ]);
        }    

        } catch (\Exception $exception) {
            Log::channel("myordercomplaint")->error($exception);
            Log::channel("myordercomplaint")->error('** end the myordercomplaint create method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
    }
}}