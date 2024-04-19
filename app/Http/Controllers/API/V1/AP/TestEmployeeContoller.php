<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Helpers\GlobalHelper;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Http\Requests\EmployeeRequest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;


class TestEmployeeContoller extends Controller
{

    public function department_getcall()
    {
        $get_dept = Department::select('department.*')->get();

        if (!empty($get_dept)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Department listed successfully'),
                    'data' => $get_dept
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
    public function employee_create(EmployeeRequest $request)
    {
        try {
            Log::channel("employee")->info('** started the employee create method **');

            if (!empty($request->employee_image)) {
                $gTImage = json_decode($request->employee_image, true);
                if (!empty($gTImage)) {
                    foreach ($gTImage as $im) {
                        $ary[] = pathinfo($im['image'], PATHINFO_EXTENSION);
                    }
                }
                $extension_array = ['jpeg', 'png', 'jpg'];
                if (!array_diff($ary, $extension_array)) {
                    $request->employee_image;
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data'        => []
                    ]);
                }
            }



            $emailexist = Employee::where([
                ['email', '=', $request->email],
                ['status', '!=', 2]
            ])->first();
            if (empty($emailexist)) {

                $mobileexist = Employee::where([
                    ['mobile_no', '=', $request->mobile_no],
                    ['status', '!=', 2]
                ])->first();

                if (empty($mobileexist)) {
                    $employee = new Employee();
                    $employee->employee_id = $request->employee_code;
                    $employee->employee_type = $request->employee_type;
                    $employee->employee_name = $request->employee_name;
                    $employee->mobile_no = $request->mobile_no;
                    $employee->email = $request->email;
                    $employee->department_id = $request->department_id;
                    $employee->employee_image = $request->employee_image;
                    $employee->created_on = Server::getDateTime();
                    // $employee->created_by = JwtHelper::getSesUserId();
                    Log::channel("employee")->info("request value :: $employee->employee_name");

                    if ($employee->save()) {

                        $employee_code = 'EMP_' . str_pad($employee->employee_id, 3, '0', STR_PAD_LEFT);
                        $update_employeedetails = Employee::find($employee->employee_id);
                        $update_employeedetails->employee_code = $employee_code;
                        $update_employeedetails->save();

                        $employees = Employee::where('employee_id', $employee->employee_id)->first();



                        // log activity
                        // $desc =  'Employee ' . $employee->employee_name  . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                        // $activitytype = Config('activitytype.Employee');
                        // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        Log::channel("employee")->info("save value :: $employees");
                        Log::channel("employee")->info('** end the employee create method **');


                        return response()->json([
                            'keyword'      => 'success',
                            'message'      => __('Employee created successfully'),
                            'data'        => [$employees]
                        ]);
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'message'      => __('Employee creation failed'),
                            'data'        => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Employee mobile number already exist'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Employee email-ID already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("employee")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }





    public function employee_list(Request $request)
    {
        try {
        // Log::channel("employee")->info('** started the employee list method **');
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';




        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'date' => 'employee.created_on',
            'employee_name' => 'employee.employee_name',
            'employee_code' => 'employee.employee_code',
            'department_name' => 'department.department_name',
            'employee_type' => 'employee.employee_type',
            // 'work_status' => 'employee.work_status'
        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "employee.employee_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
        $column_search = array('employee.created_on',
            'employee.employee_name', 'employee.employee_code', 'department.department_name',
            'employee.employee_type'
        );

        $employees = Employee::leftjoin('department', 'department.department_id', '=', 'employee.department_id')
            ->select('employee.*', 'department.department_id', 'department.department_name')
            ->where('employee.status', '!=', '2');

        $employees->where(function ($query) use ($searchval, $column_search, $employees) {
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
            $employees->orderBy($order_by_key[$sortByKey], $sortType);
        }
        if (!empty($from_date)) {
            $employees->where(function ($query) use ($from_date) {
                $query->whereDate('employee.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $employees->where(function ($query) use ($to_date) {
                $query->whereDate('employee.created_on', '<=', $to_date);
            });
        }

        if (!empty($filterByStatus)) {
            if ($filterByStatus == "inactive") {
                $employees->where('employee.status', 0);
               
            }

            if ($filterByStatus == "active") {
                $employees->where('employee.status', 1);
            }
        }

        $count = $employees->count();

        if ($offset) {
            $offset = $offset * $limit;
            $employees->offset($offset);
        }
        if ($limit) {
            $employees->limit($limit);
        }
        Log::channel("employee")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
        $employees->orderBy('employee.employee_id', 'desc');
        $employees = $employees->get();
        if ($count > 0) {
            $final = [];
            foreach ($employees as $value) {
                $ary = [];
                $ary['employee_id'] = $value['employee_id'];
                $ary['employee_code'] = $value['employee_code'];
                $ary['employee_name'] = $value['employee_name'];
                //$ary['image'] = $value['employee_image'];
                //$ary['employee_image'] = ($ary['image'] != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
                       
             
                                
                if ($value['employee_type'] == 1) {
                    $ary['employee_type'] = "In-House";
                }
                if ($value['employee_type'] == 2) {
                    $ary['employee_type'] = "Vendor";
                }
                $ary['department_id'] = $value['department_id'];
                $ary['department_name'] = $value['department_name'];
                $ary['mobile_no'] = $value['mobile_no'];
                $ary['email'] = $value['email'];
                $ary['work_status'] = 0;
                // $ary['created_on'] = $value['created_on'];
                $ary['created_on'] = date('d-m-Y', strtotime($value['created_on']));
                $ary['created_by'] = date('d-m-Y', strtotime($value['created_by']));
                $ary['updated_on'] = date('d-m-Y', strtotime($value['updated_on']));
                $ary['updated_by'] = date('d-m-Y', strtotime($value['updated_by']));
                // $ary['created_by'] = $value['created_by'];
                // $ary['updated_on'] = $value['updated_on'];
                // $ary['updated_by'] = $value['updated_by'];
                $ary['status'] = $value['status'];
                $final[] = $ary;
            }
        }
        if (!empty($final)) {
            $log = json_encode($final, true);
            Log::channel("employee")->info("list value :: $log");
            Log::channel("employee")->info('** end the employee list method **');
            return response()->json([
                'keyword' => 'success',
                'message' => __('Employee listed successfully'),
                'data' => $final,
                'count' => count($employees)
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => [],
                'count' => count($employees)
            ]);
        }
        } catch (\Exception $exception) {
            Log::channel("employee")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function getdefaultImages_allImages($gTImage)
    {
        $imG = [];
        if (!empty($gTImage)) {
            foreach ($gTImage as $im) {
                $ary = [];
                $ary['index'] = $im['index'];
                $ary['url'] = ($im['image'] != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $im['image'] : env('APP_URL') . "avatar.jpg";
                $ary['image'] = $im['image'];
                $imG[] = $ary;
            }
        }
        return $imG;
    }

    public function employee_update(EmployeeRequest $request)
    {
        try {

            if (!empty($request->employee_image)) {
                $gTImage = json_decode($request->employee_image, true);
                if (!empty($gTImage)) {
                    foreach ($gTImage as $im) {
                        $ary[] = pathinfo($im['image'], PATHINFO_EXTENSION);
                    }
                }
                $extension_array = ['jpeg', 'png', 'jpg'];
                if (!array_diff($ary, $extension_array)) {
                    $request->employee_image;
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data'        => []
                    ]);
                }
            }


            $emailexist = Employee::where([
                ['employee_id', '!=', $request->employee_id],
                ['email', '=', $request->email],
                ['status', '!=', 2]
            ])->first();
            if (empty($emailexist)) {

                $mobileexist = Employee::where([
                    ['employee_id', '!=', $request->employee_id],
                    ['mobile_no', '=', $request->mobile_no],
                    ['status', '!=', 2]
                ])->first();

                if (empty($mobileexist)) {
                    $ids = $request->input('employee_id');
                    $employee = Employee::find($ids);

                    $employee->employee_type = $request->employee_type;
                    $employee->employee_name = $request->employee_name;
                    $employee->mobile_no = $request->mobile_no;
                    $employee->email = $request->email;
                    $employee->department_id = $request->department_id;
                    $employee->employee_image = $request->employee_image;
                    $employee->updated_on = Server::getDateTime();
                    // $employee->updated_by = JwtHelper::getSesUserId();

                    if ($employee->save()) {
                        $employees = Employee::where('employee_id', $employee->employee_id)->first();
                        // log activity
                        // $desc =  'Employee' . $employee->employee_name  . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                        // $activitytype = Config('activitytype.Employee');
                        // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        Log::channel("employee")->info("save value :: $employees");



                        return response()->json([
                            'keyword'      => 'success',
                            'data'        => [$employees],
                            'message'      => __('Employee updated successfully')
                        ]);
                    } else {
                        return response()->json([
                            'keyword'      => 'failed',
                            'data'        => [],
                            'message'      => __('Employee update failed'),
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Employee mobile number already exist'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Employee Email-ID already exist'),
                    'data'        => []
                ]);

                // } else {
                //     return response()->json([
                //         'keyword'      => 'failed',
                //         'message'      => __('Employee name already exist'),
                //         'data'        => []
                //     ]);
            }
        } catch (\Exception $exception) {
            Log::channel("employee")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }



    public function employee_view($id)
    {
        try {
            Log::channel("employee")->info('** started the employee view method **');
            $employee_view = Employee::where('employee_id', $id)
                ->leftjoin('department', 'department.department_id', '=', 'employee.department_id')
                ->select('employee.*', 'department.department_id', 'department.department_name')->first();

            Log::channel("employee")->info("request value employee_id:: $id");



            $final = [];

            if (!empty($employee_view)) {
                $ary = [];
                $ary['employee_id'] = $employee_view['employee_id'];
                $ary['employee_code'] = $employee_view['employee_code'];
                $ary['employee_name'] = $employee_view['employee_name'];
                $ary['image'] = $employee_view['employee_image'];
                $ary['employee_image'] = ($ary['image'] != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $ary['image'] : env('APP_URL') . "avatar.jpg";
                if ($employee_view['employee_type'] == 1) {
                    $ary['employee_type'] = "In-House";
                }
                if ($employee_view['employee_type'] == 2) {
                    $ary['employee_type'] = "Vendor";
                }
                if ($employee_view['employee_type'] == 1) {
                $ary['department_id'] = $employee_view['department_id'];
                $ary['department_name'] = $employee_view['department_name'];
                }
                $ary['mobile_no'] = $employee_view['mobile_no'];
                $ary['email'] = $employee_view['email'];
                $gTImage = json_decode($employee_view['employee_image'], true);
                $ary['employee_image'] = $this->getdefaultImages_allImages($gTImage);
                // $ary['created_on'] = $employee_view['created_on'];
                // $ary['created_on'] = date('d-m-Y', strtotime($employee_view['created_on']));

                $ary['created_on'] = date('d-m-Y', strtotime($employee_view['created_on']));
                $ary['created_by'] = date('d-m-Y', strtotime($employee_view['created_by']));
                $ary['updated_on'] = date('d-m-Y', strtotime($employee_view['updated_on']));
                $ary['updated_by'] = date('d-m-Y', strtotime($employee_view['updated_by']));

                // $ary['created_by'] = $employee_view['created_by'];
                // $ary['updated_on'] = $employee_view['updated_on'];
                // $ary['updated_by'] = $employee_view['updated_by'];
                $ary['status'] = $employee_view['status'];
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("employee")->info("view value :: $log");
                Log::channel("employee")->info('** end the employee view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Employee viewed successfully'),
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
            Log::channel("employee")->error($exception);
            Log::channel("employee")->info('** end the employee view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function employee_status(Request $request)
    {
        try {
            if (!empty($request)) {

                $ids = $request->id;

                if (!empty($ids)) {
                    Log::channel("employee")->info('** started the employee status method **');
                    Log::channel("employee")->info("request value employee_id:: $ids :: status :: $request->status");

                    $employee = Employee::where('employee_id', $ids)->first();
                    $update = Employee::where('employee_id', $ids)->update(array(
                        'status' => $request->status,
                        'updated_on' => Server::getDateTime(),
                        // 'updated_by' => JwtHelper::getSesUserId()
                    ));

                    //   log activity
                    // $activity_status = ($request->status) ? 'activated' : 'inactivated';
                    // // $implode = implode(",", $ids);
                    // $desc = 'Employee '  . $employee->employee_name  . ' is ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                    // $activitytype = Config('activitytype.Employee');
                    // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    if ($request->status == 0) {
                        Log::channel("employee")->info("save value :: employee_id :: $ids :: employee inactive successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Employee inactivated successfully'),
                            'data' => []
                        ]);
                    } else if ($request->status == 1) {
                        Log::channel("employee")->info("save value :: employee_id :: $ids :: employee active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Employee activated successfully'),
                            'data' => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.no_data'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("employee")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function employee_delete(Request $request)
    {
        try {
            if (!empty($request)) {
                $ids = $request->id;

                if (empty($exist)) {
                    Log::channel("employee")->info('** started the employee delete method **');
                    Log::channel("employee")->info("request value employee_id:: $ids :: ");

                    $employee = Employee::where('employee_id', $ids)->first();
                    $update = Employee::where('employee_id', $ids)->update(array(
                        'status' => 2,
                        'updated_on' => Server::getDateTime(),
                        // 'updated_by' => JwtHelper::getSesUserId()
                    ));

                    // log activity
                    // $implode = implode(",", $ids);
                    // $desc =  ' Employee '  . $employee->employee_name  . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                    // $activitytype = Config('activitytype.Employee');
                    // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                    // Log::channel("employee")->info("save value :: employee_id :: $ids :: employee deleted successfully");
                    Log::channel("employee")->info('** end the employee delete method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  __('Employee deleted successfully'),
                        'data' => []
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('message.failed'),
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.failed'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("employee")->error($exception);
            Log::channel("employee")->info('** end the employee delete method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function employee_Excel(Request $request)
    {
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $all = $request->all;

        $getemployee = Employee::leftjoin('department', 'department.department_id', '=', 'employee.department_id')
            ->select('employee.*', 'department.department_id', 'department.department_name')
            ->where('employee.status', '!=', '2');

        if (!empty($from_date)) {
            $getemployee->where(function ($query) use ($from_date) {
                $query->whereDate('employee.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $getemployee->where(function ($query) use ($to_date) {
                $query->whereDate('employee.created_on', '<=', $to_date);
            });
        }
        if (!empty($filterByStatus)) {
            if ($filterByStatus == "inactive") {
                $getemployee->where('employee.status', 0);
               
            }

            if ($filterByStatus == "active") {
                $getemployee->where('employee.status', 1);
            }
        }

        $getemployee = $getemployee->get();


        $count = count($getemployee);

        $s = 1;
        if (!empty($getemployee)) {

            if ($count > 0) {
            $overll = [];
            foreach ($getemployee as $employee) {
                $ary = [];
                $ary['date'] = date('d-m-Y', strtotime($employee['created_on']));
                $ary['employee_id'] = $employee['employee_code'];
                $ary['employee_name'] = $employee['employee_name'];
                $ary['department_name'] = $employee['department_name'];
                if ($employee['employee_type'] == 1) {
                    $ary['employee_type'] = "In-House";
                }
                if ($employee['employee_type'] == 2) {
                    $ary['employee_type'] = "Vendor";
                }
                // $ary['work_status'] = $employee['work_status'];
                if ($employee['work_status'] == 0) {
                    $ary['work_status'] = "0";
                }
                
                if ($employee['status'] == 0) {
                    $ary['status'] = "In-Active";
                } 
                if  ($employee['status'] == 1) {
                    $ary['status'] = "Active";
                }
                $overll[] = $ary;
            }
            $s++;

            $excel_report_title = "Employees List Report";

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

            $sheet->setCellValue('A1', 'Date');
            $sheet->setCellValue('B1', 'Employee ID');
            $sheet->setCellValue('C1', 'Employee Name');
            $sheet->setCellValue('D1', 'Department');
            $sheet->setCellValue('E1', 'Employment Type');
            $sheet->setCellValue('F1', 'Work Status');
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
}