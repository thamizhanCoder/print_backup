<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use App\Models\CancelReason;
use App\Models\OrderItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class RefundReportController extends Controller
{
    public function refund_reason()
    {

        $get_reason = DB::table('cancel_reason')->get();

       

        if (!empty($get_reason)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Reason listed successfully'),
                    'data' => $get_reason
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => []
                ]
            );
        }
    }

    public function refund_report_list(Request $request)
    {
        try {
            Log::channel("refundsreport")->info('** started the refunds report list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByReason = ($request->filterByReason) ? $request->filterByReason : '';

            $refund = OrderItems::where('order_items.is_refund', 1)
            ->leftjoin('orders','orders.order_id','=','order_items.order_id')
            ->leftjoin('product','product.product_id','=','order_items.product_id')
            ->leftjoin('customer','customer.customer_id','=','order_items.created_by')
            ->leftjoin('cancel_reason','cancel_reason.cancel_reason_id','=','order_items.cancel_reason_id')
            ->select('order_items.*', DB::raw('ROW_NUMBER() OVER (ORDER BY order_items_id DESC) AS SrNo'),'orders.order_date','orders.order_code','customer.customer_first_name','customer.customer_last_name','customer.mobile_no','product.mrp','orders.payment_mode','orders.payment_transcation_id','orders.shipping_cost','cancel_reason.reason');
                          
            if (!empty($from_date)) {
                $refund->where(function ($query) use ($from_date) {
                    $query->whereDate('order_items.refunded_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $refund->where(function ($query) use ($to_date) {
                    $query->whereDate('order_items.refunded_on', '<=', $to_date);
                });
            }

            if (!empty($filterByReason) && $filterByReason != '[]' && $filterByReason != 'all') {
                $filterByReason = json_decode($filterByReason, true);
                $refund->whereIn('order_items.cancel_reason_id', $filterByReason)->orwhereIn('order_items.cancel_reason',$filterByReason);
            }

            $count = $refund->count();

            if ($offset) {
                $offset = $offset * $limit;
                $refund->offset($offset);
            }
            if ($limit) {
                $refund->limit($limit);
            }
            Log::channel("refundsreport")->info("request value :: $limit :: $offset ::");
            $refund->orderBy('order_items_id', 'DESC');
            $refund = $refund->get();
            $final = [];
            $count = $refund->count();
            if ($count > 0) {
                foreach ($refund as $value) {
                    $ary = [];
                    $ary['s_no'] = $value['SrNo'];
                    $ary['order_items_id'] = $value['order_items_id'];
                    $ary['order_date'] = date('d-m-Y', strtotime($value['order_date'])) ?? "-";
                    $ary['order_id'] = $value['order_code'] ?? "-";
                    $ary['product_id'] = $value['product_code'] ?? "-";
                    $ary['customer_name'] =  !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    $ary['mobile_no'] = $value['mobile_no'] ?? "-";
                    $ary['reason'] = $value['reason'] ?? "-";
                    if($value['cancel_reason_id'] == 7)
                    {
                        $ary['reason'] = $value['cancel_reason'] ?? "-";
                    }
                    $ary['is_refund'] = $value['is_refund'] ?? "-";
                    $ary['refund_date'] = date('d-m-Y', strtotime($value['refunded_on'])) ?? "-";
                    $ary['order_amount'] = number_format($value['sub_total'] + $value['delivery_charge'],2)?? "-";
                    $ary['refund_amount'] = $value['refund_amount'] ?? "-";
                    if( $value['is_refund']==0){
                        $ary['status']= 'Non Refunded';
                    }
                    if( $value['is_refund']==1){
                           $ary['status']= 'Refunded';
                    }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("refundsreport")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Refund report listed successfully'),
                    'data' => $final,
                    'count' => $count
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
            Log::channel("refundsreport")->error('** start the refund report list error method **');
            Log::channel("refundsreport")->error($exception);
            Log::channel("refundsreport")->error('** end the refunds report list error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    function refund_report_Excel(Request $request)
    {
        Log::channel("")->info('** started the admin customer list method **');
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByReason = ($request->filterByReason) ? $request->filterByReason : '';

        $refund = OrderItems::where('order_items.is_refund', 1)
        ->leftjoin('orders','orders.order_id','=','order_items.order_id')
        ->leftjoin('product','product.product_id','=','order_items.product_id')
        ->leftjoin('customer','customer.customer_id','=','order_items.created_by')
        ->leftjoin('cancel_reason','cancel_reason.cancel_reason_id','=','order_items.cancel_reason_id')
        ->select('order_items.*', DB::raw('ROW_NUMBER() OVER (ORDER BY order_items_id DESC) AS SrNo'),'orders.order_date','orders.order_code','customer.customer_first_name','customer.customer_last_name','customer.mobile_no','product.mrp','orders.payment_mode','orders.payment_transcation_id','orders.shipping_cost','cancel_reason.reason');
     
        if (!empty($from_date)) {
            $refund->where(function ($query) use ($from_date) {
                $query->whereDate('order_items.refunded_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $refund->where(function ($query) use ($to_date) {
                $query->whereDate('order_items.refunded_on', '<=', $to_date);
            });
        }

        if (!empty($filterByReason) && $filterByReason != '[]' && $filterByReason != 'all') {
            $filterByReason = json_decode($filterByReason, true);
            $refund->whereIn('order_items.cancel_reason_id', $filterByReason)->orwhereIn('order_items.cancel_reason',$filterByReason);
        }

        $get_refunds = $refund->get();
        $count = count($get_refunds);

      
        $s = 1;
        if ($count > 0) {
            $overll = [];
           
            foreach ($get_refunds as $value) {
                $ary = [];
                $ary['s_no'] = $value['SrNo'];
                $ary['date'] = date('d-m-Y', strtotime($value['order_date'])) ?? "-";
                $ary['order_id'] = $value['order_code'] ?? "-";
                $ary['product_id'] = $value['product_code'] ?? "-";
                $ary['customer_name'] =  !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                $ary['mobile_no'] = $value['mobile_no'] ?? "-";
                $ary['reason'] = $value['reason'] ?? "-";
                if($value['cancel_reason_id'] == 7)
                {
                    $ary['reason'] = $value['cancel_reason'] ?? "-";
                }
                $ary['refund_date'] = date('d-m-Y', strtotime($value['refunded_on'])) ?? "-";
                $ary['order_amount'] =number_format($value['sub_total'] + $value['delivery_charge'],2) ?? "-";
                $ary['refund_amount'] = $value['refund_amount'] ?? "-";
                $overll[] = $ary;
            }
            $s++;
            $excel_report_title = "Refund Report";
            $spreadsheet = new Spreadsheet();
            //Set document properties
            $spreadsheet->getProperties()->setCreator("Technogenesis")
                ->setLastModifiedBy("Technogenesis")
                ->setTitle("Employee List")
                ->setSubject("Employee List")
                ->setDescription("Employee List")
                ->setKeywords("Employee List")
                ->setCategory("Employee List");
            $spreadsheet->getProperties()->setCreator("technogenesis.in")
                ->setLastModifiedBy("Technogenesis");
            $spreadsheet->setActiveSheetIndex(0);
            $sheet = $spreadsheet->getActiveSheet();
            //name the worksheet
            $sheet->setTitle($excel_report_title);
            $sheet->setCellValue('A1', 'S No');
            $sheet->setCellValue('B1', 'Order Date');
            $sheet->setCellValue('C1', 'Order ID');
            $sheet->setCellValue('D1', 'Product ID');
            $sheet->setCellValue('E1', 'Customer Name');
            $sheet->setCellValue('F1', 'Mobile No');
            $sheet->setCellValue('G1', 'Reason');
            $sheet->setCellValue('H1', 'Refund Date');
            $sheet->setCellValue('I1', 'Order Amount(₹)');
            $sheet->setCellValue('J1', 'Refund Amount(₹)');

            $i = 2;
            $total = 0;
            $refund=0;
            foreach ($get_refunds as $location) {
                $sheet->setCellValue('A' . $i, $location['S No']);
                $sheet->setCellValue('B' . $i, $location['Order Date']);
                $sheet->setCellValue('C' . $i, $location['Order ID']);
                $sheet->setCellValue('D' . $i, $location['Product ID']);
                $sheet->setCellValue('E' . $i, $location['Customer Name']);
                $sheet->setCellValue('F' . $i, $location['Mobile No']);
                $sheet->setCellValue('G' . $i, $location['Reason']);
                $sheet->setCellValue('H' . $i, $location['Refund Date']);
                $sheet->setCellValue('I' . $i, $location['Order Amount(₹)']);
                $sheet->setCellValue('J' . $i, $location['Refund Amount(₹)']);
                $sheet->getStyle("I")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("J")->getNumberFormat()->setFormatCode('0.00');
                $total = $total + $location['sub_total'] +$location['delivery_charge'];
                $refund = $refund + $location['refund_amount'];
                $i++;
            }
            //here your $i val already incremented in foreach() loop
            $sheet->setCellValue('H' . $i, "Total(₹)")->getStyle('H' . $i, "Total")->getFont()->setBold( true );
            $sheet->setCellValue('I' . $i, $total)->getStyle('I' . $i, "Total")->getFont()->setBold( true );
            $sheet->setCellValue('J' . $i, $refund)->getStyle('J' . $i, "Total")->getFont()->setBold( true );
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
            $conditional1->getStyle('C3')->getFont()->setBold(true);
            $conditional1->getStyle('D3')->getFont()->setBold(true);
            $conditional1->getStyle('E3')->getFont()->setBold(true);
            $conditional1->getStyle('F3')->getFont()->setBold(true);
            $conditional1->getStyle('A3')->getFont()->setSize(16);
            $conditional1->getStyle('A3')->getFill()->getStartColor()->setARGB('#333');
            //make the font become bold
            $sheet->getStyle('A1:J1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('#333');
            for ($col = ord('A'); $col <= ord('Q'); $col++) { //set column dimension
                $sheet->getColumnDimension(chr($col))->setAutoSize(true);
            }
            //retrieve  table data
            $overll[] = array('', '', '', '');
            //Fill data
            $sheet->fromArray($overll, null, 'A2');
            $writer = new Xls($spreadsheet);
            $file_name = "customer-report-data.xls";
            $fullpath = storage_path() . '/app/customer_report' . $file_name;
            $writer->save($fullpath); // download file
            return response()->download(storage_path('app/customer_reportcustomer-report-data.xls'), "customer_report.xls");
        }
    }
}
