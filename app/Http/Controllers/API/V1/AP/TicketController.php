<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Models\Tickets;
use App\Models\OrderItems;
use App\Models\TicketInbox;
use App\Http\Requests\adminticketRequest;

class TicketController extends Controller
{
    public function ticket_list(Request $request)
    {
        try {
            Log::channel("tickets")->info('** started the tickets list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';


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

            $ticketss = Tickets::leftjoin('order_items', 'order_items.order_items_id', '=', 'tickets.order_items_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->select('tickets.*', 'orders.order_code')
                ->where('tickets.status', '!=', '4');


            $ticketss->where(function ($query) use ($searchval, $column_search, $ticketss) {
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
                $ticketss->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $ticketss->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $ticketss->where(function ($query) use ($from_date) {
                    $query->whereDate('tickets.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $ticketss->where(function ($query) use ($to_date) {
                    $query->whereDate('tickets.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
                $filterByStatus = json_decode($filterByStatus, true);
                $ticketss->whereIn('tickets.status', $filterByStatus);
            }
            // if (!empty($filterByStatus)) {
            //     $filterByStatus = json_decode($filterByStatus, true);
            //     $ticketss->where('tickets.status', $filterByStatus);
            // }
            // if($filterByStatus == 0){
            //     $ticketss->where('tickets.status', $filterByStatus);
            // }



            $count = $ticketss->count();

            if ($offset) {
                $offset = $offset * $limit;
                $ticketss->offset($offset);
            }
            if ($limit) {
                $ticketss->limit($limit);
            }
            Log::channel("tickets")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
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
                    $ary['last_updated'] = $value['updated_on'];
                    $ary['ticket_no'] = $value['ticket_no'];
                    $ary['subject'] = $value['subject'];
                    $ary['order_items_id'] = $value['order_items_id'];

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
                    'message' => __('Tickets listed successfully'),
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
                    'count' => $count
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


    public function ticket_view($id)
    {
        try {
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
                $ary['tickets_id'] = $ticket_view['tickets_id'];
                $ary['customer_id'] = $ticket_view['customer_id'];
                $ary['customer_name'] = !empty($ticket_view['customer_last_name']) ? $ticket_view['customer_first_name'] . ' ' . $ticket_view['customer_last_name'] : $ticket_view['customer_first_name'];
                $ary['mobile_number'] = $ticket_view['mobile_no'];
                $ary['email_address'] = $ticket_view['email'];
                $ary['created_on'] = $ticket_view['created_on'];
                $ary['last_updated'] = $ticket_view['last_updated'];
                $ary['ticket_no'] = $ticket_view['ticket_no'];
                $ary['subject'] = $ticket_view['subject'];
                $ary['order_items_id'] = $ticket_view['order_items_id'];
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
                $ticket_history = TicketInbox::where('tickets_id', $ticket_view['tickets_id'])
                    ->select("ticket_inbox_id", 'tickets_id', 'messages', 'reply_on', 'customer_id', 'acl_user_id')->get();
                $ary['history'] = $ticket_history;
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("ticket")->info("view value :: $log");
                Log::channel("ticket")->info('** end the ticket view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Ticket viewed successfully'),
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
                    'message' => __('Ticket inbox viewed successfully'),
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

    public function ticketinbox_create(Request $request)
    {
        try {
            Log::channel("ticketinbox")->info('** started the ticketinbox create method **');
            $exist = TicketInbox::where([
                ['tickets_id', '=', $request->tickets_id],
                // ['status', '!=', 2]
            ])->first();

            if (empty($exist)) {

                $ticketinbox = new TicketInbox();

                $ticketinbox->tickets_id = $request->tickets_id;
                $ticketinbox->messages = $request->messages;
                // $ticketinbox->customer_id = JwtHelper::getSesUserId();
                $ticketinbox->acl_user_id = JwtHelper::getSesUserId();
                $ticketinbox->reply_on = Server::getDateTime();
                $ticketinbox->ratings = $request->ratings;

                Log::channel("ticketinbox")->info("request value :: $ticketinbox->ticketinbox_name");

                if ($ticketinbox->save()) {

                    $ticketinboxs = TicketInbox::where('ticket_inbox_id', $ticketinbox->ticket_inbox_id)->first();

                    // log activity
                    $desc =  'ticketinbox ' . $ticketinbox->ticketinbox_name  . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.ticketinbox');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("ticketinbox")->info("save value :: $ticketinboxs");
                    Log::channel("ticketinbox")->info('** end the ticketinbox create method **');


                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('Ticket inbox created successfully'),
                        'data'        => [$ticketinboxs]
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Ticket inbox created failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Tickets ID already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("ticketinbox")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function replystatus_create(adminticketRequest $request)
    {
        try {

            $replystatus = new TicketInbox();

            $replystatus->tickets_id = $request->tickets_id;
            $replystatus->messages = $request->messages;
            $replystatus->acl_user_id = JwtHelper::getSesUserId();
            $replystatus->reply_on = Server::getDateTime();
            if (!empty($request->status)) {
                $closedStatus = Tickets::find($request->tickets_id);
                $closedStatus->status = $request->status;
                $closedStatus->save();
            }
            if ($replystatus->save()) {

                $ticDetails = TicketInbox::where('ticket_inbox.tickets_id', $replystatus->tickets_id)->where('ticket_inbox.customer_id', '!=', NULL)->orderby('ticket_inbox_id', 'desc')
                    ->leftjoin('tickets', 'tickets.tickets_id', '=', 'ticket_inbox.tickets_id')
                    ->leftjoin('order_items', 'order_items.order_items_id', '=', 'tickets.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                    ->leftjoin('customer', 'customer.customer_id', '=', 'ticket_inbox.customer_id')
                    ->select('tickets.ticket_no', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.customer_code', 'orders.order_code')->first();

                $desc = JwtHelper::getSesUserNameWithType() . ' ' .'Replied to the support ticket ID #' . $ticDetails->ticket_no . ' from ' . $ticDetails->customer_code . ' for the ' . $ticDetails->order_code;
                $activitytype = Config('activitytype.Ticket');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                if ($request->status == 3) {
                    $desc = JwtHelper::getSesUserNameWithType() . ' ' . 'closed the support ticket with ID #' . $ticDetails->ticket_no . ' for ' . $ticDetails->order_code;
                    $activitytype = Config('activitytype.Ticket');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                }

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Reply status successfully'),
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Reply status failed'),
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


    public function ticket_status_update(Request $request)
    {
        // try {

        $ids = $request->id;
        $ticket = Tickets::where('tickets_id', $ids)->first();
        if ($ticket->status == 0) {
                $desc =  'Ticket No ' . $ticket->ticket_no . ' is Opened by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Ticket');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
            $update = Tickets::where('tickets_id', $ids)->update(array(
                'status' => 1,
                'updated_on' => Server::getDateTime(),
                'updated_by' => JwtHelper::getSesUserId(),
            ));
            $ticket = Tickets::where('tickets_id', $ids)->first();
            return response()->json([
                'keyword' => 'success',
                'message' => 'Order dispatched successfully',
                'data' => [$ticket]
            ]);
        }

        // }
    }
}
