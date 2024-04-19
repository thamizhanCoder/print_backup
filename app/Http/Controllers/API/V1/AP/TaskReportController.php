<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use App\Models\EmployeeTaskHistory;
use App\Models\OrderItems;
use App\Models\OrderItemStage;
use App\Models\TaskManagerHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Illuminate\Support\Facades\DB;

class TaskReportController extends Controller
{
    public function taskReportList(Request $request)
    {
        try {
            $today = date('Y-m-d');
            Log::channel("ordereport")->info('** started the admin ordereport list method **');
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByEmployeeName = ($request->filterByEmployeeName) ? $request->filterByEmployeeName : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
            $filterByType = ($request->filterByType) ? $request->filterByType : '';
            $get_taskreport = TaskManagerHistory::where('task_manager_history.production_status', 1)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')
                ->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->select('task_manager_history.task_manager_history_id', 'task_manager_history.work_stage', 'task_manager_history.expected_on', 'task_manager_history.assigned_on', 'orders.order_code', 'orders.order_date', 'order_items.product_code',  'task_manager.task_type', 'task_manager.task_code', 'task_manager.task_name', 'task_manager.created_on as custom_task_date', 'task_manager.task_manager_id', 'task_manager.order_items_id', 'employee.employee_name', 'orderitem_stage.stage');


            if (!empty($from_date)) {
                $get_taskreport->where(function ($query) use ($from_date) {
                    $query->whereDate('task_manager_history.assigned_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $get_taskreport->where(function ($query) use ($to_date) {
                    $query->whereDate('task_manager_history.assigned_on', '<=', $to_date);
                });
            }

            if (!empty($filterByEmployeeName) && $filterByEmployeeName != '[]' && $filterByEmployeeName != 'all') {
                $filterByEmployeeName = json_decode($filterByEmployeeName, true);
                $get_taskreport->whereIn('task_manager_history.employee_id', $filterByEmployeeName);
            }

            if ($filterByType == 1) {
            if (!empty($filterByStatus)) {
                // $filterByStatus = json_decode($filterByStatus, true);
                if ($filterByStatus == 5) {
                    $get_taskreport->having('task_manager_history.expected_on', '<', date("Y-m-d"));
                }
                else{
                $get_taskreport->where('task_manager_history.work_stage', $filterByStatus);
                $get_taskreport->having('task_manager_history.expected_on', '>=', date("Y-m-d"));
                }
            }
        }

        if ($filterByType == 2) {
            if (!empty($filterByStatus)) {
                // $filterByStatus = json_decode($filterByStatus, true);
                if ($filterByStatus == 5) {
                    $get_taskreport->having('task_manager_history.expected_on', '<', date("Y-m-d"));
                }
                else{
                $get_taskreport->where('task_manager_history.work_stage', $filterByStatus);
                $get_taskreport->having('task_manager_history.expected_on', '>=', date("Y-m-d"));
                }
            }
        }

            // if ($filterByStatus == 5) {
            //     $get_taskreport->having('task_manager_history.expected_on', '<', date("Y-m-d 00:00:00"));
            // }

            if (!empty($filterByType)) {
                $get_taskreport->where('task_manager.task_type', $filterByType);
            }

            // $count = count($get_taskreport->get());
            $get_taskreport->groupBy('task_manager_history.task_manager_id');
            $get_taskreport->orderBy('task_manager_history.task_manager_history_id', 'desc');
            $count = count($get_taskreport->get());
            $get_order = $get_taskreport->get();
            if ($count > 0) {
                $final = [];
                foreach ($get_order as $value) {
                    $ary = [];
                    $ary['task_manager_id'] = $value['task_manager_id'];
                    $ary['task_manager_history_id'] = $value['task_manager_history_id'];
                    $ary['date'] = !empty($value['order_date']) ? $value['order_date'] : $value['custom_task_date'];
                    if ($value['task_type'] == 1) {
                        $ary['task_type'] = "Task";
                    }
                    if ($value['task_type'] == 2) {
                        $ary['task_type'] = "Order";
                    }
                    $ary['order_code'] = $value['order_code'];
                    $ary['task_name'] = $value['task_name'];
                    $ary['task_code'] = $value['task_code'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['stage'] = $value['stage'];
                    // if ($value['task_type'] == 2 && date('Y-m-d', strtotime($value['expected_on'])) < $today) {
                    //     // $stageDetails = $this->getOrderStageDetails($value['order_items_id']);
                    //     // $ary['stage'] = $stageDetails;
                    //     // $ary['stage_count'] = count($this->getOrderStageDetails($value['order_items_id']));
                    //     $ary['employee_name'] = $value['employee_name'];
                    //     $ary['work_stage'] = 5;
                    //     // print_r("hi");exit;
                    // } else {
                    //     $ary['employee_name'] = $value['employee_name'];
                    //     // if ($value['work_stage'] == 1) {
                    //     //     $ary['work_stage'] = "TO Do";
                    //     // }
                    //     // if ($value['work_stage'] == 2) {
                    //     //     $ary['work_stage'] = "IN PROGRESS";
                    //     // }
                    //     // if ($value['work_stage'] == 3) {
                    //     //     $ary['work_stage'] = "PREVIEW";
                    //     // }
                    //     // if ($value['work_stage'] == 4) {
                    //     //     $ary['work_stage'] = "COMPLETED";
                    //     // }
                    //     $ary['work_stage'] = $value['work_stage'];
                    // }
                    // if ($value['task_type'] == 1 && date('Y-m-d', strtotime($value['expected_on'])) < $today) {
                    //     $ary['expected_on'] = $value['expected_on'];
                    //     $ary['employee_name'] = $value['employee_name'];
                    //     $ary['work_stage'] = 5;
                    // } else {
                    //     // if ($value['work_stage'] == 1) {
                    //     //     $ary['work_stage'] = "TO Do";
                    //     // }
                    //     // if ($value['work_stage'] == 2) {
                    //     //     $ary['work_stage'] = "IN PROGRESS";
                    //     // }
                    //     // if ($value['work_stage'] == 3) {
                    //     //     $ary['work_stage'] = "PREVIEW";
                    //     // }
                    //     // if ($value['work_stage'] == 4) {
                    //     //     $ary['work_stage'] = "COMPLETED";
                    //     // }
                    //     $ary['work_stage'] = $value['work_stage'];
                    //     $ary['expected_on'] = $value['expected_on'];
                    //     $ary['employee_name'] = $value['employee_name'];
                    // }

                    if ($value['task_type'] == 2 && date('Y-m-d', strtotime($value['expected_on'])) < $today) {
                        // $stageDetails = $this->getOrderStageDetails($value['order_items_id']);
                        // $ary['stage'] = $stageDetails;
                        // $ary['stage_count'] = count($this->getOrderStageDetails($value['order_items_id']));
                        $ary['employee_name'] = $value['employee_name'];
                        $ary['work_stage'] = 5;
                        // print_r("hi");exit;
                    } 
                    else if ($value['task_type'] == 1 && date('Y-m-d', strtotime($value['expected_on'])) < $today) {
                        $ary['expected_on'] = $value['expected_on'];
                        $ary['employee_name'] = $value['employee_name'];
                        $ary['work_stage'] = 5;
                    } else {
                        $ary['work_stage'] = $value['work_stage'];
                        $ary['expected_on'] = $value['expected_on'];
                        $ary['employee_name'] = $value['employee_name'];
                    }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("orderreport")->info("admin order report list value :: $log");
                Log::channel("orderreport")->info('** end the admin order report list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Task report listed successfully'),
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

    public function taskReportListPdf(Request $request)
    {
        try {
            $today = date('Y-m-d');
            Log::channel("ordereport")->info('** started the admin ordereport list method **');
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByEmployeeName = ($request->filterByEmployeeName) ? $request->filterByEmployeeName : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
            $filterByType = ($request->filterByType) ? $request->filterByType : '';
            $all = $request->all;

            $get_taskreport = TaskManagerHistory::where('task_manager_history.production_status', 1)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')
                ->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->select('task_manager_history.task_manager_history_id', 'task_manager_history.work_stage', 'task_manager_history.expected_on', 'task_manager_history.assigned_on', 'orders.order_code', 'orders.order_date', 'order_items.product_code',  'task_manager.task_type', 'task_manager.task_code', 'task_manager.task_name', 'task_manager.created_on as custom_task_date', 'task_manager.task_manager_id', 'task_manager.order_items_id', 'employee.employee_name', 'orderitem_stage.stage');


            if (!empty($from_date)) {
                $get_taskreport->where(function ($query) use ($from_date) {
                    $query->whereDate('task_manager_history.assigned_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $get_taskreport->where(function ($query) use ($to_date) {
                    $query->whereDate('task_manager_history.assigned_on', '<=', $to_date);
                });
            }

            if (!empty($filterByEmployeeName) && $filterByEmployeeName != '[]' && $filterByEmployeeName != 'all') {
                $filterByEmployeeName = json_decode($filterByEmployeeName, true);
                $get_taskreport->whereIn('task_manager_history.employee_id', $filterByEmployeeName);
            }
            if ($filterByType == 1) {
                if (!empty($filterByStatus)) {
                    // $filterByStatus = json_decode($filterByStatus, true);
                    if ($filterByStatus == 5) {
                        $get_taskreport->having('task_manager_history.expected_on', '<', date("Y-m-d"));
                    }
                    else{
                    $get_taskreport->where('task_manager_history.work_stage', $filterByStatus);
                    $get_taskreport->having('task_manager_history.expected_on', '>=', date("Y-m-d"));
                    }
                }
            }
    
            if ($filterByType == 2) {
                if (!empty($filterByStatus)) {
                    // $filterByStatus = json_decode($filterByStatus, true);
                    if ($filterByStatus == 5) {
                        $get_taskreport->having('task_manager_history.expected_on', '<', date("Y-m-d"));
                    }
                    else{
                    $get_taskreport->where('task_manager_history.work_stage', $filterByStatus);
                    $get_taskreport->having('task_manager_history.expected_on', '>=', date("Y-m-d"));
                    }
                }
            }

            if (!empty($filterByType)) {
                $get_taskreport->where('task_manager.task_type', $filterByType);
            }

            // $count = count($get_taskreport->get());
            $get_taskreport->groupBy('task_manager_history.task_manager_id');
            $get_taskreport->orderBy('task_manager_history.task_manager_history_id', 'desc');
            $count = count($get_taskreport->get());
            $get_order = $get_taskreport->get();
            $s = 1;
            if ($count > 0) {
                $final = [];
                foreach ($get_order as $value) {
                    $ary = [];
                    if ($filterByType == 1) {
                        $ary['date'] = date('d-m-Y', strtotime($value['custom_task_date']));
                        if ($value['task_type'] == 1) {
                            $ary['task_type'] = "Task";
                        }
                        $ary['task_code'] = $value['task_code'];
                        $ary['task_name'] = $value['task_name'];
                        $ary['employee_name'] = $value['employee_name'];

                        // if ($value['task_type'] == 1 && date('Y-m-d 00:00:00', strtotime($value['expected_on'])) < $today) {
                        //     $ary['work_stage'] = "OVER DUE";
                        // }
                        // if ($value['work_stage'] == 1) {
                        //     $ary['work_stage'] = "TO DO";
                        // }
                        // if ($value['work_stage'] == 2) {
                        //     $ary['work_stage'] = "IN PROGRESS";
                        // }
                        // if ($value['work_stage'] == 3) {
                        //     $ary['work_stage'] = "PREVIEW";
                        // }
                        // if ($value['work_stage'] == 4) {
                        //     $ary['work_stage'] = "COMPLETED";
                        // }
                       if ($value['task_type'] == 1 && date('Y-m-d', strtotime($value['expected_on'])) < $today) {
                            // $ary['expected_on'] = $value['expected_on'];
                            $ary['employee_name'] = $value['employee_name'];
                            $ary['work_stage'] = "OVER DUE";
                        } else {
                            // $ary['work_stage'] = $value['work_stage'];
                            if ($value['work_stage'] == 1) {
                            $ary['work_stage'] = "TO DO";
                        }
                        if ($value['work_stage'] == 2) {
                            $ary['work_stage'] = "IN PROGRESS";
                        }
                        if ($value['work_stage'] == 3) {
                            $ary['work_stage'] = "PREVIEW";
                        }
                        if ($value['work_stage'] == 4) {
                            $ary['work_stage'] = "COMPLETED";
                        }
                            // $ary['expected_on'] = $value['expected_on'];
                            $ary['employee_name'] = $value['employee_name'];
                        }
                    }


                    if ($filterByType == 2) {
                        $ary['date'] = date('d-m-Y', strtotime($value['order_date']));
                        if ($value['task_type'] == 2) {
                            $ary['task_type'] = "Order";
                        }
                        $ary['order_code'] = $value['order_code'];
                        $ary['product_code'] = $value['product_code'];
                        $ary['employee_name'] = $value['employee_name'];
                        // if ($value['task_type'] == 2 && date('Y-m-d 00:00:00', strtotime($value['expected_on'])) < $today) {
                        //     $ary['work_stage'] = "OVER DUE";
                        // }
                        // if ($value['work_stage'] == 1) {
                        //     $ary['work_stage'] = "TO DO";
                        // }
                        // if ($value['work_stage'] == 2) {
                        //     $ary['work_stage'] = "IN PROGRESS";
                        // }
                        // if ($value['work_stage'] == 3) {
                        //     $ary['work_stage'] = "PREVIEW";
                        // }
                        // if ($value['work_stage'] == 4) {
                        //     $ary['work_stage'] = "COMPLETED";
                        // }
                        if ($value['task_type'] == 2 && date('Y-m-d', strtotime($value['expected_on'])) < $today) {
                            $ary['employee_name'] = "Stage " . $value['stage'] . " : " .$value['employee_name'];
                            $ary['work_stage'] = "OVER DUE";
                        }  else {
                            // $ary['work_stage'] = $value['work_stage'];
                            if ($value['work_stage'] == 1) {
                            $ary['work_stage'] = "TO DO";
                        }
                        if ($value['work_stage'] == 2) {
                            $ary['work_stage'] = "IN PROGRESS";
                        }
                        if ($value['work_stage'] == 3) {
                            $ary['work_stage'] = "PREVIEW";
                        }
                        if ($value['work_stage'] == 4) {
                            $ary['work_stage'] = "COMPLETED";
                        }
                            // $ary['expected_on'] = $value['expected_on'];
                            $ary['employee_name'] = "Stage " . $value['stage'] . " : " .$value['employee_name'];
                        }
                    }
                    // $ary['date'] = !empty($value['order_date']) ? $value['order_date'] : $value['custom_task_date'];

                    $final[] = $ary;
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
                if ($filterByType == 1) {
                    $sheet->setCellValue('A1', 'Date');
                    $sheet->setCellValue('B1', 'Type Of Task');
                    $sheet->setCellValue('C1', 'Task Code');
                    $sheet->setCellValue('D1', 'Task Name');
                    $sheet->setCellValue('E1', 'Assigned To');
                    $sheet->setCellValue('F1', 'Status');
                }
                if ($filterByType == 2) {
                    $sheet->setCellValue('A1', 'Date');
                    $sheet->setCellValue('B1', 'Type Of Task');
                    $sheet->setCellValue('C1', 'Order ID');
                    $sheet->setCellValue('D1', 'Product ID');
                    $sheet->setCellValue('E1', 'Assigned To');
                    $sheet->setCellValue('F1', 'Status');
                }
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
                $sheet->fromArray($final, null, 'A2');
                $writer = new Xls($spreadsheet);
                $file_name = "customer-report-data.xls";
                $fullpath = storage_path() . '/app/customer_report' . $file_name;
                $writer->save($fullpath); // download file
                return response()->download(storage_path('app/customer_reportcustomer-report-data.xls'), "customer_report.xls");
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

    public function taskReportListPdf_old(Request $request)
    {
        try {
            $today = date('Y-m-d');
            Log::channel("ordereport")->info('** started the admin ordereport list method **');
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByEmployeeName = ($request->filterByEmployeeName) ? $request->filterByEmployeeName : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
            $filterByType = ($request->filterByType) ? $request->filterByType : '';
            $all = $request->all;

            $get_taskreport = TaskManagerHistory::leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->select(
                'task_manager_history.task_manager_history_id',
                'task_manager_history.work_stage',
                'task_manager_history.expected_on',
                'task_manager_history.assigned_on',
                'orders.order_code',
                'orders.order_date',
                'order_items.product_code',
                'task_manager.task_type',
                'task_manager.task_code',
                'task_manager.task_name',
                'task_manager.created_on as custom_task_date',
                'task_manager.task_manager_id',
                'task_manager.order_items_id',
                'employee.employee_name',
                'orderitem_stage.stage'
            );


            if (!empty($from_date)) {
                $get_taskreport->where(function ($query) use ($from_date) {
                    $query->whereDate('task_manager_history.assigned_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $get_taskreport->where(function ($query) use ($to_date) {
                    $query->whereDate('task_manager_history.assigned_on', '<=', $to_date);
                });
            }

            if (!empty($filterByEmployeeName) && $filterByEmployeeName != '[]' && $filterByEmployeeName != 'all') {
                $filterByEmployeeName = json_decode($filterByEmployeeName, true);
                $get_taskreport->whereIn('task_manager_history.employee_id', $filterByEmployeeName);
            }
            if (!empty($filterByStatus)) {
                // $filterByStatus = json_decode($filterByStatus, true);
                $get_taskreport->where('task_manager_history.work_stage', $filterByStatus);
            }
            if ($filterByStatus == 5) {
                $get_taskreport->having('task_manager_history.expected_on', '<', date("Y-m-d 00:00:00"));
            }

            if (!empty($filterByType)) {
                $get_taskreport->where('task_manager.task_type', $filterByType);
            }

            // $count = count($get_taskreport->get());
            $get_taskreport->groupBy('task_manager_history.task_manager_id');
            $get_taskreport->orderBy('task_manager_history.task_manager_history_id', 'desc');
            $count = count($get_taskreport->get());
            $get_order = $get_taskreport->get();
            if ($count > 0) {
                $final = [];
                foreach ($get_order as $value) {
                    $ary = [];
                    $ary['task_manager_id'] = $value['task_manager_id'];
                    $ary['task_manager_history_id'] = $value['task_manager_history_id'];
                    $ary['date'] = !empty($value['order_date']) ? $value['order_date'] : $value['custom_task_date'];
                    if ($value['task_type'] == 1) {
                        $ary['task_type'] = "Task";
                    }
                    if ($value['task_type'] == 2) {
                        $ary['task_type'] = "Order";
                    }
                    $ary['order_code'] = $value['order_code'];
                    $ary['task_name'] = $value['task_name'];
                    $ary['task_code'] = $value['task_code'];
                    $ary['product_code'] = $value['product_code'];
                    $ary['stage'] = $value['stage'];
                    if ($value['task_type'] == 2 && date('Y-m-d', strtotime($value['expected_on'])) < $today) {
                        $ary['employee_name'] = $value['employee_name'];
                        $ary['work_stage'] = "5";
                    } else {
                        $ary['employee_name'] = $value['employee_name'];
                        $ary['work_stage'] = $value['work_stage'];
                    }
                    if ($value['task_type'] == 1 && date('Y-m-d', strtotime($value['expected_on'])) < $today) {
                        $ary['expected_on'] = $value['expected_on'];
                        $ary['employee_name'] = $value['employee_name'];
                        $ary['work_stage'] = "5";
                    } else {
                        $ary['work_stage'] = $value['work_stage'];
                        $ary['expected_on'] = $value['expected_on'];
                        $ary['employee_name'] = $value['employee_name'];
                    }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {

                $path = public_path() . "/taskreport";
                File::makeDirectory($path, env('PERMISSION_MODE_REPORT'), true, true);
                $fileName = "taskreport_" . time() . '.pdf';
                $location = public_path() . '/taskreport/' . $fileName;
                $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
                $mpdf->WriteHTML(\View::make('report/taskreport', $final)->with('final', $final)->with('req', $request)->with('no', 1)->render());
                $mpdf->Output($location, 'F');

                return response()->download($location, "taskreport.pdf");
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

    public function getOrderStageDetails($orderItemId)
    {
        $stageDetails = OrderItemStage::where('orderitem_stage.order_items_id', $orderItemId)->leftjoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'orderitem_stage.orderitem_stage_id')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->select('orderitem_stage.orderitem_stage_id', 'task_manager_history.work_stage', 'task_manager_history.assigned_on', 'task_manager_history.expected_on', 'orderitem_stage.stage', 'employee.employee_name')->where('task_manager_history.production_status', 1)->where('task_manager_history.orderitem_stage_id', '!=', '')->orderBy('orderitem_stage.order_items_id', 'asc')->get();

        $variantArray = [];
        $resultArray = [];

        if (!empty($stageDetails)) {

            foreach ($stageDetails as $data) {

                $variantArray['assigned_on'] = $data['assigned_on'];
                $variantArray['expected_on'] = $data['expected_on'];
                $variantArray['orderitem_stage_id'] = $data['orderitem_stage_id'];
                $variantArray['stage'] = $data['stage'];
                // $variantArray['employee_name'] = $this->getVariantTypeName($data['variant_type_id']);
                $variantArray['employee_name'] = $data['employee_name'];
                // echo date('Y-m-d', strtotime($data['expected_on']));exit;
                // echo date('Y-m-d', strtotime($data['expected_on']. ' + 1 days'));exit;
                // $today = date('Y-m-d');
                $today = date('Y-m-d');
                if (date('Y-m-d', strtotime($data['expected_on'])) < $today) {
                    $variantArray['work_stage'] = 5;
                    // echo("hi");exit;
                } else {
                    $variantArray['work_stage'] = $data['work_stage'];
                }
                $resultArray[] = $variantArray;
                // $resultArray[] = 'Stage'.' '. $data['stage'] . ':' . $data['employee_name'];
            }
        }

        return $resultArray;
    }

    public function assignedEmployeeNameList(Request $request)
    {
        $get_employeeName = EmployeeTaskHistory::leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('employee_task_history.*', 'employee.employee_name')->groupBy('employee_id')->get();

        $final = [];
        if (!empty($get_employeeName)) {

            foreach ($get_employeeName as $data) {
                $ary = [];
                $ary['employee_id'] = $data['employee_id'];
                $ary['employee_name'] = $data['employee_name'];
                $final[] = $ary;
            }
        }

        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Assigned employee name listed successfully'),
                    'data' => $final,
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                ]
            );
        }
    }
}
