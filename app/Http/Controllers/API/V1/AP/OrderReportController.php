<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Models\Orders;
use App\Models\OrderItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;


class OrderReportController extends Controller
{

    public function Order_report_list(Request $request)
    {
        try {
            Log::channel("ordereport")->info('** started the admin ordereport list method **');

            $statusMAp = [
                "pending" => [0, 1],
                "approved" => [2, 9, 10],
                "packed" => [7],
                "disapproved" => [6, 8],
                "dispatched" => [3],
                "delivered" => [5],
                "cancelled" => [4]
            ];

            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByDistrict = ($request->filterByDistrict) ? $request->filterByDistrict : '';
            $filterByState = ($request->filterByState) ? $request->filterByState : '';
            $filterByOrderStatus = ($request->filterByOrderStatus) ? $request->filterByOrderStatus : '';

            if (!empty($filterByOrderStatus)) {
                $filterByStatusArr = json_decode($filterByOrderStatus, TRUE);

                $compareStatusArr = [];

                foreach ($filterByStatusArr as $st) {

                    $statusArr = isset($statusMAp[$st]) ? $statusMAp[$st] : [];

                    if (!empty($statusArr)) {
                        $compareStatusArr = array_merge($compareStatusArr, $statusArr);
                    }
                }
            }

            $get_order_report = OrderItems::leftjoin('orders', 'order_items.order_id', '=', 'orders.order_id')
                ->leftJoin('customer', function ($leftJoin) {
                    $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                        ->where('orders.customer_id', '!=', NULL);
                })
                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                        ->where('orders.customer_id', '=', NULL);
                })
                ->leftjoin('product', 'product.product_id', '=', 'order_items.product_id')
                ->leftjoin('district', 'district.district_id', '=', 'orders.billing_city_id')
                ->leftjoin('state', 'state.state_id', '=', 'orders.billing_state_id')
                ->select(
                    'order_items.*',
                    'customer.customer_first_name',
                    'customer.customer_last_name',
                    'customer.mobile_no',
                    'bulk_order_enquiry.contact_person_name',
                    'bulk_order_enquiry.mobile_no as bulk_order_mobile_no',
                    'orders.customer_id',
                    'product.product_code',
                    'product.product_name',
                    'district.district_name',
                    'orders.shipping_cost',
                    'state.state_name',
                    'orders.billing_state_id',
                    'orders.billing_city_id',
                    'orders.order_date',
                    'orders.order_code',
                    'orders.other_district'
                );


