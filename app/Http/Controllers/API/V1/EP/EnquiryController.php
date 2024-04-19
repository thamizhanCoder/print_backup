<?php

namespace App\Http\Controllers\API\V1\EP;

use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEnquiryRequest;
use App\Http\Requests\UpdateEnquiryRequest;
use App\Http\Requests\UpdateEnquiryStatusRequest;
use App\Models\BulkOrderEnquiry;
use App\Models\BulkOrderEnquiryStatus;
use App\Models\BulkOrderQuote;
use App\Models\BulkOrderTrackHistory;
use App\Models\Customer;
use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\EnquirySearchView;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\Service;
use App\Models\State;
use App\Models\UserModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Mail;

class EnquiryController extends Controller
{
    public function create_enquiry_employee(CreateEnquiryRequest $request)
    {
        try {
            Log::channel("employeeEnquiry")->info('** started the employee enquiry create method **');

            $getSesId = JwtHelper::getSesEmployeeId();
            $getEmployeeDeptId = Employee::where('employee_id', $getSesId)->first();
            $createEnquiry = new BulkOrderEnquiry();
            $createEnquiry->customer_type = $request->customer_type;
            if ($request->customer_type == 1) {
                $createEnquiry->company_name = NULL;
            }
            if ($request->customer_type == 2) {
                $createEnquiry->company_name = $request->company_name;
            }
            $createEnquiry->contact_person_name = $request->contact_person_name;
            $createEnquiry->mobile_no = $request->mobile_number;
            $createEnquiry->alternative_mobile_no = $request->alternative_mobile_number;
            $createEnquiry->email = $request->email;
            $createEnquiry->address = $request->address;
            $createEnquiry->state_id = $request->state;
            $createEnquiry->district_id = $request->city;
            $createEnquiry->service_id = $request->category;
            $createEnquiry->message = $request->message;
            $createEnquiry->label_attachments = $request->label_attachments;
            $createEnquiry->platform = "Direct";
            $createEnquiry->department_id = $getEmployeeDeptId->department_id;
            $createEnquiry->employee_id = JwtHelper::getSesEmployeeId();
            $createEnquiry->portal_type = 2;
            $createEnquiry->created_on = Server::getDateTime();
            $createEnquiry->created_by = JwtHelper::getSesEmployeeId();
            if ($createEnquiry->save()) {

                Log::channel("employeeEnquiry")->info("request value :: " . json_encode($createEnquiry, true));

                $enquiryCode = BulkOrderEnquiry::find($createEnquiry->bulk_order_enquiry_id);
                $enquiry_code = 'ENQ' . str_pad($createEnquiry->bulk_order_enquiry_id, 3, '0', STR_PAD_LEFT);
                $enquiryCode->enquiry_code = $enquiry_code;
                $enquiryCode->save();

                //Track History Create - New Enquiry created status
                $trackhistoryCreate = new BulkOrderTrackHistory();
                $trackhistoryCreate->bulk_order_enquiry_id = $createEnquiry->bulk_order_enquiry_id;
                $trackhistoryCreate->status = 1;
                $trackhistoryCreate->employee_id = JwtHelper::getSesEmployeeId();
                $trackhistoryCreate->created_on = Server::getDateTime();
                $trackhistoryCreate->portal_type = 2;
                $trackhistoryCreate->save();

                $bulkOrderEnquiry = BulkOrderEnquiry::where('bulk_order_enquiry_id', $createEnquiry->bulk_order_enquiry_id)->first();
                
                if(!empty($bulkOrderEnquiry)){
                    if ($bulkOrderEnquiry->email != null) {
                        $mail_data = [
                            'email' => $bulkOrderEnquiry->email,
                            'enquiry_id' => $enquiry_code,
                            'customer_name'=> $bulkOrderEnquiry->contact_person_name,
                        ];

                        Mail::send('mail.sendbulkorderenquirycustomeremployee', $mail_data, function ($message) use ($mail_data) {
                            $message->to($mail_data['email'])
                                    ->subject('Enquiry Created Successfully');
                        });
                        }
                    }
                Log::channel("employeeEnquiry")->info("save value :: $createEnquiry");
                Log::channel("employeeEnquiry")->info('** end the employee enquiry create method **');

                return response()->json(['keyword' => 'success', 'message' => ('Enquiry created successfully'), 'data' => $createEnquiry]);
            } else {

                return response()->json(['keyword' => 'failure', 'message' => __('message.failed'), 'data' => []]);
            }
        } catch (\Exception $exception) {
            Log::channel("employeeEnquiry")->error('**starts error occured in employee enquiry create method **');
            Log::channel("employeeEnquiry")->error($exception);
            Log::channel("employeeEnquiry")->error('**end error occured in employee enquiry create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function update_enquiry_employee(UpdateEnquiryRequest $request)
    {
        try {
            Log::channel("employeeEnquiry")->info('** started the employee enquiry update method **');

            $updatEnquiry = BulkOrderEnquiry::find($request->enquiry_id);
            $updatEnquiry->customer_type = $request->customer_type;
            if ($request->customer_type == 1) {
                $updatEnquiry->company_name = NULL;
            }
            if ($request->customer_type == 2) {
                $updatEnquiry->company_name = $request->company_name;
            }
            $updatEnquiry->contact_person_name = $request->contact_person_name;
            $updatEnquiry->mobile_no = $request->mobile_number;
            $updatEnquiry->alternative_mobile_no = $request->alternative_mobile_number;
            $updatEnquiry->email = $request->email;
            $updatEnquiry->address = $request->address;
            $updatEnquiry->state_id = $request->state;
            $updatEnquiry->district_id = $request->city;
            $updatEnquiry->service_id = $request->category;
            $updatEnquiry->message = $request->message;
            $updatEnquiry->label_attachments = $request->label_attachments;
            $updatEnquiry->updated_on = Server::getDateTime();
            $updatEnquiry->updated_by = JwtHelper::getSesEmployeeId();

            if ($updatEnquiry->save()) {

                Log::channel("employeeEnquiry")->info("request value :: " . json_encode($updatEnquiry, true));
        
                Log::channel("employeeEnquiry")->info("save value :: $updatEnquiry");
                Log::channel("employeeEnquiry")->info('** end the employee enquiry update method **');

                return response()->json(['keyword' => 'success', 'message' => ('Enquiry updated successfully'), 'data' => $updatEnquiry]);
            } else {

                return response()->json(['keyword' => 'failure', 'message' => __('message.failed'), 'data' => []]);
            }
        } catch (\Exception $exception) {
            Log::channel("employeeEnquiry")->error('**starts error occured in employee enquiry update method **');
            Log::channel("employeeEnquiry")->error($exception);
            Log::channel("employeeEnquiry")->error('**end error occured in employee enquiry update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function employee_enquiry_list(Request $request)
    {
        try {
            Log::channel("employeeEnquiry")->info('** started the employee enquiry list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
            $filterByCustomerType = ($request->filterByCustomerType) ? $request->filterByCustomerType : '';
            $filterByPlatform = ($request->filterByPlatform) ? $request->filterByPlatform : '';

            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'date' => 'bulk_order_enquiry.created_on',
                'enquiry_code' => 'bulk_order_enquiry.enquiry_code',
                'contact_person_name' => 'bulk_order_enquiry.contact_person_name',
                'mobile_no' => 'bulk_order_enquiry.mobile_no',
                'email' => 'bulk_order_enquiry.email',
                'company_name' => 'bulk_order_enquiry.company_name',
                'platform' => 'bulk_order_enquiry.platform',
                'type' => 'bulk_order_enquiry.customer_type'
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "bulk_order_enquiry_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

            $column_search = array('bulk_order_enquiry.enquiry_code', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email', 'bulk_order_enquiry.company_name', 'bulk_order_enquiry.platform');

            $getEmployeeId = JwtHelper::getSesEmployeeId();

            $getEnquiryList = BulkOrderEnquiry::where('bulk_order_enquiry.employee_id',  $getEmployeeId)->select(
                'bulk_order_enquiry.*',
            );

            $getEnquiryList->where(function ($query) use ($searchval, $column_search, $getEnquiryList) {
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

            //Sort by key and Sort Type
            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $getEnquiryList->orderBy($order_by_key[$sortByKey], $sortType);
            }

            //Filter by from and to date
            if (!empty($from_date)) {
                $getEnquiryList->where(function ($query) use ($from_date) {
                    $query->whereDate('bulk_order_enquiry.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $getEnquiryList->where(function ($query) use ($to_date) {
                    $query->whereDate('bulk_order_enquiry.created_on', '<=', $to_date);
                });
            }

            //Filter By Status
            if (!empty($filterByStatus)) {
                $getEnquiryList->where('bulk_order_enquiry.status', $filterByStatus);
            }

            //Filter By Customer Type
            if (!empty($filterByCustomerType)) {
                $getEnquiryList->where('bulk_order_enquiry.customer_type', $filterByCustomerType);
            }

            //Filter By Platform
            if (!empty($filterByPlatform)) {
                $getEnquiryList->where('bulk_order_enquiry.platform', $filterByPlatform);
            }

            $count = count($getEnquiryList->get());
            if ($offset) {
                $offset = $offset * $limit;
                $getEnquiryList->offset($offset);
            }
            if ($limit) {
                $getEnquiryList->limit($limit);
            }
            $getEnquiryList->orderBy('bulk_order_enquiry_id', 'desc');
            $getEnquiryDetails = $getEnquiryList->get();
            if ($count > 0) {
                $final = [];
                foreach ($getEnquiryDetails as $value) {
                    $ary = [];
                    $today = date('Y-m-d');
                    $ary['bulk_order_enquiry_id'] = $value['bulk_order_enquiry_id'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['enquiry_code'] = $value['enquiry_code'] ?? "-";
                    $ary['contact_person_name'] = $value['contact_person_name'];
                    $ary['mobile_no'] = $value['mobile_no'];
                    $ary['email'] = $value['email'];
                    if (!empty($value['service_id'])) {
                        $service_name = $this->getServiceNameforList($value['service_id']);
                        $ary['category'] = (!empty($service_name)) ? json_encode($service_name, true) : NULL;
                    } else {
                        $ary['category'] = $value['service_id'];
                    }
                    $ary['customer_type'] = $value['customer_type'];
                    if ($value['customer_type'] == 1) {
                        $ary['type'] = "Individual";
                    }
                    if ($value['customer_type'] == 2) {
                        $ary['type'] = "Company";
                    }
                    $ary['company_name'] = $value['company_name'];
                    $ary['status'] = $value['status'];
                    $getStatus = BulkOrderEnquiryStatus::where('bulk_order_enquiry_status_id', $ary['status'])->first();
                    $ary['enquiry_status'] =  $getStatus->status;
                    $ary['platform'] = $value['platform'];
                    if ($value['enquiry_date'] == $today && $value['status'] == 2) {
                        $ary['follow_up_reminder'] = 1;
                    } else {
                        $ary['follow_up_reminder'] = 0;
                    }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("employeeEnquiry")->info("employee enquiry list value :: $log");
                Log::channel("employeeEnquiry")->info('** end the employee enquiry list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Enquiry listed successfully'),
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
            Log::channel("employeeEnquiry")->info('** start the error employee enquiry list method **');
            Log::channel("employeeEnquiry")->error($exception);
            Log::channel("employeeEnquiry")->info('** end the error employee enquiry list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function getServiceNameforList($serviceId)
    {
        $serviceNameClk = json_decode($serviceId, true);
        $serviceClicked = Service::whereIn('service_id', $serviceNameClk)->orderBy('service_id', 'asc')->get();
        $service_name = [];
        foreach ($serviceClicked as $key => $name) {
            $service_name[$key] = $name->service_name;
        }
        return $service_name;
    }

    public function employee_enquiry_list_excel(Request $request)
    {

        Log::channel("employeeEnquiry")->info('** started the employee enquiry list excel method **');
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';
        $filterByCustomerType = ($request->filterByCustomerType) ? $request->filterByCustomerType : '';
        $filterByPlatform = ($request->filterByPlatform) ? $request->filterByPlatform : '';

        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'date' => 'bulk_order_enquiry.created_on',
            'enquiry_code' => 'bulk_order_enquiry.enquiry_code',
            'contact_person_name' => 'bulk_order_enquiry.contact_person_name',
            'mobile_no' => 'bulk_order_enquiry.mobile_no',
            'email' => 'bulk_order_enquiry.email',
            'company_name' => 'bulk_order_enquiry.company_name',
            'platform' => 'bulk_order_enquiry.platform',
            'type' => 'bulk_order_enquiry.customer_type'
        ];
        $sort_dir = ['ASC', 'DESC'];
        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "bulk_order_enquiry_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

        $column_search = array('bulk_order_enquiry.enquiry_code', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no', 'bulk_order_enquiry.email', 'bulk_order_enquiry.company_name', 'bulk_order_enquiry.platform');

        $getEmployeeId = JwtHelper::getSesEmployeeId();



        $getEnquiryList = BulkOrderEnquiry::where('bulk_order_enquiry.employee_id',$getEmployeeId)->select(
            'bulk_order_enquiry.*'
        );

        //Sort by key and Sort Type
        if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
            $getEnquiryList->orderBy($order_by_key[$sortByKey], $sortType);
        }

        $getEnquiryList->where(function ($query) use ($searchval, $column_search, $getEnquiryList) {
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

        //Filter by from and to date
        if (!empty($from_date)) {
            $getEnquiryList->where(function ($query) use ($from_date) {
                $query->whereDate('bulk_order_enquiry.created_on', '>=', $from_date);
            });
        }
        if (!empty($to_date)) {
            $getEnquiryList->where(function ($query) use ($to_date) {
                $query->whereDate('bulk_order_enquiry.created_on', '<=', $to_date);
            });
        }

        //Filter By Status
        if (!empty($filterByStatus)) {
            $getEnquiryList->where('bulk_order_enquiry.status', $filterByStatus);
        }

        //Filter By Customer Type
        if (!empty($filterByCustomerType)) {
            $getEnquiryList->where('bulk_order_enquiry.customer_type', $filterByCustomerType);
        }

        //Filter By Platform
        if (!empty($filterByPlatform)) {
            $getEnquiryList->where('bulk_order_enquiry.platform', $filterByPlatform);
        }

        $count = count($getEnquiryList->get());
        if ($offset) {
            $offset = $offset * $limit;
            $getEnquiryList->offset($offset);
        }
        if ($limit) {
            $getEnquiryList->limit($limit);
        }
        Log::channel("employeeEnquiry")->info("request value :: $limit :: $offset :: $searchval :: $from_date:: $to_date::");
        $getEnquiryList->orderBy('bulk_order_enquiry_id', 'desc');
        $getEnquiryDetails = $getEnquiryList->get();
        $s = 1;
        if (!empty($getEnquiryDetails)) {
            if ($count > 0) {
                $overll = [];
                foreach ($getEnquiryDetails as $value) {
                    $ary = [];
                    $today = date('Y-m-d');
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on'])) ?? "-";
                    $ary['enquiry_code'] = $value['enquiry_code'] ?? "-";
                    $ary['contact_person_name'] = $value['contact_person_name'] ?? "-";
                    $ary['mobile_no'] = $value['mobile_no'] ?? "-";
                    $ary['email'] = $value['email'] ?? "-";
                    if (!empty($value['service_id'])) {
                        $service_name = $this->getServiceNameforList($value['service_id']);
                        $itmesNames = implode(", ", $service_name) ?? "-";
                        $ary['category'] =  $itmesNames;
                    } else {
                        $ary['category'] = "-";
                    }

                    if ($value['customer_type'] == 1) {
                        $ary['type'] = "Individual";
                    }
                    if ($value['customer_type'] == 2) {
                        $ary['type'] = "Company";
                    }
                    $ary['company_name'] = $value['company_name'] ?? "-";
                    $ary['platform'] = $value['platform'];
                    if ($value['enquiry_date'] == $today && $value['status'] == 2) {
                        $ary['follow_up_reminder'] = date('d-m-Y', strtotime($value['enquiry_date']));
                    } else {
                        $ary['follow_up_reminder'] = '-';
                    }
                    $getStatus = BulkOrderEnquiryStatus::where('bulk_order_enquiry_status_id', $value['status'])->first();
                    $ary['enquiry_status'] =  $getStatus->status;
                    $overll[] = $ary;
                }
                $s++;
                $excel_report_title = "Enquiry List Report";
                $spreadsheet = new Spreadsheet();
                //Set document properties
                $spreadsheet->getProperties()->setCreator("Technogenesis")
                    ->setLastModifiedBy("Technogenesis")
                    ->setTitle("Enquiry List Report")
                    ->setSubject("Enquiry List Report")
                    ->setDescription("Enquiry List Report")
                    ->setKeywords("Enquiry List Report")
                    ->setCategory("Enquiry List Report");
                $spreadsheet->getProperties()->setCreator("technogenesis.in")
                    ->setLastModifiedBy("Technogenesis");
                $spreadsheet->setActiveSheetIndex(0);
                $sheet = $spreadsheet->getActiveSheet();
                //name the worksheet
                $sheet->setTitle($excel_report_title);
                $sheet->setCellValue('A1', 'Date');
                $sheet->setCellValue('B1', 'Enquiry ID');
                $sheet->setCellValue('C1', 'Customer');
                $sheet->setCellValue('D1', 'Mobile No');
                $sheet->setCellValue('E1', 'Email ID');
                $sheet->setCellValue('F1', 'Category');
                $sheet->setCellValue('G1', 'Customer Type');
                $sheet->setCellValue('H1', 'Company');
                $sheet->setCellValue('I1', 'Platform');
                $sheet->setCellValue('J1', 'Follow Up Date');
                $sheet->setCellValue('K1', 'Status');
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
                $file_name = "Enquiry-report-data.xls";
                $fullpath = storage_path() . '/app/Enquiry_report' . $file_name;
                $writer->save($fullpath); // download file
                return response()->download(storage_path('app/Enquiry_reportEnquiry-report-data.xls'), "Enquiry_report.xls");
            }
        }
    }

    public function employee_enquiry_status_update(UpdateEnquiryStatusRequest $request)
    {
        try {
            Log::channel("employeeEnquiry")->info('** started the employee enquiry status update method **');

            $updateStatusEnquiry = new BulkOrderTrackHistory();
            $updateStatusEnquiry->bulk_order_enquiry_id = $request->bulk_order_enquiry_id;
            $updateStatusEnquiry->status = $request->enquiry_status;
            $updateStatusEnquiry->enquiry_date = $request->enquiry_date;
            $updateStatusEnquiry->enquiry_notes = $request->enquiry_notes;
            $updateStatusEnquiry->is_check = $request->is_requested_or_reviewed;
            $updateStatusEnquiry->portal_type = 2;
            $updateStatusEnquiry->created_on = Server::getDateTime();
            $updateStatusEnquiry->employee_id = JwtHelper::getSesEmployeeId();

            if ($updateStatusEnquiry->save()) {

                Log::channel("employeeEnquiry")->info("request value :: " . json_encode($request->all(), true));

                //Bulk order table update
                $enquiryCode = BulkOrderEnquiry::find($updateStatusEnquiry->bulk_order_enquiry_id);
                $enquiryCode->enquiry_date = $updateStatusEnquiry->enquiry_date;
                $enquiryCode->enquiry_notes = $updateStatusEnquiry->enquiry_notes;
                $enquiryCode->status = $updateStatusEnquiry->status;
                $enquiryCode->save();

                
                //Send Mail For Customer - Sample Requested
                if($updateStatusEnquiry->status == 3){

                    $getEnqDet = BulkOrderEnquiry::where('bulk_order_enquiry_id',$updateStatusEnquiry->bulk_order_enquiry_id)->first();
                    if (!empty($getEnqDet->email)) {
                        if ($getEnqDet->email != null) {
    
    
                            $mail_data = [
                                'email' => $getEnqDet->email,
                                'enquiry_code' => $getEnqDet->enquiry_code,
                                'customer_name' => !empty($getEnqDet->contact_person_name) ? $getEnqDet->contact_person_name : $getEnqDet->contact_person_name,
                                'notes' => $getEnqDet->enquiry_notes,
                                'date' => date('d/m/Y', strtotime($getEnqDet->enquiry_date)),
                            ];
    
                            Mail::send('mail.sendsamplerequestedcustomeremployee', $mail_data, function ($message) use ($mail_data) {
                                $message->to($mail_data['email'])
                                    ->subject('Sample Requested');
                            });
                        }
                    }

                }

                  //Send Mail For Customer - Sample Reviewed
                  if($updateStatusEnquiry->status == 4){

                    $getEnqDet = BulkOrderEnquiry::where('bulk_order_enquiry_id',$updateStatusEnquiry->bulk_order_enquiry_id)->first();
                    if (!empty($getEnqDet->email)) {

                        if ($getEnqDet->email != null) {
    
                            $mail_data = [
                                'email' => $getEnqDet->email,
                                'enquiry_code' => $getEnqDet->enquiry_code,
                                'customer_name' => !empty($getEnqDet->contact_person_name) ? $getEnqDet->contact_person_name : $getEnqDet->contact_person_name,
                                'notes' => $getEnqDet->enquiry_notes,
                                'date' => date('d/m/Y', strtotime($getEnqDet->enquiry_date)),
                            ];
    
                            Mail::send('mail.sendsamplereviewedcustomeremployee', $mail_data, function ($message) use ($mail_data) {
                                $message->to($mail_data['email'])
                                    ->subject('Sample Reviewed');
                            });
                        }
                    }

                }

                 //Send Mail For Customer - Interested
                if($updateStatusEnquiry->status == 5){

                    $getEnqDet = BulkOrderEnquiry::where('bulk_order_enquiry_id',$updateStatusEnquiry->bulk_order_enquiry_id)->first();
                    if (!empty($getEnqDet->email)) {

                        if ($getEnqDet->email != null) {
    
                            $mail_data = [
                                'email' => $getEnqDet->email,
                                'enquiry_code' => $getEnqDet->enquiry_code,
                                'customer_name' => !empty($getEnqDet->contact_person_name) ? $getEnqDet->contact_person_name : $getEnqDet->contact_person_name,
                                'notes' => $getEnqDet->enquiry_notes,
                            ];
    
                            Mail::send('mail.sendinterestedenquirycustomeremployee', $mail_data, function ($message) use ($mail_data) {
                                $message->to($mail_data['email'])
                                    ->subject('Interested');
                            });
                        }
                    }

                }

                Log::channel("employeeEnquiry")->info("save value :: $updateStatusEnquiry");
                Log::channel("employeeEnquiry")->info('** end the employee enquiry status update method **');

                return response()->json(['keyword' => 'success', 'message' => ('Enquiry status updated successfully'), 'data' => $updateStatusEnquiry]);
            } else {

                return response()->json(['keyword' => 'failure', 'message' => __('message.failed'), 'data' => []]);
            }
        } catch (\Exception $exception) {
            Log::channel("employeeEnquiry")->error('**starts error occured in employee enquiry status update method **');
            Log::channel("employeeEnquiry")->error($exception);
            Log::channel("employeeEnquiry")->error('**end error occured in employee enquiry status update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function employee_enquiry_view($id)
    {
        try {
            // log start *********
            Log::channel("employeeEnquiry")->info("******* Employee Enquiry View Method Start *******");
            Log::channel("employeeEnquiry")->info("Employee Enquiry Controller start:: find ID : $id");
            // log start *********

            if ($id != '' && $id > 0) {
                $data = BulkOrderEnquiry::where('bulk_order_enquiry_id', $id)->leftjoin('state', 'state.state_id', '=', 'bulk_order_enquiry.state_id')->select('bulk_order_enquiry.*', 'state.state_name')->leftjoin('district', 'district.district_id', '=', 'bulk_order_enquiry.district_id')->select('bulk_order_enquiry.*', 'state.state_name', 'district.district_name')->first();

                if (!empty($data)) {

                    $ary = [];
                    $ary['bulk_order_enquiry_id'] = $data['bulk_order_enquiry_id'];
                    $ary['date'] = date('d-m-Y', strtotime($data['created_on']));
                    $ary['enquiry_code'] = $data['enquiry_code'];
                    $ary['contact_person_name'] = $data['contact_person_name'];
                    $ary['mobile_no'] = $data['mobile_no'];
                    $ary['alternative_mobile_no'] = $data['alternative_mobile_no'];
                    $ary['email'] = $data['email'];
                    $ary['category_id'] = $data['service_id'];
                    if (!empty($data['service_id'])) {
                        $service_name = $this->getServiceNameforList($data['service_id']);
                        $ary['category'] = (!empty($service_name)) ? json_encode($service_name, true) : NULL;
                    } else {
                        $ary['category'] = NULL;
                    }
                    $ary['customer_type'] = $data['customer_type'];
                    if ($data['customer_type'] == 1) {
                        $ary['type'] = "Individual";
                    }
                    if ($data['customer_type'] == 2) {
                        $ary['type'] = "Company";
                    }
                    $ary['company_name'] = $data['company_name'];
                    $ary['status'] = $data['status'];
                    $getStatus = BulkOrderEnquiryStatus::where('bulk_order_enquiry_status_id', $ary['status'])->first();
                    $ary['enquiry_status'] =  $getStatus->status;
                    $ary['message'] = $data['message'];
                    $ary['address'] = $data['address'];
                    $ary['state_id'] = $data['state_id'];
                    $ary['state_name'] = $data['state_name'];
                    $ary['district_id'] = $data['district_id'];
                    $ary['district_name'] = $data['district_name'];
                    $ary['platform'] = $data['platform'];
                    $ary['portal_type'] = $data['portal_type'];
                    if (!empty($data['label_attachments'])) {
                        $ary['label_attachments'] = $this->getLabelAttachmentImage($data['label_attachments']);
                    } else {
                        $ary['label_attachments'] = $data['label_attachments'];
                    }
                    $ary['track_history_info'] = $this->enquiry_track_history_info($data['bulk_order_enquiry_id']);
                    $ary['enquiry_quote_info'] = $this->enquiry_quotes_info($data['bulk_order_enquiry_id']);
                    $ary['enquiry_order_info'] = $this->enquiry_order_info($data['bulk_order_enquiry_id']);
                    // log end ***********
                    Log::channel("employeeEnquiry")->info("Employee Enquiry Controller end:: save values :: id :: $id :: value:: " . json_encode($ary, true) . " ::::end");
                    Log::channel("employeeEnquiry")->info("******* Employee Enquiry View Method End *******");
                    Log::channel("employeeEnquiry")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'Enquiry viewed successfully',
                        'data' => [$ary]
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failure',
                        'message' =>  __('No data found'), 'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' =>  __('No data found'), 'data' => []
                ]);
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("employeeEnquiry")->error("******* Employee Enquiry View Method Error Start *******");
            Log::channel("employeeEnquiry")->error($exception);
            Log::channel("employeeEnquiry")->error("******* Employee Enquiry View Method Error End *******");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function enquiry_track_history_info($enqId)
    {
        $Enquiry_track_info = BulkOrderTrackHistory::where('bulk_order_track_history.bulk_order_enquiry_id', $enqId)->leftjoin('bulk_order_enquiry_status', 'bulk_order_track_history.status', '=', 'bulk_order_enquiry_status.bulk_order_enquiry_status_id')->select('bulk_order_track_history.*', 'bulk_order_enquiry_status.status')->orderBy('bulk_order_track_history.bulk_order_track_history_id', 'asc')->get();

        $trackHisAry = [];
        if (!empty($Enquiry_track_info)) {
            foreach ($Enquiry_track_info as $enqTrackHis) {
                $ary = [];
                $ary['bulk_order_track_history_id'] = $enqTrackHis['bulk_order_track_history_id'];
                $ary['bulk_order_enquiry_id'] = $enqTrackHis['bulk_order_enquiry_id'];
                $ary['acl_user_id'] = $enqTrackHis['acl_user_id'];
                $ary['employee_id'] = $enqTrackHis['employee_id'];
                $getEnquiryDet = BulkOrderEnquiry::where('bulk_order_enquiry_id', $enqTrackHis['bulk_order_enquiry_id'])->first();
                if ($enqTrackHis['acl_user_id'] != '') {
                    $getAdminUserName = UserModel::where('acl_user_id', $enqTrackHis['acl_user_id'])->first();
                    $ary['created_by_name'] = $getAdminUserName->name;
                    $ary['created_by_email'] = $getAdminUserName->email;
                    $ary['portal'] = "Admin";
                }
                if ($enqTrackHis['employee_id'] != '') {
                    $getEmployeeName = Employee::where('employee_id', $enqTrackHis['employee_id'])->first();
                    $ary['created_by_name'] = $getEmployeeName->employee_name;
                    $ary['created_by_email'] = $getEmployeeName->email;
                    $ary['portal'] = "Employee";
                }
                if ($enqTrackHis['employee_id'] == '' && $enqTrackHis['acl_user_id'] == '') {
                    $ary['created_by_name'] = $getEnquiryDet->contact_person_name;
                }
                $ary['enquiry_notes'] = $enqTrackHis['enquiry_notes'];
                $ary['status'] = $enqTrackHis['status'];
                $ary['portal_type'] = $enqTrackHis['portal_type'];
                if($enqTrackHis['portal_type'] == 1){
                    $ary['portal'] = "Admin";
                }
                if($enqTrackHis['portal_type'] == 2){
                    $ary['portal'] = "Employee";
                }
                if($enqTrackHis['portal_type'] == 3){
                    $ary['portal'] = "Web";
                }
                if($enqTrackHis['portal_type'] == 4){
                    $ary['portal'] = "Mobile";
                }
                if ($enqTrackHis['assign_employee_id'] != '' && $enqTrackHis['assign_department_id'] != '') {
                    $ary['assign_employee_id'] =  $enqTrackHis['assign_employee_id'];
                    $ary['assign_department_id'] =  $enqTrackHis['assign_department_id'];
                    $getAssignEmployeeName = Employee::where('employee_id', $enqTrackHis['assign_employee_id'])->first();
                    $ary['assign_employee_name'] = $getAssignEmployeeName->employee_name;
                    $getAssignEmployeeDeptName = Department::where('department_id', $enqTrackHis['assign_department_id'])->first();
                    $ary['assign_department_name'] = $getAssignEmployeeDeptName->department_name;
                }
                else{
                    $ary['assign_employee_id'] =  $enqTrackHis['assign_employee_id'];
                    $ary['assign_department_id'] =  $enqTrackHis['assign_department_id'];
                }
                $ary['enquiry_date'] = $enqTrackHis['enquiry_date'];
                $ary['created_on'] = $enqTrackHis['created_on'];
                $trackHisAry[] = $ary;
            }
        }
        return $trackHisAry;
    }

    public function enquiry_quotes_info($enqId)
    {
        $bulkorder_quote = BulkOrderQuote::where('bulk_order_quote.bulk_order_enquiry_id', $enqId)->get();

        $quotesAry = [];
        if (!empty($bulkorder_quote)) {
            foreach ($bulkorder_quote as $quotes) {
                $ary = [];
                $ary['bulk_order_quote_id'] = $quotes['bulk_order_quote_id'];
                $ary['bulk_order_enquiry_id'] = $quotes['bulk_order_enquiry_id'];
                $ary['quote_date'] = $quotes['quote_date'];
                $ary['quote_code'] = $quotes['quote_code'];
                $ary['quote_amount'] = $quotes['grand_total'];
                if ($quotes['status'] == 1 || $quotes['status'] == 7) {
                    $ary['quotes_status'] = "Quote Pending";
                } else if ($quotes['status'] == 2 || $quotes['status'] == 6) {
                    $ary['quotes_status'] = "Request for Re-raise";
                } else if ($quotes['status'] == 3) {
                    $ary['quotes_status'] = "Quote Re-raised";
                } else if ($quotes['status'] == 4) {
                    $ary['quotes_status'] = "Quote Approved";
                } else if ($quotes['status'] == 5) {
                    $ary['quotes_status'] = "Quote Disapproved";
                } else if ($quotes['status'] == 8) {
                    $ary['quotes_status'] = "Order Placed";
                }
                $quotesAry[] = $ary;
            }
        }
        return $quotesAry;
    }

    public function enquiry_order_info($enqId)
    {
        $bulkorder_quote = Orders::where('orders.bulk_order_enquiry_id', $enqId)->get();

        $quotesAry = [];
        if (!empty($bulkorder_quote)) {
            foreach ($bulkorder_quote as $quotes) {
                $ary = [];
                $ary['order_id'] = $quotes['order_id'];
                $ary['bulk_order_enquiry_id'] = $quotes['bulk_order_enquiry_id'];
                $ary['order_date'] = $quotes['order_date'];
                $ary['order_code'] = $quotes['order_code'];
                $ary['order_amount'] = $quotes['order_totalamount'];
                $getOrderItemcount = OrderItems::where('order_id', $quotes['order_id'])->count();
                $getOrderItemPendingcount = OrderItems::where('order_id', $quotes['order_id'])->where('order_status', 1)->count();
                $getOrderItemRejectcount = OrderItems::where('order_id', $quotes['order_id'])->whereIn('order_status', [6, 4])->count();
                $getOrderItemApprovecount = OrderItems::where('order_id', $quotes['order_id'])->whereNotIn('order_status', [1, 6, 4])->count();
                if ($getOrderItemcount == $getOrderItemPendingcount) {
                    $ary['order_status'] = "Pending";
                }
                if ($getOrderItemcount == $getOrderItemRejectcount) {
                    $ary['order_status'] = "Rejected";
                }
                if ($getOrderItemcount == $getOrderItemApprovecount) {
                    $ary['order_status'] = "Approved";
                }

                $quotesAry[] = $ary;
            }
        }
        return $quotesAry;
    }

    public function getLabelAttachmentImage($productImageData)
    {
        $imageArray = [];
        $resultArray = [];
        $productImageData = json_decode($productImageData, true);
        if (!empty($productImageData)) {
            foreach ($productImageData as $data) {
                $imageArray['label_name'] = $data['label_name'];
                $imageArray['attachment'] = $data['attachment'];
                $imageArray['attachment_url'] = ($data['attachment'] != '') ? env('APP_URL') . env('ENQUIRY_URL') . $data['attachment'] : env('APP_URL') . "avatar.jpg";
                $resultArray[] = $imageArray;
            }
        }
        return $resultArray;
    }

    public function EmployeeSearchEnquiry(Request $request)
    {
        try {
            Log::channel("employeeEnquiry")->info('** started the employee enquiry search list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";

            $column_search = array('enquiry_search_view.full_name', 'enquiry_search_view.mobile_no');

            $getEnquiryList = EnquirySearchView::select(
                'enquiry_search_view.*',
            )->groupby('enquiry_search_view.mobile_no')->distinct();


            $getEnquiryList->where(function ($query) use ($searchval, $column_search, $getEnquiryList) {
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

            $count = count($getEnquiryList->get());
            if ($offset) {
                $offset = $offset * $limit;
                $getEnquiryList->offset($offset);
            }
            if ($limit) {
                $getEnquiryList->limit($limit);
            }
            $getEnquiryList->orderBy('customer_id', 'desc');
            $getEnquiryDetails = $getEnquiryList->get();
            if ($count > 0) {
                $final = [];
                foreach ($getEnquiryDetails as $value) {
                    $ary = [];
                    $ary['customer_id'] = $value['customer_id'];
                    if ($value['full_name'] == "") {
                        $ary['mobile_no_name'] = $value['mobile_no'];
                    }
                    if ($value['full_name'] != "") {
                        $ary['mobile_no_name'] = $value['mobile_no'] . ' - ' . $value['full_name'];
                    }
                    if ($value['mobile_no'] == "") {
                        $ary['mobile_no_name'] = $value['full_name'];
                    }
                    $ary['type'] = $value['type'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("employeeEnquiry")->info("employee enquiry search list value :: $log");
                Log::channel("employeeEnquiry")->info('** end the employee search enquiry list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Enquiry search listed successfully'),
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
            Log::channel("employeeEnquiry")->info('** start the error employee enquiry search list method **');
            Log::channel("employeeEnquiry")->error($exception);
            Log::channel("employeeEnquiry")->info('** end the error employee enquiry search list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function employee_enquiry_search_view($id, $type)
    {
        try {
            if ($type == "Enquiry") {
                // log start *********
                Log::channel("employeeEnquiry")->info("******* Employee Enquiry Search View Method Start *******");
                Log::channel("employeeEnquiry")->info(" Employee Enquiry Controller start:: find ID : $id :: type:: $type");
                // log start *********

                if ($id != '' && $id > 0) {
                    $data = BulkOrderEnquiry::where('bulk_order_enquiry_id', $id)->leftjoin('state', 'state.state_id', '=', 'bulk_order_enquiry.state_id')->leftjoin('district', 'district.district_id', '=', 'bulk_order_enquiry.district_id')->select('bulk_order_enquiry.*', 'state.state_name', 'district.district_name')->first();

                    if (!empty($data)) {

                        $ary = [];
                        $ary['bulk_order_enquiry_id'] = $data['bulk_order_enquiry_id'];
                        $ary['date'] = date('d-m-Y', strtotime($data['created_on']));
                        $ary['enquiry_code'] = $data['enquiry_code'];
                        $ary['contact_person_name'] = $data['contact_person_name'];
                        $ary['mobile_no'] = $data['mobile_no'];
                        $ary['alternative_mobile_no'] = $data['alternative_mobile_no'];
                        $ary['email'] = $data['email'];
                        if (!empty($data['service_id'])) {
                            $service_name = $this->getServiceNameforList($data['service_id']);
                            $ary['category'] = (!empty($service_name)) ? json_encode($service_name, true) : NULL;
                        } else {
                            $ary['category'] = $data['service_id'];
                        }
                        $ary['customer_type'] = $data['customer_type'];
                        if ($data['customer_type'] == 1) {
                            $ary['type'] = "Individual";
                        }
                        if ($data['customer_type'] == 2) {
                            $ary['type'] = "Company";
                        }
                        $ary['company_name'] = $data['company_name'];
                        $ary['status'] = $data['status'];
                        $getStatus = BulkOrderEnquiryStatus::where('bulk_order_enquiry_status_id', $ary['status'])->first();
                        $ary['enquiry_status'] =  $getStatus->status;
                        $ary['message'] = $data['message'];
                        $ary['address'] = $data['address'];
                        $ary['state_id'] = $data['state_id'];
                        $ary['state_name'] = $data['state_name'];
                        $ary['district_id'] = $data['district_id'];
                        $ary['district_name'] = $data['district_name'];
                        if (!empty($data['label_attachments'])) {

                            $ary['label_attachments'] = $this->getLabelAttachmentImage($data['label_attachments']);
                        } else {
                            $ary['label_attachments'] = $data['label_attachments'];
                        }
                        // log end ***********
                        Log::channel("employeeEnquiry")->info(" Employee Enquiry Controller end:: save values :: id :: $id :: $type :: value:: " . json_encode($ary, true) . " ::::end");
                        Log::channel("employeeEnquiry")->info("******* Employee Enquiry Search View Method End *******");
                        Log::channel("employeeEnquiry")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                        // log end ***********

                        return response()->json([
                            'keyword' => 'success',
                            'message' => 'Enquiry search viewed successfully',
                            'data' => $ary
                        ]);
                    } else {
                        return response()->json([
                            'keyword' => 'failure',
                            'message' =>  __('No data found'), 'data' => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' =>  __('No data found'), 'data' => []
                    ]);
                }
            }
            if ($type == "Customer") {
                // log start *********
                Log::channel("employeeEnquiry")->info("******* Employee Enquiry Search View Method Start *******");
                Log::channel("employeeEnquiry")->info(" Employee Enquiry Controller start:: find ID : $id :: type:: $type");
                // log start *********

                if ($id != '' && $id > 0) {
                    $data = Customer::where('customer.customer_id', $id)->leftjoin('state', 'state.state_id', '=', 'customer.state_id')->leftjoin('district', 'district.district_id', '=', 'customer.district_id')->select('customer.*', 'state.state_name', 'district.district_name')->first();

                    if (!empty($data)) {

                        $ary = [];
                        $ary['customer_id'] = $data['customer_id'];
                        $ary['customer_name'] = !empty($data['customer_last_name']) ? $data['customer_first_name'] . ' ' . $data['customer_last_name'] : $data['customer_first_name'];
                        $ary['mobile_no'] = $data['mobile_no'];
                        $ary['email'] = $data['email'];
                        $ary['state_id'] = $data['state_id'];
                        $ary['district_id'] = $data['district_id'];
                        $ary['state_name'] = $data['state_name'];
                        $ary['district_name'] = $data['district_name'];

                        // log end ***********
                        Log::channel("employeeEnquiry")->info(" Employee Enquiry Controller end:: save values :: id :: $id :: $type :: value:: " . json_encode($ary, true) . " ::::end");
                        Log::channel("employeeEnquiry")->info("******* Employee Enquiry Search View Method End *******");
                        Log::channel("employeeEnquiry")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                        // log end ***********

                        return response()->json([
                            'keyword' => 'success',
                            'message' => 'Enquiry search viewed successfully',
                            'data' => $ary
                        ]);
                    } else {
                        return response()->json([
                            'keyword' => 'failure',
                            'message' =>  __('No data found'), 'data' => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' =>  __('No data found'), 'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("employeeEnquiry")->error("******* Employee Enquiry Search View Method Error Start *******");
            Log::channel("employeeEnquiry")->error($exception);
            Log::channel("employeeEnquiry")->error("******* Employee Enquiry Search View Method Error End *******");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function employee_enquiry_update_status_getcall()
    {
        $getStatus = BulkOrderEnquiryStatus::whereIn('bulk_order_enquiry_status_id',[2, 3, 4, 5, 6])->select('*');

        $count = count($getStatus->get());

        $getStatusDet = $getStatus->get();

        if ($count > 0) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Enquiry status listed successfully'),
                    'data' => $getStatusDet
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

    public function enquiry_list_status_getcall()
    {
        $getStatus = BulkOrderEnquiryStatus::where('is_check',1)->select('*');

        $count = count($getStatus->get());

        $getStatusDet = $getStatus->get();

        if ($count > 0) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Enquiry status listed successfully'),
                    'data' => $getStatusDet
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

    public function servicename_getcall()
    {
        $get_service = Service::select('service.*');

        $count = count($get_service->get());

        $getServiceDet = $get_service->get();

        if ($count > 0) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Service listed successfully'),
                    'data' => $getServiceDet,
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

    public function getState(Request $request)
    {
        $country_id = $request->country_id;

        $state_list = State::orderBy('state_name', 'ASC');

        $count = count($state_list->get());

        $getStateDet = $state_list->get();

        $state_ary = [];

        if ($count > 0) {
            foreach ($getStateDet as $row) {

                $allData = [];

                $allData['country_id'] = $row->country_id;
                $allData['state_id'] = $row->state_id;
                $allData['name'] = $row->state_name;

                $state_ary[] = $allData; 
            }

            return response()->json(['keyword' => 'success', 'message' => 'State listed successfully', 'data' => $state_ary]);

        } else {

            return response()->json(['keyword' => 'success', 'message' => 'No data found', 'data' => $state_ary]);
        }
    }

    public function getCity(Request $request)
    {
        $state_id = $request->state_id;
        
        $city_list = District::where('state_id', $state_id);

        $count = count($city_list->get());

        $getCityDet = $city_list->get();

        $city_ary = [];

        if ($count > 0) {
            foreach ($getCityDet as $row) {

                $allData = [];

                $allData['state_id'] = $row->state_id;
                $allData['district_id'] = $row->district_id;
                $allData['city_name'] = $row->district_name;

                $city_ary[] = $allData;
            }

            return response()->json(['keyword' => 'success', 'message' => 'City listed successfully', 'data' => $city_ary]);

        } else {

            return response()->json(['keyword' => 'success', 'message' => 'No data found', 'data' => $city_ary]);
        }
    }
}