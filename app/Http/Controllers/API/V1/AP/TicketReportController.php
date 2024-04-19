<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Models\ProductCatalogue;
use App\Models\RelatedProduct;
use App\Helpers\GlobalHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use App\Http\Requests\PhotoPrintRequest;
use App\Models\GstPercentage;
use App\Models\Service;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Tickets;
use File;
use PhpOffice\PhpSpreadsheet\Writer\Pdf;
use App\Models\OrderItems;
use App\Models\TicketInbox;
use App\Http\Requests\adminticketRequest;

class TicketReportController extends Controller
{
    public function ticketreport_list(Request $request)
    {
        try {
            Log::channel("ticketreports")->info('** started the ticketreports list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
            // $filterByServiceType = ($request->filterByServiceType) ? $request->filterByServiceType : '';
            // $TypeOfStock = ($request->filterByTypeOfStock) ? $request->filterByTypeOfStock : '[]';
            $all = $request->all;


            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'created_on' => 'tickets.created_on',
                'ticket_no' => 'tickets.ticket_no',
                'order_items_id' => 'tickets.order_items_id',
                'order_id' => 'orders.order_id',
                'customer' => 'customer.customer_name',
                'mobile_no' => 'customer.mobile_no',
                'subject' => 'tickets.subject',
                'priority' => 'tickets.priority',
                'status' => 'tickets.status',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "order_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('tickets.created_on', 'tickets.ticket_no', 'orders.order_id', 'tickets.order_items_id', 'customer.customer_name', 'customer.mobile_no', 'tickets.subject', 'tickets.priority', 'tickets.status');

            $ticketreportss = Tickets::leftjoin('order_items', 'order_items.order_items_id', '=', 'tickets.order_items_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftJoin('customer', 'tickets.created_by', '=', 'customer.customer_id')
                ->select('tickets.*', 'orders.order_code', 'orders.order_code', 'customer.customer_id', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no',)
                // ->where('tickets.status', '!=', '4');
                ->whereIn('tickets.status',[0,1,2,3]);

            // $ticket_history = TicketInbox::where('tickets_id', $value['tickets_id'])
            //             ->select("ticket_inbox_id",'tickets_id','messages','reply_on','customer_id','acl_user_id')->get();

            // if ($TypeOfStock != '[]') {
            //     $TypeOfStock = json_decode($TypeOfStock, true);
            //     if ($TypeOfStock == [1]) {
            //         $ticketreportss->where('product_variant.quantity','>',0);
            //     }
            //     if ($TypeOfStock == [0]) {
            //         $ticketreportss->where('product_variant.quantity',"<=",0);
            //     }
            //     if ($TypeOfStock == [2]) {
            //         $ticketreportss->where('product_variant.status','!=',2);
            //     }
            // }


            $ticketreportss->where(function ($query) use ($searchval, $column_search, $ticketreportss) {
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
                $ticketreportss->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $ticketreportss->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $ticketreportss->where(function ($query) use ($from_date) {
                    $query->whereDate('tickets.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $ticketreportss->where(function ($query) use ($to_date) {
                    $query->whereDate('tickets.created_on', '<=', $to_date);
                });
            }

            // if (!empty($filterByServiceType) && $filterByServiceType != ' ' && $filterByServiceType != 'all') {
            //     $filterByServiceType = json_decode($filterByServiceType, true);
            //     $ticketreportss->whereIn('service.service_id', $filterByServiceType);
            // }
            if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
                $filterByStatus = json_decode($filterByStatus, true);
                $ticketreportss->whereIn('tickets.status', $filterByStatus);
            }
            // if (!empty($filterByTypeOfStock) && $filterByTypeOfStock != ' ' && $filterByTypeOfStock != 'all') {
            //     $filterByTypeOfStock = json_decode($filterByTypeOfStock, true);
            //     $ticketreportss->where('product_variant.quantity', $filterByTypeOfStock);
            // }

            // if ($TypeOfStock == [1]) {
            //     $ticketreportss->where('product_variant.quantity','>',0);
            // }
            // if ($TypeOfStock == [0]) {
            //     $ticketreportss->where('product_variant.quantity',"<=",0);
            // }

            $count = $ticketreportss->count();

            if ($offset) {
                $offset = $offset * $limit;
                $ticketreportss->offset($offset);
            }
            if ($limit) {
                $ticketreportss->limit($limit);
            }

            $ticketreportss->orderBy('tickets_id', 'DESC');
            $ticketreportss = $ticketreportss->get();

            $opened = Tickets::where('status', 1)->count();
            $closed = Tickets::where('status', 3)->count();
            $reply = Tickets::where('status', 2)->count();
            $latest = Tickets::where('status', 0)->count();

            if ($count > 0) {
                $final = [];
                foreach ($ticketreportss as $value) {
                    $ary = [];

                    $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                    $ary['ticket_no'] = $value['ticket_no'] ?? "-";
                    $ary['order_id'] = $value['order_code'] ?? "-";
                    // $ary['tickets_id'] = $value['tickets_id'];
                    // $ary['customer_id'] = $value['customer_id'];
                    $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'] ?? "-";
                    $ary['mobile_no'] = $value['mobile_no'] ?? "-";
                    $ary['subject'] = $value['subject'];
                    if ($value['priority'] == 2) {
                        $ary['priority'] = "Low";
                    }
                    if ($value['priority'] == 1) {
                        $ary['priority'] = "Medium";
                    }
                    if ($value['priority'] == 0) {
                        $ary['priority'] = "High";
                    }
                    $ary['status'] = $value['status'] ?? "-";


                    // $ary['history'] = $ticket_history;

                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("ticketreports")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Ticket reports listed successfully'),
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
                    'count' => $count
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("ticketreports")->error($exception);
            Log::channel("ticketreports")->error('** end the ticketreports list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function ticketreport_excel(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        // $filterByServiceType = ($request->filterByServiceType) ? $request->filterByServiceType : '';
        // $TypeOfStock = ($request->filterByTypeOfStock) ? $request->filterByTypeOfStock : '[]';
        $all = $request->all;

        $ticketreport = Tickets::leftjoin('order_items', 'order_items.order_items_id', '=', 'tickets.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->leftJoin('customer', 'tickets.created_by', '=', 'customer.customer_id')
            ->select('tickets.*', 'orders.order_code', 'orders.order_code', 'customer.customer_id', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no',)
            // ->where('tickets.status', '!=', '4');
            ->whereIn('tickets.status',[0,1,2,3]);


        // if ($TypeOfStock != '[]') {
        //     $TypeOfStock = json_decode($TypeOfStock, true);
        //     if ($TypeOfStock == [1]) {
        //         $ticketreport->where('product_variant.quantity','>',0);
        //     }
        //     if ($TypeOfStock == [0]) {
        //         $ticketreport->where('product_variant.quantity',"<=",0);
        //     }
        //     if ($TypeOfStock == [2]) {
        //         $ticketreport->where('product_variant.status','!=',2);
        //     }
        // }

        if (!empty($from_date)) {
            $ticketreport->where(function ($query) use ($from_date) {
                $query->whereDate('tickets.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $ticketreport->where(function ($query) use ($to_date) {
                $query->whereDate('tickets.created_on', '<=', $to_date);
            });
        }

        if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
            $filterByStatus = json_decode($filterByStatus, true);
            $ticketreport->whereIn('tickets.status', $filterByStatus);
        }

        $ticketreport = $ticketreport->get();

        // $totalAmount_count = $ticketreport->pluck('totalamount')->sum();

        $count = count($ticketreport);

        $s = 1;
        if ($count > 0) {
            $overll = [];
            foreach ($ticketreport as $value) {
                $ary = [];
                $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                $ary['ticket_no'] = $value['ticket_no'] ?? "-";
                $ary['order_id'] = $value['order_code'] ?? "-";
                // $ary['tickets_id'] = $value['tickets_id'];
                // $ary['customer_id'] = $value['customer_id'];
                $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'] ?? "-";
                $ary['mobile_no'] = $value['mobile_no'] ?? "-";
                $ary['subject'] = $value['subject'] ?? "-";
                if ($value['priority'] == 2) {
                    $ary['priority'] = "Low";
                }
                if ($value['priority'] == 1) {
                    $ary['priority'] = "Medium";
                }
                if ($value['priority'] == 0) {
                    $ary['priority'] = "High";
                }
                // $ary['status'] = $value['status'] ?? "-";
                if ($value['status'] == 0) {
                    $ary['status'] = "latest";
                }
                if ($value['status'] == 1) {
                    $ary['status'] = "opened";
                }
                if ($value['status'] == 2) {
                    $ary['status'] = "reply";
                }
                if ($value['status'] == 3) {
                    $ary['status'] = "closed";
                }

                $overll[] = $ary;
            }
            $s++;


            $excel_report_title = "Ticket Report";

            $spreadsheet = new Spreadsheet();

            //Set document properties
            $spreadsheet->getProperties()->setCreator("Technogenesis")
                ->setLastModifiedBy("Technogenesis")
                ->setTitle("Credit Accepted Data")
                ->setSubject("Credit Accepted Data")
                ->setDescription("Credit Accepted Data")
                ->setKeywords("Credit Accepted Data")
                ->setCategory("Credit Accepted Data");

            $spreadsheet->getProperties()->setCreator("technogenesis.in")
                ->setLastModifiedBy("Technognesis");

            $spreadsheet->setActiveSheetIndex(0);

            $sheet = $spreadsheet->getActiveSheet();

            //name the worksheet
            $sheet->setTitle($excel_report_title);


            $sheet->setCellValue('A1', 'Date');
            $sheet->setCellValue('B1', 'Ticket No');
            $sheet->setCellValue('C1', 'Order ID');
            $sheet->setCellValue('D1', 'Customer');
            $sheet->setCellValue('E1', 'Mobile No');
            $sheet->setCellValue('F1', 'Subject');
            $sheet->setCellValue('G1', 'Priority');
            $sheet->setCellValue('H1', 'Status');
            // $sheet->setCellValue('I1', 'Type Of Stock');

            // $i = 2;
            // $total = 0;

            // foreach ($ticketreport as $location) {
            //     $sheet->setCellValue('A' . $i, $location['Date']);
            //     $sheet->setCellValue('B' . $i, $location['Product ID']);
            //     $sheet->setCellValue('C' . $i, $location['Service Type']);
            //     $sheet->setCellValue('D' . $i, $location['Product Name']);
            //     $sheet->setCellValue('E' . $i, $location['Variant Details']);
            //     $sheet->setCellValue('F' . $i, $location['MRP(₹)']);
            //     $sheet->setCellValue('G' . $i, $location['Selling Price(₹)']);
            //     $sheet->setCellValue('H' . $i, $location['Quantity']);
            //     $sheet->setCellValue('I' . $i, $location['Type Of Stock']);
            //     $sheet->getStyle("F")->getNumberFormat()->setFormatCode('0.00');
            //     $sheet->getStyle("G")->getNumberFormat()->setFormatCode('0.00');
            //     $total = $total + $location['mrp'];
            //     $total2 = $total2 + $location['selling_price'];
            //     $i++;
            // }
            //here your $i val already incremented in foreach() loop
            // $sheet->setCellValue('E' . $i, "Total Amount")->getStyle('E' . $i, "Total Amount")->getFont()->setBold(true);
            // $sheet->setCellValue('F' . $i, $total)->getStyle('F' . $i, "Total Amount")->getFont()->setBold(true);
            // $sheet->setCellValue('G' . $i, $total2)->getStyle('G' . $i, "Total Amount")->getFont()->setBold(true);


            $conditional1 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
            $conditional1->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS);
            $conditional1->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_LESSTHAN);
            $conditional1->addCondition('0');
            $conditional1->getStyle()->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
            $conditional1->getStyle()->getFont()->setBold(true);

            $conditionalStyles = $spreadsheet->getActiveSheet()->getStyle('B2')->getConditionalStyles();
            $conditional1->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            //make the font become bold
            $conditional1->getStyle('A2')->getFont()->setBold(true);
            $conditional1->getStyle('A1')->getFont()->setBold(true);
            $conditional1->getStyle('B1')->getFont()->setBold(true);
            $conditional1->getStyle('C1')->getFont()->setBold(true);
            $conditional1->getStyle('C3')->getFont()->setBold(true);
            $conditional1->getStyle('D3')->getFont()->setBold(true);
            $conditional1->getStyle('E3')->getFont()->setBold(true);
            $conditional1->getStyle('F3')->getFont()->setBold(true);
            $conditional1->getStyle('G1')->getFont()->setBold(true);
            $conditional1->getStyle('H1')->getFont()->setBold(true);
            // $conditional1->getStyle('I1')->getFont()->setBold(true);
            $conditional1->getStyle('A3')->getFont()->setSize(16);
            $conditional1->getStyle('A3')->getFill()->getStartColor()->setARGB('#333');

            //make the font become bold
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);
            // $sheet->getStyle('A1')->getFont()->setSize(16);
            $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('#333');

            for ($col = ord('A'); $col <= ord('Q'); $col++) { //set column dimension
                $sheet->getColumnDimension(chr($col))->setAutoSize(true);
            }

            //retrieve  table data
            $overll[] = array('', '',  '', '');

            //Fill data
            $sheet->fromArray($overll, null, 'A2');
            $writer = new Xls($spreadsheet);
            $file_name = "ticketreport-report-data.xls";
            $fullpath =  storage_path() . '/app/ticketreport_report' . $file_name;
            // print_r($fullpath);exit;
            $writer->save($fullpath); // download file
            // return $file_name;
            // return response()->download($fullpath, "sales_report.xls");
            return response()->download(storage_path('app/ticketreport_reportticketreport-report-data.xls'), "ticketreport_reportticketreport-report-data.xls");
        }
    }
}