            if (!empty($from_date)) {
                $get_order_report->where(function ($query) use ($from_date) {
                    $query->whereDate('orders.order_date', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $get_order_report->where(function ($query) use ($to_date) {
                    $query->whereDate('orders.order_date', '<=', $to_date);
                });
            }

            if (!empty($filterByDistrict) && $filterByDistrict != '[]' && $filterByDistrict != 'all') {
                $filterByDistrict = json_decode($filterByDistrict, true);
                $get_order_report->whereIn('district.district_id', $filterByDistrict);
            }
            if (!empty($filterByState) && $filterByState != '[]' && $filterByState != 'all') {
                $filterByState = json_decode($filterByState, true);
                $get_order_report->whereIn('state.state_id', $filterByState);
            }

            // if (!empty($filterByOrderStatus) && $filterByOrderStatus != '[]' && $filterByOrderStatus != 'all') {
            //     $filterByOrderStatus = json_decode($filterByOrderStatus, true);
            //     $get_order_report->whereIn('order_items.order_status', $filterByOrderStatus);
            // }

            if (!empty($compareStatusArr)) {
                $get_order_report->whereIn('order_items.order_status', $compareStatusArr);
            }

            $count = $get_order_report->count();
            $get_order_report->orderBy('order_id', 'desc');
            $get_order = $get_order_report->get();
            if ($count > 0) {
                $final = [];
                foreach ($get_order as $value) {
                    $ary = [];
                    $ary['date'] = date('d-m-Y', strtotime($value['order_date'])) ?? "-";
                    $ary['order_id'] = $value['order_id'] ?? "-";
                    $ary['order_code'] = $value['order_code'] ?? "-";
                    $ary['product_id'] = $value['product_code'] ?? "-";
                    $ary['product_name'] = $value['product_name'] ?? "-";
                    $ary['order_status'] = $value['order_status'] ?? "-";
                    $ary['quantity'] = $value['quantity'] ?? "-";
                    if (!empty($value['customer_id'])) {
                        $ary['customer_name'] =  !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                        $ary['mobile_number'] = $value['mobile_no'] ?? "-";
                        $ary['order_amount'] = number_format($value['sub_total'] + $value['delivery_charge'], 2) ?? "-";
                    } else {
                        $ary['customer_name'] =  $value['contact_person_name'] ?? "-";
                        $ary['mobile_number'] = $value['bulk_order_mobile_no'] ?? "-";
                        $ary['order_amount'] = number_format(($value['unit_price'] * $value['quantity']) + $value['delivery_charge'], 2) ?? "-";
                    }
                    $ary['billing_city_id'] = $value['billing_city_id'] ?? "-";
                    if ($value['billing_city_id'] != '') {
                        $ary['district_name'] = $value['district_name'] ?? "-";
                    } else {
                        $ary['district_name'] = $value['other_district'] ?? "-";
                    }
                    $ary['billing_state_id'] = $value['billing_state_id'] ?? "-";
                    $ary['state_name'] = $value['state_name'] ?? "-";
                    if ($value['order_status'] == 1 || $value['order_status'] == 0) {
                        $ary['status'] = "Pending";
                    }
                    if ($value['order_status'] == 2 || $value['order_status'] == 9 || $value['order_status'] == 10) {
                        $ary['status'] = "Approved";
                    }
                    if ($value['order_status'] == 3) {
                        $ary['status'] = "Dispatched";
                    }
                    if ($value['order_status'] == 4) {
                        $ary['status'] = "Cancelled";
                    }
                    if ($value['order_status'] == 5) {
                        $ary['status'] = "Delivered";
                    }
                    if ($value['order_status'] == 7) {
                        $ary['status'] = "Packed";
                    }
                    if ($value['order_status'] == 6 || $value['order_status'] == 8) {
                        $ary['status'] = "Disapproved";
                    }
                    // $ary['order_amount'] = number_format($value['sub_total'] + $value['shipping_cost'], 2) ?? "-";
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("orderreport")->info("admin order report list value :: $log");
                Log::channel("orderreport")->info('** end the admin order report list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Order report listed successfully'),
                    'data' => $final,
                    'count' => $count,
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => [],
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("orderreport")->error($exception);
            Log::channel("orderreport")->info('** end the admin orderreport list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function Order_list_report_Excel(Request $request)
    {
        Log::channel("orderreport")->info('** started the orderreport list method **');

        $statusMAp = [
            "pending" => [0, 1],
            "approved" => [2, 9, 10],
            "packed" => [7],
            "disapproved" => [6, 8],
            "dispatched" => [3],
            "delivered" => [5],
            "cancelled" => [4]
        ];

        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByDistrict = ($request->filterByDistrict) ? $request->filterByDistrict : '';
        $filterByState = ($request->filterByState) ? $request->filterByState : '';
        $filterByOrderStatus = ($request->filterByOrderStatus) ? $request->filterByOrderStatus : '';

        if (!empty($filterByOrderStatus)) {
            $filterByStatusArr = json_decode($filterByOrderStatus, TRUE);

            $compareStatusArr = [];

            foreach ($filterByStatusArr as $st) {

                $statusArr = isset($statusMAp[$st]) ? $statusMAp[$st] : [];

                if (!empty($statusArr)) {
                    $compareStatusArr = array_merge($compareStatusArr, $statusArr);
                }
            }
        }

        $get_order_report = OrderItems::leftjoin('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->leftJoin('customer', function ($leftJoin) {
                $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                    ->where('orders.customer_id', '!=', NULL);
            })
            ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                    ->where('orders.customer_id', '=', NULL);
            })
            ->leftjoin('product', 'product.product_id', '=', 'order_items.product_id')
            ->leftjoin('district', 'district.district_id', '=', 'orders.billing_city_id')
            ->leftjoin('state', 'state.state_id', '=', 'orders.billing_state_id')
            ->select(
                'order_items.*',
                'customer.customer_first_name',
                'customer.customer_last_name',
                'customer.mobile_no',
                'bulk_order_enquiry.contact_person_name',
                'bulk_order_enquiry.mobile_no as bulk_order_mobile_no',
                'orders.customer_id',
                'product.product_code',
                'product.product_code',
                'product.product_name',
                'district.district_name',
                'state.state_name',
                'orders.billing_state_id',
                'orders.billing_city_id',
                'orders.order_date',
                'orders.order_code',
                'orders.shipping_cost',
                'orders.other_district'
            );


        if (!empty($from_date)) {
            $get_order_report->where(function ($query) use ($from_date) {
                $query->whereDate('orders.order_date', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $get_order_report->where(function ($query) use ($to_date) {
                $query->whereDate('orders.order_date', '<=', $to_date);
            });
        }
        if (!empty($filterByDistrict) && $filterByDistrict != '[]' && $filterByDistrict != 'all') {
            $filterByDistrict = json_decode($filterByDistrict, true);
            $get_order_report->whereIn('district.district_id', $filterByDistrict);
        }

        if (!empty($filterByState) && $filterByState != '[]' && $filterByState != 'all') {
            $filterByState = json_decode($filterByState, true);
            $get_order_report->whereIn('state.state_id', $filterByState);
        }

        // if (!empty($filterByOrderStatus) && $filterByOrderStatus != '[]' && $filterByOrderStatus != 'all') {
        //     $filterByOrderStatus = json_decode($filterByOrderStatus, true);
        //     $get_order_report->whereIn('order_items.order_status', $filterByOrderStatus);
        // }

        if (!empty($compareStatusArr)) {
            $get_order_report->whereIn('order_items.order_status', $compareStatusArr);
        }

        $count = $get_order_report->count();
        $get_order_report->orderBy('order_id', 'desc');
        $get_order_report = $get_order_report->get();
        $s = 1;
        if (!empty($get_order_report)) {
            if ($count > 0) {
                $overll = [];
                foreach ($get_order_report as $value) {
                    $ary = [];
                    $ary['date'] = date('d-m-Y', strtotime($value['order_date'])) ?? "-";
                    $ary['order_id'] = $value['order_code'] ?? "-";
                    $ary['product_id'] = $value['product_code'] ?? "-";
                    $ary['product_name'] = $value['product_name'] ?? "-";
                    if (!empty($value['customer_id'])) {
                        $ary['customer_name'] =  !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'];
                        $ary['mobile_number'] = $value['mobile_no'] ?? "-";
                    } else {
                        $ary['customer_name'] =  $value['contact_person_name'];
                        $ary['mobile_number'] = $value['bulk_order_mobile_no'] ?? "-";
                    }
                    $ary['order_quantity'] = $value['quantity'] ?? "-";
                    $ary['state_name'] = $value['state_name'] ?? "-";
                    if ($value['billing_city_id'] != '') {
                        $ary['district_name'] = $value['district_name'] ?? "-";
                    } else {
                        $ary['district_name'] = $value['other_district'] ?? "-";
                    }
                    if ($value['order_status'] == 1 || $value['order_status'] == 0) {
                        $ary['status'] = "Pending";
                    }
                    if ($value['order_status'] == 2 || $value['order_status'] == 9 || $value['order_status'] == 10) {
                        $ary['status'] = "Approved";
                    }
                    if ($value['order_status'] == 3) {
                        $ary['status'] = "Dispatched";
                    }
                    if ($value['order_status'] == 4) {
                        $ary['status'] = "Cancelled";
                    }
                    if ($value['order_status'] == 5) {
                        $ary['status'] = "Delivered";
                    }
                    if ($value['order_status'] == 7) {
                        $ary['status'] = "Packed";
                    }
                    if ($value['order_status'] == 6 || $value['order_status'] == 8) {
                        $ary['status'] = "Disapproved";
                    }
                    if (!empty($value['customer_id'])) {
                        $ary['order_amount'] = $value['sub_total'] + $value['delivery_charge'] ?? "-";
                    } else {
                        $ary['order_amount'] = number_format(($value['unit_price'] * $value['quantity']) + $value['delivery_charge'], 2) ?? "-";
                    }
                    // $ary['order_amount'] = $value['sub_total'] + $value['shipping_cost'] ?? "-";
                    $overll[] = $ary;
                }
                $s++;
                $excel_report_title = "Order Report List Report";
                $spreadsheet = new Spreadsheet();
                //Set document properties
                $spreadsheet->getProperties()->setCreator("Technogenesis")
                    ->setLastModifiedBy("Technogenesis")
                    ->setTitle("Order Report List")
                    ->setSubject("Order Report List")
                    ->setDescription("Order Report List")
                    ->setKeywords("Order Report List")
                    ->setCategory("Order Report List");
                $spreadsheet->getProperties()->setCreator("technogenesis.in")
                    ->setLastModifiedBy("Technogenesis");
                $spreadsheet->setActiveSheetIndex(0);
                $sheet = $spreadsheet->getActiveSheet();
                //name the worksheet
                $sheet->setTitle($excel_report_title);
                $sheet->setCellValue('A1', 'Order Date');
                $sheet->setCellValue('B1', 'Order ID');
                $sheet->setCellValue('C1', 'Product Code');
                $sheet->setCellValue('D1', 'Product Name');
                $sheet->setCellValue('E1', 'Customer');
                $sheet->setCellValue('F1', 'Mobile No');
                $sheet->setCellValue('G1', 'Order Quantity');
                $sheet->setCellValue('H1', 'State');
                $sheet->setCellValue('I1', 'District');
                $sheet->setCellValue('J1', 'Order Status');
                $sheet->setCellValue('K1', 'Order Amount (₹)');
                $i = 2;
                $total = 0;
                foreach ($get_order_report as $location) {
                    $sheet->setCellValue('A' . $i, $location['Order Date']);
                    $sheet->setCellValue('B' . $i, $location['Order ID']);
                    $sheet->setCellValue('C' . $i, $location['Product Code']);
                    $sheet->setCellValue('D' . $i, $location['Product Name']);
                    $sheet->setCellValue('E' . $i, $location['Customer']);
                    $sheet->setCellValue('F' . $i, $location['Mobile No']);
                    $sheet->setCellValue('G' . $i, $location['Order Quantity']);
                    $sheet->setCellValue('H' . $i, $location['State']);
                    $sheet->setCellValue('I' . $i, $location['District']);
                    $sheet->setCellValue('J' . $i, $location['Order Status']);
                    $sheet->setCellValue('K' . $i, $location['Order Amount']);
                    $sheet->getStyle("K")->getNumberFormat()->setFormatCode('0.00');
                    if (!empty($location['customer_id'])) {
                        $total = $total + $location['sub_total'] + $location['delivery_charge'];
                    } else {
                        $total = $total + ($location['unit_price'] * $location['quantity']) + $location['delivery_charge'];
                    }
                    $i++;
                }
                //here your $i val already incremented in foreach() loop
                $sheet->setCellValue('J' . $i, "Total")->getStyle('J' . $i, "Total")->getFont()->setBold(true);
                $sheet->setCellValue('K' . $i, $total)->getStyle('K' . $i, "Total")->getFont()->setBold(true);
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
                $sheet->getStyle('A1:K1')->getFont()->setBold(true);
                $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('#333');
                for ($col = ord('A'); $col <= ord('Q'); $col++) { //set column dimension
                    $sheet->getColumnDimension(chr($col))->setAutoSize(true);
                }
                //retrieve  table data
                $overll[] = array('', '', '', '');
                //Fill data
                $sheet->fromArray($overll, null, 'A2');
                $writer = new Xls($spreadsheet);
                $file_name = "Order-report-data.xls";
                $fullpath = storage_path() . '/app/Order_report' . $file_name;
                $writer->save($fullpath); // download file
                return response()->download(storage_path('app/Order_reportOrder-report-data.xls'), "Order_report.xls");
            }
        }
    }
}
