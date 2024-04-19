<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;


class EmployeeReportController extends Controller
{

    public function employee_report_list(Request $request)
    {
        try {
            Log::channel("employeereport")->info('** started the admin employee list method **');
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByEmployeeType = ($request->filterByEmployeeType) ? $request->filterByEmployeeType : '';
            $filterByDepartment = ($request->filterByDepartment) ? $request->filterByDepartment : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                'employee_name' => 'employee.employee_name',
                'employee_id' => 'employee.employee_code',
                'date' => 'employee.created_on',
                'employee_type' => 'employee.employee_type',
                'location' => 'department.department_name',
                'status' => 'employee.status',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "employee_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('employee.employee_name', 'employee.employee_code', 'employee.created_on', 'employee.employee_type', 'department.department_name');
            $get_employee_report = Employee::where('employee.status', "!=", 2)
                ->leftjoin('department', 'department.department_id', '=', 'employee.department_id')
                ->select(DB::raw('ROW_NUMBER() OVER (ORDER BY employee_id DESC) AS SrNo'), 'employee.*', 'department.department_name');
            $get_employee_report->where(function ($query) use ($searchval, $column_search, $get_employee_report) {
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
                $get_employee_report->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $get_employee_report->where(function ($query) use ($from_date) {
                    $query->whereDate('employee.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $get_employee_report->where(function ($query) use ($to_date) {
                    $query->whereDate('employee.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByEmployeeType) && $filterByEmployeeType != '[]' && $filterByEmployeeType != 'all') {
                $filterByEmployeeType = json_decode($filterByEmployeeType, true);
                $get_employee_report->whereIn('employee.employee_type', $filterByEmployeeType);
            }

            if (!empty($filterByDepartment) && $filterByDepartment != '[]' && $filterByDepartment != 'all') {
                $filterByDepartment = json_decode($filterByDepartment, true);
                $get_employee_report->whereIn('department.department_id', $filterByDepartment);
            }

            if (!empty($filterByStatus) && $filterByStatus != '[]' && $filterByStatus != 'all') {
                $filterByStatus = json_decode($filterByStatus, true);
                $get_employee_report->whereIn('employee.status', $filterByStatus);
            }

            $count = $get_employee_report->count();
            $get_employee_report->orderBy('employee_id', 'desc');
            $get_employee = $get_employee_report->get();
            if ($count > 0) {
                $final = [];
                foreach ($get_employee as $value) {
                    $ary = [];
                    $ary['s_no'] = $value['SrNo'] ?? "-";
                    $ary['created_on'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                    $ary['employee_code'] = $value['employee_code'] ?? "-";
                    $ary['employee_name'] =  $value['employee_name'] ?? "-";
                    if ($value['employee_type'] == 1) {
                        $ary['employment_type'] = "In House";
                    }
                    if ($value['employee_type'] == 2) {
                        $ary['employment_type'] = "Vendor / Freelancer";
                    }
                    $ary['department_name'] =  $value['department_name'] ?? "-";
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
                Log::channel("employeereport")->info("admin employee report list value :: $log");
                Log::channel("employeereport")->info('** end the admin employee report list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Employee reports listed successfully'),
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
            Log::channel("employeereport")->error($exception);
            Log::channel("employeereport")->info('** end the admin employee report list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }


    function employee_list_report_Excel(Request $request)
    {
        Log::channel("employeereport")->info('** started the admin employee report list method **');
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByEmployeeType = ($request->filterByEmployeeType) ? $request->filterByEmployeeType : '';
        $filterByDepartment = ($request->filterByDepartment) ? $request->filterByDepartment : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $order_by_key = [
            'employee_name' => 'employee.employee_name',
            'employee_id' => 'employee.employee_code',
            'date' => 'employee.created_on',
            'employee_type' => 'employee.employee_type',
            'location' => 'department.department_name',
            'status' => 'employee.status',
        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "employee_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('employee.employee_name', 'employee.employee_code', 'employee.created_on', 'employee.employee_type', 'department.department_name');
        $get_employee_report =  Employee::where('employee.status', "!=", 2)
            ->leftjoin('department', 'department.department_id', '=', 'employee.department_id')
            ->select(DB::raw('ROW_NUMBER() OVER (ORDER BY employee_id DESC) AS SrNo'), 'employee.*', 'department.department_name');
        $get_employee_report->where(function ($query) use ($searchval, $column_search, $get_employee_report) {
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
            $get_employee_report->orderBy($order_by_key[$sortByKey], $sortType);
        }
        if (!empty($from_date)) {
            $get_employee_report->where(function ($query) use ($from_date) {
                $query->whereDate('employee.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $get_employee_report->where(function ($query) use ($to_date) {
                $query->whereDate('employee.created_on', '<=', $to_date);
            });
        }
        if (!empty($filterByEmployeeType) && $filterByEmployeeType != '[]' && $filterByEmployeeType != 'all') {
            $filterByEmployeeType = json_decode($filterByEmployeeType, true);
            $get_employee_report->whereIn('employee.employee_type', $filterByEmployeeType);
        }

        if (!empty($filterByDepartment) && $filterByDepartment != '[]' && $filterByDepartment != 'all') {
            $filterByDepartment = json_decode($filterByDepartment, true);
            $get_employee_report->whereIn('department.department_id', $filterByDepartment);
        }

        if (!empty($filterByStatus) && $filterByStatus != '[]' && $filterByStatus != 'all') {
            $filterByStatus = json_decode($filterByStatus, true);
            $get_employee_report->whereIn('employee.status', $filterByStatus);
        }
        $get_employee = $get_employee_report->get();
        $count = count($get_employee_report->get());


        $s = 1;
        if ($count > 0) {
            $overll = [];

            foreach ($get_employee as $value) {
                $ary = [];
                $ary['s_no'] = $value['SrNo'] ?? "-";
                $ary['created_on'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                $ary['employee_code'] = $value['employee_code'] ?? "-";
                $ary['employee_name'] =  $value['employee_name'] ?? "-";
                if ($value['employee_type'] == 1) {
                    $ary['employment_type'] = "In House";
                } elseif ($value['employee_type'] == 2) {
                    $ary['employment_type'] = "Vendor / Freelancer";
                }
                $ary['department_name'] =  $value['department_name'] ?? "-";
                if ($value['status'] == 1) {
                    $ary['status'] = "Active";
                } else if ($value['status'] == 0) {
                    $ary['status'] = "Inactive";
                }
                $overll[] = $ary;
            }
            $s++;
            $excel_report_title = "Employee Report";
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
            $sheet->setCellValue('C1', 'Employee ID');
            $sheet->setCellValue('D1', 'Employee Name');
            $sheet->setCellValue('E1', 'Employment Type');
            $sheet->setCellValue('F1', 'Department');
            $sheet->setCellValue('G1', 'Status');
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
            $file_name = "employee-report-data.xls";
            $fullpath = storage_path() . '/app/employee_report' . $file_name;
            $writer->save($fullpath); // download file
            return response()->download(storage_path('app/employee_reportemployee-report-data.xls'), "employee_report.xls");
        }
    }
}
