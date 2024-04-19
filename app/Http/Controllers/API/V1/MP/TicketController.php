<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Helpers\GlobalHelper;
use App\Models\Ticket;

class TicketController extends Controller
{
    public function create(Request $request)
    {
        try {
            Log::channel("ticket")->info('** started the ticket create method **');
            $ticket = new Ticket();

            $ticket->department_id = $request->department_id;
            $ticket->employee_id = $request->employee_id;
            $ticket->customer_id = $request->customer_id;
            $ticket->order_id = $request->order_id;
            $ticket->order_code = $request->order_code;
            $ticket->order_item_id = $request->order_item_id;
            $ticket->product_id = $request->product_id;
            $ticket->subject = $request->subject;
            $ticket->priority = $request->priority;
            $ticket->ticket_no = $request->ticket_no;
            $ticket->status = $request->status;
            $ticket->created_on = Server::getDateTime();
            $ticket->created_by = JwtHelper::getSesUserId();
            Log::channel("ticket")->info("request value :: $ticket->order_code");

            if ($ticket->save()) {
                $tickets = Ticket::where('ticket_id', $ticket->ticket_id)->first();

                // log activity
                // $desc =  'ticket ' . $ticket->ticket_name . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                // $activitytype = Config('activitytype.Ticket');
                // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                Log::channel("ticket")->info("save value :: $tickets->ticket_name");
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Ticket created successfully'),
                    'data'        => [$tickets]
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Ticket creation failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("ticket")->error($exception);
            Log::channel("ticket")->error('** end the ticket create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function update(Request $request)
    {
        try {
            Log::channel("ticket")->info('** started the ticket update method **');

            $ids = $request->ticket_id;
            $ticket = Ticket::find($ids);
            $ticket->department_id = $request->department_id;
            $ticket->employee_id = $request->employee_id;
            $ticket->customer_id = $request->customer_id;
            $ticket->order_id = $request->order_id;
            $ticket->order_code = $request->order_code;
            $ticket->order_item_id = $request->order_item_id;
            $ticket->product_id = $request->product_id;
            $ticket->subject = $request->subject;
            $ticket->priority = $request->priority;
            $ticket->ticket_no = $request->ticket_no;
            $ticket->status = $request->status;
            $ticket->updated_on = Server::getDateTime();
            $ticket->updated_by = JwtHelper::getSesUserId();
            Log::channel("ticket")->info("request value :: $ticket->orderno");

            if ($ticket->save()) {
                $tickets = Ticket::where('ticket_id', $ticket->ticket_id)->first();

                // log activity
                // $desc =   $department->order_code . ' Department ' . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                // $activitytype = Config('activitytype.department');
                // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                Log::channel("ticket")->info("save value :: $tickets->orderno");
                Log::channel("ticket")->info('** end the ticket update method **');

                return response()->json([
                    'keyword'      => 'success',
                    'data'        => [$tickets],
                    'message'      => __('Ticket updated successfully')
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'data'        => [],
                    'message'      => __('Ticket update failed')
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("ticket")->error($exception);
            Log::channel("ticket")->error('** end the ticket update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
