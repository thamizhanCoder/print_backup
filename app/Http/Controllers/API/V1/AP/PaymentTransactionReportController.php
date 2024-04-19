<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Illuminate\Support\Facades\DB;


class PaymentTransactionReportController extends Controller
{
    function paymentTranslist(Request $request)
    {
        try {
            Log::channel("paymentreport")->info('** started the paymentreport list method **');
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByCity = ($request->filterByCity) ? $request->filterByCity : '[]';
            $filterByPaymentMethod = ($request->filterByPaymentMethod) ? $request->filterByPaymentMethod : '[]';
            $filterByState = ($request->filterByState) ? $request->filterByState : '[]';

            $payment = Orders::where('orders.payment_status',1)->leftjoin('order_items', 'orders.order_id', '=', 'order_items.order_id')
                ->leftjoin('product', 'order_items.product_id', '=', 'product.product_id')
                ->leftjoin('state', 'state.state_id', '=', 'orders.billing_state_id')
                ->leftjoin('district', 'district.district_id', '=', 'orders.billing_city_id')
                ->leftjoin('pg_link_history', 'pg_link_history.order_id', '=', 'orders.order_id')
                ->select(
                    'orders.order_code',
                    'orders.order_date',
                    'product.product_code',
                    'orders.order_totalamount',
                    'orders.billing_customer_first_name',
                    'orders.billing_customer_last_name',
                    'orders.payment_mode',
                    'orders.payment_transcation_id',
                    'orders.is_cod',
                    'orders.payment_status',
                    'orders.billing_state_id',
                    'orders.billing_city_id',
                    'state.state_name',
                    'district.district_name',
                    'orders.payment_amount',
                    'pg_link_history.transaction_id'
                )
                // ->groupBy('orders.order_id')
                ;

            if (!empty($from_date)) {
                $payment->where(function ($query) use ($from_date) {
                    $query->whereDate('orders.order_date', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $payment->where(function ($query) use ($to_date) {
                    $query->whereDate('orders.order_date', '<=', $to_date);
                });
            }

            if (!empty($filterByPaymentMethod) && $filterByPaymentMethod != '[]' && $filterByPaymentMethod != 'all') {
                $filterByPaymentMethod = json_decode($filterByPaymentMethod, true);
                $payment->whereIn('orders.payment_mode', $filterByPaymentMethod);
            }

            if (!empty($filterByState) && $filterByState != '[]' && $filterByState != 'all') {
                $filterByState = json_decode($filterByState, true);
                $payment->whereIn('state.state_id', $filterByState);
            }

            if (!empty($filterByCity) && $filterByCity != '[]' && $filterByCity != 'all') {
                $filterByCity = json_decode($filterByCity, true);
                $payment->whereIn('district.district_id', $filterByCity);
            }

            $count = $payment->count();
            $payment->orderBy('orders.order_id', 'desc');
            $payment = $payment->get();
            if ($count > 0) {
                $final = [];
                $sum = 0;
                foreach ($payment as $value) {
                    $ary = [];
                    $ary['order_date'] = date('d-m-Y', strtotime($value['order_date']));
                    $ary['order_id'] = $value['order_code'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['customer_name'] = !empty($value['billing_customer_last_name']) ? $value['billing_customer_first_name'] . ' ' . $value['billing_customer_last_name'] : $value['billing_customer_first_name'];
                    $ary['payment_mode'] = $value['payment_mode'];
                    $ary['payment_transcation_id'] = $value['payment_transcation_id'];
                    $ary['payment_transcation_id'] = $value['transaction_id'];
                    $ary['is_cod'] = $value['is_cod'];
                    $ary['payment_status'] = $value['payment_status'];
                    $ary['billing_state_id'] = $value['billing_state_id'];
                    $ary['state_name'] = $value['state_name'];
                    $ary['billing_city_id'] = $value['billing_city_id'];
                    $ary['district_name'] = $value['district_name'];
                    $sum += $value['order_totalamount'];
                    $ary['order_totalamount'] = $value['order_totalamount'];
                    $ary['paid_amount'] = $value['payment_amount'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $impl = json_encode($final, true);
                Log::channel("paymentreport")->info("paymentreport Controller end:: save values :: $impl ::::end");
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Payment transaction report listed successfully',
                    'data' => $final,
                    'total_amount' => $sum
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("paymentreport")->error('** start the paymentreport list error method **');
            Log::channel("paymentreport")->error($exception);
            Log::channel("paymentreport")->error('** end the paymentreport list error method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }


    function paymentTransExcel(Request $request)
    {
        Log::channel("paymentreport")->info('** started the admin paymentreport xl method **');
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByCity = ($request->filterByCity) ? $request->filterByCity : '[]';
        $filterByPaymentMethod = ($request->filterByPaymentMethod) ? $request->filterByPaymentMethod : '[]';
        $filterByState = ($request->filterByState) ? $request->filterByState : '[]';


        $payment = Orders::where('orders.payment_status',1)->leftjoin('order_items', 'orders.order_id', '=', 'order_items.order_id')
            ->leftjoin('product', 'order_items.product_id', '=', 'product.product_id')
            ->leftjoin('state', 'state.state_id', '=', 'orders.billing_state_id')
            ->leftjoin('district', 'district.district_id', '=', 'orders.billing_city_id')
            ->leftjoin('pg_link_history', 'pg_link_history.order_id', '=', 'orders.order_id')
            ->select(
                'orders.order_code',
                'orders.order_date',
                'product.product_code',
                'orders.order_totalamount',
                'orders.billing_customer_first_name',
                'orders.billing_customer_last_name',
                'orders.payment_mode',
                'orders.payment_transcation_id',
                'orders.is_cod',
                'orders.billing_state_id',
                'orders.billing_city_id',
                'state.state_name',
                'district.district_name',
                'orders.payment_amount',
                'pg_link_history.transaction_id'
            )
            // ->groupBy('orders.order_id')
            ;

        if (!empty($from_date)) {
            $payment->where(function ($query) use ($from_date) {
                $query->whereDate('orders.order_date', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $payment->where(function ($query) use ($to_date) {
                $query->whereDate('orders.order_date', '<=', $to_date);
            });
        }

        if (!empty($filterByPaymentMethod) && $filterByPaymentMethod != '[]' && $filterByPaymentMethod != 'all') {
            $filterByPaymentMethod = json_decode($filterByPaymentMethod, true);
            $payment->whereIn('orders.payment_mode', $filterByPaymentMethod);
        }

        if (!empty($filterByState) && $filterByState != '[]' && $filterByState != 'all') {
            $filterByState = json_decode($filterByState, true);
            $payment->whereIn('state.state_id', $filterByState);
        }

        if (!empty($filterByCity) && $filterByCity != '[]' && $filterByCity != 'all') {
            $filterByCity = json_decode($filterByCity, true);
            $payment->whereIn('district.district_id', $filterByCity);
        }

        $count = $payment->count();
        $payment->orderBy('orders.order_id', 'desc');
        $payment = $payment->get();
        $count = count($payment);

        $headers = ['S No', 'Order Date', 'Order ID', 'Prodcut ID', 'Customer'];

        $headers[] = 'Payment Method';

        $headers[] = 'Payment Transaction Id';
   
        $headers[] = 'State';
    
        $headers[] = 'District';

        $headers[] = 'Paid Amount(₹)';
   
        $headers[] = 'Order Amount(₹)';



        $s = 1;
        $sum = 0;
        if ($count > 0) {
            $overll = [];
            $overll[] = $headers;
            foreach ($payment as $value) {
                $ary = [];
                $ary['s_no'] = $s;
                $ary['order_date'] = date('d-m-Y', strtotime($value['order_date']));
                $ary['order_code'] = $value['order_code'];
                $ary['product_code'] = $value['product_code'];
                $ary['customer_name'] = !empty($value['billing_customer_last_name']) ? $value['billing_customer_first_name'] . ' ' . $value['billing_customer_last_name'] : $value['billing_customer_first_name'];
                $ary['payment_mode'] = $value['payment_mode'] ?? "-";
                $ary['payment_transcation_id'] = $value['payment_transcation_id'] ?? "-";
                $ary['state'] = $value['state_name'] ?? "-";
                $ary['district'] = $value['district_name'] ?? "-";
                $sum += $value['order_totalamount'];
                if($value['payment_amount'] == null)
                {
                    $ary['paid_amount'] = "0.00";
                }
                else
                {
                    $ary['paid_amount'] = $value['payment_amount'];
                }
                $ary['order_totalamount'] = $value['order_totalamount'];
                $s++;
                $overll[] = $ary;
            }

            $total_amount = $sum;
            $row_count = $count + 2;

            $excel_report_title = "Payment Transaction Report";

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
            $conditional1->getStyle('A3')->getFont()->setBold(true);
            $conditional1->getStyle('B2')->getFont()->setSize(16);
            $conditional1->getStyle('A3')->getFill()->getStartColor()->setARGB('#333');

            //make the font become bold
            $sheet->getStyle('A1:k1')->getFont()->setBold(true);
            // $sheet->getStyle('A1')->getFont()->setSize(16);
            $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('#333');

            for ($col = ord('A'); $col <= ord('Q'); $col++) { //set column dimension
                $sheet->getColumnDimension(chr($col))->setAutoSize(true);
            }

            //retrieve  table data
            $overll[] = array('', '',  '', '');

            //Fill data
            $sheet->fromArray($overll, null, 'A1');

            $sheet->getStyle("J")->getNumberFormat()->setFormatCode('0.00');
            $sheet->getStyle("K")->getNumberFormat()->setFormatCode('0.00');

            $sheet->setCellValue('I' . $row_count, "Total (₹)");
            $sheet->setCellValue('J' . $row_count, $total_amount);


            $writer = new Xls($spreadsheet);
            $file_name = "payment_transaction_report.xls";
            $fullpath =  storage_path() . '/app/' . $file_name;
            $writer->save($fullpath); // download file
            return response()->download(storage_path('app/payment_transaction_report.xls'), "payment_transaction_report.xls");
        }
    }
}
