<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Events\SendApproved;
use App\Events\SendCodApproved;
use App\Events\SendCodDisapproved;
use App\Events\SendCodRevoke;
use App\Events\SendDisapproved;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVisitHistory;
use App\Models\Rating;
use App\Models\TicketInbox;
use App\Models\Tickets;
use App\Models\UserModel;
use App\Models\Visitors;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent as Agent;

class DashBoardController extends Controller

{
    public function recentorders_list(Request $request)
    {
        try {
            Log::channel("recentorders")->info('** started the recentorders list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                'order_code' => 'orders.order_code',
                'order_date' => 'orders.order_date',
                'customer_code' => 'customer.customer_code',
                'customer_id' => 'customer.customer_id',
                'customer_name' => 'customer.customer_first_name',
                'customer_name' => 'customer.customer_last_name',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_code";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('orders.order_code', 'orders.order_date', 'customer.customer_code', 'customer.customer_id', DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'));
            $recentorders = Orders::leftJoin('customer', function ($leftJoin) {
                $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                    ->where('orders.customer_id', '!=', NULL);
                 })
                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                    ->where('orders.customer_id', '=', NULL);
                })
                ->select('orders.*', 'customer.customer_first_name', 'customer.customer_last_name', DB::raw('DATE_FORMAT(orders.order_date, "%d-%m-%Y") as
                order_date'),'bulk_order_enquiry.contact_person_name')->groupBy('orders.order_id')
                ->whereIn('orders.payment_status', [0, 1]);
            $recentorders->where(function ($query) use ($searchval, $column_search, $recentorders) {
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
                $recentorders->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $recentorders->orderBy($order_by_key[$sortByKey], $sortType);
            }
            // $count = $recentorders->count();
            $count = count($recentorders->limit(10)->get());
            if ($offset) {
                $offset = $offset * $limit;
                $recentorders->offset($offset);
            }
            if ($limit) {
                $recentorders->limit($limit);
            }
            Log::channel("recentorders")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $recentorders->orderBy('order_code', 'DESC');
            // $recentorders = $recentorders->get();
            $recentorders = $recentorders->limit(10)->get();
            if ($count > 0) {
                $final = [];
                foreach ($recentorders as $value) {
                    $ary = [];
                    $ary['order_code'] = $value['order_code'];
                    $ary['customer_id'] = $value['customer_id'];
                    $ary['bulk_order_enquiry_id'] = $value['bulk_order_enquiry_id'];
                    if(!empty($value['customer_id'])){
                        $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    } else {
                        $ary['customer_name'] = $value['contact_person_name'];
                    }
                    $ary['is_cod'] = $value['is_cod'];
                    $ary['order_date'] = $value['order_date'];
                    $ary['payment_status'] = $value['payment_status'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("orders")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Recent Orders Listed Successfully'),
                    'data' => $final,
                    'count' => $count,
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
            Log::channel("recentorders")->error($exception);
            Log::channel("recentorders")->error('** end the recentorders list method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function recentfeedback_list(Request $request)
    {
        try {
            Log::channel("recentfeedback")->info('** started the recentfeedback list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            // $filterByReply = ($request->filterByReply) ? $request->filterByReply : '';
            $order_by_key = [
                'order_code' => 'orders.order_code',
                'order_id' => 'order_items.order_id',
                'order_date' => 'orders.order_date',
                'customer_code' => 'customer.customer_code',
                'customer_id' => 'customer.customer_id',
                'customer_name' => 'customer.customer_first_name',
                'customer_name' => 'customer.customer_last_name',
                'messages'  => 'ticket_inbox.messages',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "tickets_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('orders.order_code', 'order_items.order_id', 'orders.order_date', 'tickets.subject', 'customer.customer_code', 'customer.customer_id', DB::raw('CONCAT_WS(" ", customer.customer_first_name, customer.customer_last_name)'));
            $recent_feedback = Tickets::leftjoin('ticket_inbox', 'ticket_inbox.tickets_id', '=', 'tickets.tickets_id')
                ->leftjoin('order_items', 'order_items.order_items_id', '=', 'tickets.order_items_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftjoin('customer', 'customer.customer_id', '=', 'tickets.created_by')
                ->select(
                    'tickets.*',
                    'customer.profile_image',
                    'orders.order_id',
                    'orders.order_date',
                    DB::raw('DATE_FORMAT(orders.order_date, "%d-%m-%Y") as
            order_date'),
                    'orders.order_code'
                )->groupBy('order_items.order_id')
                ->orderBy('orders.order_id', 'desc')
                ->whereIn('tickets.status', [0, 2]);
            $recent_feedback->where(function ($query) use ($searchval, $column_search, $recent_feedback) {
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
                $recent_feedback->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $recent_feedback->orderBy($order_by_key[$sortByKey], $sortType);
            }
            // if (!empty($filterByReply)) {
            //     if ($filterByReply == "reply") {
            //         $recent_feedback->where('reply', 0);
            //     }
            //     if ($filterByReply == "replied") {
            //         $recent_feedback->where('reply', 1);
            //     }
            // }
            $count = count($recent_feedback->limit(10)->get());
            if ($offset) {
                $offset = $offset * $limit;
                $recent_feedback->offset($offset);
            }
            if ($limit) {
                $recent_feedback->limit($limit);
            }
            Log::channel("recentfeedback")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $recent_feedback->orderBy('tickets_id', 'DESC');
            $recent_feedback = $recent_feedback->limit(10)->get();
            if ($count > 0) {
                $final = [];
                foreach ($recent_feedback as $value) {
                    $ary = [];
                    $ary['order_id'] = $value['order_code'];
                    $ary['tickets_id'] = $value['tickets_id'];
                    $ary['profile_image'] = ($value['profile_image'] != '') ? env('APP_URL') . env('PROFILE_URL') . $value['profile_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['subject'] = $value['subject'];
                    //  if ($value['reply'] == 0) {
                    //     $ary['reply'] = 0;
                    //  }
                    //  if ($value['reply'] == 1) {
                    //      $ary['reply'] = 1;
                    //  }
                    $seconds_ago = (time() - strtotime($value['created_on']));
                    if ($seconds_ago >= 31536000) {
                        $date1 = intval($seconds_ago / 31536000);
                        $posted_date = ($date1 == 1) ? $date1 . " year ago" : $date1 . " years ago";
                    } elseif ($seconds_ago >= 2419200) {
                        $date2 = intval($seconds_ago / 2419200);
                        $posted_date = ($date2 == 1) ? $date2 . " month ago" : $date2 . " months ago";
                    } elseif ($seconds_ago >= 604800) {
                        $date3 = intval($seconds_ago / 604800);
                        $posted_date = ($date3 == 1) ? $date3 . " week ago" : $date3 . " weeks ago";
                    } elseif ($seconds_ago >= 86400) {
                        $date4 = intval($seconds_ago / 86400);
                        $posted_date = ($date4 == 1) ? $date4 . " day ago" : $date4 . " days ago";
                    } elseif ($seconds_ago >= 3600) {
                        $date5 = intval($seconds_ago / 3600);
                        $posted_date = ($date5 == 1) ? $date5 . " hour ago" : $date5 . " hours ago";
                    } elseif ($seconds_ago >= 60) {
                        $date6 = intval($seconds_ago / 60);
                        $posted_date = ($date6 == 1) ? $date6 . " minute ago" : $date6 . " minutes ago";
                    } else {
                        $posted_date = "Just now";
                    }
                    $ary['time'] =  $posted_date;
                    $ary['status']  = $value['status'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("orders")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Recent Feed Backs Listed Successfully'),
                    'data' => $final,
                    'count' => $count,
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                    // 'count' => $count
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("recentfeedback")->error($exception);
            Log::channel("recentfeedback")->error('** end the recentfeedbacks list method **');
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
            $replystatus->acl_user_id = JwtHelper::getSesUserId();
            $replystatus->reply_on = Server::getDateTime();
            if (!empty($request->status)) {
                $closedStatus = Tickets::find($request->tickets_id);
                $closedStatus->status = $request->status;
                $closedStatus->save();
            }
            if ($replystatus->save()) {
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

    public function overview()
    {
        //Month Wise Customer count decrease or increase
        $total_customers = Customer::where('status', '!=', 2)->count();
        $now = date('Y-m-d');
        $previousMonthUsers = Customer::where('status', '!=', 2)->whereMonth('created_on', date('m', strtotime($now . ' -1 months')))->count();
        $thisMonthUsers = Customer::where('status', '!=', 2)->whereMonth('created_on',  date('m'))->count();
        if ($previousMonthUsers > 0) {
            // If it has decreased then it will give you a percentage with '-'
            // $differenceInpercentage = ($thisMonthUsers - $previousMonthUsers) * 100 / $previousMonthUsers;
            $differenceInpercentage = (($thisMonthUsers - $previousMonthUsers) / $previousMonthUsers);
            if ($differenceInpercentage >= 100) {
                $differenceInpercentage = substr($differenceInpercentage, 0, -2);
            }
            if ($differenceInpercentage >= -100) {
                $differenceInpercentage = round($differenceInpercentage);
            }
        } else {
            $differenceInpercentage = $thisMonthUsers > 0 ? '100' : '0';
        }
        //Month Wise product count decrease or increase
        $total_products = Product::where('status', '!=', 2)->count();
        $previousMonthProduct = Product::where('status', '!=', 2)->whereMonth('created_on', date('m', strtotime($now . ' -1 months')))->count();
        $thisMonthProduct = Product::where('status', '!=', 2)->whereMonth('created_on',  date('m'))->count();
        if ($previousMonthProduct > 0) {
            // If it has decreased then it will give you a percentage with '-'
            // $differenceInpercentageProduct = ($thisMonthProduct - $previousMonthProduct) * 100 / $previousMonthProduct;
            $differenceInpercentageProduct = (($thisMonthProduct - $previousMonthProduct) / $previousMonthProduct);
            if ($differenceInpercentageProduct >= 100) {
                $differenceInpercentageProduct = substr($differenceInpercentageProduct, 0, -2);
            }
            if ($differenceInpercentageProduct >= -100) {
                $differenceInpercentageProduct = round($differenceInpercentageProduct);
            }
        } else {
            $differenceInpercentageProduct = $thisMonthProduct > 0 ? '100' : '0';
        }
        //Month Wise Orders count decrease or increase
        $total_orders = Orders::where('payment_status', '!=', 2)->count();
        $previousMonthOrders = Orders::where('payment_status', '!=', 2)->whereMonth('created_on', date('m', strtotime($now . ' -1 months')))->count();
        $thisMonthOrders = Orders::where('payment_status', '!=', 2)->whereMonth('created_on',  date('m'))->count();
        if ($previousMonthOrders > 0) {
            // If it has decreased then it will give you a percentage with '-'
            // $differenceInpercentageOrder = ($thisMonthOrders - $previousMonthOrders) * 100 / $previousMonthOrders;
            $differenceInpercentageOrder = (($thisMonthOrders - $previousMonthOrders) / $previousMonthOrders);
            if ($differenceInpercentageOrder >= 100) {
                $differenceInpercentageOrder = substr($differenceInpercentageOrder, 0, -2);
            }
            if ($differenceInpercentageOrder >= -100) {
                $differenceInpercentageOrder = round($differenceInpercentageOrder);
            }
        } else {
            $differenceInpercentageOrder = $thisMonthOrders > 0 ? '100' : '0';
        }
        //Month Wise revenue decrease or increase
        $total_revenue = Orders::where('payment_status', 1)->where('payment_delivery_status',1)->sum(DB::raw('cancelled_order_totalamount'));
        $previousMonthrevenue = Orders::where('payment_status', 1)->where('payment_delivery_status',1)->whereMonth('created_on', date('m', strtotime($now . ' -1 months')))->sum(DB::raw('cancelled_order_totalamount'));
        $thisMonthrevenue = Orders::where('payment_status', 1)->where('payment_delivery_status',1)->whereMonth('created_on', date('m'))->sum(DB::raw('cancelled_order_totalamount'));
        if ($previousMonthrevenue > 0) {
            // $differenceInpercentage_revenue = ($thisMonthrevenue - $previousMonthrevenue)  * 100 / $previousMonthrevenue;
            $differenceInpercentage_revenue = ($thisMonthrevenue - $previousMonthrevenue ) / $previousMonthrevenue;
            
        } else {
            $differenceInpercentage_revenue = $thisMonthrevenue > 0 ? '100' : '0';
        }
        $count = [
            //Total Customers
            'total_customers' => $total_customers,
            'this_month_customer' => $thisMonthUsers,
            'previous_month_customer' => $previousMonthUsers,
            'percentage_of_customer' => round($differenceInpercentage) . '%',
            //Total Product
            'total_products' => $total_products,
            'this_month_products' => $thisMonthProduct,
            'previous_month_products' => $previousMonthProduct,
            'percentage_of_products' => round($differenceInpercentageProduct) . '%',
            //Total orders
            'total_orders' => $total_orders,
            'this_month_orders' => $thisMonthOrders,
            'previous_month_orders' => $previousMonthOrders,
            'percentage_of_orders' => round($differenceInpercentageOrder) . '%',
            //Total Revenue
            'total_revenue' => $total_revenue,
            'this_month_revenue' => $thisMonthrevenue,
            'previous_month_revenue' => $previousMonthrevenue,
            'percentage_of_revenue' => round($differenceInpercentage_revenue) . '%',
        ];
        return response()->json([
            'keyword' => 'success',
            'message' => __('Overview listed successfully'),
            'data' => [$count],
        ]);
    }


    public function Product_sale_count(Request $request, $product_sale_date)
    {

        //Product Sale Analytics Day Wise
        if ($product_sale_date == "today") {
            $passport_photo = OrderItems::where('order_status',5)->where('service_id', '=', '1')->whereDate('created_on', now())->get();
            $passport_photo = $passport_photo->count();


            $photo_print = OrderItems::where('order_status',5)->where('service_id', '=', '2')->whereDate('created_on', now())->get();
            $photo_print = $photo_print->count();


            $photo_frame = OrderItems::where('order_status',5)->where('service_id', '=', '3')->whereDate('created_on', now())->get();
            $photo_frame = $photo_frame->count();


            $personized_products = OrderItems::where('order_status',5)->where('service_id', '=', '4')->whereDate('created_on', now())->get();
            $personized_products = $personized_products->count();


            $ecommerce_products = OrderItems::where('order_status',5)->where('service_id', '=', '5')->whereDate('created_on', now())->get();
            $ecommerce_products = $ecommerce_products->count();


            $selfie_album = OrderItems::where('order_status',5)->where('service_id', '=', '6')->whereDate('created_on', now())->get();
            $selfie_album = $selfie_album->count();


            if ($product_sale_date == "today") {
                $product_sale_daywise = [
                    'passport_photo' => $passport_photo,
                    'photo_print' => $photo_print,
                    'photo_frame' => $photo_frame,
                    'personized_products' => $personized_products,
                    'ecommerce_products' => $ecommerce_products,
                    'selfie_album' => $selfie_album,

                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Day wise Product Sale Count listed successfully'),
                    'customer_section' => [$product_sale_daywise],
                ]);
            }
        }

        // Product Sale Analytics Week Wise
        if ($product_sale_date == "week") {
            $passport_photo = OrderItems::where('order_status',5)->where('service_id', '=', '1')->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $passport_photo = $passport_photo->count();


            $photo_print = OrderItems::where('order_status',5)->where('service_id', '=', '2')->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $photo_print = $photo_print->count();


            $photo_frame = OrderItems::where('order_status',5)->where('service_id', '=', '3')->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $photo_frame = $photo_frame->count();


            $personized_products = OrderItems::where('order_status',5)->where('service_id', '=', '4')->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $personized_products = $personized_products->count();


            $ecommerce_products = OrderItems::where('order_status',5)->where('service_id', '=', '5')->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $ecommerce_products = $ecommerce_products->count();


            $selfie_album = OrderItems::where('order_status',5)->where('service_id', '=', '6')->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $selfie_album = $selfie_album->count();


            if ($product_sale_date == "week") {
                $product_sale_weekwise = [
                    'passport_photo' => $passport_photo,
                    'photo_print' => $photo_print,
                    'photo_frame' => $photo_frame,
                    'personized_products' => $personized_products,
                    'ecommerce_products' => $ecommerce_products,
                    'selfie_album' => $selfie_album,
                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Week Wise Product Sale listed successfully'),
                    'customer_section' => [$product_sale_weekwise],
                ]);
            }
        }

        //Product Sale Analytics Month Wise
        $now = date('Y-m-d');
        if ($product_sale_date == "month") {
            $passport_photo = OrderItems::where('order_status',5)->where('service_id', '=', '1')->whereMonth('created_on', date('m'))->get();
            $passport_photo = $passport_photo->count();

            $photo_print = OrderItems::where('order_status',5)->where('service_id', '=', '2')->whereMonth('created_on', date('m'))->get();
            $photo_print = $photo_print->count();

            $photo_frame = OrderItems::where('order_status',5)->where('service_id', '=', '3')->whereMonth('created_on', date('m'))->get();
            $photo_frame = $photo_frame->count();

            $personized_products = OrderItems::where('order_status',5)->where('service_id', '=', '4')->whereMonth('created_on', date('m'))->get();
            $personized_products = $personized_products->count();

            $ecommerce_products = OrderItems::where('order_status',5)->where('service_id', '=', '5')->whereMonth('created_on', date('m'))->get();
            $ecommerce_products = $ecommerce_products->count();

            $selfie_album = OrderItems::where('order_status',5)->where('service_id', '=', '6')->whereMonth('created_on', date('m'))->get();
            $selfie_album = $selfie_album->count();


            if ($product_sale_date == "month") {
                $product_sale_monthwise = [
                    'passport_photo' => $passport_photo,
                    'photo_print' => $photo_print,
                    'photo_frame' => $photo_frame,
                    'personized_products' => $personized_products,
                    'ecommerce_products' => $ecommerce_products,
                    'selfie_album' => $selfie_album,
                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Month Wise Product sale Count listed successfully'),
                    'customer_section' => [$product_sale_monthwise],
                ]);
            }
        }

        $now = date('Y-m-d');
        if ($product_sale_date == "year") {
            $passport_photo = OrderItems::where('order_status',5)->where('service_id', '=', '1')->whereYear('created_on', date('Y'))->get();
            $passport_photo = $passport_photo->count();

            $photo_print = OrderItems::where('order_status',5)->where('service_id', '=', '2')->whereYear('created_on', date('Y'))->get();
            $photo_print = $photo_print->count();

            $photo_frame = OrderItems::where('order_status',5)->where('service_id', '=', '3')->whereYear('created_on', date('Y'))->get();
            $photo_frame = $photo_frame->count();

            $personized_products = OrderItems::where('order_status',5)->where('service_id', '=', '4')->whereYear('created_on', date('Y'))->get();
            $personized_products = $personized_products->count();

            $ecommerce_products = OrderItems::where('order_status',5)->where('service_id', '=', '5')->whereYear('created_on', date('Y'))->get();
            $ecommerce_products = $ecommerce_products->count();

            $selfie_album = OrderItems::where('order_status',5)->where('service_id', '=', '6')->whereYear('created_on', date('Y'))->get();
            $selfie_album = $selfie_album->count();


            if ($product_sale_date == "year") {
                $product_sale_monthwise = [
                    'passport_photo' => $passport_photo,
                    'photo_print' => $photo_print,
                    'photo_frame' => $photo_frame,
                    'personized_products' => $personized_products,
                    'ecommerce_products' => $ecommerce_products,
                    'selfie_album' => $selfie_album,
                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Year Wise Product sale Count listed successfully'),
                    'customer_section' => [$product_sale_monthwise],
                ]);
            }
        }
    }

    // public function visitorsByWeekMonth(Request $request, $type)
    // {
    //     $data = [];
    //     if ($type == 'this_week') {
    //         $today = Carbon::now();
    //         $week = [];
    //         for ($i = 0; $i < 7; $i++) {
    //             $week[] = $today->startOfWeek()->addDay($i)->format('Y-m-d'); //push the current day and plus the mount of $i
    //         }
    //         $collection = collect($week);
    //         $combined = $collection->combine([0, 0, 0, 0, 0, 0, 0]);
    //         $current_week = $combined->all();
    //         foreach ($week as $wk) {
    //             $dt = [
    //                 "created_on" => $wk,
    //                 "visited_count" => 0
    //             ];
    //             $arry[] = $dt;
    //         }
    //         $data = [];
    //         $data = DB::table('orders')
    //             // ->select(DB::raw('DATE_FORMAT(created_on,"%d") as created_on'),'visitmonth','visityear',
    //             ->select('order_from')
    //             ->orderBy('created_on')
    //             ->groupBy('created_on')
    //             ->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
    //             ->get();
    //         $collection = collect($data);
    //         //   $union = $collection->union($arry);
    //         $union = $collection->merge($arry);
    //         $data = $union->sortBy('created_on')->values();
    //         $data = $data->unique('created_on')->values();
    //     }
    //     // if ($type == 'this_month') {
    //     //     $data = DB::table('website_daywise_visit_history')
    //     //         ->select(DB::raw('DATE_FORMAT(created_on,"%d") as created_on'),'visitmonth','visityear','page_type','visited_count')
    //     //         ->orderBy('created_on')
    //     //         ->whereBetween('created_on', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()])
    //     //         ->get();
    //     // }
    //     if ($type == 'this_month') {
    //         $today = today();
    //         $dates = [];
    //         for ($i = 1; $i < $today->daysInMonth + 1; ++$i) {
    //             $dates[] = Carbon::createFromDate($today->year, $today->month, $i)->format('Y-m-d');
    //         }
    //         foreach ($dates as $d) {
    //             $dt = [
    //                 "created_on" => $d,
    //                 "visited_count" => 0
    //             ];
    //             $arry[] = $dt;
    //         }
    //         $data = [];
    //         $data = DB::table('orders')
    //             // ->select(DB::raw('DATE_FORMAT(created_on,"%d") as created_on'),'visitmonth','visityear',
    //             ->select('order_from')
    //             ->orderBy('created_on')
    //             ->groupBy('created_on')
    //             ->whereBetween('created_on', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()])
    //             ->get();
    //         $collection = collect($data);
    //         $union = $collection->merge($arry);
    //         $data = $union->sortBy('created_on')->values();
    //         $data = $data->unique('created_on')->values();
    //     }
    //     if ($type == 'this_year') {
    //         $data = DB::table('orders')
    //             ->select('order_from')
    //             ->get();
    //     }
    //     $count = count($data);
    //     if (!empty($data)) {
    //         return response()->json([
    //             'keyword' => 'success',
    //             'message' => 'Visitors month and week list',
    //             'data' => $data,
    //             'count' => $count
    //         ]);
    //     } else {
    //         return response()->json([
    //             'keyword' => 'failed',
    //             'data'        => [],
    //             'message'      => __('No data Found')
    //         ]);
    //     }
    // }


    public function revenue_analytics(Request $request, $type)
    {
        $data = [];
        if ($type == 'today') {
            $today = Carbon::now();
            $weeks = ['00', '01', '02', '03', '04', '05', '06', '07','08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];
            $data =[];
            $data = DB::table('orders')
                    ->where('payment_status', 1)
                    ->where('payment_delivery_status',1)
                    ->select(DB::raw('DATE_FORMAT(order_date,"%H") as order_date'), DB::raw('sum(cancelled_order_totalamount) as daily_revenue'))
                    ->groupBy('order_time')
                    ->whereDate('order_date', date('Y-m-d'))
                    ->orderBy('order_time','asc')
                    ->get();
       
            foreach ($weeks as $wk) {
                $dt = [
                    "order_date" => $wk,
                    "daily_revenue" => 0
                ];
                $arry[] = $dt;
            }

            $collection = collect($data);
           
            $union = $collection->merge($arry);
            $data = $union->sortBy('order_date')->values();
            $data = $data->unique('order_date')->values();

            if(!empty($data)){
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Today revenue listed successfully'),
                    'data' => $data,
                ]);
            }
            else{
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        }
        if ($type == 'this_week') {
            $today = Carbon::now();
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $today->startOfWeek()->addDay($i)->format('Y-m-d'); //push the current day and plus the mount of $i
            }
            $collection = collect($week);
            $combined = $collection->combine([0, 0, 0, 0, 0, 0, 0]);
            $current_week = $combined->all();
            foreach ($week as $wk) {
                $dt = [
                    "order_date" => $wk,
                    "daily_revenue" => 0
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('orders')
                ->where('payment_status', 1)
                ->where('payment_delivery_status',1)
                ->select(DB::raw('date(order_date) as order_date'),  DB::raw('sum(cancelled_order_totalamount) as daily_revenue'))
                ->groupByRaw('date(order_date)')
                ->whereBetween('order_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->get();
            $collection = collect($data);
            //   $union = $collection->union($arry);
            $union = $collection->merge($arry);
            $data = $union->sortBy('order_date')->values();
            $data = $data->unique('order_date')->values();
        }
        if ($type == 'this_month') {
            $today = today();
            $dates = [];
            for ($i = 1; $i < $today->daysInMonth + 1; ++$i) {
                $dates[] = Carbon::createFromDate($today->year, $today->month, $i)->format('Y-m-d');
            }
            foreach ($dates as $d) {
                $dt = [
                    "order_date" => $d,
                    "daily_revenue" => 0
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('orders')
                ->where('payment_status', 1)
                ->where('payment_delivery_status',1)
                ->select(DB::raw('date(order_date) as order_date'),  DB::raw('sum(cancelled_order_totalamount) as daily_revenue'))
                ->groupByRaw('date(order_date)')
                ->whereBetween('order_date', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()])
                ->get();
            $collection = collect($data);
            $arrys = collect($arry);
            $union = $collection->merge($arrys);
            $data = $union->sortBy('order_date')->values();
            // print_r($data);die;
            $data = $data->unique('order_date')->values();
        }
        if ($type == 'this_year') {
            $year = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
            foreach ($year as $yr) {
                $dt = [
                    "order_date" => $yr,
                    "daily_revenue" => "0.00"
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('orders')
                ->where('payment_status', 1)
                ->where('payment_delivery_status',1)
                ->select(DB::raw('month(order_date) as order_date'), DB::raw('sum(cancelled_order_totalamount) as daily_revenue'))
                ->groupByRaw('year(order_date), month(order_date)')
                ->orderByRaw('year(order_date) ASC, month(order_date) ASC')
                ->whereYear('order_date', date('Y'))
                ->get();
            $collection = collect($data);
            $union = $collection->merge($arry);
            $data = $union->sortBy('order_date')->values();
            $data = $data->unique('order_date')->values();
        }
        if ($type == 'comparitive_year') {
            $year = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
            foreach ($year as $yr) {
                $dt = [
                    "order_date" => $yr,
                    "daily_revenue" => "0.00"
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('orders')
                ->where('payment_status', 1)
                ->where('payment_delivery_status',1)
                ->select(DB::raw('month(order_date) as order_date'), DB::raw('sum(cancelled_order_totalamount) as daily_revenue'))
                ->groupByRaw('year(order_date), month(order_date)')
                ->orderByRaw('year(order_date) ASC, month(order_date) ASC')
                ->whereYear('order_date', date('Y'))
                ->get();
            $collection = collect($data);
            $union = $collection->merge($arry);
            $data = $union->sortBy('order_date')->values();
            $data = $data->unique('order_date')->values();
            $previous_year = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
            foreach ($previous_year as $pre_yr) {
                $dts = [
                    "order_date" => $pre_yr,
                    "daily_revenue" => "0.00"
                ];
                $arrys[] = $dts;
            }
            $pre_datas = [];
            $pre_datas = DB::table('orders')
                ->where('payment_status', 1)
                ->where('payment_delivery_status',1)
                ->select(DB::raw('month(order_date) as order_date'), DB::raw('sum(cancelled_order_totalamount) as daily_revenue'))
                ->groupByRaw('year(order_date), month(order_date)')
                ->orderByRaw('year(order_date) ASC, month(order_date) ASC')
                ->whereYear('order_date', Carbon::now()->year - 1)
                ->get();
            $pre_collections = collect($pre_datas);
            $unions = $pre_collections->merge($arrys);
            $pre_data = $unions->sortBy('order_date')->values();
            $pre_data = $pre_data->unique('order_date')->values();
        }
        $count = count($data);
        if ($type == 'comparitive_year') {
            if (!empty($data) && !empty($pre_data)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Comparitive years listed successfully',
                    'this_year' => $data,
                    'previous_year' => $pre_data
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => __('message.failed')
                ]);
            }
        }
        if (!empty($data)) {
            return response()->json([
                'keyword' => 'success',
                'message' => 'Revenue months listed successfully',
                'data' => $data,
                // 'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'data'        => [],
                'message'      => __('message.failed')
            ]);
        }
    }

    public function customer_count(Request $request)
    {
        //Customer User Analytics Day Wise
        $Customer_count = ($request->Customer_count) ? $request->Customer_count : ' ';
        if ($Customer_count == "today") {
            $android_count = Customer::where('platform', '=', 'Android')->whereDate('created_on', now())->get();
            $android_count = $android_count->count();
            $yesterday_date = date('Y-m-d', strtotime("-1 days"));
            $android_count_previous = Customer::where('platform', '=', 'Android')->whereDate('created_on', $yesterday_date)->get();
            $android_count_previous = $android_count_previous->count();
            if ($android_count_previous > 0) {
                $differenceInpercentage_android = ($android_count - $android_count_previous) / $android_count_previous;
            } else {
                $differenceInpercentage_android = $android_count > 0 ? '100' : '0';
            }
            $ios_count = Customer::where('platform', '=', 'IOS')->whereDate('created_on', now())->get();
            $ios_count = $ios_count->count();
            $ios_count_previous  = Customer::where('platform', '=', 'IOS')->whereDate('created_on', $yesterday_date)->get();
            $ios_count_previous  = $ios_count_previous->count();
            if ($ios_count_previous > 0) {
                $differenceInpercentage_ios = ($ios_count - $ios_count_previous) / $ios_count_previous;
            } else {
                $differenceInpercentage_ios = $ios_count > 0 ? '100' : '0';
            }
            //Today website customer count
            $website_count = Customer::where('customer_from', '=', 'Web')->whereDate('created_on', now())->get();
            $website_count = $website_count->count();
            $website_count_previous  = Customer::where('customer_from', '=', 'Web')->whereDate('created_on', $yesterday_date)->get();
            $website_count_previous  = $website_count_previous->count();
            if ($website_count_previous > 0) {
                $differenceInpercentage_web = ($website_count - $website_count_previous) / $website_count_previous;
            } else {
                $differenceInpercentage_web = $website_count > 0 ? '100' : '0';
            }
            if ($Customer_count == "today") {
                $mobile_Device_users_daywise = [
                    'android_count' => $android_count,
                    'android_count_previous' => $android_count_previous,
                    'percentage_of_customer_android' => round($differenceInpercentage_android) . '%',
                    'ios_count' => $ios_count,
                    'ios_count_previous' => $ios_count_previous,
                    'percentage_of_customer_ios' => round($differenceInpercentage_ios) . '%',
                    'website_count' => $website_count,
                    'website_count_previous' => $website_count_previous,
                    'percentage_of_customer_website' => round($differenceInpercentage_web) . '%',
                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Day wise count viewed successfully'),
                    'customer_section' => [$mobile_Device_users_daywise],
                ]);
            }
        }


        // Customer User Analytics Week Wise
        $Customer_count = ($request->Customer_count) ? $request->Customer_count : ' ';
        if ($Customer_count == "week") {
            $android_count_week = Customer::where('platform', '=', 'Android')->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $android_count_week = $android_count_week->count();
            $start = Carbon::now()->subWeek()->startOfWeek();
            $end = Carbon::now()->subWeek()->endOfWeek();
            $android_count_previous_week = Customer::where('platform', '=', 'Android')
                ->whereBetween('created_on', [$start, $end])->get();
            $android_count_previous_week = $android_count_previous_week->count();
            if ($android_count_previous_week > 0) {
                $differenceInpercentage_android = ($android_count_week - $android_count_previous_week) / $android_count_previous_week;
            } else {
                $differenceInpercentage_android = $android_count_week > 0 ? '100' : '0';
            }
            $ios_count_week = Customer::where('platform', '=', 'IOS')->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $ios_count_week = $ios_count_week->count();
            $start = Carbon::now()->subWeek()->startOfWeek();
            $end = Carbon::now()->subWeek()->endOfWeek();
            $ios_count_previous_week = Customer::where('platform', '=', 'IOS')->whereBetween('created_on', [$start, $end])->get();
            $ios_count_previous_week = $ios_count_previous_week->count();
            if ($ios_count_previous_week > 0) {
                $differenceInpercentage_ios = ($ios_count_week - $ios_count_previous_week) / $ios_count_previous_week;
            } else {
                $differenceInpercentage_ios = $ios_count_week > 0 ? '100' : '0';
            }
            $website_count_week = Customer::where('customer_from', '=', 'Web')->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $website_count_week = $website_count_week->count();
            $start = Carbon::now()->subWeek()->startOfWeek();
            $end = Carbon::now()->subWeek()->endOfWeek();
            $website_count_previous_week = Customer::where('customer_from', '=', 'Web')->whereBetween('created_on', [$start, $end])->get();
            $website_count_previous_week = $website_count_previous_week->count();
            if ($website_count_previous_week > 0) {
                $differenceInpercentage_website = ($website_count_week - $website_count_previous_week) / $website_count_previous_week;
            } else {
                $differenceInpercentage_website = $website_count_week > 0 ? '100' : '0';
            }
            if ($Customer_count == "week") {
                $mobile_Device_users_weekwise = [
                    'android_count' => $android_count_week,
                    'android_count_previous' => $android_count_previous_week,
                    'percentage_of_customer_android' => round($differenceInpercentage_android) . '%',
                    'ios_count' => $ios_count_week,
                    'ios_count_previous' => $ios_count_previous_week,
                    'percentage_of_customer_ios' => round($differenceInpercentage_ios) . '%',
                    'website_count' => $website_count_week,
                    'website_count_previous' => $website_count_previous_week,
                    'percentage_of_customer_website' => round($differenceInpercentage_website) . '%',
                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Week wise count viewed successfully'),
                    'customer_section' => [$mobile_Device_users_weekwise],
                ]);
            }
        }
        //Customer User Analytics Month Wise
        $Customer_count = ($request->Customer_count) ? $request->Customer_count : ' ';
        if ($Customer_count == "month") {
            $now = date('Y-m-d');
            $android_count_month = Customer::where('platform', '=', 'Android')->whereMonth('created_on', date('m'))->get();
            $android_count_month = $android_count_month->count();
            $android_count_previous_month = Customer::where('platform', '=', 'Android')->whereMonth('created_on', date('m', strtotime($now . ' -1 months')))->get();
            $android_count_previous_month = $android_count_previous_month->count();
            if ($android_count_previous_month > 0) {
                $differenceInpercentage_android_month = ($android_count_month - $android_count_previous_month) / $android_count_previous_month;
            } else {
                $differenceInpercentage_android_month = $android_count_month > 0 ? '100' : '0';
            }
            $ios_count_month = Customer::where('platform', '=', 'IOS')->whereMonth('created_on', date('m'))->get();
            $ios_count_month = $ios_count_month->count();
            $ios_count_previous_month  = Customer::where('platform', '=', 'IOS')->whereMonth('created_on', date('m', strtotime($now . ' -1 months')))->get();
            $ios_count_previous_month  = $ios_count_previous_month->count();
            if ($ios_count_previous_month > 0) {
                $differenceInpercentage_ios_month = ($ios_count_month - $ios_count_previous_month) / $ios_count_previous_month;
            } else {
                $differenceInpercentage_ios_month = $ios_count_month > 0 ? '100' : '0';
            }
            $website_count_month = Customer::where('customer_from', '=', 'Web')->whereMonth('created_on', date('m'))->get();
            $website_count_month = $website_count_month->count();
            $website_count_previous_month  = Customer::where('customer_from', '=', 'Web')->whereMonth('created_on', date('m', strtotime($now . ' -1 months')))->get();
            $website_count_previous_month  = $website_count_previous_month->count();
            if ($website_count_previous_month > 0) {
                $differenceInpercentage_web_month = ($website_count_month - $website_count_previous_month) / $website_count_previous_month;
            } else {
                $differenceInpercentage_web_month = $website_count_month > 0 ? '100' : '0';
            }
            if ($Customer_count == "month") {
                $mobile_Device_users_weekwise = [
                    'android_count' => $android_count_month,
                    'android_count_previous' => $android_count_previous_month,
                    'percentage_of_customer_android' => round($differenceInpercentage_android_month) . '%',
                    'ios_count' => $ios_count_month,
                    'ios_count_previous' => $ios_count_previous_month,
                    'percentage_of_customer_ios' => round($differenceInpercentage_ios_month) . '%',
                    'website_count' => $website_count_month,
                    'website_count_previous' => $website_count_previous_month,
                    'percentage_of_customer_website' => round($differenceInpercentage_web_month) . '%',
                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Month wise count viewed successfully'),
                    'customer_section' => [$mobile_Device_users_weekwise],
                ]);
            }
        }
        //Customer User Analytics Year Wise
        $Customer_count = ($request->Customer_count) ? $request->Customer_count : ' ';
        if ($Customer_count == "year") {
            $now = date('Y-m-d');
            $android_count_this_year = Customer::where('platform', '=', 'Android')->whereYear('created_on', date('Y'))->get();
            $android_count_this_year = $android_count_this_year->count();
            $android_count_previous_year = Customer::where('platform', '=', 'Android')->whereYear('created_on', date('Y', strtotime('-1 year')))->get();
            $android_count_previous_year = $android_count_previous_year->count();
            if ($android_count_previous_year > 0) {
                $differenceInpercentage_android_year = ($android_count_this_year - $android_count_previous_year) / $android_count_previous_year;
            } else {
                $differenceInpercentage_android_year = $android_count_this_year > 0 ? '100' : '0';
            }
            $ios_count_this_year = Customer::where('platform', '=', 'IOS')->whereYear('created_on', date('Y'))->get();
            $ios_count_this_year = $ios_count_this_year->count();
            $ios_count_previous_year  = Customer::where('platform', '=', 'IOS')->whereYear('created_on', date('Y', strtotime('-1 year')))->get();
            $ios_count_previous_year  = $ios_count_previous_year->count();
            if ($ios_count_previous_year > 0) {
                $differenceInpercentage_ios_year = ($ios_count_this_year - $ios_count_previous_year) / $ios_count_previous_year;
            } else {
                $differenceInpercentage_ios_year = $ios_count_this_year > 0 ? '100' : '0';
            }
            $website_count_this_year = Customer::where('customer_from', '=', 'Web')->whereYear('created_on', date('Y'))->get();
            $website_count_this_year = $website_count_this_year->count();
            $website_count_previous_year  = Customer::where('customer_from', '=', 'Web')->whereYear('created_on', date('Y', strtotime('-1 year')))->get();
            $website_count_previous_year  = $website_count_previous_year->count();
            if ($website_count_previous_year > 0) {
                $differenceInpercentage_web_year = ($website_count_this_year - $website_count_previous_year) / $website_count_previous_year;
            } else {
                $differenceInpercentage_web_year = $website_count_this_year > 0 ? '100' : '0';
            }
            if ($Customer_count == "year") {
                $mobile_Device_users_weekwise = [
                    'android_count_this_year' => $android_count_this_year,
                    'android_count_previous_year' => $android_count_previous_year,
                    'percentage_of_customer_android' => round($differenceInpercentage_android_year) . '%',
                    'ios_count_this_year' => $ios_count_this_year,
                    'ios_count_previous_year' => $ios_count_previous_year,
                    'percentage_of_customer_ios' => round($differenceInpercentage_ios_year) . '%',
                    'website_count_this_year' => $website_count_this_year,
                    'website_count_previous_year' => $website_count_previous_year,
                    'percentage_of_customer_website' => round($differenceInpercentage_web_year) . '%',
                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Year wise count viewed successfully'),
                    'customer_section' => [$mobile_Device_users_weekwise],
                ]);
            }
        }
    }
 
    public function order_statistics(Request $request)
    {
        //Customer User Analytics Day Wise
        $order_count = ($request->order_count) ? $request->order_count : ' ';
        if ($order_count == "today") {
            $order_placed_count = OrderItems::whereIn('order_status', [0,1])->whereDate('created_on', now())->get();
            $order_placed_count = $order_placed_count->count();
            // print_r($android_count);die;
            $packed_order_count = OrderItems::where('order_status', '=', 7)->whereDate('created_on', now())->get();
            $packed_order_count = $packed_order_count->count();

            //Today website customer count
            $dispatched_order_count = OrderItems::where('order_status', '=', 3)->whereDate('created_on', now())->get();
            $dispatched_order_count = $dispatched_order_count->count();

            $cancelled_order_count = OrderItems::where('order_status', '=', 4)->whereDate('created_on', now())->get();
            $cancelled_order_count = $cancelled_order_count->count();

            $delivered_order_count = OrderItems::where('order_status', '=', 5)->whereDate('created_on', now())->get();
            $delivered_order_count = $delivered_order_count->count();

            if ($order_count == "today") {
                $order_statistics_daywise = [
                    'order_placed_count' => $order_placed_count,
                    'packed_order_count' => $packed_order_count,
                    'dispatched_order_count' => $dispatched_order_count,
                    'cancelled_order_count' => $cancelled_order_count,
                    'delivered_order_count' => $delivered_order_count,


                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Day wise order statistics listed successfully'),
                    'customer_section' => [$order_statistics_daywise],
                ]);
            }
        }


        // Customer User Analytics Week Wise
        $order_count = ($request->order_count) ? $request->order_count : ' ';
        if ($order_count == "week") {
            $order_placed_count = OrderItems::whereIn('order_status', [0,1])->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $order_placed_count = $order_placed_count->count();

            $packed_order_count = OrderItems::where('order_status', '=', 7)->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $packed_order_count = $packed_order_count->count();

            $dispatched_order_count = OrderItems::where('order_status', '=', 3)->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $dispatched_order_count = $dispatched_order_count->count();

            $cancelled_order_count = OrderItems::where('order_status', '=', 4)->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $cancelled_order_count = $cancelled_order_count->count();

            $delivered_order_count = OrderItems::where('order_status', '=', 5)->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
            $delivered_order_count = $delivered_order_count->count();

            if ($order_count == "week") {
                $order_statistics_weekwise = [
                    'order_placed_count' => $order_placed_count,
                    'packed_order_count' => $packed_order_count,
                    'dispatched_order_count' => $dispatched_order_count,
                    'cancelled_order_count' => $cancelled_order_count,
                    'delivered_order_count' => $delivered_order_count,
                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Week wise order statistics listed successfully'),
                    'customer_section' => [$order_statistics_weekwise],
                ]);
            }
        }
        //Customer User Analytics Month Wise
        $order_count = ($request->order_count) ? $request->order_count : ' ';
        if ($order_count == "month") {
            $now = date('Y-m-d');
            $order_placed_count = OrderItems::whereIn('order_status', [0,1])->whereMonth('created_on', date('m'))->get();
            $order_placed_count = $order_placed_count->count();

            $packed_order_count = OrderItems::where('order_status', '=', 7)->whereMonth('created_on', date('m'))->get();
            $packed_order_count = $packed_order_count->count();

            $dispatched_order_count = OrderItems::where('order_status', '=', 3)->whereMonth('created_on', date('m'))->get();
            $dispatched_order_count = $dispatched_order_count->count();

            $cancelled_order_count = OrderItems::where('order_status', '=', 4)->whereMonth('created_on', date('m'))->get();
            $cancelled_order_count = $cancelled_order_count->count();

            $delivered_order_count = OrderItems::where('order_status', '=', 5)->whereMonth('created_on', date('m'))->get();
            $delivered_order_count = $delivered_order_count->count();
            if ($order_count == "month") {
                $order_statistics_monthwise = [
                    'order_placed_count' => $order_placed_count,
                    'packed_order_count' => $packed_order_count,
                    'dispatched_order_count' => $dispatched_order_count,
                    'cancelled_order_count' => $cancelled_order_count,
                    'delivered_order_count' => $delivered_order_count,

                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Month wise order statistics listed successfully'),
                    'customer_section' => [$order_statistics_monthwise],
                ]);
            }
        }

        //Customer User Analytics Year Wise
        $order_count = ($request->order_count) ? $request->order_count : ' ';
        if ($order_count == "year") {
            $now = date('Y-m-d');
            $order_placed_count = OrderItems::whereIn('order_status', [0,1])->whereYear('created_on', date('Y'))->get();
            $order_placed_count = $order_placed_count->count();

            $packed_order_count = OrderItems::where('order_status', '=', 7)->whereYear('created_on', date('Y'))->get();
            $packed_order_count = $packed_order_count->count();

            $dispatched_order_count = OrderItems::where('order_status', '=', 3)->whereYear('created_on', date('Y'))->get();
            $dispatched_order_count = $dispatched_order_count->count();

            $cancelled_order_count = OrderItems::where('order_status', '=', 4)->whereYear('created_on', date('Y'))->get();
            $cancelled_order_count = $cancelled_order_count->count();

            $delivered_order_count = OrderItems::where('order_status', '=', 5)->whereYear('created_on', date('Y'))->get();
            $delivered_order_count = $delivered_order_count->count();
            if ($order_count == "year") {
                $order_statistics_Yearwise = [
                    'order_placed_count' => $order_placed_count,
                    'packed_order_count' => $packed_order_count,
                    'dispatched_order_count' => $dispatched_order_count,
                    'cancelled_order_count' => $cancelled_order_count,
                    'delivered_order_count' => $delivered_order_count,

                ];
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Year wise order statistics listed successfully'),
                    'customer_section' => [$order_statistics_Yearwise],
                ]);
            }
        }
    }


    public function order_analytics(Request $request, $type)
    {
        $data = [];
        if ($type == 'today') {
            $today = Carbon::now();
            $week = [];
            $final = [];
            for ($i = 0; $i < 24; $i++) {
                $week = $today->startOfDay()->addHour($i)->format('H');
                //push the current day and plus the mount of $i
                    $data = [];
                    $data = DB::table('orders')->where('order_from', "Mobile")
                    ->select(DB::raw('DATE_FORMAT(order_date,"%H") as order_date'), 'order_from', DB::raw('count(order_from) as order_count'))
                    ->groupBy('order_time')
                        ->where('order_time', $week)
                        ->whereDate('order_date',date('Y-m-d'))
                        ->count();
                        // print_r($data);die;
                    $ary = [];
                    $ary['order_date'] = $week;
                    $ary['order_from'] = 'Mobile';
                    $ary['order_count'] = $data;
                    $final[] = $ary;
            }
            
            $web = [];
            for ($i = 0; $i < 24; $i++) {
                $week = $today->startOfDay()->addHour($i)->format('H');
                //push the current day and plus the mount of $i
                    $data = [];
                    $data = DB::table('orders')->where('order_from', "Web")
                    ->select(DB::raw('DATE_FORMAT(order_date,"%H") as order_date'), 'order_from', DB::raw('count(order_from) as order_count'))
                    ->groupBy('order_time')
                        ->where('order_time', $week)->whereDate('order_date',date('Y-m-d'))
                        ->count();
                    $ary = [];
                    $ary['order_time'] = $week;
                    $ary['order_from'] = 'Web';
                    $ary['order_count'] = $data;
                    $web[] = $ary;
            }
        }
        if ($type == 'today') {
            if (!empty($final) && ($web)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Hour wise order analytics listed successfully',
                    'mobile_analytics' => $final,
                    'web_analytics' => $web
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => __('message.failed')
                ]);
            }
        }
        if ($type == 'this_week') {
            $today = Carbon::now();
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $today->startOfWeek()->addDay($i)->format('Y-m-d'); //push the current day and plus the mount of $i
            }
            $collection = collect($week);
            $combined = $collection->combine([0, 0, 0, 0, 0, 0, 0]);
            $current_week = $combined->all();
            foreach ($week as $wk) {
                $dt = [
                    "order_date" => $wk,
                    "order_from" => 0,
                    "order_count" => 0
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('orders')->where('order_from', "Mobile")
                ->select(DB::raw('date(order_date) as order_date'), 'order_from', DB::raw('count(order_from) as order_count'))
                ->groupByRaw('date(order_date)')
                ->whereBetween('order_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->get();
            $collection = collect($data);
            $union = $collection->merge($arry);
            $data = $union->sortBy('order_date')->values();
            $data = $data->unique('order_date')->values();
            $today = Carbon::now();
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $today->startOfWeek()->addDay($i)->format('Y-m-d'); //push the current day and plus the mount of $i
            }
            $collection = collect($week);
            $combined = $collection->combine([0, 0, 0, 0, 0, 0, 0]);
            $current_week = $combined->all();
            foreach ($week as $wk) {
                $dt = [
                    "order_date" => $wk,
                    "order_from" => 0,
                    "order_count" => 0
                ];
                $arry[] = $dt;
            }
            $pre_datas = [];
            $pre_datas = DB::table('orders')->where('order_from', "Web")
                ->select(DB::raw('date(order_date) as order_date'), 'order_from', DB::raw('count(order_from) as order_count'))
                ->groupByRaw('date(order_date)')
                ->whereBetween('order_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->get();
            $collection = collect($pre_datas);
            $union = $collection->merge($arry);
            $pre_datas = $union->sortBy('order_date')->values();
            $pre_datas = $pre_datas->unique('order_date')->values();
            $count = count($union);
        }
        if ($type == 'this_week') {
            if (!empty($data) && !empty($pre_datas)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Week wise order analytics listed successfully',
                    'mobile_analytics' => $data,
                    'web_analytics' => $pre_datas
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => __('message.failed')
                ]);
            }
        }
        if ($type == 'this_month') {
            $today = Carbon::now()->startOfMonth();
            $dates = [];
            for ($i = 1; $i < $today->daysInMonth + 1; ++$i) {
                $dates[] = Carbon::createFromDate($today->year, $today->month, $i)->format('Y-m-d');
            }
            foreach ($dates as $d) {
                $dt = [
                    "order_date" => $d,
                    "order_from" => 0,
                    "order_count" => 0
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('orders')->where('order_from', '=', 'Mobile')
                ->select(DB::raw('date(order_date) as order_date'), 'order_from',  DB::raw('count(order_from) as order_count'))
                ->groupByRaw('date(order_date)')
                ->whereBetween('order_date', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()])
                ->get();
            $collection = collect($data);
            $union = $collection->merge($arry);
            $data = $union->sortBy('order_date')->values();
            $data = $data->unique('order_date')->values();
            $today = Carbon::now()->startOfMonth();
            $previous_dates = [];
            for ($i = 1; $i < $today->daysInMonth + 1; ++$i) {
                $dates[] = Carbon::createFromDate($today->year, $today->month, $i)->format('Y-m-d');
            }
            foreach ($previous_dates as $d) {
                $dt = [
                    "order_date" => $d,
                    "order_from" => 0,
                    "order_count" => 0
                ];
                $arry[] = $dt;
            }
            $pre_datas = [];
            $pre_datas = DB::table('orders')->where('order_from', '=', 'Web')
                ->select(DB::raw('date(order_date) as order_date'), 'order_from',  DB::raw('count(order_from) as order_count'))
                ->groupByRaw('date(order_date)')
                ->whereBetween('order_date', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()])
                ->get();
            $collection = collect($pre_datas);
            $union = $collection->merge($arry);
            $pre_datas = $union->sortBy('order_date')->values();
            $pre_datas = $pre_datas->unique('order_date')->values();
            $count = count($union);
        }
        if ($type == 'this_month') {
            if (!empty($data) && !empty($pre_datas)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Month wise order analytics listed successfully',
                    'mobile_analytics' => $data,
                    'web_analytics' => $pre_datas
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => __('message.failed')
                ]);
            }
        }
        if ($type == 'this_year') {
            $year = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
            foreach ($year as $yr) {
                $dt = [
                    "order_date" => $yr,
                    "order_from" => 0,
                    "order_count" => 0
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('orders')->where('order_from', '=', 'Mobile')
                ->select(DB::raw('month(order_date) as order_date'), 'order_from', DB::raw('count(order_from) as order_count'))
                ->groupByRaw('year(order_date), month(order_date)')
                ->orderByRaw('year(order_date) ASC, month(order_date) ASC')
                ->whereYear('order_date', date('Y'))
                ->get();
            $collection = collect($data);
            $union = $collection->merge($arry);
            $data = $union->sortBy('order_date')->values();
            $data = $data->unique('order_date')->values();
            $previous_year = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
            foreach ($previous_year as $pre_yr) {
                $dts = [
                    "order_date" => $pre_yr,
                    "order_from" => 0,
                    "order_count" => 0
                ];
                $arrys[] = $dts;
            }
            $pre_datas = [];
            $pre_datas = DB::table('orders')->where('order_from', '=', 'Web')
                ->select(DB::raw('month(order_date) as order_date'), 'order_from', DB::raw('count(order_from) as order_count'))
                ->groupByRaw('year(order_date), month(order_date)')
                ->orderByRaw('year(order_date) ASC, month(order_date) ASC')
                ->whereYear('order_date', date('Y'))
                ->get();
            $pre_collections = collect($pre_datas);
            $unions = $pre_collections->merge($arrys);
            $pre_datas = $unions->sortBy('order_date')->values();
            $pre_datas = $pre_datas->unique('order_date')->values();
        }
        $count = count($data);
        if ($type == 'this_year') {
            if (!empty($data) && !empty($pre_datas)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Year wise order analytics listed successfully',
                    'mobile_analytics' => $data,
                    'web_analytics' => $pre_datas
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => __('No data Found')
                ]);
            }
        }
    }


    public function sales_location(Request $request, $state_id, $top, $days)
    {
        if ($days == 'today') {
            $sales_location = DB::table('orders')->where('billing_state_id', $state_id)->where('payment_delivery_status',1)
                ->leftJoin('state', 'orders.billing_state_id', '=', 'state.state_id')
                ->leftJoin('district', 'orders.billing_city_id', 'district.district_id')
                ->select(DB::raw('DATE(order_date) AS date'), 'billing_city_id', 'billing_state_id', DB::raw('COUNT(order_id) AS count_for_sale'), 'state.state_name', 'district.district_name')
                ->whereDate('order_date', now())
                ->groupBy(['billing_city_id'])
                ->orderBy('count_for_sale', 'Desc')
                ->limit($top);
            $count = count($sales_location->get());
            $sales_location = $sales_location->get();
            $result = json_decode($sales_location, true);
            $orderAry = [];
            foreach ($result as $value) {
                $ary = [];
                $ary['date'] = $value['date'];
                $ary['billing_city_id'] = $value['billing_city_id'];
                $ary['billing_state_id'] = $value['billing_state_id'];
                $ary['state_name'] = $value['state_name'];
                $ary['district_name'] = $value['district_name'];
                $ary['count_for_sale'] = $value['count_for_sale'];
                $ary['total_orders'] =  DB::table('orders')->select('order_id')->where('payment_delivery_status',1)->count();
                $ary['sales_percent'] =  $ary['count_for_sale'] /  $ary['total_orders'] * 100;
                $orderAry[] = $ary;
            }
            if ($count > 0) {
                if (!empty($orderAry)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Today sales location listed successfully'),
                        'data' => $orderAry,
                        'count' => $count
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        }
        if ($days == 'this_week') {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
            $sales_location = DB::table('orders')->where('billing_state_id', $state_id)->where('payment_delivery_status',1)
                ->leftJoin('state', 'orders.billing_state_id', '=', 'state.state_id')
                ->leftJoin('district', 'orders.billing_city_id', 'district.district_id')
                ->select(DB::raw('DATE(order_date) AS date'), 'billing_city_id', 'billing_state_id', DB::raw('COUNT(order_id) AS count_for_sale'), 'state.state_name', 'district.district_name')
                ->whereBetween('order_date', [$start, $end])
                ->groupBy(['billing_city_id'])
                ->orderBy('count_for_sale', 'Desc')
                ->limit($top);
            $count = count($sales_location->get());
            $sales_location = $sales_location->get();
            $result = json_decode($sales_location, true);
            $orderAry = [];
            foreach ($result as $value) {
                $ary = [];
                $ary['date'] = $value['date'];
                $ary['billing_city_id'] = $value['billing_city_id'];
                $ary['billing_state_id'] = $value['billing_state_id'];
                $ary['state_name'] = $value['state_name'];
                $ary['district_name'] = $value['district_name'];
                $ary['count_for_sale'] = $value['count_for_sale'];
                $ary['total_orders'] =  DB::table('orders')->where('payment_delivery_status',1)->select('order_id')->count();
                $ary['sales_percent'] =  $ary['count_for_sale'] /  $ary['total_orders'] * 100;
                $orderAry[] = $ary;
            }
            if ($count > 0) {
                if (!empty($orderAry)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('This week sales location listed successfully'),
                        'data' => $orderAry,
                        'count' => $count
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        }
        if ($days == 'this_month') {
            $sales_location = DB::table('orders')->where('billing_state_id', $state_id)->where('payment_delivery_status',1)
                ->leftJoin('state', 'orders.billing_state_id', '=', 'state.state_id')
                ->leftJoin('district', 'orders.billing_city_id', 'district.district_id')
                ->select(DB::raw('DATE(order_date) AS date'), 'billing_city_id', 'billing_state_id', DB::raw('COUNT(order_id) AS count_for_sale'), 'state.state_name', 'district.district_name')
                ->whereMonth('order_date', date('m'))
                ->groupBy(['billing_city_id'])
                ->orderBy('count_for_sale', 'Desc')
                ->limit($top);
            $count = count($sales_location->get());
            $sales_location = $sales_location->get();
            $result = json_decode($sales_location, true);
            $orderAry = [];
            foreach ($result as $value) {
                $ary = [];
                $ary['date'] = $value['date'];
                $ary['billing_city_id'] = $value['billing_city_id'];
                $ary['billing_state_id'] = $value['billing_state_id'];
                $ary['state_name'] = $value['state_name'];
                $ary['district_name'] = $value['district_name'];
                $ary['count_for_sale'] = $value['count_for_sale'];
                $ary['total_orders'] =  DB::table('orders')->where('payment_delivery_status',1)->select('order_id')->count();
                $ary['sales_percent'] =  $ary['count_for_sale'] /  $ary['total_orders'] * 100;
                $orderAry[] = $ary;
            }
            if ($count > 0) {
                if (!empty($sales_location)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('This month sales location listed successfully'),
                        'data' => $orderAry,
                        'count' => $count
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        }
        if ($days == 'this_year') {
            $sales_location = DB::table('orders')->where('billing_state_id', $state_id)->where('payment_delivery_status',1)
                ->leftJoin('state', 'orders.billing_state_id', '=', 'state.state_id')
                ->leftJoin('district', 'orders.billing_city_id', 'district.district_id')
                ->select(DB::raw('DATE(order_date) AS date'), 'billing_city_id', 'billing_state_id', DB::raw('COUNT(order_id) AS count_for_sale'), 'state.state_name', 'district.district_name')
                ->whereYear('order_date', date('Y'))
                ->groupBy(['billing_city_id'])
                ->orderBy('count_for_sale', 'Desc')
                ->limit($top);
            $count = count($sales_location->get());
            $sales_location = $sales_location->get();
            $result = json_decode($sales_location, true);
            $orderAry = [];
            foreach ($result as $value) {
                $ary = [];
                $ary['date'] = $value['date'];
                $ary['billing_city_id'] = $value['billing_city_id'];
                $ary['billing_state_id'] = $value['billing_state_id'];
                $ary['state_name'] = $value['state_name'];
                $ary['district_name'] = $value['district_name'];
                $ary['count_for_sale'] = $value['count_for_sale'];
                $ary['total_orders'] =  DB::table('orders')->where('payment_delivery_status',1)->select('order_id')->count();
                $ary['sales_percent'] =  $ary['count_for_sale'] /  $ary['total_orders'] * 100;
                $orderAry[] = $ary;
            }
            if ($count > 0) {
                if (!empty($orderAry)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('This year sales location listed successfully'),
                        'data' => $orderAry,
                        'count' => $count
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        }
    }


    public function new_customer(Request $request, $state_id, $top, $days)
    {
        if ($days == 'today') {
            $new_customer = DB::table('customer')->where('customer.state_id', $state_id)->whereIn('customer.status', [0, 1])
                ->leftJoin('state', 'customer.state_id', '=', 'state.state_id')
                ->leftJoin('district', 'customer.district_id', '=', 'district.district_id')
                ->select(DB::raw('DATE(created_on) AS date'), 'district.district_id', 'customer.state_id', DB::raw('COUNT(customer_id) AS count_for_customer'), 'state.state_name', 'district.district_name')
                ->whereDate('created_on', now())
                ->groupBy(['district_id'])
                ->orderBy('count_for_customer', 'Desc')
                ->limit($top);
            $count = count($new_customer->get());
            $new_customer = $new_customer->get();
            $result = json_decode($new_customer, true);
            $orderAry = [];
            foreach ($result as $value) {
                $ary = [];
                $ary['date'] = $value['date'];
                $ary['district_id'] = $value['district_id'];
                $ary['state_id'] = $value['state_id'];
                $ary['state_name'] = $value['state_name'];
                $ary['district_name'] = $value['district_name'];
                $ary['count_for_customer'] = $value['count_for_customer'];
                $orderAry[] = $ary;
            }
            if ($count > 0) {
                if (!empty($orderAry)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Today new customer listed successfully'),
                        'data' => $orderAry,
                        'count' => $count
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        }
        if ($days == 'this_week') {
            $new_customer = DB::table('customer')->where('customer.state_id', $state_id)->whereIn('customer.status', [0, 1])
                ->leftJoin('state', 'customer.state_id', '=', 'state.state_id')
                ->leftJoin('district', 'customer.district_id', '=', 'district.district_id')
                ->select(DB::raw('DATE(created_on) AS date'), 'customer.district_id', 'customer.state_id', DB::raw('COUNT(customer_id) AS count_for_customer'), 'state.state_name', 'district.district_name')
                ->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->groupBy(['district_id'])
                ->orderBy('count_for_customer', 'Desc')
                ->limit($top);
            $count = count($new_customer->get());

            $new_customer = $new_customer->get();

            $result = json_decode($new_customer, true);
            $orderAry = [];
            foreach ($result as $value) {
                $ary = [];
                $ary['date'] = $value['date'];
                $ary['district_id'] = $value['district_id'];
                $ary['state_id'] = $value['state_id'];
                $ary['state_name'] = $value['state_name'];
                $ary['district_name'] = $value['district_name'];
                $ary['count_for_customer'] = $value['count_for_customer'];
                $orderAry[] = $ary;
            }
            if ($count > 0) {
                if (!empty($orderAry)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('This week new customer listed successfully'),
                        'data' => $orderAry,
                        'count' => $count
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        }
        if ($days == 'this_month') {
            $new_customer = DB::table('customer')->where('customer.state_id', $state_id)->whereIn('customer.status', [0, 1])
                ->leftJoin('state', 'customer.state_id', '=', 'state.state_id')
                ->leftJoin('district', 'customer.district_id', '=', 'district.district_id')
                ->select(DB::raw('DATE(created_on) AS date'), 'customer.district_id', 'customer.state_id', DB::raw('COUNT(customer_id) AS count_for_customer'), 'state.state_name', 'district.district_name')
                ->whereMonth('created_on', date('m'))
                ->groupBy(['customer.district_id'])
                ->orderBy('count_for_customer', 'Desc')
                ->limit($top);
            $count = count($new_customer->get());
            $new_customer = $new_customer->get();
            $result = json_decode($new_customer, true);
            $orderAry = [];
            foreach ($result as $value) {
                $ary = [];
                $ary['date'] = $value['date'];
                $ary['district_id'] = $value['district_id'];
                $ary['state_id'] = $value['state_id'];
                $ary['state_name'] = $value['state_name'];
                $ary['district_name'] = $value['district_name'];
                $ary['count_for_customer'] = $value['count_for_customer'];
                $orderAry[] = $ary;
            }
            if ($count > 0) {
                if (!empty($new_customer)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('This month new customer listed successfully'),
                        'data' => $orderAry,
                        'count' => $count
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        }
        if ($days == 'this_year') {
            $new_customer = DB::table('customer')->where('customer.state_id', $state_id)->whereIn('customer.status', [0, 1])
                ->leftJoin('state', 'customer.state_id', '=', 'state.state_id')
                ->leftJoin('district', 'customer.district_id', '=', 'district.district_id')
                ->select(DB::raw('DATE(created_on) AS date'), 'customer.district_id', 'customer.state_id', DB::raw('COUNT(customer_id) AS count_for_customer'), 'state.state_name', 'district.district_name')
                ->whereYear('created_on', date('Y'))
                ->groupBy(['customer.district_id'])
                ->orderBy('count_for_customer', 'Desc')
                ->limit($top);
            $count = count($new_customer->get());
            $new_customer = $new_customer->get();
            $result = json_decode($new_customer, true);
            $orderAry = [];
            foreach ($result as $value) {
                $ary = [];
                $ary['date'] = $value['date'];
                $ary['district_id'] = $value['district_id'];
                $ary['state_id'] = $value['state_id'];
                $ary['state_name'] = $value['state_name'];
                $ary['district_name'] = $value['district_name'];
                $ary['count_for_customer'] = $value['count_for_customer'];
                $orderAry[] = $ary;
            }
            if ($count > 0) {
                if (!empty($orderAry)) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('This year new customer listed successfully'),
                        'data' => $orderAry,
                        'count' => $count
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        }
    }

    // public function insert(Request $request)
    // {
    //     $data = new ProductVisitHistory();
    //     $data->service_id = $request->input('service_id');
    //     $data->visited_on = Server::getDateTime();
    //     $data->ip_address = $_SERVER['REMOTE_ADDR'];
    //     $Agent = new Agent();
    //     // agent detection influences the view storage path
    //     if ($Agent->isMobile()) {
    //         // you're a mobile device
    //         $data->user_agent = 'mobile';
    //     } else {
    //         $data->user_agent = $request->server('HTTP_USER_AGENT');
    //     }
    //     if ($data->save()) {
    //         return response()->json([
    //             'keyword' => 'success',
    //             'data'   => $data,
    //             'message' => __('product visit history Created')
    //         ]);
    //     } else {
    //         return response()->json([
    //             'keyword' => 'failed',
    //             'data'        => [],
    //             'message'      => __('product visit history failed')
    //         ]);
    //     }
    // }

    public function topProductSeen(Request $request, $type, $limit = 'all')
    {
        $data = [];
        if ($type == 'today') {
            $qry = DB::table('product_daywise_visit_history')
                ->select(DB::raw('product_daywise_visit_history.service_id, product_daywise_visit_history.service_name, sum(product_daywise_visit_history.visited_count) as visited_count'))
                ->whereDate('visited_on', [Carbon::now()]);
            if ($limit != 'all') {
                $qry->when($limit, function ($query, $limit) {
                    return $query->limit($limit);
                }, function ($query) use ($limit) {
                    return $query->limit($limit);
                });
            }
            $qry->groupByRaw('service_id');
            $qry->orderByRaw('visited_count DESC');
            $data_week =  $qry->get();
        }
        if ($type == 'this_week') {
            $qry = DB::table('product_daywise_visit_history')
                ->select(DB::raw('product_daywise_visit_history.service_id, product_daywise_visit_history.service_name, sum(product_daywise_visit_history.visited_count) as visited_count'))
                ->whereBetween('visited_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            if ($limit != 'all') {
                $qry->when($limit, function ($query, $limit) {
                    return $query->limit($limit);
                }, function ($query) use ($limit) {
                    return $query->limit($limit);
                });
            }
            $qry->groupByRaw('service_id');
            $qry->orderByRaw('visited_count DESC');
            $data_week =  $qry->get();
        }
        if ($type == 'this_month') {
            $qry = DB::table('product_daywise_visit_history')
                ->select(DB::raw('product_daywise_visit_history.service_id, product_daywise_visit_history.service_name, sum(product_daywise_visit_history.visited_count) as visited_count'))
                ->whereBetween('visited_on', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()]);
            if ($limit != 'all') {
                $qry->when($limit, function ($query, $limit) {
                    return $query->limit($limit);
                }, function ($query) use ($limit) {
                    return $query->limit($limit);
                });
            }
            $qry->groupByRaw('service_id');
            $qry->orderByRaw('visited_count DESC');
            $data_month =  $qry->get();
        }
        if ($type == 'this_year') {
            $qry = DB::table('product_daywise_visit_history')
                ->select(DB::raw('product_daywise_visit_history.service_id, product_daywise_visit_history.service_name, sum(product_daywise_visit_history.visited_count) as visited_count'))
                ->whereYear('visited_on', date('Y'));
            if ($limit != 'all') {
                $qry->when($limit, function ($query, $limit) {
                    return $query->limit($limit);
                }, function ($query) use ($limit) {
                    return $query->limit($limit);
                });
            }
            $qry->groupByRaw('service_id');
            $qry->orderByRaw('visited_count DESC');
            $data_year =  $qry->get();
        }

        if (!empty($data_week)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Week wise product seen listed successfully'),
                'data' => $data_week,
            ]);
        }
        if (!empty($data_month)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Month wise product seen listed successfully'),
                'data' => $data_month,
            ]);
        }
        if (!empty($data_year)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Year wise product seen listed successfully'),
                'data' => $data_year,
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('Product seen data not found'),
                'data' => []
            ]);
        }
    }

    public function visitorsByWeekMonth(Request $request, $type)
    {
        // $data = [];
        // if ($type == 'today') {
        //     $today = Carbon::now();
        //     $hour = [];
        //     for ($i = 0; $i < 24; $i++) {
        //         $day[] = $today->startOfDay()->addHour($i)->format('H-i-s'); //push the current day and plus the mount of $i
        //     }
        //     $collection = collect($day);
        //     $combined = $collection->combine([0, 0, 0, 0, 0, 0, 0]);
        //     $current_day = $combined->all();
        //     foreach ($day as $dy) {
        //         $dt = [
        //             "visited_on" => $dy,
        //             "visited_count" => 0
        //         ];
        //         $arry[] = $dt;
        //     }
        $data = [];
        if ($type == 'this_week') {
            $today = Carbon::now();
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $today->startOfWeek()->addDay($i)->format('Y-m-d'); //push the current day and plus the mount of $i
            }
            $collection = collect($week);
            $combined = $collection->combine([0, 0, 0, 0, 0, 0, 0]);
            $current_week = $combined->all();
            foreach ($week as $wk) {
                $dt = [
                    "visited_on" => $wk,
                    "visited_count" => 0
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('website_daywise_visit_history')
                ->select(DB::raw('DATE_FORMAT(visited_on,"%d") as visited_on'), 'visitmonth', 'visityear')
                ->select('visited_on', 'visited_count')
                ->orderBy('visited_on')
                ->groupBy('visited_on')
                ->whereBetween('visited_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->get();
            $collection = collect($data);
            $union = $collection->union($arry);
            $union = $collection->merge($arry);
            $data = $union->sortBy('visited_on')->values();
            $data = $data->unique('visited_on')->values();
        }

        if ($type == 'this_month') {
            $today = today();
            $dates = [];
            for ($i = 1; $i < $today->daysInMonth + 1; ++$i) {
                $dates[] = Carbon::createFromDate($today->year, $today->month, $i)->format('Y-m-d');
            }
            foreach ($dates as $d) {
                $dt = [
                    "visited_on" => $d,
                    "visited_count" => 0
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('website_daywise_visit_history')
                ->select(DB::raw('DATE_FORMAT(visited_on,"%d") as visited_on'), 'visitmonth', 'visityear')
                ->select('visited_on', 'visited_count')
                ->orderBy('visited_on')
                ->groupBy('visited_on')
                ->whereBetween('visited_on', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()])
                ->get();
            $collection = collect($data);
            $union = $collection->merge($arry);
            $data = $union->sortBy('visited_on')->values();
            $data = $data->unique('visited_on')->values();
        }

        if ($type == 'this_year') {
            $data = DB::table('website_daywise_visit_history_count')
                ->select('website_daywise_visit_history_count.*')
                ->get();
        }
        $count = count($data);
        if (!empty($data)) {
            return response()->json([
                'keyword' => 'success',
                'message' => 'Visitors month and week list',
                'data' => $data,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'data'        => [],
                'message'      => __('message.failed')
            ]);
        }
    }
    // people visited count
    public function visit_time_history(Request $request)
    {
        $websitevisitors = new Visitors();
        $websitevisitors->ip_address = $_SERVER['REMOTE_ADDR'];
        $websitevisitors->page_type = $request->input('page_type');
        $websitevisitors->user_agent = $request->server('HTTP_USER_AGENT');
        // $websitevisitors->device = "web";
        // $websitevisitors->device_platform = "web";
        $websitevisitors->visited_on = Server::getDateTime();
        if ($websitevisitors->save()) {
            return response()->json([
                'keyword' => 'success',
                'data'   => $websitevisitors,
                'message' => 'Visitor history created',
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'data'        => [],
                'message'      => __('message.failed')
            ]);
        }
    }


    // public function visitor_analytics(Request $request, $type)
    // {
    //     $data = [];
    //     if ($type == 'this_week') {
    //         $today = Carbon::now();
    //         $week = [];
    //         for ($i = 0; $i < 7; $i++) {
    //             $week[] = $today->startOfWeek()->addDay($i)->format('Y-m-d'); //push the current day and plus the mount of $i
    //         }
    //         $collection = collect($week);
    //         $combined = $collection->combine([0, 0, 0, 0, 0, 0, 0]);
    //         $current_week = $combined->all();
    //         foreach ($week as $wk) {
    //             $dt = [
    //                 "created_on" => $wk,
    //                 "customer_from" => 0,
    //                 "customer_count" => 0
    //             ];
    //             $arry[] = $dt;
    //         }

    //         $data = [];
    //         $data = DB::table('customer')->where('customer_from', "Mobile")
    //             ->select(DB::raw('date(created_on) as created_on'), 'customer_from', DB::raw('count(customer_from) as customer_count'))
    //             ->groupByRaw('date(created_on)')
    //             ->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
    //             ->get();

    //         $collection = collect($data);
    //         $union = $collection->merge($arry);
    //         $data = $union->sortBy('created_on')->values();
    //         $data = $data->unique('created_on')->values();

    //         $today = Carbon::now();
    //         $week = [];
    //         for ($i = 0; $i < 7; $i++) {
    //             $week[] = $today->startOfWeek()->addDay($i)->format('Y-m-d'); //push the current day and plus the mount of $i
    //         }
    //         $collection = collect($week);
    //         $combined = $collection->combine([0, 0, 0, 0, 0, 0, 0]);
    //         $current_week = $combined->all();
    //         foreach ($week as $wk) {
    //             $dt = [
    //                 "created_on" => $wk,
    //                 "order_from" => 0,
    //                 "customer_count" => 0
    //             ];
    //             $arry[] = $dt;
    //         }

    //         $pre_datas = [];
    //         $pre_datas = DB::table('customer')->where('customer_from', "Web")
    //             ->select(DB::raw('date(created_on) as created_on'), 'customer_from', DB::raw('count(customer_from) as customer_count'))
    //             ->groupByRaw('date(created_on)')
    //             ->whereBetween('created_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
    //             ->get();

    //         $collection = collect($pre_datas);
    //         $union = $collection->merge($arry);
    //         $pre_datas = $union->sortBy('created_on')->values();
    //         $pre_datas = $pre_datas->unique('created_on')->values();
    //         $count = count($union);
    //     }
    //     if ($type == 'this_week') {
    //         if (!empty($data) && !empty($pre_datas)) {
    //             return response()->json([
    //                 'keyword' => 'success',
    //                 'message' => 'Week Wise Order Analytics listed successfully',
    //                 'mobile_analytics' => $data,
    //                 'web_analytics' => $pre_datas
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'keyword' => 'failed',
    //                 'data'        => [],
    //                 'message'      => __('message.failed')
    //             ]);
    //         }
    //     }


    //     if ($type == 'this_month') {
    //         $today = Carbon::now()->startOfMonth();
    //         $dates = [];
    //         for ($i = 1; $i < $today->daysInMonth + 1; ++$i) {
    //             $dates[] = Carbon::createFromDate($today->year, $today->month, $i)->format('Y-m-d');
    //         }
    //         foreach ($dates as $d) {
    //             $dt = [
    //                 "created_on" => $d,
    //                 "customer_from" => 0,
    //                 "customer_count" => 0
    //             ];
    //             $arry[] = $dt;
    //         }
    //         $data = [];
    //         $data = DB::table('customer')->where('customer_from', '=', 'Mobile')
    //             ->select(DB::raw('date(created_on) as created_on'), 'customer_from',  DB::raw('count(customer_from) as customer_count'))
    //             ->groupByRaw('date(created_on)')
    //             ->whereBetween('created_on', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()])
    //             ->get();
    //         $collection = collect($data);
    //         $union = $collection->merge($arry);
    //         $data = $union->sortBy('created_on')->values();
    //         $data = $data->unique('created_on')->values();


    //         $today = Carbon::now()->startOfMonth();
    //         $previous_dates = [];
    //         for ($i = 1; $i < $today->daysInMonth + 1; ++$i) {
    //             $dates[] = Carbon::createFromDate($today->year, $today->month, $i)->format('Y-m-d');
    //         }
    //         foreach ($previous_dates as $d) {
    //             $dt = [
    //                 "created_on" => $d,
    //                 "customer_from" => 0,
    //                 "customer_count" => 0
    //             ];
    //             $arry[] = $dt;
    //         }
    //         $pre_datas = [];
    //         $pre_datas = DB::table('customer')->where('customer_from', '=', 'Web')
    //             ->select(DB::raw('date(created_on) as created_on'), 'customer_from',  DB::raw('count(customer_from) as customer_count'))
    //             ->groupByRaw('date(created_on)')
    //             ->whereBetween('created_on', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()])
    //             ->get();
    //         $collection = collect($pre_datas);
    //         $union = $collection->merge($arry);
    //         $pre_datas = $union->sortBy('created_on')->values();
    //         $pre_datas = $pre_datas->unique('created_on')->values();
    //         $count = count($union);
    //     }
    //     if ($type == 'this_month') {
    //         if (!empty($data) && !empty($pre_datas)) {
    //             return response()->json([
    //                 'keyword' => 'success',
    //                 'message' => 'Month Wise Order Analytics listed successfully',
    //                 'mobile_analytics' => $data,
    //                 'web_analytics' => $pre_datas
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'keyword' => 'failed',
    //                 'data'        => [],
    //                 'message'      => __('message.failed')
    //             ]);
    //         }
    //     }

    //     if ($type == 'this_year') {
    //         $year = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
    //         foreach ($year as $yr) {
    //             $dt = [
    //                 "created_on" => $yr,
    //                 "customer_from" => 0,
    //                 "customer_count" => 0
    //             ];
    //             $arry[] = $dt;
    //         }
    //         $data = [];
    //         $data = DB::table('customer')->where('customer_from', '=', 'Mobile')
    //             ->select(DB::raw('month(created_on) as created_on'), 'customer_from', DB::raw('count(customer_from) as customer_count'))
    //             ->groupByRaw('year(created_on), month(created_on)')
    //             ->orderByRaw('year(created_on) ASC, month(created_on) ASC')
    //             ->whereYear('created_on', date('Y'))
    //             ->get();

    //         $collection = collect($data);
    //         $union = $collection->merge($arry);
    //         $data = $union->sortBy('created_on')->values();
    //         $data = $data->unique('created_on')->values();

    //         $previous_year = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];

    //         foreach ($previous_year as $pre_yr) {
    //             $dts = [
    //                 "created_on" => $pre_yr,
    //                 "customer_from" => 0,
    //                 "customer_count" => 0
    //             ];
    //             $arrys[] = $dts;
    //         }
    //         $pre_datas = [];
    //         $pre_datas = DB::table('customer')->where('customer_from', '=', 'Web')
    //             ->select(DB::raw('month(created_on) as created_on'), 'customer_from', DB::raw('count(customer_from) as customer_count'))
    //             ->groupByRaw('year(created_on), month(created_on)')
    //             ->orderByRaw('year(created_on) ASC, month(created_on) ASC')
    //             ->whereYear('created_on', date('Y'))
    //             ->get();
    //         $pre_collections = collect($pre_datas);
    //         $unions = $pre_collections->merge($arrys);
    //         $pre_datas = $unions->sortBy('created_on')->values();
    //         $pre_datas = $pre_datas->unique('created_on')->values();
    //     }
    //     $count = count($data);

    //     if ($type == 'this_year') {
    //         if (!empty($data) && !empty($pre_datas)) {
    //             return response()->json([
    //                 'keyword' => 'success',
    //                 'message' => 'Year Wise Order Analytics listed successfully',
    //                 'mobile_analytics' => $data,
    //                 'web_analytics' => $pre_datas
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'keyword' => 'failed',
    //                 'data'        => [],
    //                 'message'      => __('No data Found')
    //             ]);
    //         }
    //     }
    // }

    public function visitor_analytics(Request $request, $type)
    {
        $weekFilter = [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
        $monthFilter = [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()];
        $data = [];
        if ($type == 'today') {
            $today = Carbon::now();
            $week = [];
            $final = [];
            for ($i = 0; $i < 24; $i++) {
                $week = $today->startOfDay()->addHour($i)->format('H');
                //push the current day and plus the mount of $i
                    $data = [];
                    $data = DB::table('visit_history')->where('user_agent', "mobile")
                        ->select('user_agent',DB::raw('DATE_FORMAT(visited_on,"%H") as visited_on'),DB::raw('count(user_agent) as customer_count'))
                        ->groupBy('visited_time')
                        ->where('visited_time', $week)->whereDate('visited_on',date('Y-m-d'))
                        ->count();
                    $ary = [];
                    $ary['visited_on'] = $week;
                    $ary['user_agent'] = 'mobile';
                    $ary['customer_count'] = $data;
                    $final[] = $ary;
            }

            $web = [];
            for ($i = 0; $i < 24; $i++) {
                $week = $today->startOfDay()->addHour($i)->format('H');
                //push the current day and plus the mount of $i
                    $data = [];
                    $data = DB::table('visit_history')->where('user_agent', "web")
                        ->select('user_agent',DB::raw('DATE_FORMAT(visited_on,"%H") as visited_on'),DB::raw('count(user_agent) as customer_count'))
                        ->groupBy('visited_time')
                        ->where('visited_time', $week)->whereDate('visited_on',date('Y-m-d'))
                        ->count();
                    $ary = [];
                    $ary['visited_on'] = $week;
                    $ary['user_agent'] = 'web';
                    $ary['customer_count'] = $data;
                    $web[] = $ary;
            }
        }
        if ($type == 'today') {
            if (!empty($final) && ($web)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Week wise visitor analytics listed successfully',
                    'mobile_analytics' => $final,
                    'web_analytics' => $web
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => __('message.failed')
                ]);
            }
        }
        if ($type == 'this_week') {
            $today = Carbon::now();
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $today->startOfWeek()->addDay($i)->format('Y-m-d'); //push the current day and plus the mount of $i
            }
            $collection = collect($week);
            $combined = $collection->combine([0, 0, 0, 0, 0, 0, 0]);
            $current_week = $combined->all();
            foreach ($week as $wk) {
                $dt = [
                    "visited_on" => $wk,
                    "user_agent" => 0,
                    "customer_count" => 0
                ];
                $arry[] = $dt;
            }

            $data = [];
            $data = DB::table('visit_history')->where('user_agent', "mobile")
                ->select(DB::raw('date(visited_on) as visited_on'), 'user_agent', DB::raw('count(user_agent) as customer_count'))
                ->groupByRaw('date(visited_on)')
                ->whereBetween('visited_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->get();

            $collection = collect($data);
            $union = $collection->merge($arry);
            $data = $union->sortBy('visited_on')->values();
            $data = $data->unique('visited_on')->values();

            $today = Carbon::now();
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $today->startOfWeek()->addDay($i)->format('Y-m-d'); //push the current day and plus the mount of $i
            }
            $collection = collect($week);
            $combined = $collection->combine([0, 0, 0, 0, 0, 0, 0]);
            $current_week = $combined->all();
            foreach ($week as $wk) {
                $dt = [
                    "visited_on" => $wk,
                    "user_agent" => 0,
                    "customer_count" => 0
                ];
                $arry[] = $dt;
            }

            $pre_datas = [];
            $pre_datas = DB::table('visit_history')->where('user_agent', "web")
            ->select(DB::raw('date(visited_on) as visited_on'), 'user_agent', DB::raw('count(user_agent) as customer_count'))
            ->groupByRaw('date(visited_on)')
                ->whereBetween('visited_on', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->get();

            $collection = collect($pre_datas);
            $union = $collection->merge($arry);
            $pre_datas = $union->sortBy('visited_on')->values();
            $pre_datas = $pre_datas->unique('visited_on')->values();
            $count = count($union);
        }
        if ($type == 'this_week') {
            if (!empty($data) && !empty($pre_datas)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Week wise visitor analytics listed successfully',
                    'mobile_analytics' => $data,
                    'web_analytics' => $pre_datas
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => __('message.failed')
                ]);
            }
        }


        if ($type == 'this_month') {
            $today = today();
            $dates = [];
            for ($i = 1; $i < $today->daysInMonth + 1; ++$i) {
                $dates[] = Carbon::createFromDate($today->year, $today->month, $i)->format('Y-m-d');
            }
            foreach ($dates as $d) {
                $dt = [
                    "visited_on" => $d,
                    "user_agent" => 0,
                    "customer_count" => 0
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('visit_history')->where('user_agent', "mobile")
            ->select(DB::raw('date(visited_on) as visited_on'), 'user_agent', DB::raw('count(user_agent) as customer_count'))
            ->groupByRaw('date(visited_on)')
                ->whereBetween('visited_on', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()])
                ->get();
            $collection = collect($data);
            $union = $collection->merge($arry);
            $data = $union->sortBy('visited_on')->values();
            $data = $data->unique('visited_on')->values();


            $today = Carbon::now()->startOfMonth();
            $previous_dates = [];
            for ($i = 1; $i < $today->daysInMonth + 1; ++$i) {
                $dates[] = Carbon::createFromDate($today->year, $today->month, $i)->format('Y-m-d');
            }
            foreach ($previous_dates as $d) {
                $dt = [
                    "visited_on" => $d,
                    "user_agent" => 0,
                    "customer_count" => 0
                ];
                $arry[] = $dt;
            }
            $pre_datas = [];
                $pre_datas = DB::table('visit_history')->where('user_agent', "web")
                ->select(DB::raw('date(visited_on) as visited_on'), 'user_agent', DB::raw('count(user_agent) as customer_count'))
            ->groupByRaw('date(visited_on)')
                ->whereBetween('visited_on', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()])
                ->get();
            $collection = collect($pre_datas);
            $union = $collection->merge($arry);
            $pre_datas = $union->sortBy('visited_on')->values();
            $pre_datas = $pre_datas->unique('visited_on')->values();
            $count = count($union);
        }
        if ($type == 'this_month') {
            if (!empty($data) && !empty($pre_datas)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Month wise visitor analytics listed successfully',
                    'mobile_analytics' => $data,
                    'web_analytics' => $pre_datas
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => __('message.failed')
                ]);
            }
        }

        if ($type == 'this_year') {
            $year = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
            foreach ($year as $yr) {
                $dt = [
                    "visited_on" => $yr,
                    "customer_from" => 0,
                    "customer_count" => 0
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('visit_history')->where('user_agent', "mobile")
            ->select(DB::raw('date(visited_on) as visited_on'), 'user_agent', DB::raw('count(user_agent) as customer_count'))
                ->groupByRaw('year(visited_on), month(visited_on)')
                ->orderByRaw('year(visited_on) ASC, month(visited_on) ASC')
                ->whereYear('visited_on', date('Y'))
                ->get();

            $collection = collect($data);
            $union = $collection->merge($arry);
            $data = $union->sortBy('visited_on')->values();
            $data = $data->unique('visited_on')->values();

            $previous_year = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];

            foreach ($previous_year as $pre_yr) {
                $dts = [
                    "visited_on" => $pre_yr,
                    "customer_from" => 0,
                    "customer_count" => 0
                ];
                $arrys[] = $dts;
            }
            $pre_datas = [];
            $pre_datas = DB::table('visit_history')->where('user_agent', "web")
            ->select(DB::raw('date(visited_on) as visited_on'), 'user_agent', DB::raw('count(user_agent) as customer_count'))
                ->groupByRaw('year(visited_on), month(visited_on)')
                ->orderByRaw('year(visited_on) ASC, month(visited_on) ASC')
                ->whereYear('visited_on', date('Y'))
                ->get();
            $pre_collections = collect($pre_datas);
            $unions = $pre_collections->merge($arrys);
            $pre_datas = $unions->sortBy('visited_on')->values();
            $pre_datas = $pre_datas->unique('visited_on')->values();
        }
        $count = count($data);
        
        if ($type == 'this_year') {
            if (!empty($data) && !empty($pre_datas)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Year wise visitor analytics listed successfully',
                    'mobile_analytics' => $data,
                    'web_analytics' => $pre_datas
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'data'        => [],
                    'message'      => __('No data Found')
                ]);
            }
        }
    }

    // public function visit_history(Request $request)
    // {
    //     $websitevisitors = new Visitors();
    //     $websitevisitors->ip_address = $_SERVER['REMOTE_ADDR'];
    //     $websitevisitors->page_type = $request->input('page_type');
    //     $Agent = new Agent();
    //     // agent detection influences the view storage path
    //     if ($Agent->isMobile()) {
    //         // you're a mobile device
    //         $websitevisitors->user_agent = 'mobile';
    //     } else {
    //         $websitevisitors->user_agent = $request->server('HTTP_USER_AGENT');
    //     }
    //     $websitevisitors->device = "web";
    //     $websitevisitors->device_platform = "web";
    //     $websitevisitors->visited_on = Server::getDateTime();
    //     if ($websitevisitors->save()) {
    //         return response()->json([
    //             'keyword' => 'success',
    //             'data'  => $websitevisitors,
    //             'message' => 'Visitor history Created',
    //         ]);
    //     } else {
    //         return response()->json([
    //             'keyword' => 'failed',
    //             'data'       => [],
    //             'message'     => __('message.failed')
    //         ]);
    //     }
    // }

    public function Most_Visitors_Time(Request $request, $type)
    {
        // if ($type == 'this_week' || $type == 'this_month') {
        $weekFilter = [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
        $monthFilter = [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()];

        if ($type == 'today') {
            $today = Carbon::now();
            $week = [];
            for ($i = 0; $i < 24; $i++) {
                $week[] = $today->startOfDay()->addHour($i)->format('H'); //push the current day and plus the mount of $i
            }
            $collection = collect($week);
            $combined = $collection->combine([1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16-00-00,17,18,19,20,21,22,23,24]);
            $current_week = $combined->all();
            foreach ($week as $wk) {
                $dt = [
                    "visited_count" => 0,
                    // "visited_time" => $wk,
                    "visited_time" => $wk,
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('website_timewise_history')
                ->select(DB::raw('ROUND(AVG(visited_count)) as visited_count'),'visited_time')
                ->groupByRaw('visited_time')
                ->orderBy('visited_time', 'desc')
                ->whereDate('visited_on', Carbon::now())
                ->get();
            $collection = collect($data);
            //   $union = $collection->union($arry);
            $union = $collection->merge($arry);
            $data = $union->sortBy('visited_time')->values();
            $data = $data->unique('visited_time')->values();
        }

        if ($type == 'this_week') {
            $today = Carbon::now();
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $today->startOfWeek()->addDay($i)->format('Y-m-d'); //push the current day and plus the mount of $i
            }
            $collection = collect($week);
            $combined = $collection->combine([0, 0, 0, 0, 0, 0, 0]);
            $current_week = $combined->all();
            foreach ($week as $wk) {
                $dt = [
                    "visited_count" => 0,
                    "visited_time" => 0,
                    "visited_on" => $wk,
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('website_timewise_history')
                ->select(DB::raw('ROUND(AVG(visited_count)) as visited_count, visited_time,visited_on'))
                ->groupByRaw('visited_time')
                ->orderBy('visited_time', 'ASC')
                ->whereBetween('visited_on', $type == 'this_week' ? $weekFilter : $monthFilter)
                ->get();
            $collection = collect($data);
            //   $union = $collection->union($arry);
            $union = $collection->merge($arry);
            $data = $union->sortBy('visited_on')->values();
            $data = $data->unique('visited_on')->values();
        }

        if ($type == 'this_month') {
            $today = today();
            // $today = Carbon::now()->startOfMonth();
            $dates = [];
            for ($i = 1; $i < $today->daysInMonth + 1; ++$i) {
                $dates[] = Carbon::createFromDate($today->year, $today->month, $i)->format('Y-m-d');
            }
            foreach ($dates as $d) {
                $dt = [
                    "visited_count" => 0,
                    "visited_time" => 0,
                    "visited_on" => $d,
                ];
                $arry[] = $dt;
            }
            $data = [];
            $data = DB::table('website_timewise_history')
                ->select(DB::raw('ROUND(AVG(visited_count)) as visited_count,visited_time, visited_on'))
                ->groupByRaw('visited_time')
                ->orderBy('visited_time', 'ASC')
                ->whereBetween('visited_on', $type == 'this_month' ? $monthFilter : $weekFilter)
                // ->whereBetween('visited_on', [Carbon::now()->startOfmonth(), Carbon::now()->endOfmonth()])
                ->get();
            $collection = collect($data);
            $union = $collection->merge($arry);
            $data = $union->sortBy('visited_on')->values();
            $data = $data->unique('visited_on')->values();
        }
        // if ($type == 'this_year') {
        //     $year = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
        //     foreach ($year as $d) {
        //         $dt = [
        //             "visited_on" => $d,
        //             "visited_count" => 0,
        //             // "visited_time" => 0,
        //         ];
        //         $arry[] = $dt;
        //     }
        //     $data = [];
        //     $data = DB::table('website_timewise_history')
        //     // ->select(DB::raw('month(visited_on) as visited_on'),DB::raw('count(page_type) as visited_count'))
        //     ->select(DB::raw('ROUND(AVG(visited_count)) as visited_count, visited_on'))
        //     ->groupByRaw('year(visited_on), month(visited_on)')
        //     ->orderByRaw('year(visited_on) ASC, month(visited_on) ASC')
        //     ->whereYear('visited_on',date('Y'))
        //     ->get();

        //     $collection = collect($data);
        //     $union = $collection->merge($arry);
        //     $data = $union->sortBy('visited_on')->values();
        //     $data = $data->unique('visited_on')->values();
        // }
        // $count = count($data);

        // if ($type == 'this_year') {
        //     if (!empty($data)) {
        //         return response()->json([
        //             'keyword' => 'success',
        //             'message' => 'Year Wise Order Analytics listed successfully',
        //             'data' => $data,
        //         ]);
        //     } else {
        //         return response()->json([
        //             'keyword' => 'failed',
        //             'data'        => [],
        //             'message'      => __('No data Found')
        //         ]);
        //     }
        // }
        if (!empty($data)) {
            return response()->json([
                'keyword' => 'success',
                'message' => 'Most visited time list',
                'data' => $data,
                // 'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'data'        => [],
                'message'      => __('message.failed')
            ]);
        }
        // } else {
        //     return response()->json([
        //         'keyword' => 'failed',
        //         'message' => 'Not type',
        //         'data' => []
        //     ]);
        // }
    }
}
