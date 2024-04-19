<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use App\Models\BillItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class GstReportController extends Controller
{
    public function gst_report_list(Request $request)
    {
        try {
            Log::channel("gstreport")->info('** started the gst report list method **');

            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';

            $gstreport = BillItems::leftjoin('bill', 'bill_item.bill_id', '=', 'bill.bill_id')
                ->leftjoin('order_items', 'bill_item.order_items_id', '=', 'order_items.order_items_id')
                ->leftjoin('orders', 'order_items.order_id', '=', 'orders.order_id')
                // ->leftjoin('customer', 'order_items.created_by', '=', 'customer.customer_id')
                ->leftJoin('customer', function ($leftJoin) {
                    $leftJoin->on('customer.customer_id', '=', 'orders.created_by')
                        ->where('orders.customer_id', '!=', NULL);
                })
                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                        ->where('orders.customer_id', '=', NULL);
                })
                ->leftjoin('product', 'order_items.product_id', '=', 'product.product_id')
                ->leftjoin('gst_percentage', 'product.gst_percentage', '=', 'gst_percentage.gst_percentage_id')
                ->select('orders.customer_id','customer.mobile_no','bill.bill_no', 'bill.created_on', 'customer.customer_first_name', 'customer.customer_last_name', 'orders.billing_gst_no', 'order_items.sub_total', 'gst_percentage.gst_percentage', 'orders.billing_state_id', 'orders.shipping_cost','bulk_order_enquiry.contact_person_name','bulk_order_enquiry.mobile_no as bulk_order_mobile_no','order_items.gst_value', 'order_items.unit_price', 'order_items.quantity', 'order_items.delivery_charge','order_items.taxable_amount');

            if (!empty($from_date)) {
                $gstreport->where(function ($query) use ($from_date) {
                    $query->whereDate('bill.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $gstreport->where(function ($query) use ($to_date) {
                    $query->whereDate('bill.created_on', '<=', $to_date);
                });
            }

            $count = $gstreport->count();

            Log::channel("gstreport")->info("request value :: $from_date :: $to_date");
            $gstreport->orderBy('bill_item_id', 'DESC');
            $gstreport = $gstreport->get();
            $final = [];
            $count = $gstreport->count();
            if ($count > 0) {
                foreach ($gstreport as $value) {
                    $ary = [];
                    $ary['bill_date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                    $ary['bill_no'] = $value['bill_no'] ?? "-";
                    if(!empty($value['customer_id'] != null)){
                        $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                        $ary['mobile_no'] = $value['mobile_no'];
                    } else {
                        $ary['customer_name'] = $value['contact_person_name'];
                        $ary['mobile_no'] = $value['bulk_order_mobile_no'];
                    }
                    $ary['gst_number'] = $value['billing_gst_no'] ?? "-";

                    if(!empty($value['customer_id'])){
                    $ary['amount'] = $value['sub_total'] ?? "-";

                    if ($value['billing_state_id'] == 33) {

                        $gst_calc = 1 + ($value['gst_value'] / 100 * 1);

                        $exc_gst = $value['sub_total'] / $gst_calc;

                        $round_exc_gst = sprintf("%.2f", $exc_gst);

                        $gst_percent_rates = $value['sub_total'] -  $round_exc_gst;

                        $ary['cgst_percent'] = $value['gst_value'] / 2 ?? "-";

                        $ary['cgst_amount'] = sprintf("%.2f", $gst_percent_rates / 2);

                        $ary['sgst_percent'] = $value['gst_value'] / 2 ?? "-";

                        $ary['sgst_amount'] =  sprintf("%.2f", $gst_percent_rates / 2);

                        $ary['igst_percent'] = "-";

                        $ary['igst_amount'] = "-";

                        $ary['round_off'] =   sprintf("%.2f", $ary['cgst_amount'] +   $ary['sgst_amount'] +  $ary['amount']);

                        $ary['bill_amount'] = sprintf("%.2f", $value['delivery_charge'] + $ary['round_off']);
                    } else {
                        $gst_calc = 1 + ($value['gst_value'] / 100 * 1);

                        $exc_gst = $value['sub_total'] / $gst_calc;

                        $round_exc_gst = sprintf("%.2f", $exc_gst);

                        $gst_percent_rates = $value['sub_total'] -  $round_exc_gst;

                        $ary['cgst_percent'] = "-";

                        $ary['cgst_amount'] = "-";

                        $ary['sgst_percent'] = "-";

                        $ary['sgst_amount'] = "-";

                        $ary['igst_percent'] = $value['gst_value'] ?? "-";

                        $ary['igst_amount'] = sprintf("%.2f", $gst_percent_rates);

                        $ary['round_off'] =  sprintf("%.2f", $ary['igst_amount'] +  $ary['amount']);

                        $ary['bill_amount'] = sprintf("%.2f", $value['delivery_charge'] + $ary['round_off']);
                    }
                }
                else{

                    // $subTotal = sprintf("%.2f", $value['unit_price'] * $value['quantity']);
                    // $ary['amount'] = $subTotal ?? "-";

                    $subTotal = $value['taxable_amount'];
                    $ary['amount'] = $subTotal ?? "-";

                    if ($value['billing_state_id'] == 33) {

                        $gst_calc = 1 + ($value['gst_value'] / 100 * 1);

                        $exc_gst = $subTotal / $gst_calc;

                        $round_exc_gst = sprintf("%.2f", $exc_gst);

                        $gst_percent_rates = $subTotal -  $round_exc_gst;

                        $ary['cgst_percent'] = $value['gst_value'] / 2 ?? "-";

                        $ary['cgst_amount'] = sprintf("%.2f", $gst_percent_rates / 2);

                        $ary['sgst_percent'] = $value['gst_value'] / 2 ?? "-";

                        $ary['sgst_amount'] =  sprintf("%.2f", $gst_percent_rates / 2);

                        $ary['igst_percent'] = "-";

                        $ary['igst_amount'] = "-";

                        $ary['round_off'] =   sprintf("%.2f", $ary['cgst_amount'] +   $ary['sgst_amount'] +  $subTotal);

                        $ary['bill_amount'] = sprintf("%.2f", $value['delivery_charge'] + $ary['round_off']);
                    } else {
                        $gst_calc = 1 + ($value['gst_value'] / 100 * 1);

                        $exc_gst = $subTotal / $gst_calc;

                        $round_exc_gst = sprintf("%.2f", $exc_gst);

                        $gst_percent_rates = $subTotal -  $round_exc_gst;

                        $ary['cgst_percent'] = "-";

                        $ary['cgst_amount'] = "-";

                        $ary['sgst_percent'] = "-";

                        $ary['sgst_amount'] = "-";

                        $ary['igst_percent'] = $value['gst_value'] ?? "-";

                        $ary['igst_amount'] = sprintf("%.2f", $gst_percent_rates);

                        $ary['round_off'] =  sprintf("%.2f", $ary['igst_amount'] +  $subTotal);

                        $ary['bill_amount'] = sprintf("%.2f", $value['delivery_charge'] + $ary['round_off']);
                    }
                }

                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("gstreport")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Gst report listed Successfully'),
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
            Log::channel("gstreport")->error('** start the gst report list error method **');
            Log::channel("gstreport")->error($exception);
            Log::channel("gstreport")->error('** end the gst report list error method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    function gst_report_list_Excel(Request $request)
    {
        Log::channel("gstreport")->info('** started the admin gst report list method **');
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';


        $gstreport = BillItems::leftjoin('bill', 'bill_item.bill_id', '=', 'bill.bill_id')
            ->leftjoin('order_items', 'bill_item.order_items_id', '=', 'order_items.order_items_id')
            ->leftjoin('orders', 'order_items.order_id', '=', 'orders.order_id')
            // ->leftjoin('customer', 'order_items.created_by', '=', 'customer.customer_id')
            ->leftJoin('customer', function ($leftJoin) {
                $leftJoin->on('customer.customer_id', '=', 'orders.created_by')
                    ->where('orders.customer_id', '!=', NULL);
            })
            ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                    ->where('orders.customer_id', '=', NULL);
            })
            ->leftjoin('product', 'order_items.product_id', '=', 'product.product_id')
            ->leftjoin('gst_percentage', 'product.gst_percentage', '=', 'gst_percentage.gst_percentage_id')
            ->select('orders.customer_id','bill.bill_no', 'bill.created_on', 'customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no','orders.billing_gst_no', 'order_items.sub_total', 'gst_percentage.gst_percentage', 'orders.billing_state_id', 'orders.shipping_cost','bulk_order_enquiry.contact_person_name','bulk_order_enquiry.mobile_no as bulk_order_mobile_no','order_items.gst_value', 'order_items.unit_price', 'order_items.quantity', 'order_items.delivery_charge','order_items.taxable_amount');

        if (!empty($from_date)) {
            $gstreport->where(function ($query) use ($from_date) {
                $query->whereDate('bill.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $gstreport->where(function ($query) use ($to_date) {
                $query->whereDate('bill.created_on', '<=', $to_date);
            });
        }
        $gstreport = $gstreport->get();
        $count = count($gstreport);

        $cgst = 0;
        $sgst = 0;
        $igst = 0;
        $roundoff = 0;
        $bill_amt = 0;
        $s = 1;
        if ($count > 0) {
            $overll = [];

            foreach ($gstreport as $value) {
                $ary = [];
                $ary['bill_date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                $ary['bill_no'] = $value['bill_no'] ?? "-";
                if(!empty($value['customer_id'] != null)){
                    $ary['customer_name'] = !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                    // $ary['mobile_no'] = $value['mobile_no'];
                } else {
                    $ary['customer_name'] = $value['contact_person_name'];
                    // $ary['mobile_no'] = $value['bulk_order_mobile_no'];
                }
                $ary['gst_number'] = $value['billing_gst_no'] ?? "-";

                if(!empty($value['customer_id'])){
                $ary['amount'] = $value['sub_total'] ?? "-";

                if ($value['billing_state_id'] == 33) {

                    $gst_calc = 1 + ($value['gst_value'] / 100 * 1);

                    $exc_gst = $value['sub_total'] / $gst_calc;

                    $round_exc_gst = sprintf("%.2f", $exc_gst);

                    $gst_percent_rates = $value['sub_total'] -  $round_exc_gst;

                    $ary['cgst_percent'] = $value['gst_value'] / 2 ?? "-";

                    $ary['cgst_amount'] = sprintf("%.2f", $gst_percent_rates / 2);

                    $cgst = $cgst + $ary['cgst_amount'];

                    $ary['sgst_percent'] = $value['gst_value'] / 2 ?? "-";

                    $ary['sgst_amount'] =  sprintf("%.2f", $gst_percent_rates / 2);

                    $sgst = $sgst + $ary['sgst_amount'];

                    $ary['igst_percent'] = "-";

                    $ary['igst_amount'] = "-";

                    $ary['round_off'] =  sprintf("%.2f", $ary['cgst_amount'] +   $ary['sgst_amount'] +  $ary['amount']);

                    $ary['bill_amount'] = sprintf("%.2f", $value['delivery_charge'] + $ary['round_off']);

                } else {
                    $gst_calc = 1 + ($value['gst_value'] / 100 * 1);

                    $exc_gst = $value['sub_total'] / $gst_calc;

                    $round_exc_gst = sprintf("%.2f", $exc_gst);

                    $gst_percent_rates = $value['sub_total'] -  $round_exc_gst;

                    $ary['cgst_percent'] = "-";

                    $ary['cgst_amount'] = "-";

                    $ary['sgst_percent'] = "-";

                    $ary['sgst_amount'] = "-";

                    $ary['igst_percent'] = $value['gst_value'] ?? "-";

                    $ary['igst_amount'] = sprintf("%.2f", $gst_percent_rates);

                    $igst = $igst + $ary['igst_amount'];

                    $ary['round_off'] =  sprintf("%.2f", $ary['igst_amount'] +  $ary['amount']);

                    $ary['bill_amount'] = sprintf("%.2f", $value['delivery_charge'] + $ary['round_off']);
                }
                $roundoff = sprintf("%.2f", $roundoff + $ary['round_off']);
                $bill_amt = sprintf("%.2f", $bill_amt + $ary['bill_amount']);
            }
            else{
                // $subTotal = sprintf("%.2f", $value['unit_price'] * $value['quantity']);

                $subTotal = $value['taxable_amount'];
                $ary['amount'] = $subTotal ?? "-";

                if ($value['billing_state_id'] == 33) {

                    $gst_calc = 1 + ($value['gst_value'] / 100 * 1);

                    $exc_gst = $subTotal / $gst_calc;

                    $round_exc_gst = sprintf("%.2f", $exc_gst);

                    $gst_percent_rates = $subTotal -  $round_exc_gst;

                    $ary['cgst_percent'] = $value['gst_value'] / 2 ?? "-";

                    $ary['cgst_amount'] = sprintf("%.2f", $gst_percent_rates / 2);

                    $cgst = $cgst + $ary['cgst_amount'];

                    $ary['sgst_percent'] = $value['gst_value'] / 2 ?? "-";

                    $ary['sgst_amount'] =  sprintf("%.2f", $gst_percent_rates / 2);

                    $sgst = $sgst + $ary['sgst_amount'];

                    $ary['igst_percent'] = "-";

                    $ary['igst_amount'] = "-";

                    $ary['round_off'] =  sprintf("%.2f", $ary['cgst_amount'] +   $ary['sgst_amount'] +  $ary['amount']);

                    $ary['bill_amount'] = sprintf("%.2f", $value['delivery_charge'] + $ary['round_off']);

                } else {
                    $gst_calc = 1 + ($value['gst_value'] / 100 * 1);

                    $exc_gst = $subTotal / $gst_calc;

                    $round_exc_gst = sprintf("%.2f", $exc_gst);

                    $gst_percent_rates = $subTotal -  $round_exc_gst;

                    $ary['cgst_percent'] = "-";

                    $ary['cgst_amount'] = "-";

                    $ary['sgst_percent'] = "-";

                    $ary['sgst_amount'] = "-";

                    $ary['igst_percent'] = $value['gst_value'] ?? "-";

                    $ary['igst_amount'] = sprintf("%.2f", $gst_percent_rates);

                    $igst = $igst + $ary['igst_amount'];

                    $ary['round_off'] =  sprintf("%.2f", $ary['igst_amount'] +  $ary['amount']);

                    $ary['bill_amount'] = sprintf("%.2f", $value['delivery_charge'] + $ary['round_off']);
                }
                $roundoff = sprintf("%.2f", $roundoff + $ary['round_off']);
                $bill_amt = sprintf("%.2f", $bill_amt + $ary['bill_amount']);
            }
                $overll[] = $ary;
                
            }
            $s++;
            $excel_report_title = "Gst Report";
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
            $sheet->setCellValue('A1', 'Bill Date');
            $sheet->setCellValue('B1', 'Bill No');
            $sheet->setCellValue('C1', 'Customer');
            $sheet->setCellValue('D1', 'GST Number');
            $sheet->setCellValue('E1', 'Amount (₹)');
            $sheet->setCellValue('F1', 'CGST (%)');
            $sheet->setCellValue('G1', 'CGST (₹)');
            $sheet->setCellValue('H1', 'SGST (%)');
            $sheet->setCellValue('I1', 'SGST (₹)');
            $sheet->setCellValue('J1', 'IGST (%)');
            $sheet->setCellValue('K1', 'IGST (₹)');
            $sheet->setCellValue('L1', 'Round off (₹)');
            $sheet->setCellValue('M1', 'Bill Amount (₹)');

            $i = 2;
            $total = 0;
            $refund = 0;
            foreach ($gstreport as $location) {
                $sheet->setCellValue('A' . $i, $location['Bill Date']);
                $sheet->setCellValue('B' . $i, $location['Bill No']);
                $sheet->setCellValue('C' . $i, $location['Customer']);
                $sheet->setCellValue('D' . $i, $location['GST Number']);
                $sheet->setCellValue('E' . $i, $location['Amount (₹)']);
                $sheet->setCellValue('F' . $i, $location['CGST (%)']);
                $sheet->setCellValue('G' . $i, $location['CGST (₹)']);
                $sheet->setCellValue('H' . $i, $location['SGST (%)']);
                $sheet->setCellValue('I' . $i, $location['SGST (₹)']);
                $sheet->setCellValue('J' . $i, $location['IGST (%)']);
                $sheet->setCellValue('K' . $i, $location['IGST (₹)']);
                $sheet->setCellValue('L' . $i, $location['Round off (₹)']);
                $sheet->setCellValue('M' . $i, $location['Bill Amount (₹)']);
                $sheet->getStyle("E")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("G")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("I")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("K")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("L")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("M")->getNumberFormat()->setFormatCode('0.00');
                if(!empty($location['customer_id'])){
                $total = $total + $location['sub_total'];
                }
                else{
                $total = $total + $location['unit_price'] * $location['quantity'];
                }
                $i++;
            }
            //here your $i val already incremented in foreach() loop
            $sheet->setCellValue('D' . $i, "Total(₹)")->getStyle('D' . $i, "Total")->getFont()->setBold(true);
            $sheet->setCellValue('E' . $i, $total)->getStyle('E' . $i, "Total")->getFont()->setBold(true);
            $sheet->setCellValue('G' . $i, $cgst)->getStyle('G' . $i, "Total")->getFont()->setBold(true);
            $sheet->setCellValue('I' . $i, $sgst)->getStyle('I' . $i, "Total")->getFont()->setBold(true);
            $sheet->setCellValue('K' . $i, $igst)->getStyle('K' . $i, "Total")->getFont()->setBold(true);
            $sheet->setCellValue('L' . $i, $roundoff)->getStyle('L' . $i, "Total")->getFont()->setBold(true);
            $sheet->setCellValue('M' . $i, $bill_amt)->getStyle('M' . $i, "Total")->getFont()->setBold(true);
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
            $sheet->getStyle('A1:M1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('#333');
            for ($col = ord('A'); $col <= ord('Q'); $col++) { //set column dimension
                $sheet->getColumnDimension(chr($col))->setAutoSize(true);
            }
            //retrieve  table data
            $overll[] = array('', '', '', '');
            //Fill data
            $sheet->fromArray($overll, null, 'A2');
            $writer = new Xls($spreadsheet);
            $file_name = "gst-report-data.xls";
            $fullpath = storage_path() . '/app/gst_report' . $file_name;
            $writer->save($fullpath); // download file
            return response()->download(storage_path('app/gst_reportgst-report-data.xls'), "gst_report.xls");
        }
    }
}
