<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\Tickets;
use App\Models\OrderItems;
use App\Models\TicketInbox;


class TicketMobileController extends Controller
{
    public function ticket_list(Request $request)
    {
        try {
            Log::channel("ticketmobile")->info('** started the ticketmobile list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'tickets_id' => 'tickets.tickets_id',
                'ticket_no' => 'tickets.ticket_no',
                'order_items_id' => 'tickets.order_items_id',
                'subject' => 'tickets.subject',
                'priority' => 'tickets.priority',
                'status' => 'tickets.status',
                'created_on' => 'tickets.created_on',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "tickets_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'tickets.tickets_id', 'tickets.order_items_id', 'tickets.subject', 'tickets.priority',
                'tickets.status', 'tickets.created_on', 'tickets.updated_on'
            );
            // $ticketss = Tickets::where([
            //     ['status', '!=', '4']
            // ]);
            $cus_id = JwtHelper::getSesUserId();
            $ticketss = Tickets::where('tickets.created_by', $cus_id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'tickets.order_items_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->select('tickets.*', 'orders.order_code')
                ->where('tickets.status', '!=', '4');


            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $ticketss->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $ticketss->orderBy($order_by_key[$sortByKey], $sortType);
            }

            $count = $ticketss->count();

            if ($offset) {
                $offset = $offset * $limit;
                $ticketss->offset($offset);
            }
            if ($limit) {
                $ticketss->limit($limit);
            }
            Log::channel("tickets")->info("request value :: $limit :: $offset :: $sortByKey :: $sortType");
            $ticketss->orderBy('tickets_id', 'DESC');
            $ticketss = $ticketss->get();

            $opened = Tickets::where('status', 1)->count();
            $closed = Tickets::where('status', 3)->count();
            $reply = Tickets::where('status', 2)->count();
            $latest = Tickets::where('status', 0)->count();

            if ($count > 0) {
                $final = [];
                foreach ($ticketss as $value) {
                    $ary = [];
                    $ary['created_on'] = $value['created_on'];
                    $ary['last_updated'] = $value['last_updated'];
                    $ary['ticket_no'] = $value['ticket_no'];
                    $ary['subject'] = $value['subject'];
                    $ary['order_items_id'] = $value['order_items_id'];
                    $ary['order_code'] = $value['order_code'];

                    if ($value['priority'] == 2) {
                        $ary['priority'] = "Low";
                    }
                    if ($value['priority'] == 1) {
                        $ary['priority'] = "Medium";
                    }
                    if ($value['priority'] == 0) {
                        $ary['priority'] = "High";
                    }

                    // if ($value['status'] == 3) {
                    //     $ary['status'] = "Closed";
                    // }
                    // if ($value['status'] == 2) {
                    //     $ary['status'] = "Reply";
                    // }
                    // if ($value['status'] == 1) {
                    //     $ary['status'] = "Opened";
                    // }
                    // if ($value['status'] == 0) {
                    //     $ary['status'] = "Latest";
                    // }
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("tickets")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Mobile ticket listed successfully'),
                    'data' => $final,
                    'count' => $count,
                    'opened' => $opened,
                    'closed' => $closed,
                    'reply' => $reply,
                    'latest' => $latest
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                    'count' => $count,
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("tickets")->error($exception);
            Log::channel("tickets")->error('** end the tickets list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function ticket_view(Request $request, $id)
    {
        try {

            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';

            Log::channel("ticket")->info('** started the ticket view method **');
            Log::channel("ticket")->info("request value ticket_id:: $id");

            $ticket_view = Tickets::where('tickets_id', $id)
                ->leftJoin('customer', 'tickets.created_by', '=', 'customer.customer_id')
                ->leftjoin('order_items', 'order_items.order_items_id', '=', 'tickets.order_items_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->select('tickets.*', 'orders.order_code', 'customer.customer_id', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'customer.email')->first();

            $final = [];
            if (!empty($ticket_view)) {
                $ary = [];
                $ary['created_on'] = $ticket_view['created_on'];
                $ary['customer_id'] = $ticket_view['customer_id'];
                $ary['customer_name'] = !empty($ticket_view['customer_last_name']) ? $ticket_view['customer_first_name'] . ' ' . $ticket_view['customer_last_name'] : $ticket_view['customer_first_name'];
                $ary['mobile_number'] = $ticket_view['mobile_no'];
                $ary['email_address'] = $ticket_view['email'];
                $ary['last_updated'] = $ticket_view['last_updated'];
                $ary['ticket_no'] = $ticket_view['ticket_no'];
                $ary['subject'] = $ticket_view['subject'];
                $ary['order_items_id'] = $ticket_view['order_items_id'];
                $ary['order_code'] = $ticket_view['order_code'];
                if ($ticket_view['priority'] == 2) {
                    $ary['priority'] = "Low";
                }
                if ($ticket_view['priority'] == 1) {
                    $ary['priority'] = "Medium";
                }
                if ($ticket_view['priority'] == 0) {
                    $ary['priority'] = "High";
                }
                $ary['status'] = $ticket_view['status'];
                $final[] = $ary;
            }


            //Ticket History
            $ticket_history = TicketInbox::where('tickets_id', $ticket_view['tickets_id'])
                ->select('ticket_inbox_id', 'tickets_id', 'messages', 'customer_id', 'acl_user_id', 'reply_on');

            $count = count($ticket_history->get());

            if ($offset) {
                $offset = $offset * $limit;
                $ticket_history->offset($offset);
            }
            if ($limit) {
                $ticket_history->limit($limit);
            }

            $ticket_history = $ticket_history->get();


            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("ticket")->info("view value :: $log");
                Log::channel("ticket")->info('** end the ticket view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Mobile tickets viewed successfully'),
                    'data' => $final,
                    'ticket_history' => $ticket_history,
                    'count' => $count
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No Data Found'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("ticket")->error($exception);
            Log::channel("ticket")->info('** end the ticket view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function ticketinboxview($id)
    {
        try {
            Log::channel("ticket")->info('** started the ticket view method **');
            Log::channel("ticket")->info("request value ticket_id:: $id");

            $ticket_inbox_view = TicketInbox::where('ticket_inbox.tickets_id', $id)
                ->leftjoin('tickets', 'tickets.tickets_id', '=', 'ticket_inbox.tickets_id')
                ->select('ticket_inbox.*')->first();


            $final = [];
            if (!empty($ticket_inbox_view)) {
                $ary = [];
                $ary['ticket_inbox_id'] = $ticket_inbox_view['ticket_inbox_id'];
                $ary['tickets_id'] = $ticket_inbox_view['tickets_id'];
                $ary['messages'] = $ticket_inbox_view['messages'];
                $ary['customer_id'] = $ticket_inbox_view['customer_id'];
                $ary['acl_user_id'] = $ticket_inbox_view['acl_user_id'];
                $ary['reply_on'] = $ticket_inbox_view['reply_on'];
                $ary['ratings'] = $ticket_inbox_view['ratings'];
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("ticket")->info("view value :: $log");
                Log::channel("ticket")->info('** end the ticket view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Mobile ticket inbox viewed successfully'),
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
            Log::channel("ticket")->error($exception);
            Log::channel("ticket")->info('** end the ticket view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function replystatus_create(Request $request)
    {
        try {

            $replystatus = new TicketInbox();

            $replystatus->tickets_id = $request->tickets_id;
            $replystatus->messages = $request->messages;
            $replystatus->customer_id = JwtHelper::getSesUserId();
            $replystatus->reply_on = Server::getDateTime();
            if (!empty($request->status)) {
                if ($request->status == 3) {
                    $closedStatus = Tickets::find($request->tickets_id);
                    $closedStatus->status = $request->status;
                    $closedStatus->save();
                }
            }


            // Log::channel("replystatus")->info("request value :: $replystatus->replystatus");

            if ($replystatus->save()) {


                $replystatus = TicketInbox::where('ticket_inbox_id', $replystatus->ticket_inbox_id)->first();

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Reply sent successfully'),

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Reply send failed'),
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



    public function ticket_status(Request $request)
    {
        // try {

        $ids = $request->id;

        if (!empty($ids)) {
            // Log::channel("ticket")->info("request value tickets_id:: $ids :: status :: $request->status");

            // $ticket = Tickets::where('tickets_id', $ids)->first();
            $update = Tickets::where('tickets_id', $ids)->update(array(
                'status' => $request->status,
                'updated_on' => Server::getDateTime(),
                'updated_by' => JwtHelper::getSesUserId()
            ));
            $ticket = Tickets::where('tickets_id', $ids)->first();
            //   log activity
            if ($request->status == 0) {
                $activity_status = 'latest';
            } else if ($request->status == 1) {
                $activity_status = 'opened';
            } else if ($request->status == 2) {
                $activity_status = 'reply';
            } else if ($request->status == 3) {
                $activity_status = 'closed';


                if ($request->status == 0) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Latest ticket listed successfully'),
                        'data' => [$ticket]
                    ]);
                } else if ($request->status == 1) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Opened ticket listed successfully'),
                        'data' => [$ticket]
                    ]);
                } else if ($request->status == 2) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Reply ticket listed successfully'),
                        'data' => []
                    ]);
                } else if ($request->status == 3) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Closed ticket listed successfully'),
                        'data' => []
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            }
        }
    }
}
