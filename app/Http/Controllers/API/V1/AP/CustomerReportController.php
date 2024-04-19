<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

use function PHPUnit\Framework\isNull;

class CustomerReportController extends Controller
{

    public function Customer_report_list(Request $request)
    {
        try {
            Log::channel("customer")->info('** started the admin customer list method **');
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByCustomerType = ($request->filterByCustomerType) ? $request->filterByCustomerType : '';
            $filterByDistrict = ($request->filterByDistrict) ? $request->filterByDistrict : '';
            $filterByState = ($request->filterByState) ? $request->filterByState : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                'date' => 'customer.created_on',
                'customer_name' => 'customer.customer_first_name',
                'customer_code' => 'customer.customer_code',
                'mobile_number' => 'customer.mobile_no',
                'district_name' => 'district.district_name',
                'state_name' => 'state.state_name',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "customer_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('customer.customer_first_name', 'customer.created_on', 'customer.mobile_no', 'customer.customer_code', 'district.district_name', 'state.state_name');
            $get_customer_report = Customer::where('status', "!=", 2)
                ->leftjoin('district', 'district.district_id', '=', 'customer.billing_city_id')
                ->leftjoin('state', 'state.state_id', '=', 'customer.billing_state_id')
                ->leftjoin('district as d', 'd.district_id', '=', 'customer.district_id')
                ->leftjoin('state as s', 's.state_id', '=', 'customer.state_id')
                ->select('customer.*', DB::raw('ROW_NUMBER() OVER (ORDER BY customer_id DESC) AS SrNo'), 'district.district_name', 'state.state_name','d.district_name as dist_name', 's.state_name as st_name');
            $get_customer_report->where(function ($query) use ($searchval, $column_search, $get_customer_report) {
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
                $get_customer_report->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $get_customer_report->where(function ($query) use ($from_date) {
                    $query->whereDate('customer.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $get_customer_report->where(function ($query) use ($to_date) {
                    $query->whereDate('customer.created_on', '<=', $to_date);
                });
            }

            if (!empty($filterByCustomerType) && $filterByCustomerType != '[]' && $filterByCustomerType != 'all') {
                $filterByCustomerType = json_decode($filterByCustomerType, true);
                $get_customer_report->whereIn('customer.customer_from', $filterByCustomerType);
            }

            if (!empty($filterByDistrict) && $filterByDistrict != '[]' && $filterByDistrict != 'all') {
                // $filterByDistrict = json_decode($filterByDistrict, true);
                // $get_customer_report->whereIn('district.district_id', $filterByDistrict);
                $filterByDistrict = json_decode($filterByDistrict, true);
                $get_customer_report->where(function ($query) use ($filterByDistrict) {
                    $query->where(function ($query) use ($filterByDistrict) {
                        $query->whereNull('customer.district_id')
                            ->whereNotNull('customer.billing_city_id')
                            ->whereIn('customer.billing_city_id', $filterByDistrict);
                    })->orWhere(function ($query) use ($filterByDistrict) {
                        $query->whereNotNull('customer.district_id')
                            ->whereIn('customer.district_id', $filterByDistrict);
                    });
                });
            }
            if (!empty($filterByState) && $filterByState != '[]' && $filterByState != 'all') {
                $filterByState = json_decode($filterByState, true);
                // $get_customer_report->whereIn('state.state_id', $filterByState);
                $get_customer_report->where(function ($query) use ($filterByState) {
                    $query->where(function ($query) use ($filterByState) {
                        $query->whereNull('customer.state_id')
                            ->whereNotNull('customer.billing_state_id')
                            ->where('customer.billing_state_id', $filterByState);
                    })->orWhere(function ($query) use ($filterByState) {
                        $query->whereNotNull('customer.state_id')
                            ->where('customer.state_id', $filterByState);
                    });
                });
            }

            if (!empty($filterByStatus) && $filterByStatus != '[]' && $filterByStatus != 'all') {
                $filterByStatus = json_decode($filterByStatus, true);
                $get_customer_report->whereIn('customer.status', $filterByStatus);
            }

            $count = $get_customer_report->count();
            $get_customer_report->orderBy('customer_id', 'desc');
            $get_customer = $get_customer_report->get();
            if ($count > 0) {
                $final = [];
                foreach ($get_customer as $value) {
                    $ary = [];
                    $ary['s_no'] = $value['SrNo'] ?? "-";
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                    $ary['customer_code'] = $value['customer_code'] ?? "-";
                    $ary['customer_name'] =  !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'] ??"-";
                    $ary['customer_type'] = $value['customer_from'] ?? "-";
                    $ary['mobile_number'] = !empty($value['mobile_no']) ? $value['mobile_no'] ?? "-" : $value['billing_mobile_number'] ?? "-";
                    $address = $value['billing_address_1'] . ' ' . $value['billing_landmark'];
                    $ary['address'] = ($address == " ") ? "-" : $address;
                    $ary['billing_city_id'] = !empty($value['district_id']) ? $value['district_id'] ?? "-" : $value['billing_city_id'] ?? "-";
                    if ($value['billing_city_id'] != '') {
                        $ary['district_name'] = !empty($value['dist_name']) ? $value['dist_name'] ?? "-" : $value['district_name'] ?? "-";
                    } else {
                        $ary['district_name'] = !empty($value['dist_name']) ? $value['dist_name'] ?? "-" : $value['district_name'] ?? "-";
                    }
                    $ary['billing_state_id'] = !empty($value['state_id']) ? $value['state_id'] ?? "-" : $value['billing_state_id'] ?? "-";
                    $ary['state_name'] = !empty($value['st_name']) ? $value['st_name'] ?? "-": $value['state_name'] ?? "-";
                    if ($value['status'] == 1) {
                        $ary['status'] = "Active";
                    }
                    if ($value['status'] == 0) {
                        $ary['status'] = "Inactive";
                    }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("customer")->info("admin customer report list value :: $log");
                Log::channel("customer")->info('** end the admin customer report list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Customers report listed successfully'),
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
            Log::channel("customer")->error($exception);
            Log::channel("customer")->info('** end the admin customer list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }


    function customer_list_report_Excel(Request $request)
    {
        Log::channel("customer")->info('** started the admin customer list method **');
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByCustomerType = ($request->filterByCustomerType) ? $request->filterByCustomerType : '';
        $filterByDistrict = ($request->filterByDistrict) ? $request->filterByDistrict : '';
        $filterByState = ($request->filterByState) ? $request->filterByState : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $order_by_key = [
            'date' => 'customer.created_on',
            'customer_name' => 'customer.customer_first_name',
            'customer_code' => 'customer.customer_code',
            'mobile_number' => 'customer.mobile_no',
            'district_name' => 'district.district_name',
            'state_name' => 'state.state_name',
        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "customer_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('customer.customer_first_name', 'customer.created_on', 'customer.mobile_no', 'customer.customer_code', 'district.district_name', 'state.state_name');
        $get_customer_report = Customer::where('status', "!=", 2)
            ->leftjoin('district', 'district.district_id', '=', 'customer.billing_city_id')
            ->leftjoin('state', 'state.state_id', '=', 'customer.billing_state_id')
            ->leftjoin('district as d', 'd.district_id', '=', 'customer.district_id')
            ->leftjoin('state as s', 's.state_id', '=', 'customer.state_id')
            ->select('customer.*', DB::raw('ROW_NUMBER() OVER (ORDER BY customer_id DESC) AS SrNo'), 'district.district_name', 'state.state_name','d.district_name as dist_name', 's.state_name as st_name');
        $get_customer_report->where(function ($query) use ($searchval, $column_search, $get_customer_report) {
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
            $get_customer_report->orderBy($order_by_key[$sortByKey], $sortType);
        }
        if (!empty($from_date)) {
            $get_customer_report->where(function ($query) use ($from_date) {
                $query->whereDate('customer.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $get_customer_report->where(function ($query) use ($to_date) {
                $query->whereDate('customer.created_on', '<=', $to_date);
            });
        }

        if (!empty($filterByCustomerType) && $filterByCustomerType != '[]' && $filterByCustomerType != 'all') {
            $filterByCustomerType = json_decode($filterByCustomerType, true);
            $get_customer_report->whereIn('customer.customer_from', $filterByCustomerType);
        }

        if (!empty($filterByDistrict) && $filterByDistrict != '[]' && $filterByDistrict != 'all') {
            // $filterByDistrict = json_decode($filterByDistrict, true);
            // $get_customer_report->whereIn('district.district_id', $filterByDistrict);

            $filterByDistrict = json_decode($filterByDistrict, true);
            $get_customer_report->where(function ($query) use ($filterByDistrict) {
                $query->where(function ($query) use ($filterByDistrict) {
                    $query->whereNull('customer.district_id')
                        ->whereNotNull('customer.billing_city_id')
                        ->whereIn('customer.billing_city_id', $filterByDistrict);
                })->orWhere(function ($query) use ($filterByDistrict) {
                    $query->whereNotNull('customer.district_id')
                        ->whereIn('customer.district_id', $filterByDistrict);
                });
            });
        }

        if (!empty($filterByState) && $filterByState != '[]' && $filterByState != 'all') {
            $filterByState = json_decode($filterByState, true);
            // $get_customer_report->whereIn('state.state_id', $filterByState);
            $get_customer_report->where(function ($query) use ($filterByState) {
                $query->where(function ($query) use ($filterByState) {
                    $query->whereNull('customer.state_id')
                        ->whereNotNull('customer.billing_state_id')
                        ->where('customer.billing_state_id', $filterByState);
                })->orWhere(function ($query) use ($filterByState) {
                    $query->whereNotNull('customer.state_id')
                        ->where('customer.state_id', $filterByState);
                });
            });
        }

        if (!empty($filterByStatus) && $filterByStatus != '[]' && $filterByStatus != 'all') {
            $filterByStatus = json_decode($filterByStatus, true);
            $get_customer_report->whereIn('customer.status', $filterByStatus);
        }
        $get_ads = $get_customer_report->get();
        $count = count($get_customer_report->get());

        $s = 1;
        if ($count > 0) {
            $overll = [];
            // $overll[] = $headers;
            foreach ($get_ads as $value) {
                $ary = [];
                $ary['s_no'] = $value['SrNo'] ?? "-";
                $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                $ary['customer_code'] = $value['customer_code'] ?? "-";
                $ary['customer_name'] =  !empty($value['customer_last_name']) ? $value['customer_first_name'] . ' ' . $value['customer_last_name'] : $value['customer_first_name'] ??"-";
                $ary['customer_type'] = $value['customer_from'] ?? "-";
                $ary['mobile_number'] = !empty($value['mobile_no']) ? $value['mobile_no'] ?? "-" : $value['billing_mobile_number'] ?? "-";
                $address = $value['billing_address_1'] . ' ' . $value['billing_landmark'];
                $ary['address'] = ($address == " ") ? "-" : $address;
                if ($value['billing_city_id'] != '') {
                    $ary['district'] = !empty($value['dist_name']) ? $value['dist_name'] ?? "-" : $value['district_name'] ?? "-";
                } else {
                    $ary['district'] = !empty($value['dist_name']) ? $value['dist_name'] ?? "-" : $value['district_name'] ?? "-";
                }
                $ary['state'] = !empty($value['st_name']) ? $value['st_name'] ?? "-": $value['state_name'] ?? "-";
                if ($value['status'] == 0) {
                    $ary['status'] = "Inactive";
                } else if ($value['status'] == 1) {
                    $ary['status'] = "Active";
                }
                $overll[] = $ary;
            }
            $s++;
            $excel_report_title = "Customer Report";
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
            $sheet->setCellValue('B1', 'Date');
            $sheet->setCellValue('C1', 'Customer ID');
            $sheet->setCellValue('D1', 'Customer Name');
            $sheet->setCellValue('E1', 'Customer Type');
            $sheet->setCellValue('F1', 'Mobile No');
            $sheet->setCellValue('G1', 'Address');
            $sheet->setCellValue('H1', 'District');
            $sheet->setCellValue('I1', 'State');
            $sheet->setCellValue('J1', 'Status');
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
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);
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
