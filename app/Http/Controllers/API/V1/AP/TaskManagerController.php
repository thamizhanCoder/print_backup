<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Traits\OrderResponseTrait;
use App\Models\BulkOrderEnquiry;
use App\Models\Customer;
use App\Models\CustomTaskQcHistory;
use App\Models\DeliveryManagement;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeTaskHistory;
use App\Models\ExpectedDays;
use App\Models\OperationTaskManagerView;
use App\Models\OrderItems;
use App\Models\OrderItemStage;
use App\Models\Orders;
use App\Models\PassportSizeUploadModel;
use App\Models\PassportSizeUploadQcModel;
use App\Models\PersonalizedUploadHistoryModel;
use App\Models\PersonalizedUploadModel;
use App\Models\PhotoFrameLabelModel;
use App\Models\PhotoFramePreviewHistory;
use App\Models\PhotoFrameQcHistory;
use App\Models\PhotoFrameUploadHistoryModel;
use App\Models\PhotoFrameUploadModel;
use App\Models\PhotoPrintUploadModel;
use App\Models\PhotoPrintUploadQcUpload;
use App\Models\QcTaskHistory;
use App\Models\SelfieUploadHistoryModel;
use App\Models\SelfieUploadModel;
use App\Models\SelfieUploadPreviewHistoryModel;
use App\Models\SelfieUploadQcModel;
use App\Models\TaskDuration;
use App\Models\TaskManager;
use App\Models\TaskManagerHistory;
use App\Models\TaskManagerPreviewHistory;
use App\Models\TaskManagerQcHistory;
use App\Models\Taskstage;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Illuminate\Support\Facades\Mail;

class TaskManagerController extends Controller
{
    use OrderResponseTrait;

    public function task_create(Request $request)
    {
        try {
            Log::channel("directtask")->info('** started the directtask create method **');

            if (!empty($request->attachment_image) && $request->attachment_image != '[]') {
                $gTImage = json_decode($request->attachment_image, true);
                if (!empty($gTImage)) {
                    foreach ($gTImage as $im) {
                        $ary[] = pathinfo($im['image'], PATHINFO_EXTENSION);
                    }
                }
                $extension_array = ['jpeg', 'png', 'jpg'];
                if (!array_diff($ary, $extension_array)) {
                    $request->attachment_image;
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data'        => []
                    ]);
                }
            }

            $nameexist = TaskManager::where([
                ['task_name', '=', $request->task_name],
                ['status', '!=', 2]
            ])->first();

            if (empty($nameexist)) {

                $task = new TaskManager();
                $task->task_type = 1;
                $task->task_name = $request->task_name;
                $task->description = $request->description;
                $task->attachment_image = $request->attachment_image;
                $task->folder = $request->folder;
                $task->created_on = Server::getDateTime();
                $task->created_by = JwtHelper::getSesUserId();

                if ($task->save()) {

                    $task_code = env('TASKCODE') . str_pad($task->task_manager_id, 3, '0', STR_PAD_LEFT);
                    $update_taskdetails = TaskManager::find($task->task_manager_id);
                    $update_taskdetails->task_code = $task_code;
                    $update_taskdetails->save();

                    $tasks = TaskManager::where('task_manager_id', $task->task_manager_id)->first();

                    Log::channel("directtask")->info("request value :: $tasks");

                    // log activity
                    $desc =  'Task ' . $tasks->task_name  . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Task Manager');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("directtask")->info('** end the directtask create method **');


                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('Task created successfully'),
                        'data'        => [$tasks]
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('task creation failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Task name already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("directtask")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function task_update(Request $request)
    {
        try {
            Log::channel("directtask")->info('** started the directtask create method **');

            if (!empty($request->attachment_image) && $request->attachment_image != '[]') {
                $gTImage = json_decode($request->attachment_image, true);
                if (!empty($gTImage)) {
                    foreach ($gTImage as $im) {
                        $ary[] = pathinfo($im['image'], PATHINFO_EXTENSION);
                    }
                }
                $extension_array = ['jpeg', 'png', 'jpg'];
                if (!array_diff($ary, $extension_array)) {
                    $request->attachment_image;
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => ('Only JPG,JPEG,PNG formats allowed for image'),
                        'data'        => []
                    ]);
                }
            }

            $nameexist = TaskManager::where([
                ['task_name', '=', $request->task_name],
                ['task_manager_id', '!=', $request->task_manager_id],
                ['status', '!=', 2]
            ])->first();

            if (empty($nameexist)) {

                $task = TaskManager::find($request->task_manager_id);
                $task->task_name = $request->task_name;
                $task->description = $request->description;
                $task->attachment_image = $request->attachment_image;
                $task->folder = $request->folder;
                $task->created_on = Server::getDateTime();
                $task->created_by = JwtHelper::getSesUserId();

                if ($task->save()) {

                    $tasks = TaskManager::where('task_manager_id', $task->task_manager_id)->first();

                    Log::channel("directtask")->info("request value :: $tasks");

                    // log activity
                    $desc =  'Task ' . $tasks->task_name  . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Task Manager');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    Log::channel("directtask")->info('** end the directtask create method **');


                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('Task updated successfully'),
                        'data'        => [$tasks]
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Task update failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Task name already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("directtask")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function task_view($id)
    {
        try {
            Log::channel("directtask")->info('** started the directtask view method **');
            $task_view = TaskManager::where('task_manager.task_manager_id', $id)->leftjoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->select('task_manager.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.assigned_on', 'task_manager_history.taken_on', 'task_manager_history.expected_on', 'task_manager_history.completed_on', 'task_manager_history.work_stage', 'employee.employee_name')->first();

            Log::channel("directtask")->info("request value task_manager_id:: $id");



            $final = [];

            if (!empty($task_view)) {
                $ary = [];
                $ary['task_manager_id'] = $task_view['task_manager_id'];
                $ary['order_items_id'] = $task_view['order_items_id'];
                $ary['task_type'] = $task_view['task_type'];
                $ary['task_code'] = $task_view['task_code'];
                $ary['task_name'] = $task_view['task_name'];
                $ary['description'] = $task_view['description'];
                $ary['folder'] = $task_view['folder'];
                $gTImage = json_decode($task_view['attachment_image'], true);
                $ary['attachment_image'] = $this->getdefaultImages_allImages($gTImage, $task_view['folder']);
                $ary['current_task_stage'] = $task_view['current_task_stage'];
                $ary['created_on'] = $task_view['created_on'];
                $ary['created_by'] = $task_view['created_by'];
                $ary['updated_on'] = $task_view['updated_on'];
                $ary['updated_by'] = $task_view['updated_by'];
                $ary['status'] = $task_view['status'];
                $gTImage = json_decode($task_view['qc_image'], true);
                $ary['currentQc_attachment_image'] = $this->getdefaultImages_allImages($gTImage, $task_view['folder']);
                $ary['qc_status'] = $task_view['qc_status'];
                $selfieArray['qc_message'] = $task_view['qc_message'];
                $ary['qc_reason'] = $task_view['qc_reason'];
                $ary['qc_reason_on'] = $task_view['qc_reason_on'];
                $ary['task_manager_history_id'] = $task_view['task_manager_history_id'];
                $ary['assigned_on'] = $task_view['assigned_on'];
                $ary['assigned_to'] = $task_view['employee_name'];
                $ary['expected_on'] = $task_view['expected_on'];
                $ary['taken_on'] = $task_view['taken_on'];
                $ary['completed_on'] = $task_view['completed_on'];
                $ary['current_task_stage'] = $task_view['current_task_stage'];
                $ary['work_stage'] = $task_view['work_stage'];
                $ary['task_qc_history'] = $this->customtaskQchistory($task_view['task_manager_id']);
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("directtask")->info("view value :: $log");
                Log::channel("directtask")->info('** end the directtask view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Task viewed successfully'),
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
            Log::channel("directtask")->error($exception);
            Log::channel("directtask")->info('** end the directtask view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function customtaskQchistory($taskManagerId)
    {

        $customImageUpload = CustomTaskQcHistory::where('task_manager_id', $taskManagerId)->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($customImageUpload)) {

            foreach ($customImageUpload as $sd) {

                $selfieArray['custom_task_qc_history_id'] = $sd['custom_task_qc_history_id'];
                $selfieArray['task_manager_id'] = $sd['task_manager_id'];
                $gTImage = json_decode($sd['attachment_image'], true);
                $selfieArray['attachment_image'] = $this->taskQc_allImages($gTImage);
                $selfieArray['qc_message'] = $sd['qc_message'];
                $selfieArray['qc_on'] = $sd['qc_on'];
                $selfieArray['qc_reason'] = $sd['qc_reason'];
                $selfieArray['qc_reason_on'] = $sd['qc_reason_on'];
                $selfieArray['qc_status'] = $sd['qc_status'];
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function taskQc_allImages($gTImage)
    {
        $imG = [];
        if (!empty($gTImage)) {
            foreach ($gTImage as $im) {
                $ary = [];
                $ary['url'] = ($im['image'] != '') ? env('APP_URL') . env('DIRECTTASKQC_URL') . $im['image'] : env('APP_URL') . "avatar.jpg";
                $ary['image'] = $im['image'];
                $imG[] = $ary;
            }
        }
        return $imG;
    }


    public function getdefaultImages_allImages($gTImage, $folder)
    {
        $imG = [];
        if (!empty($gTImage)) {
            foreach ($gTImage as $im) {
                $ary = [];
                $ary['url'] = ($im['image'] != '') ? env('APP_URL') . env('DIRECTTASK_URL') . $folder . '/' . $im['image'] : env('APP_URL') . "avatar.jpg";
                $ary['image'] = $im['image'];
                $imG[] = $ary;
            }
        }
        return $imG;
    }

    public function task_delete(Request $request)
    {
        try {
            if (!empty($request)) {
                $id = $request->id;

                if (empty($exist)) {
                    Log::channel("directtask")->info('** started the directtask delete method **');
                    Log::channel("directtask")->info("request value task_manager_id:: $id :: ");

                    $task = TaskManager::where('task_manager_id', $id)->first();
                    $update = TaskManager::where('task_manager_id', $id)->delete();

                    // log activity
                    // $implode = implode(",", $ids);
                    $desc =  ' Task '  . $task->task_name  . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Task Manager');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                    Log::channel("directtask")->info("save value :: task_manager_id :: $id :: task deleted successfully");

                    Log::channel("directtask")->info('** end the directtask delete method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  __('Task deleted successfully'),
                        'data' => []
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('Task deleted failed'),
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
            Log::channel("directtask")->error($exception);
            Log::channel("directtask")->info('** end the directtask delete method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function productionList(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $column_search = array('orders.order_code', 'order_items.product_code', 'order_items.product_name', 'task_manager.task_code', 'task_manager.task_name', 'task_manager.description');

        $id = JwtHelper::getSesUserId();

        $orders = TaskManager::where('task_manager.current_task_stage', 2)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->leftJoin('task_manager_history', function ($leftJoin) {
                $leftJoin->on('task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')
                    ->where('task_manager_history.production_status', 1);
            })
            ->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->leftjoin('department', 'department.department_id', '=', 'employee.department_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')
            ->select('order_items.order_id', 'order_items.product_id', 'order_items.product_variant_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'orders.order_code', 'orders.order_date', 'task_manager.order_items_id', 'task_manager.task_manager_id', 'task_manager.task_code', 'task_manager.task_type', 'task_manager.task_name', 'task_manager.description', 'task_manager.attachment_image', 'task_manager.current_task_stage', 'task_manager.created_on', 'task_manager.folder', 'task_manager.qc_status as task_qc_status', 'task_manager_history.task_manager_history_id', 'task_manager_history.assigned_on', 'task_manager_history.completed_on', 'task_manager_history.expected_on', 'task_manager_history.taken_on', 'task_manager_history.work_stage', 'task_manager_history.production_status', 'employee.employee_name',  'employee.employee_image', 'department.department_name', 'employee.employee_type', 'employee.mobile_no', 'employee.email', 'orderitem_stage.qc_status')->groupBy('task_manager.task_manager_id')->orderBy('task_manager.created_on', 'desc');

        $orders->where(function ($query) use ($searchval, $column_search, $orders) {

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

        $count = count($orders->get());

        if ($offset) {
            $offset = $offset * $limit;
            $orders->offset($offset);
        }
        if ($limit) {
            $orders->limit($limit);
        }

        $orders = $orders->get();

        $orderAry = [];
        foreach ($orders as $value) {
            $ary = [];
            if (!empty($value['order_items_id'])) {
                $ary['group_id'] = "order|" . $value['order_code'] . "|" . $value['order_date'];

                $ary['task_manager_id'] = $value->task_manager_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['order_id'] = $value->order_id;
                $ary['order_items_id'] = $value->order_items_id;
                $ary['product_id'] = $value->product_id;
                $ary['product_variant_id'] = $value->product_variant_id;
                $ary['product_code'] = $value->product_code;
                $ary['product_name'] = $value->product_name;
                if (!empty($value->service_id)) {
                    $ary['task_stages'] = $this->orderItemtaskStageDetails($value->order_items_id, $value->task_manager_id);
                    $ary['no_of_stages_involve'] = count($this->orderItemtaskStageDetails($value->order_items_id, $value->task_manager_id));
                }
                $ary['service_id'] = $value->service_id;
                $ary['task_code'] = $value->task_code;
                $ary['task_type'] = $value->task_type;
                $ary['task_name'] = $value->task_name;
                $ary['description'] = $value->description;
                $gTImage = json_decode($value['attachment_image'], true);
                $ary['attachment_image'] = $this->getdefaultImages_allImages($gTImage, $value->folder);
                $ary['current_task_stage'] = $value->current_task_stage;
                $ary['created_on'] = $value->created_on;
                $ary['board_status'] = $value->qc_status == 2 ? $value->qc_status : null;
            } else {
                $ary['group_id'] = "task|" . $value['task_code'] . "|" . $value['created_on'];

                $ary['task_manager_id'] = $value->task_manager_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['order_id'] = $value->order_id;
                $ary['order_items_id'] = $value->order_items_id;
                $ary['product_id'] = $value->product_id;
                $ary['product_variant_id'] = $value->product_variant_id;
                $ary['product_code'] = $value->product_code;
                $ary['product_name'] = $value->product_name;
                if (!empty($value->service_id)) {
                    $ary['task_stages'] = $this->orderItemtaskStageDetails($value->order_items_id, $value->task_manager_id);
                    $ary['no_of_stages_involve'] = count($this->orderItemtaskStageDetails($value->order_items_id, $value->task_manager_id));
                }
                $ary['service_id'] = $value->service_id;
                $ary['task_code'] = $value->task_code;
                $ary['task_type'] = $value->task_type;
                $ary['task_name'] = $value->task_name;
                $ary['description'] = $value->description;
                $gTImage = json_decode($value['attachment_image'], true);
                $ary['attachment_image'] = $this->getdefaultImages_allImages($gTImage, $value->folder);
                $ary['current_task_stage'] = $value->current_task_stage;
                $ary['created_on'] = $value->created_on;
                $ary['assigned_on'] = $value['production_status'] == 1 ? $value['assigned_on'] : null;
                $ary['assigned_to'] = $value['production_status'] == 1 ? $value['employee_name'] : null;
                $ary['completed_on'] = $value['production_status'] == 1 ? $value['completed_on'] : null;
                $ary['expected_on'] = $value['production_status'] == 1 ? $value['expected_on'] : null;
                $ary['taken_on'] = $value['production_status'] == 1 ? $value['taken_on'] : null;
                $ary['work_stage'] = $value['production_status'] == 1 ? $value['work_stage'] : null;
                $ary['board_status'] = $value->task_qc_status == 2 ? $value->task_qc_status : null;
                $employeeImage = ($value->employee_image != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $value->employee_image : env('APP_URL') . "avatar.jpg";
                $ary['employee_image'] = $value['production_status'] == 1 ? $employeeImage : null;
                $ary['department_name'] = $value['production_status'] == 1 ? $value->department_name : null;
                $ary['employee_type'] = $value['production_status'] == 1 ? $value->employee_type : null;
                $ary['mobile_no'] = $value['production_status'] == 1 ? $value->mobile_no : null;
                $ary['email'] = $value['production_status'] == 1 ? $value->email : null;
            }
            $orderAry[] = $ary;
        }
        if (!empty($orderAry)) {
            $final = collect($orderAry)->groupBy('group_id')->all();
        }
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Production listed successfully'),
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => []
            ]);
        }
    }

    public function orderItemtaskStageDetails($orderItemId, $taskmanagerId)
    {
        // $orderstage = OrderItemStage::where('order_items_id', $orderItemId)
        // ->leftjoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'orderitem_stage.orderitem_stage_id')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->leftjoin('department', 'department.department_id', '=', 'employee.department_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.assigned_on', 'task_manager_history.expected_on', 'task_manager_history.completed_on', 'task_manager_history.work_stage', 'task_manager_history.production_status', 'employee.employee_name', 'employee.employee_image', 'department.department_name', 'employee.employee_type', 'employee.mobile_no', 'employee.email')->groupBy('orderitem_stage.orderitem_stage_id')->get();

        $orderstage = OrderItemStage::where('order_items_id', $orderItemId)
            ->leftjoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'orderitem_stage.orderitem_stage_id')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->leftjoin('department', 'department.department_id', '=', 'orderitem_stage.department_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.assigned_on', 'task_manager_history.expected_on', 'task_manager_history.completed_on', 'task_manager_history.work_stage', 'task_manager_history.production_status', 'employee.employee_name', 'employee.employee_image', 'department.department_name', 'employee.employee_type', 'employee.mobile_no', 'employee.email')->groupBy('orderitem_stage.orderitem_stage_id')->get();

        $cusArray = [];
        $resultArray = [];

        if (!empty($orderstage)) {

            foreach ($orderstage as $cm) {
                $cusArray['orderitem_stage_id'] = $cm['orderitem_stage_id'];
                $cusArray['order_items_id'] = $cm['order_items_id'];
                $cusArray['stage'] = $cm['stage'];
                $cusArray['department_id'] = $cm['department_id'];
                $cusArray['is_customer_preview'] = $cm['is_customer_preview'];
                $cusArray['is_qc'] = $cm['is_qc'];
                $cusArray['completed_reason'] = $cm['completed_reason'];
                $cusArray['status'] = $cm['status'];
                $cusArray['department_name'] = $this->getDepName($cm['department_id']);
                $cusArray['task_manager_history_id'] = $cm['task_manager_history_id'];
                $cusArray['assigned_on'] = $cm['production_status'] == 1 ? $cm['assigned_on'] : null;
                $cusArray['assigned_to'] = $cm['production_status'] == 1 ? $cm['employee_name'] : null;
                $cusArray['expected_on'] = $cm['production_status'] == 1 ? $cm['expected_on'] : null;
                $cusArray['completed_on'] = $cm['production_status'] == 1 ? $cm['completed_on'] : null;
                $cusArray['work_stage'] = $cm['production_status'] == 1 ? $cm['work_stage'] : null;
                $cusArray['employee_image'] = ($cm['employee_image'] != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $cm['employee_image'] : env('APP_URL') . "avatar.jpg";
                $cusArray['department_name'] = $cm['department_name'];
                $cusArray['employee_type'] = $cm['employee_type'];
                $cusArray['mobile_no'] = $cm['mobile_no'];
                $cusArray['email'] = $cm['email'];

                $resultArray[] = $cusArray;
            }
        }


        return $resultArray;
    }

    public function getAssignDetails($taskmanagerId)
    {
        $taskDetails = TaskManagerHistory::where('task_manager_history.production_status', 1)->where('task_manager_history.task_manager_id', $taskmanagerId)->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->select('task_manager_history.*', 'employee.employee_name')->first();

        if (!empty($taskDetails)) {

            return $taskDetails;
        } else {

            $taskDetails = "";

            return $taskDetails;
        }
    }

    public function getTaskAssignDetails($taskmanagerId)
    {
        $taskmanager = TaskManager::where('task_manager_id', $taskmanagerId)->first();
        $taskDetails = TaskManagerHistory::where('task_manager_history.production_status', 1)->where('task_manager_history.department_id', $taskmanager->department_id)->where('task_manager_history.task_manager_id', $taskmanagerId)->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->select('task_manager_history.*', 'employee.employee_name')->first();

        if (!empty($taskDetails)) {

            return $taskDetails;
        } else {

            $taskDetails = "";

            return $taskDetails;
        }
    }

    public function getDepName($departmentId)
    {

        $dep = Department::where('department_id', $departmentId)->first();

        if (!empty($dep)) {

            return $dep->department_name;
        } else {

            $value = "";

            return $value;
        }
    }

    public function qcList(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";
        $from_date = ($request->from_date) ? $request->from_date : '';
        $to_date = ($request->to_date) ? $request->to_date : '';

        $column_search = array('orders.order_code', 'order_items.product_code', 'order_items.product_name', 'task_manager.task_code', 'task_manager.task_name', 'task_manager.description');

        $id = JwtHelper::getSesUserId();

        $orders = TaskManager::where('task_manager.current_task_stage', 3)->where('task_manager_history.production_status', 1)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftjoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('order_items.order_id', 'order_items.product_id', 'order_items.product_variant_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'orders.order_code', 'orders.order_date', 'task_manager.order_items_id', 'task_manager.task_manager_id', 'task_manager.task_code', 'task_manager.task_type', 'task_manager.task_name', 'task_manager.description', 'task_manager.attachment_image', 'task_manager.current_task_stage', 'task_manager.created_on', 'task_manager.folder', 'task_manager.qc_status as task_qc_status', 'task_manager_history.task_manager_history_id', 'task_manager_history.assigned_on', 'task_manager_history.completed_on', 'task_manager_history.expected_on', 'task_manager_history.taken_on', 'task_manager_history.work_stage', 'task_manager.orderitem_stage_id', 'employee.employee_name', 'orderitem_stage.qc_status')->groupBy('task_manager.task_manager_id')->orderBy('task_manager.qc_on', 'desc');

        $orders->where(function ($query) use ($searchval, $column_search, $orders) {

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

        $count = count($orders->get());

        if ($offset) {
            $offset = $offset * $limit;
            $orders->offset($offset);
        }
        if ($limit) {
            $orders->limit($limit);
        }

        $orders = $orders->get();

        $orderAry = [];
        foreach ($orders as $value) {
            $ary = [];
            if (!empty($value['order_items_id'])) {
                $ary['group_id'] = "order|" . $value['order_code'] . "|" . $value['order_date'];
                $ary['task_manager_id'] = $value->task_manager_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['order_id'] = $value->order_id;
                $ary['order_items_id'] = $value->order_items_id;
                $ary['product_id'] = $value->product_id;
                $ary['product_variant_id'] = $value->product_variant_id;
                $ary['product_code'] = $value->product_code;
                $ary['product_name'] = $value->product_name;
                if (!empty($value->service_id)) {
                    $ary['task_stages'] = $this->qcTaskStageDetails($value->orderitem_stage_id, $value->task_manager_id);
                    $ary['no_of_stages_involve'] = count($this->qcTaskStageDetails($value->orderitem_stage_id, $value->task_manager_id));
                }
                $ary['service_id'] = $value->service_id;
                $ary['task_code'] = $value->task_code;
                $ary['task_type'] = $value->task_type;
                $ary['task_name'] = $value->task_name;
                $ary['description'] = $value->description;
                $gTImage = json_decode($value['attachment_image'], true);
                $ary['attachment_image'] = $this->getdefaultImages_allImages($gTImage, $value->folder);
                $ary['current_task_stage'] = $value->current_task_stage;
                $ary['created_on'] = $value->created_on;
                $ary['board_status'] = $value->qc_status == 1 ? $value->qc_status : null;
            } else {
                $ary['group_id'] = "task|" . $value['task_code'] . "|" . $value['created_on'];
                $ary['task_manager_id'] = $value->task_manager_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['order_id'] = $value->order_id;
                $ary['order_items_id'] = $value->order_items_id;
                $ary['product_id'] = $value->product_id;
                $ary['product_variant_id'] = $value->product_variant_id;
                $ary['product_code'] = $value->product_code;
                $ary['product_name'] = $value->product_name;
                $ary['service_id'] = $value->service_id;
                $ary['task_code'] = $value->task_code;
                $ary['task_type'] = $value->task_type;
                $ary['task_name'] = $value->task_name;
                $ary['description'] = $value->description;
                $gTImage = json_decode($value['attachment_image'], true);
                $ary['attachment_image'] = $this->getdefaultImages_allImages($gTImage, $value->folder);
                $ary['current_task_stage'] = $value->current_task_stage;
                $ary['created_on'] = $value->created_on;
                $ary['assigned_on'] = $value['assigned_on'];
                $ary['assigned_to'] = $value['employee_name'];
                $ary['completed_on'] = $value['completed_on'];
                $ary['expected_on'] = $value['expected_on'];
                $ary['taken_on'] = $value['taken_on'];
                $ary['work_stage'] = $value['work_stage'];
                $ary['board_status'] = $value->task_qc_status == 1 ? $value->task_qc_status : null;
            }
            $orderAry[] = $ary;
        }

        if (!empty($orderAry)) {
            $final = collect($orderAry)->groupBy('group_id')->all();
        }
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Qc listed successfully'),
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => []
            ]);
        }
    }

    public function qcTaskStageDetails($orderitemStageId, $taskmanagerId)
    {
        $orderstage = OrderItemStage::where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $orderitemStageId)->leftjoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'orderitem_stage.orderitem_stage_id')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->leftjoin('department', 'department.department_id', '=', 'employee.department_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.assigned_on', 'task_manager_history.expected_on', 'task_manager_history.completed_on', 'task_manager_history.work_stage', 'employee.employee_name', 'employee.employee_image', 'department.department_name')->groupBy('orderitem_stage.orderitem_stage_id')->get();
        $cusArray = [];
        $resultArray = [];

        if (!empty($orderstage)) {

            foreach ($orderstage as $cm) {
                $cusArray['orderitem_stage_id'] = $cm['orderitem_stage_id'];
                $cusArray['order_items_id'] = $cm['order_items_id'];
                $cusArray['stage'] = $cm['stage'];
                $cusArray['department_id'] = $cm['department_id'];
                $cusArray['is_customer_preview'] = $cm['is_customer_preview'];
                $cusArray['is_qc'] = $cm['is_qc'];
                $cusArray['status'] = $cm['status'];
                $cusArray['department_name'] = $this->getDepName($cm['department_id']);
                $cusArray['task_manager_history_id'] = $cm['task_manager_history_id'];
                $cusArray['assigned_on'] = $cm['assigned_on'];
                $cusArray['assigned_to'] = $cm['employee_name'];
                $cusArray['expected_on'] = $cm['expected_on'];
                $cusArray['completed_on'] = $cm['completed_on'];
                $cusArray['work_stage'] = $cm['work_stage'];
                $cusArray['employee_image'] = ($cm['employee_image'] != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $cm['employee_image'] : env('APP_URL') . "avatar.jpg";
                $cusArray['department_name'] = $cm['department_name'];

                $resultArray[] = $cusArray;
            }
        }


        return $resultArray;
    }

    public function customerPreviewPushNotification($orderItemId)
    {
        $order = OrderItems::where('order_items_id', $orderItemId)->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'service.service_name', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'order_items.order_items_id')->first();


        // $emp_info = [
        //     'first_name' => $order->customer_first_name,
        //     'last_name' => $order->customer_last_name,
        //     'product_name' => $order->product_name
        // ];

        $title = "Order Customer Preview" . " - " . $order->order_code;
        $body = "Your order $order->order_code - $order->product_name is completed & we’re waiting for your confirmation.";
        // $body = GlobalHelper::mergeFields($body, $emp_info);
        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'Customer Preview';
        $portal = "website";
        $portal2 = "mobile";
        $page = 'customer_preview';
        // $titlemod = "Rating & Review !";
        $data = [
            'customer_id' => $order->customer_id,
            'order_id' => $order->order_id,
            'order_items_id' => $order->order_items_id,
            'service_id' => $order->service_id,
            'random_id' => $random_id,
            'page' => $page,
            'url' => "account/orders/item-detail?"
        ];

        $data2 = [
            'customer_id' => $order->customer_id,
            'order_id' => $order->order_id,
            'order_items_id' => $order->order_items_id,
            'service_id' => $order->service_id,
            'random_id' => $random_id2,
            'page' => $page,
            'url' => "account/orders/item-detail?"
        ];

        if ($order->customer_id != "") {


            $token = Customer::where('customer_id', $order->customer_id)->where('token', '!=', NULL)->orwhere('mbl_token', '!=', NULL)->select('token', 'mbl_token', 'customer_id')->get();
            if (!empty($token)) {
                $tokens = [];
                foreach ($token as $tk) {
                    $tokens[] = $tk['token'];
                }

                $mbl_tokens = [];
                foreach ($token as $tks) {
                    $mbl_tokens[] = $tks['mbl_token'];
                }
                $customerId = [];
                foreach ($token as $tk) {
                    $customerId[] = $tk['customer_id'];
                }
            }
            if (!empty($tokens)) {
                foreach (array_chunk($tokens, 500) as $tok) {
                    $key = $tok;
                    if (!empty($key)) {
                        $message = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data2,
                            'portal' => $portal,
                            'module' => $module
                        ];
                        $push = Firebase::sendMultiple($key, $message);
                    }
                }
                if (!empty($customerId)) {
                    $prod = array_chunk($customerId, 500);
                    if (!empty($prod)) {
                        for ($i = 0; $i < count($prod); $i++) {
                            $sizeOfArrayChunk = sizeof($prod[$i]);
                            for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                                $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $prod[$i][$j], $module, $page, "website", $data2, $random_id2);
                            }
                        }
                    }
                }
            }

            if (!empty($mbl_tokens)) {
                foreach (array_chunk($mbl_tokens, 500) as $mbl_tok) {
                    $key_mbl = $mbl_tok;
                    if (!empty($key_mbl)) {
                        $message = [
                            'title' => $title,
                            'body' => $body,
                            'page' => $page,
                            'data' => $data,
                            'portal' => $portal2,
                            'module' => $module
                        ];
                        $push2 = Firebase::sendMultipleMbl($key_mbl, $message);
                    }
                }
                if (!empty($customerId)) {
                    $prod = array_chunk($customerId, 500);
                    if (!empty($prod)) {
                        for ($i = 0; $i < count($prod); $i++) {
                            $sizeOfArrayChunk = sizeof($prod[$i]);
                            for ($j = 0; $j < $sizeOfArrayChunk; $j++) {
                                $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $prod[$i][$j], $module, $page, "mobile", $data, $random_id);
                            }
                        }
                    }
                }
            }
        }
    }

    //Employee qc approved
    public function employeeQcApprovedPushNotification($orderItemId)
    {
        $orderDetails = TaskManager::where('task_manager.order_items_id', $orderItemId)->where('task_manager_history.production_status', 1)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->leftJoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'task_manager.order_items_id', 'task_manager_history.task_manager_history_id', 'task_manager_history.employee_id', 'task_manager_history.orderitem_stage_id')->first();

        $title = "QC Approved" . " - " . $orderDetails->product_code;
        $body = "Your product $orderDetails->product_code - $orderDetails->product_name has been approved. The item is ready for the next stage!";

        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'QC Approved';
        $portal = "employee";
        $page = 'qc_approved';

        $data = [
            'employee_id' => $orderDetails->employee_id,
            'product_code' => $orderDetails->product_code,
            'product_name' => $orderDetails->product_name,
            'order_items_id' => $orderDetails->order_items_id,
            'task_manager_history_id' => $orderDetails->task_manager_history_id,
            'orderitem_stage_id' => $orderDetails->orderitem_stage_id,
            'random_id' => $random_id,
            'page' => $page,
            'url' => "employee/employee-task-manger/employee-task-detail?"
        ];
        $message = [
            'title' => $title,
            'module' => $module,
            'portal' => $portal,
            'body' => $body,
            'page' => $page,
            'data' => $data
        ];

        $token = Employee::where('employee_id', $orderDetails->employee_id)->where('fcm_token', '!=', NULL)->select('fcm_token', 'employee_id')->first();

        $employeeDetail = Employee::where('employee_id', $orderDetails->employee_id)->first();
        if (!empty($token)) {
            $push = Firebase::sendSingle($token->fcm_token, $message);
        }
        $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $employeeDetail->employee_id, $module, $page, $portal, $data, $random_id);
    }

    //Employee qc rejected
    public function employeeQcRejectedPushNotification($orderItemId)
    {
        $orderDetails = TaskManager::where('task_manager.order_items_id', $orderItemId)->where('task_manager_history.production_status', 1)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->leftJoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'task_manager.order_items_id', 'task_manager_history.task_manager_history_id', 'task_manager_history.employee_id', 'task_manager_history.orderitem_stage_id')->first();

        $title = "QC Rejected" . " - " . $orderDetails->product_code;
        $body = "Your product $orderDetails->product_code - $orderDetails->product_name has been rejected. Please check and update it.";

        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'QC Rejected';
        $portal = "employee";
        $page = 'qc_rejected';

        $data = [
            'employee_id' => $orderDetails->employee_id,
            'product_code' => $orderDetails->product_code,
            'product_name' => $orderDetails->product_name,
            'order_items_id' => $orderDetails->order_items_id,
            'task_manager_history_id' => $orderDetails->task_manager_history_id,
            'orderitem_stage_id' => $orderDetails->orderitem_stage_id,
            'random_id' => $random_id,
            'page' => $page,
            'url' => "employee/employee-task-manger/employee-task-detail?"
        ];
        $message = [
            'title' => $title,
            'module' => $module,
            'portal' => $portal,
            'body' => $body,
            'page' => $page,
            'data' => $data
        ];

        $token = Employee::where('employee_id', $orderDetails->employee_id)->where('fcm_token', '!=', NULL)->select('fcm_token', 'employee_id')->first();

        $employeeDetail = Employee::where('employee_id', $orderDetails->employee_id)->first();

        if (!empty($token)) {
            $push = Firebase::sendSingle($token->fcm_token, $message);
        }
        $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $employeeDetail->employee_id, $module, $page, $portal, $data, $random_id);
    }

    //Qc Approved or Rejected
    public function qcApprovedRejected(Request $request)
    {
        try {

            if (!empty($request)) {

                $type = $request->type;

                $id = $request->id;
                if (!empty($id)) {
                    if ($type == "approved") {
                        Log::channel("attachedImageApproved")->info('** started the attachedImageApproved method **');

                        //Passportsize Photo
                        if ($request->service_id == 1) {
                            Log::channel("attachedImageApproved")->info("request value PassportSizeUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Passport Size approved successfully");
                            $update = PassportSizeUploadModel::where('order_passport_upload_id', $id)->update(array(
                                'qc_status' => $request->qc_status,
                                'qc_on' => Server::getDateTime(),
                                'qc_by' => JwtHelper::getSesUserId()
                            ));

                            $labelDetails = PassportSizeUploadModel::where('order_passport_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_passport_upload.order_items_id')->select('task_manager.*')->first();

                            $taskManDetails = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('task_manager.*', 'orders.order_code', 'order_items.product_name')->first();

                            $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'employee.employee_name')->first();

                            $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc approved by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Task Manager');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                            //Approved after insert in preview image
                            $taskManPassportDetails = PassportSizeUploadModel::where('order_passport_upload_id', $id)->first();
                            if (!empty($taskManPassportDetails->qc_image)) {
                                $previewImageUpdate = PassportSizeUploadModel::find($id);
                                $previewImageUpdate->preview_image = $taskManPassportDetails->qc_image;
                                $previewImageUpdate->preview_reason = null;
                                $previewImageUpdate->preview_reason_on = null;
                                $previewImageUpdate->preview_status = 0;
                                $previewImageUpdate->preview_on = Server::getDateTime();
                                $previewImageUpdate->preview_by = JwtHelper::getSesUserId();
                                $previewImageUpdate->save();

                                $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskManagerHistoryUpdate->qc_approved_on = Server::getDateTime();
                                $taskManagerHistoryUpdate->qc_approved_by = JwtHelper::getSesUserId();
                                $taskManagerHistoryUpdate->work_stage = 3;
                                $taskManagerHistoryUpdate->save();

                                $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHistoryUpdate->status = 3;
                                $employeetaskManagerHistoryUpdate->save();

                                if (!empty($taskManDetails->order_items_id)) {

                                    $order = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->select('order_items.*')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', function ($leftJoin) {
                                        $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                                            ->where('orders.customer_id', '!=', NULL);
                                    })
                                        ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                                            $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                                                ->where('orders.customer_id', '=', NULL);
                                        })->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'service.service_name', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no as bulk_order_mobile_no')->first();

                                    if (!empty($order->customer_id)) {
                                        $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                                        $mobileNo = $order->mobile_no;
                                    } else {
                                        $customerName = $order->contact_person_name;
                                        $mobileNo = $order->bulk_order_mobile_no;
                                    }

                                    $WEBSITE_URL = env('WEBSITE_URL');
                                    $msg =  "Dear $customerName,We are completed your order $order->service_name for getting image confirmation, and we are waiting for your reply.For more detail: $WEBSITE_URL Team Print App";
                                    $isSmsSent = GlobalHelper::sendSMS($mobileNo, $msg);
                                }
                                $pushNotification = $this->customerPreviewPushNotification($taskManDetails->order_items_id);

                                $pushNotificationforQcImageApprove = $this->employeeQcApprovedPushNotification($taskManDetails->order_items_id);
                            }

                            //Order item stage check update
                            $updateStatusPassportDetails = PassportSizeUploadModel::where('order_passport_upload_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_passport_upload.order_items_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*', 'task_manager.task_manager_id', 'order_passport_upload.qc_status')->first();

                            if ($updateStatusPassportDetails->is_customer_preview == 0 && $updateStatusPassportDetails->is_qc == 1 && $updateStatusPassportDetails->qc_status == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatusPassportDetails->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesUserId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();
                            }

                            $orderitemstageCount = TaskManager::where('task_manager_id', $updateStatusPassportDetails->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                            $stageCompletedCount = TaskManager::where('task_manager_id', $updateStatusPassportDetails->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                            if ($orderitemstageCount == $stageCompletedCount) {
                                Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                                $taskStatusUpdate = TaskManager::find($updateStatusPassportDetails->task_manager_id);
                                $taskStatusUpdate->current_task_stage = 4;
                                $taskStatusUpdate->save();
                            }
                            //Email for Bulk Order
                            $taskManCheck = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->where('task_type', 2)->first();
                            $getImage = PassportSizeUploadModel::where('order_passport_upload_id', $id)->first();
                            $concatImage = ($getImage->qc_image != '') ? env('APP_URL') . env('ORDER_URL') . $getImage->qc_image : env('APP_URL') . "avatar.jpg";
                            if (!empty($taskManCheck)) {
                                if ($taskManCheck->order_items_id != null) {
                                    $getOrderId = OrderItems::where('order_items_id', $taskManCheck->order_items_id)->first();
                                    $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                    $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();

                                    if (!empty($getBulkOrderEmail)) {
                                        if ($getBulkOrderEmail->email != null) {
                                            $mail_data = [
                                                'email' => $getBulkOrderEmail->email,
                                                'order_id' => $getBulkOrderId->order_code,
                                                'customer_name' => $getBulkOrderEmail->contact_person_name,
                                                'image' => $concatImage
                                            ];
                                            Mail::send('mail.sendQcApproveEmail', $mail_data, function ($message) use ($mail_data, $concatImage) {
                                                $message->to($mail_data['email'])
                                                    ->subject('Status of Image Approval')
                                                    ->attach($concatImage, [
                                                        'as' => 'preview_image.jpg',
                                                        'mime' => 'image/jpeg'
                                                    ]);
                                            });
                                        }
                                    }
                                }
                            }
                        }

                        //Photoprint
                        if ($request->service_id == 2) {
                            Log::channel("attachedImageApproved")->info("request value PhotoPrintUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Photo Print approved successfully");
                            $update = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->update(array(
                                'qc_status' => $request->qc_status,
                                'qc_on' => Server::getDateTime(),
                                'qc_by' => JwtHelper::getSesUserId()
                            ));

                            $labelDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoprint_upload.order_items_id')->select('task_manager.*')->first();

                            $taskManDetails = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('task_manager.*', 'orders.order_code', 'order_items.product_name')->first();

                            $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'employee.employee_name')->first();

                            $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc approved by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Task Manager');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                            //Approved after insert in preview image
                            $taskManPhotoPrintDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->first();
                            if (!empty($taskManPhotoPrintDetails->qc_image)) {
                                $previewImageUpdate = PhotoPrintUploadModel::find($id);
                                $previewImageUpdate->preview_image = $taskManPhotoPrintDetails->qc_image;
                                $previewImageUpdate->preview_reason = null;
                                $previewImageUpdate->preview_reason_on = null;
                                $previewImageUpdate->preview_status = 0;
                                $previewImageUpdate->preview_on = Server::getDateTime();
                                $previewImageUpdate->preview_by = JwtHelper::getSesUserId();
                                $previewImageUpdate->save();

                                $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskManagerHistoryUpdate->qc_approved_on = Server::getDateTime();
                                $taskManagerHistoryUpdate->qc_approved_by = JwtHelper::getSesUserId();
                                $taskManagerHistoryUpdate->work_stage = 3;
                                $taskManagerHistoryUpdate->save();

                                $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHistoryUpdate->status = 3;
                                $employeetaskManagerHistoryUpdate->save();

                                if (!empty($taskManDetails->order_items_id)) {

                                    $order = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->select('order_items.*')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', function ($leftJoin) {
                                        $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                                            ->where('orders.customer_id', '!=', NULL);
                                    })
                                        ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                                            $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                                                ->where('orders.customer_id', '=', NULL);
                                        })->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'service.service_name', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no as bulk_order_mobile_no')->first();

                                    if (!empty($order->customer_id)) {
                                        $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                                        $mobileNo = $order->mobile_no;
                                    } else {
                                        $customerName = $order->contact_person_name;
                                        $mobileNo = $order->bulk_order_mobile_no;
                                    }

                                    $WEBSITE_URL = env('WEBSITE_URL');
                                    $msg =  "Dear $customerName,We are completed your order $order->service_name for getting image confirmation, and we are waiting for your reply.For more detail: $WEBSITE_URL Team Print App";
                                    $isSmsSent = GlobalHelper::sendSMS($mobileNo, $msg);
                                }
                                $pushNotification = $this->customerPreviewPushNotification($taskManDetails->order_items_id);

                                $pushNotificationforQcImageApprove = $this->employeeQcApprovedPushNotification($taskManDetails->order_items_id);
                            }

                            //Order item stage check update
                            $updateStatusPhotoPrintDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoprint_upload.order_items_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*', 'task_manager.task_manager_id', 'order_photoprint_upload.qc_status')->first();

                            if ($updateStatusPhotoPrintDetails->is_customer_preview == 0 && $updateStatusPhotoPrintDetails->is_qc == 1 && $updateStatusPhotoPrintDetails->qc_status == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatusPhotoPrintDetails->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesUserId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();
                            }

                            $orderitemstageCount = TaskManager::where('task_manager_id', $updateStatusPhotoPrintDetails->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                            $stageCompletedCount = TaskManager::where('task_manager_id', $updateStatusPhotoPrintDetails->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                            if ($orderitemstageCount == $stageCompletedCount) {
                                Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                                $taskStatusUpdate = TaskManager::find($updateStatusPhotoPrintDetails->task_manager_id);
                                $taskStatusUpdate->current_task_stage = 4;
                                $taskStatusUpdate->save();
                            }
                            //Email for Bulk Order
                            $taskManCheck = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->where('task_type', 2)->first();
                            $getImage = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->first();
                            $concatImage = ($getImage->qc_image != '') ? env('APP_URL') . env('ORDER_URL') . $getImage->qc_image : env('APP_URL') . "avatar.jpg";
                            if (!empty($taskManCheck)) {
                                if ($taskManCheck->order_items_id != null) {
                                    $getOrderId = OrderItems::where('order_items_id', $taskManCheck->order_items_id)->first();
                                    $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                    $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();

                                    if (!empty($getBulkOrderEmail)) {
                                        if ($getBulkOrderEmail->email != null) {
                                            $mail_data = [
                                                'email' => $getBulkOrderEmail->email,
                                                'order_id' => $getBulkOrderId->order_code,
                                                'customer_name' => $getBulkOrderEmail->contact_person_name,
                                                'image' => $concatImage
                                            ];
                                            Mail::send('mail.sendQcApproveEmail', $mail_data, function ($message) use ($mail_data, $concatImage) {
                                                $message->to($mail_data['email'])
                                                    ->subject('Status of Image Approval')
                                                    ->attach($concatImage, [
                                                        'as' => 'preview_image.jpg',
                                                        'mime' => 'image/jpeg'
                                                    ]);
                                            });
                                        }
                                    }
                                }
                            }
                        }
                        //Personalized Product
                        if ($request->service_id == 4) {
                            Log::channel("attachedImageApproved")->info("request value PhotoFrameUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Photo frame approved successfully");
                            $update = TaskManager::where('task_manager_id', $id)->update(array(
                                'qc_status' => $request->qc_status,
                                'qc_on' => Server::getDateTime(),
                                'qc_by' => JwtHelper::getSesUserId()
                            ));

                            //Approved after task manager history preview status change
                            $taskManDetails = TaskManager::where('task_manager.task_manager_id', $id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('task_manager.*', 'orders.order_code', 'order_items.product_name')->first();

                            $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'employee.employee_name')->first();

                            $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc approved by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Task Manager');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                            //Approved after insert in preview image
                            $taskManDetails = TaskManager::where('task_manager_id', $id)->first();
                            if (!empty($taskManDetails->qc_image)) {
                                $previewImageUpdate = TaskManager::find($id);
                                $previewImageUpdate->preview_image = $taskManDetails->qc_image;
                                $previewImageUpdate->preview_reason = null;
                                $previewImageUpdate->preview_reason_on = null;
                                $previewImageUpdate->preview_status = 0;
                                $previewImageUpdate->preview_on = Server::getDateTime();
                                $previewImageUpdate->preview_by = JwtHelper::getSesUserId();
                                $previewImageUpdate->save();

                                $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskManagerHistoryUpdate->qc_approved_on = Server::getDateTime();
                                $taskManagerHistoryUpdate->qc_approved_by = JwtHelper::getSesUserId();
                                $taskManagerHistoryUpdate->work_stage = 3;
                                $taskManagerHistoryUpdate->save();

                                $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHistoryUpdate->status = 3;
                                $employeetaskManagerHistoryUpdate->save();

                                if (!empty($taskManDetails->order_items_id)) {

                                    $order = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->select('order_items.*')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->leftJoin('customer', function ($leftJoin) {
                                        $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                                            ->where('orders.customer_id', '!=', NULL);
                                    })
                                        ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                                            $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                                                ->where('orders.customer_id', '=', NULL);
                                        })->select(
                                            'customer.customer_first_name',
                                            'customer.customer_last_name',
                                            'customer.mobile_no',
                                            'service.service_name',
                                            'orders.order_id',
                                            'orders.order_code',
                                            'orders.customer_id',
                                            'bulk_order_enquiry.contact_person_name',
                                            'bulk_order_enquiry.mobile_no as bulk_order_mobile_no'
                                        )->first();

                                    if (!empty($order->customer_id)) {
                                        $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                                        $mobileNo = $order->mobile_no;
                                    } else {
                                        $customerName = $order->contact_person_name;
                                        $mobileNo = $order->bulk_order_mobile_no;
                                    }
                                    $WEBSITE_URL = env('WEBSITE_URL');
                                    $msg =  "Dear $customerName, We are completed your order $order->service_name for getting image confirmation, and we are waiting for your reply. For more detail: $WEBSITE_URL Team Print App";
                                    $isSmsSent = GlobalHelper::sendSMS($mobileNo, $msg);
                                }
                                $pushNotificationforPreview = $this->customerPreviewPushNotification($taskManDetails->order_items_id);

                                $pushNotificationforQcImageApprove = $this->employeeQcApprovedPushNotification($taskManDetails->order_items_id);
                            }

                            //Order item stage check update
                            $updateStatusStage = TaskManager::where('task_manager.task_manager_id', $id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*', 'task_manager.qc_status')->first();

                            if ($updateStatusStage->is_customer_preview == 0 && $updateStatusStage->is_qc == 1 && $updateStatusStage->qc_status == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatusStage->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesUserId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();
                            }

                            $orderitemstageCount = TaskManager::where('task_manager_id', $id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                            $stageCompletedCount = TaskManager::where('task_manager_id', $id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                            if ($orderitemstageCount == $stageCompletedCount) {
                                Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                                $taskStatusUpdate = TaskManager::find($id);
                                $taskStatusUpdate->current_task_stage = 4;
                                $taskStatusUpdate->save();
                            }

                            //Email for Bulk Order
                            $taskManCheck = TaskManager::where('task_manager_id', $id)->where('task_type', 2)->first();
                            $getImage = TaskManager::where('task_manager_id', $id)->first();
                            $concatImage = ($getImage->qc_image != '') ? env('APP_URL') . env('ORDER_URL') . $getImage->qc_image : env('APP_URL') . "avatar.jpg";
                            if ($taskManCheck->order_items_id != null) {

                                $getOrderId = OrderItems::where('order_items_id', $taskManCheck->order_items_id)->first();
                                $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();

                                if (!empty($getBulkOrderEmail)) {
                                    if ($getBulkOrderEmail->email != null) {
                                        $mail_data = [
                                            'email' => $getBulkOrderEmail->email,
                                            'order_id' => $getBulkOrderId->order_code,
                                            'customer_name' => $getBulkOrderEmail->contact_person_name,
                                            'image' => $concatImage
                                        ];

                                        Mail::send('mail.sendQcApproveEmail', $mail_data, function ($message) use ($mail_data, $concatImage) {
                                            $message->to($mail_data['email'])
                                                ->subject('Status of Image Approval')
                                                ->attach($concatImage, [
                                                    'as' => 'preview_image.jpg',
                                                    'mime' => 'image/jpeg'
                                                ]);
                                        });
                                    }
                                }
                            }
                        }

                        //Photoframe
                        if ($request->service_id == 3) {
                            Log::channel("attachedImageApproved")->info("request value PhotoFrameUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Photo frame approved successfully");
                            $update = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->update(array(
                                'qc_status' => $request->qc_status,
                                'qc_on' => Server::getDateTime(),
                                'qc_by' => JwtHelper::getSesUserId()
                            ));

                            $labelDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->select('task_manager.*')->first();

                            $taskManDetails = TaskManager::where('task_manager.task_manager_id', $labelDetails->task_manager_id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('task_manager.*', 'orders.order_code', 'order_items.product_name')->first();

                            $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'employee.employee_name')->first();

                            $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc approved by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Task Manager');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                            //Approved after insert in preview image
                            $taskManLabelDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->first();
                            if (!empty($taskManLabelDetails->qc_image)) {
                                $previewImageUpdate = PhotoFrameLabelModel::find($id);
                                $previewImageUpdate->preview_image = $taskManLabelDetails->qc_image;
                                $previewImageUpdate->preview_reason = null;
                                $previewImageUpdate->preview_reason_on = null;
                                $previewImageUpdate->preview_status = 0;
                                $previewImageUpdate->preview_on = Server::getDateTime();
                                $previewImageUpdate->preview_by = JwtHelper::getSesUserId();
                                $previewImageUpdate->save();

                                $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskManagerHistoryUpdate->qc_approved_on = Server::getDateTime();
                                $taskManagerHistoryUpdate->qc_approved_by = JwtHelper::getSesUserId();
                                $taskManagerHistoryUpdate->work_stage = 3;
                                $taskManagerHistoryUpdate->save();

                                $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHistoryUpdate->status = 3;
                                $employeetaskManagerHistoryUpdate->save();

                                if (!empty($taskManDetails->order_items_id)) {

                                    $order = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->select('order_items.*')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', function ($leftJoin) {
                                        $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                                            ->where('orders.customer_id', '!=', NULL);
                                    })
                                        ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                                            $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                                                ->where('orders.customer_id', '=', NULL);
                                        })->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select(
                                            'customer.customer_first_name',
                                            'customer.customer_last_name',
                                            'customer.mobile_no',
                                            'service.service_name',
                                            'orders.order_id',
                                            'orders.order_code',
                                            'orders.customer_id',
                                            'bulk_order_enquiry.contact_person_name',
                                            'bulk_order_enquiry.mobile_no as bulk_order_mobile_no'
                                        )->first();

                                    if (!empty($order->customer_id)) {
                                        $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                                        $mobileNo = $order->mobile_no;
                                    } else {
                                        $customerName = $order->contact_person_name;
                                        $mobileNo = $order->bulk_order_mobile_no;
                                    }

                                    $WEBSITE_URL = env('WEBSITE_URL');
                                    $msg =  "Dear $customerName,We are completed your order $order->service_name for getting image confirmation, and we are waiting for your reply.For more detail: $WEBSITE_URL Team Print App";
                                    $isSmsSent = GlobalHelper::sendSMS($mobileNo, $msg);
                                }
                                $pushNotification = $this->customerPreviewPushNotification($taskManDetails->order_items_id);

                                $pushNotificationforQcImageApprove = $this->employeeQcApprovedPushNotification($taskManDetails->order_items_id);
                            }

                            //Order item stage check update
                            $updateStatusLabelDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*', 'order_photoframe_upload_label.qc_status')->first();

                            if ($updateStatusLabelDetails->is_customer_preview == 0 && $updateStatusLabelDetails->is_qc == 1 && $updateStatusLabelDetails->qc_status == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatusLabelDetails->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesUserId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();
                            }

                            //total stage count
                            $totalStageCount = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->select('task_manager.task_manager_id')->first();

                            $orderitemstageCount = TaskManager::where('task_manager_id', $totalStageCount->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                            $stageCompletedCount = TaskManager::where('task_manager_id', $totalStageCount->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                            if ($orderitemstageCount == $stageCompletedCount) {
                                Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                                $taskStatusUpdate = TaskManager::find($totalStageCount->task_manager_id);
                                $taskStatusUpdate->current_task_stage = 4;
                                $taskStatusUpdate->save();
                            }

                            //Email for Bulk Order

                            $getUploadId = PhotoFrameUploadModel::where('order_photoframe_upload_label_id', $id)->first();
                            // $getUploadId = PhotoFrameUploadModel::where('order_photoframe_upload_id',$getUploadId->order_photoframe_upload_id)->first();
                            $concatImage = ($getUploadId->qc_image != '') ? env('APP_URL') . env('ORDER_URL') . $getUploadId->qc_image : env('APP_URL') . "avatar.jpg";

                            $taskManCheck = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->where('task_type', 2)->first();
                            if (!empty($taskManCheck)) {
                                if ($taskManCheck->order_items_id != null) {
                                    $getOrderId = OrderItems::where('order_items_id', $taskManCheck->order_items_id)->first();
                                    $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                    $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();
                                    if (!empty($getBulkOrderEmail)) {
                                        if ($getBulkOrderEmail->email != null) {
                                            $mail_data = [
                                                'email' => $getBulkOrderEmail->email,
                                                'image' => $concatImage,
                                                'order_id' => $getBulkOrderId->order_code,
                                                'customer_name' => $getBulkOrderEmail->contact_person_name
                                            ];

                                            Mail::send('mail.sendQcApproveEmail', $mail_data, function ($message) use ($mail_data, $concatImage) {
                                                $message->to($mail_data['email'])
                                                    ->subject('Status of Image Approval')
                                                    ->attach($concatImage, [
                                                        'as' => 'preview_image.jpg',
                                                        'mime' => 'image/jpeg'
                                                    ]);
                                            });
                                        }
                                    }
                                }
                            }
                        }

                        //Selfie
                        if ($request->service_id == 6) {
                            Log::channel("attachedImageApproved")->info("request value PhotoFrameUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Photo frame approved successfully");
                            $update = SelfieUploadModel::where('order_selfie_upload_id', $id)->update(array(
                                'qc_status' => $request->qc_status,
                                'qc_on' => Server::getDateTime(),
                                'qc_by' => JwtHelper::getSesUserId()
                            ));

                            $labelDetails = SelfieUploadModel::where('order_selfie_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_selfie_upload.order_items_id')->select('task_manager.*')->first();

                            $taskManDetails = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('task_manager.*', 'orders.order_code', 'order_items.product_name')->first();

                            $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'employee.employee_name')->first();

                            $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc approved by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Task Manager');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                            //Approved after insert in preview image
                            $taskManSelfieDetails = SelfieUploadModel::where('order_selfie_upload_id', $id)->first();
                            if (!empty($taskManSelfieDetails->qc_image)) {
                                $previewImageUpdate = SelfieUploadModel::find($id);
                                $previewImageUpdate->preview_image = $taskManSelfieDetails->qc_image;
                                $previewImageUpdate->preview_reason = null;
                                $previewImageUpdate->preview_reason_on = null;
                                $previewImageUpdate->preview_status = 0;
                                $previewImageUpdate->preview_on = Server::getDateTime();
                                $previewImageUpdate->preview_by = JwtHelper::getSesUserId();
                                $previewImageUpdate->save();

                                $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskManagerHistoryUpdate->qc_approved_on = Server::getDateTime();
                                $taskManagerHistoryUpdate->qc_approved_by = JwtHelper::getSesUserId();
                                $taskManagerHistoryUpdate->work_stage = 3;
                                $taskManagerHistoryUpdate->save();

                                $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHistoryUpdate->status = 3;
                                $employeetaskManagerHistoryUpdate->save();

                                if (!empty($taskManDetails->order_items_id)) {

                                    $order = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->select('order_items.*')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', function ($leftJoin) {
                                        $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                                            ->where('orders.customer_id', '!=', NULL);
                                    })
                                        ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                                            $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                                                ->where('orders.customer_id', '=', NULL);
                                        })->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'service.service_name', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no as bulk_order_mobile_no')->first();

                                    if (!empty($order->customer_id)) {
                                        $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                                        $mobileNo = $order->mobile_no;
                                    } else {
                                        $customerName = $order->contact_person_name;
                                        $mobileNo = $order->bulk_order_mobile_no;
                                    }

                                    $WEBSITE_URL = env('WEBSITE_URL');
                                    $msg =  "Dear $customerName,We are completed your order $order->service_name for getting image confirmation, and we are waiting for your reply.For more detail: $WEBSITE_URL Team Print App";
                                    $isSmsSent = GlobalHelper::sendSMS($mobileNo, $msg);
                                }
                                $pushNotification = $this->customerPreviewPushNotification($taskManDetails->order_items_id);

                                $pushNotificationforQcImageApprove = $this->employeeQcApprovedPushNotification($taskManDetails->order_items_id);
                            }

                            //Order item stage check update
                            $updateStatusSelfieDetails = SelfieUploadModel::where('order_selfie_upload_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_selfie_upload.order_items_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*', 'task_manager.task_manager_id', 'order_selfie_upload.qc_status')->first();

                            if ($updateStatusSelfieDetails->is_customer_preview == 0 && $updateStatusSelfieDetails->is_qc == 1 && $updateStatusSelfieDetails->qc_status == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatusSelfieDetails->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesUserId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();
                            }

                            $orderitemstageCount = TaskManager::where('task_manager_id', $updateStatusSelfieDetails->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                            $stageCompletedCount = TaskManager::where('task_manager_id', $updateStatusSelfieDetails->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                            if ($orderitemstageCount == $stageCompletedCount) {
                                Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                                $taskStatusUpdate = TaskManager::find($updateStatusSelfieDetails->task_manager_id);
                                $taskStatusUpdate->current_task_stage = 4;
                                $taskStatusUpdate->save();
                            }
                            //Email for Bulk Order
                            $taskManCheck = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->where('task_type', 2)->first();
                            $getImage = SelfieUploadModel::where('order_selfie_upload_id', $id)->first();
                            $concatImage = ($getImage->qc_image != '') ? env('APP_URL') . env('ORDER_URL') . $getImage->qc_image : env('APP_URL') . "avatar.jpg";
                            if (!empty($taskManCheck)) {
                                if ($taskManCheck->order_items_id != null) {
                                    $getOrderId = OrderItems::where('order_items_id', $taskManCheck->order_items_id)->first();
                                    $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                    $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();

                                    if (!empty($getBulkOrderEmail)) {
                                        if ($getBulkOrderEmail->email != null) {
                                            $mail_data = [
                                                'email' => $getBulkOrderEmail->email,
                                                'order_id' => $getBulkOrderId->order_code,
                                                'customer_name' => $getBulkOrderEmail->contact_person_name,
                                                'image' => $concatImage
                                            ];
                                            Mail::send('mail.sendQcApproveEmail', $mail_data, function ($message) use ($mail_data, $concatImage) {
                                                $message->to($mail_data['email'])
                                                    ->subject('Status of Image Approval')
                                                    ->attach($concatImage, [
                                                        'as' => 'preview_image.jpg',
                                                        'mime' => 'image/jpeg'
                                                    ]);
                                            });
                                        }
                                    }
                                }
                            }
                        }
                        Log::channel("attachedImageApproved")->info('** end the attachedImageApproved method **');
                    }

                    if ($type == "rejected") {
                        Log::channel("attachedImageRejected")->info('** started the attachedImageRejected method **');

                        //PassportSize
                        if ($request->service_id == 1) {
                            Log::channel("previewImageRejected")->info("request value PassportSizeUploadModel previewImageRejected_id:: $id :: status :: $request->qc_status");
                            Log::channel("previewImageRejected")->info("Passport Size rejected successfully");

                            $update = PassportSizeUploadModel::where('order_passport_upload_id', $id)->update(array(
                                'qc_status' => $request->qc_status,
                                'qc_reason' => $request->qc_reason,
                                'qc_reason_on' => Server::getDateTime()
                            ));

                            $passportSizeQc = PassportSizeUploadModel::where('order_passport_upload_id', $id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'order_passport_upload.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('order_passport_upload.*', 'orders.order_code', 'order_items.product_name')->first();

                            if (!empty($passportSizeQc->qc_image)) {
                                $qcHistory = new PassportSizeUploadQcModel();
                                $qcHistory->order_passport_upload_id = $request->id;
                                $qcHistory->qc_image = $passportSizeQc->qc_image;
                                $qcHistory->qc_on = $passportSizeQc->qc_on;
                                $qcHistory->qc_by = $passportSizeQc->qc_by;
                                $qcHistory->qc_reason = $passportSizeQc->qc_reason;
                                $qcHistory->qc_reason_on = $passportSizeQc->qc_reason_on;
                                $qcHistory->qc_status = $passportSizeQc->qc_status;
                                $qcHistory->save();
                                Log::channel("previewAttachedImageUpload")->info("request value PassportSizeUploadHistoryModel previewAttachedImageUpload_id:: $qcHistory");
                            }

                            $passportSizeDetails = PassportSizeUploadModel::where('order_passport_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_passport_upload.order_items_id')->select('task_manager.*')->first();

                            $taskManDetails = TaskManager::where('task_manager_id', $passportSizeDetails->task_manager_id)->first();

                            $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id', 'employee.employee_name')->first();

                            $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc rejected by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Task Manager');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                            $updateStageStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                            $updateStageStatus->qc_status = $request->qc_status;
                            $updateStageStatus->qc_reason = $request->qc_reason;
                            $updateStageStatus->qc_reason_on = Server::getDateTime();
                            $updateStageStatus->qc_reason_by = JwtHelper::getSesUserId();
                            $updateStageStatus->save();

                            $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskManagerHistoryUpdate->work_stage = 2;
                            $taskManagerHistoryUpdate->save();

                            $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHistoryUpdate->status = 2;
                            $employeetaskManagerHistoryUpdate->save();

                            $updateTaskStatus = TaskManager::find($updateStatus->task_manager_id);
                            $updateTaskStatus->current_task_stage = 2;
                            $updateTaskStatus->save();
                            $pushNotificationforQcImageApprove = $this->employeeQcRejectedPushNotification($taskManDetails->order_items_id);
                        }

                        //PhotoPrint
                        if ($request->service_id == 2) {
                            Log::channel("previewImageRejected")->info("request value PhotoPrintUploadModel previewImageRejected_id:: $id :: status :: $request->qc_status");
                            Log::channel("previewImageRejected")->info("Photo Print rejected successfully");

                            $update = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->update(array(
                                'qc_status' => $request->qc_status,
                                'qc_reason' => $request->qc_reason,
                                'qc_reason_on' => Server::getDateTime()
                            ));

                            $photoPrintQc = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'order_photoprint_upload.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('order_photoprint_upload.*', 'orders.order_code', 'order_items.product_name')->first();

                            if (!empty($photoPrintQc->qc_image)) {
                                $qcHistory = new PhotoPrintUploadQcUpload();
                                $qcHistory->order_photoprint_upload_id = $request->id;
                                $qcHistory->qc_image = $photoPrintQc->qc_image;
                                $qcHistory->qc_on = $photoPrintQc->qc_on;
                                $qcHistory->qc_by = $photoPrintQc->qc_by;
                                $qcHistory->qc_reason = $photoPrintQc->qc_reason;
                                $qcHistory->qc_reason_on = $photoPrintQc->qc_reason_on;
                                $qcHistory->qc_status = $photoPrintQc->qc_status;
                                $qcHistory->save();
                                Log::channel("previewAttachedImageUpload")->info("request value PhotoPrintUploadHistoryModel previewAttachedImageUpload_id:: $qcHistory");
                            }

                            $PhotoPrintDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoprint_upload.order_items_id')->select('task_manager.*')->first();

                            $taskManDetails = TaskManager::where('task_manager_id', $PhotoPrintDetails->task_manager_id)->first();

                            $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id', 'employee.employee_name')->first();

                            $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc rejected by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Task Manager');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                            $updateStageStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                            $updateStageStatus->qc_status = $request->qc_status;
                            $updateStageStatus->qc_reason = $request->qc_reason;
                            $updateStageStatus->qc_reason_on = Server::getDateTime();
                            $updateStageStatus->qc_reason_by = JwtHelper::getSesUserId();
                            $updateStageStatus->save();

                            $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskManagerHistoryUpdate->work_stage = 2;
                            $taskManagerHistoryUpdate->save();

                            $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHistoryUpdate->status = 2;
                            $employeetaskManagerHistoryUpdate->save();

                            $updateTaskStatus = TaskManager::find($updateStatus->task_manager_id);
                            $updateTaskStatus->current_task_stage = 2;
                            $updateTaskStatus->save();
                            $pushNotificationforQcImageApprove = $this->employeeQcRejectedPushNotification($taskManDetails->order_items_id);
                        }

                        //Personalized
                        if ($request->service_id == 4) {
                            Log::channel("attachedImageRejected")->info("request value PhotoFrameUploadModel attachedImageRejected_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageRejected")->info("Photo frame rejected successfully");

                            $update = TaskManager::where('task_manager_id', $id)->update(array(
                                'qc_status' => $request->qc_status,
                                'qc_reason' => $request->qc_reason,
                                'qc_reason_on' => Server::getDateTime()
                            ));

                            $personalizedQcDetails = TaskManager::where('task_manager_id', $id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('task_manager.*', 'orders.order_code', 'order_items.product_name')->first();

                            if (!empty($personalizedQcDetails->qc_image)) {
                                $personalizedQc = new TaskManagerQcHistory();
                                $personalizedQc->task_manager_id = $request->id;
                                $personalizedQc->qc_image = $personalizedQcDetails->qc_image;
                                $personalizedQc->qc_on = $personalizedQcDetails->qc_on;
                                $personalizedQc->qc_by = $personalizedQcDetails->qc_by;
                                $personalizedQc->qc_reason = $personalizedQcDetails->qc_reason;
                                $personalizedQc->qc_reason_on = $personalizedQcDetails->qc_reason_on;
                                $personalizedQc->qc_status = $personalizedQcDetails->qc_status;
                                $personalizedQc->save();
                                Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $personalizedQc");
                            }

                            $taskManDetails = TaskManager::where('task_manager_id', $id)->first();

                            $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id', 'employee.employee_name')->first();

                            $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc rejected by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Task Manager');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                            $updateStageStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                            $updateStageStatus->qc_status = $request->qc_status;
                            $updateStageStatus->qc_reason = $request->qc_reason;
                            $updateStageStatus->qc_reason_on = Server::getDateTime();
                            $updateStageStatus->qc_reason_by = JwtHelper::getSesUserId();
                            $updateStageStatus->save();

                            $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskManagerHistoryUpdate->work_stage = 2;
                            $taskManagerHistoryUpdate->save();

                            $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHistoryUpdate->status = 2;
                            $employeetaskManagerHistoryUpdate->save();

                            $updateTaskStatus = TaskManager::find($updateStatus->task_manager_id);
                            $updateTaskStatus->current_task_stage = 2;
                            $updateTaskStatus->save();

                            $pushNotificationforQcImageApprove = $this->employeeQcRejectedPushNotification($taskManDetails->order_items_id);
                        }

                        //Photoframe
                        if ($request->service_id == 3) {
                            Log::channel("previewImageRejected")->info("request value PhotoFrameUploadModel previewImageRejected_id:: $id :: status :: $request->qc_status");
                            Log::channel("previewImageRejected")->info("Photo frame rejected successfully");

                            $update = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->update(array(
                                'qc_status' => $request->qc_status,
                                'qc_reason' => $request->qc_reason,
                                'qc_reason_on' => Server::getDateTime()
                            ));

                            $photoframeQc = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->first();

                            if (!empty($photoframeQc->qc_image)) {
                                $qcHistory = new PhotoFrameQcHistory();
                                $qcHistory->order_photoframe_upload_label_id = $request->id;
                                $qcHistory->qc_image = $photoframeQc->qc_image;
                                $qcHistory->qc_on = $photoframeQc->qc_on;
                                $qcHistory->qc_by = $photoframeQc->qc_by;
                                $qcHistory->qc_reason = $photoframeQc->qc_reason;
                                $qcHistory->qc_reason_on = $photoframeQc->qc_reason_on;
                                $qcHistory->qc_status = $photoframeQc->qc_status;
                                $qcHistory->save();
                                Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $qcHistory");
                            }

                            $labelDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->select('task_manager.*')->first();

                            $taskManDetails = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('task_manager.*', 'orders.order_code', 'order_items.product_name')->first();

                            $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id', 'employee.employee_name')->first();

                            $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc rejected by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Task Manager');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                            $updateStageStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                            $updateStageStatus->qc_status = $request->qc_status;
                            $updateStageStatus->qc_reason = $request->qc_reason;
                            $updateStageStatus->qc_reason_on = Server::getDateTime();
                            $updateStageStatus->qc_reason_by = JwtHelper::getSesUserId();
                            $updateStageStatus->save();

                            $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskManagerHistoryUpdate->work_stage = 2;
                            $taskManagerHistoryUpdate->save();

                            $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHistoryUpdate->status = 2;
                            $employeetaskManagerHistoryUpdate->save();

                            $updateTaskStatus = TaskManager::find($updateStatus->task_manager_id);
                            $updateTaskStatus->current_task_stage = 2;
                            $updateTaskStatus->save();
                            $pushNotificationforQcImageApprove = $this->employeeQcRejectedPushNotification($taskManDetails->order_items_id);
                        }

                        //Selfie
                        if ($request->service_id == 6) {
                            Log::channel("previewImageRejected")->info("request value PhotoFrameUploadModel previewImageRejected_id:: $id :: status :: $request->qc_status");
                            Log::channel("previewImageRejected")->info("Photo frame rejected successfully");

                            $update = SelfieUploadModel::where('order_selfie_upload_id', $id)->update(array(
                                'qc_status' => $request->qc_status,
                                'qc_reason' => $request->qc_reason,
                                'qc_reason_on' => Server::getDateTime()
                            ));

                            $selfieQc = SelfieUploadModel::where('order_selfie_upload_id', $id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'order_selfie_upload.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('order_selfie_upload.*', 'orders.order_code', 'order_items.product_name')->first();

                            if (!empty($selfieQc->qc_image)) {
                                $qcHistory = new SelfieUploadQcModel();
                                $qcHistory->order_selfie_upload_id = $request->id;
                                $qcHistory->qc_image = $selfieQc->qc_image;
                                $qcHistory->qc_on = $selfieQc->qc_on;
                                $qcHistory->qc_by = $selfieQc->qc_by;
                                $qcHistory->qc_reason = $selfieQc->qc_reason;
                                $qcHistory->qc_reason_on = $selfieQc->qc_reason_on;
                                $qcHistory->qc_status = $selfieQc->qc_status;
                                $qcHistory->save();
                                Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $qcHistory");
                            }

                            $selfieDetails = SelfieUploadModel::where('order_selfie_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_selfie_upload.order_items_id')->select('task_manager.*')->first();

                            $taskManDetails = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->first();

                            $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id', 'employee.employee_name')->first();

                            $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc rejected by ' . JwtHelper::getSesUserNameWithType() . '';
                            $activitytype = Config('activitytype.Task Manager');
                            GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                            $updateStageStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                            $updateStageStatus->qc_status = $request->qc_status;
                            $updateStageStatus->qc_reason = $request->qc_reason;
                            $updateStageStatus->qc_reason_on = Server::getDateTime();
                            $updateStageStatus->qc_reason_by = JwtHelper::getSesUserId();
                            $updateStageStatus->save();

                            $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskManagerHistoryUpdate->work_stage = 2;
                            $taskManagerHistoryUpdate->save();

                            $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHistoryUpdate->status = 2;
                            $employeetaskManagerHistoryUpdate->save();

                            $updateTaskStatus = TaskManager::find($updateStatus->task_manager_id);
                            $updateTaskStatus->current_task_stage = 2;
                            $updateTaskStatus->save();
                            $pushNotificationforQcImageApprove = $this->employeeQcRejectedPushNotification($taskManDetails->order_items_id);
                        }
                        Log::channel("attachedImageRejected")->info('** end the attachedImageRejected method **');
                    }

                    if ($request->qc_status == 1) {
                        Log::channel("attachedimages")->info("save value :: attachedimages_id :: $id :: attachedimages inactive successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Approved successfully'),
                            'data' => []
                        ]);
                    } else if ($request->qc_status == 2) {
                        Log::channel("attachedimages")->info("save value :: attachedimages_id :: $id :: attachedimages active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Rejected successfully'),
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
            Log::channel("attachedimages")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //Onlyqc
    //Qc Approved or Rejected
    public function onlyQcApprovedRejected(Request $request)
    {
        try {

            if (!empty($request)) {

                $type = $request->type;

                $id = $request->id;

                if (!empty($id)) {
                    if ($type == "approved") {
                        Log::channel("attachedImageApproved")->info('** started the attachedImageApproved method **');
                        Log::channel("attachedImageApproved")->info("Photo frame approved successfully");

                        $taskManDetails = TaskManager::where('task_manager_id', $id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('task_manager.*', 'orders.order_code', 'order_items.product_name')->first();

                        $update = OrderItemStage::where('orderitem_stage_id', $taskManDetails->orderitem_stage_id)->update(array(
                            'qc_status' => $request->qc_status,
                            'qc_on' => Server::getDateTime(),
                            'qc_by' => JwtHelper::getSesUserId()
                        ));

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'task_manager.task_manager_id', 'employee.employee_name')->first();

                        $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc approved by ' . JwtHelper::getSesUserNameWithType() . '';
                        $activitytype = Config('activitytype.Task Manager');
                        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        if ($updateStatus->is_customer_preview == 0 && $updateStatus->is_qc == 1 && $updateStatus->qc_status == 1) {
                            $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                            $orderItemStatus->completed_on = Server::getDateTime();
                            $orderItemStatus->completed_by = JwtHelper::getSesUserId();
                            $orderItemStatus->status = 2;
                            $orderItemStatus->save();

                            $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskHisStatusUpdate->completed_on = Server::getDateTime();
                            $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                            $taskHisStatusUpdate->work_stage = 4;
                            $taskHisStatusUpdate->save();

                            $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHisUpdate->status = 4;
                            $employeetaskManagerHisUpdate->save();

                            $updateTaskStatus = TaskManager::find($updateStatus->task_manager_id);
                            $updateTaskStatus->current_task_stage = 2;
                            $updateTaskStatus->save();
                        }

                        $orderitemstageCount = TaskManager::where('task_manager_id', $id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        $stageCompletedCount = TaskManager::where('task_manager_id', $id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        if ($orderitemstageCount == $stageCompletedCount) {
                            Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                            $taskStatusUpdate = TaskManager::find($id);
                            $taskStatusUpdate->current_task_stage = 4;
                            $taskStatusUpdate->save();
                        }
                        $onlyQc = $this->OnlyQcApprovedPushNotification($taskManDetails->order_items_id);
                    }

                    if ($type == "rejected") {
                        Log::channel("attachedImageRejected")->info('** started the attachedImageRejected method **');

                        $taskManDetails = TaskManager::where('task_manager_id', $id)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('task_manager.*', 'orders.order_code', 'order_items.product_name')->first();

                        $update = OrderItemStage::where('orderitem_stage_id', $taskManDetails->orderitem_stage_id)->update(array(
                            'qc_status' => $request->qc_status,
                            'qc_reason' => $request->qc_reason,
                            'qc_reason_on' => Server::getDateTime(),
                            'qc_by' => JwtHelper::getSesUserId()
                        ));

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id', 'employee.employee_name')->first();

                        $desc = 'This ' . $taskManDetails->order_code . ' - ' . $taskManDetails->product_name . ' ' . 'stage ' . $updateStatus->stage . ' - ' . 'qc rejected by ' . JwtHelper::getSesUserNameWithType() . '';
                        $activitytype = Config('activitytype.Task Manager');
                        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                        $orderItemStatus->qc_on = Server::getDateTime();
                        $orderItemStatus->qc_by = JwtHelper::getSesUserId();
                        $orderItemStatus->status = 1;
                        $orderItemStatus->save();

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $updateTaskStatus = TaskManager::find($updateStatus->task_manager_id);
                        $updateTaskStatus->current_task_stage = 2;
                        $updateTaskStatus->save();

                        Log::channel("attachedImageRejected")->info('** end the attachedImageRejected method **');
                        $onlyQc = $this->OnlyQcRejectedPushNotification($taskManDetails->order_items_id);
                    }

                    if ($request->qc_status == 1) {
                        Log::channel("attachedimages")->info("save value :: attachedimages_id :: $id :: attachedimages inactive successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Approved successfully'),
                            'data' => []
                        ]);
                    } else if ($request->qc_status == 2) {
                        Log::channel("attachedimages")->info("save value :: attachedimages_id :: $id :: attachedimages active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Rejected successfully'),
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
            Log::channel("attachedimages")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //Employee only qc approved
    public function OnlyQcApprovedPushNotification($orderItemId)
    {
        $orderDetails = TaskManager::where('task_manager.order_items_id', $orderItemId)->where('task_manager_history.production_status', 1)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->leftJoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'task_manager.order_items_id', 'task_manager_history.task_manager_history_id', 'task_manager_history.employee_id', 'task_manager_history.orderitem_stage_id')->first();

        $title = "QC Approved" . " - " . $orderDetails->product_code;
        $body = "Your product $orderDetails->product_code - $orderDetails->product_name has been approved. The item is ready for the next stage!";

        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'QC Approved';
        $portal = "employee";
        $page = 'qc_approved';

        $data = [
            'employee_id' => $orderDetails->employee_id,
            'product_code' => $orderDetails->product_code,
            'product_name' => $orderDetails->product_name,
            'order_items_id' => $orderDetails->order_items_id,
            'task_manager_history_id' => $orderDetails->task_manager_history_id,
            'orderitem_stage_id' => $orderDetails->orderitem_stage_id,
            'random_id' => $random_id,
            'page' => $page,
            'url' => "employee/employee-task-manger/employee-task-detail?"
        ];
        $message = [
            'title' => $title,
            'module' => $module,
            'portal' => $portal,
            'body' => $body,
            'page' => $page,
            'data' => $data
        ];

        $token = Employee::where('employee_id', $orderDetails->employee_id)->where('fcm_token', '!=', NULL)->select('fcm_token', 'employee_id')->first();

        $employeeDetail = Employee::where('employee_id', $orderDetails->employee_id)->first();
        if (!empty($token)) {
            $push = Firebase::sendSingle($token->fcm_token, $message);
        }
        $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $employeeDetail->employee_id, $module, $page, $portal, $data, $random_id);
    }

    //Employee qc rejected
    public function OnlyQcRejectedPushNotification($orderItemId)
    {
        $orderDetails = TaskManager::where('task_manager.order_items_id', $orderItemId)->where('task_manager_history.production_status', 1)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->leftJoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'task_manager.order_items_id', 'task_manager_history.task_manager_history_id', 'task_manager_history.employee_id', 'task_manager_history.orderitem_stage_id')->first();

        $title = "QC Rejected" . " - " . $orderDetails->product_code;
        $body = "Your product $orderDetails->product_code - $orderDetails->product_name has been rejected. Please check and update it.";

        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'QC Rejected';
        $portal = "employee";
        $page = 'qc_rejected';

        $data = [
            'employee_id' => $orderDetails->employee_id,
            'product_code' => $orderDetails->product_code,
            'product_name' => $orderDetails->product_name,
            'order_items_id' => $orderDetails->order_items_id,
            'task_manager_history_id' => $orderDetails->task_manager_history_id,
            'orderitem_stage_id' => $orderDetails->orderitem_stage_id,
            'random_id' => $random_id,
            'page' => $page,
            'url' => "employee/employee-task-manger/employee-task-detail?"
        ];
        $message = [
            'title' => $title,
            'module' => $module,
            'portal' => $portal,
            'body' => $body,
            'page' => $page,
            'data' => $data
        ];

        $token = Employee::where('employee_id', $orderDetails->employee_id)->where('fcm_token', '!=', NULL)->select('fcm_token', 'employee_id')->first();

        $employeeDetail = Employee::where('employee_id', $orderDetails->employee_id)->first();
        if (!empty($token)) {
            $push = Firebase::sendSingle($token->fcm_token, $message);
        }
        $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $employeeDetail->employee_id, $module, $page, $portal, $data, $random_id);
    }

    public function onlyTaskQcApprovedRejected(Request $request)
    {
        try {

            if (!empty($request)) {

                $type = $request->type;

                $id = $request->id;

                if (!empty($id)) {
                    if ($type == "approved") {
                        Log::channel("attachedImageApproved")->info('** started the attachedImageApproved method **');
                        Log::channel("attachedImageApproved")->info("Photo frame approved successfully");

                        $taskManDetails = CustomTaskQcHistory::where('task_manager_id', $id)->orderBy('custom_task_qc_history_id', 'desc')->first();

                        $update = CustomTaskQcHistory::where('custom_task_qc_history_id', $taskManDetails->custom_task_qc_history_id)->update(array(
                            'qc_status' => $request->qc_status,
                            'qc_on' => Server::getDateTime(),
                            'qc_by' => JwtHelper::getSesUserId()
                        ));

                        $update = TaskManager::where('task_manager_id', $id)->update(array(
                            'qc_status' => $request->qc_status,
                            'qc_on' => Server::getDateTime(),
                            'qc_by' => JwtHelper::getSesUserId()
                        ));

                        $updateStatus = TaskManagerHistory::where('task_manager.task_manager_id', $id)->where('task_manager_history.production_status', 1)
                            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'task_manager.qc_status', 'task_manager.task_code', 'task_manager.task_name', 'employee.employee_name')->first();

                        $desc = 'This ' . $updateStatus->task_code . ' - ' . $updateStatus->task_name . ' qc approved by ' . JwtHelper::getSesUserNameWithType() . '';
                        $activitytype = Config('activitytype.Task Manager');
                        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        if ($updateStatus->qc_status == 1) {

                            $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskHisStatusUpdate->completed_on = Server::getDateTime();
                            $taskHisStatusUpdate->completed_by = JwtHelper::getSesUserId();
                            $taskHisStatusUpdate->work_stage = 4;
                            $taskHisStatusUpdate->save();

                            $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHisUpdate->status = 4;
                            $employeetaskManagerHisUpdate->save();

                            $taskStatusUpdate = TaskManager::find($id);
                            $taskStatusUpdate->current_task_stage = 4;
                            $taskStatusUpdate->save();
                        }
                        $onlyQc = $this->OnlyTaskQcApprovedPushNotification($taskManDetails->task_manager_id);
                    }

                    if ($type == "rejected") {
                        Log::channel("attachedImageRejected")->info('** started the attachedImageRejected method **');

                        $taskManDetails = CustomTaskQcHistory::where('task_manager_id', $id)->orderBy('custom_task_qc_history_id', 'desc')->first();

                        $update = CustomTaskQcHistory::where('custom_task_qc_history_id', $taskManDetails->custom_task_qc_history_id)->update(array(
                            'qc_status' => $request->qc_status,
                            'qc_reason' => $request->qc_reason,
                            'qc_reason_on' => Server::getDateTime(),
                            'qc_by' => JwtHelper::getSesUserId()
                        ));

                        $update = TaskManager::where('task_manager_id', $id)->update(array(
                            'qc_status' => $request->qc_status,
                            'qc_reason' => $request->qc_reason,
                            'qc_reason_on' => Server::getDateTime(),
                            'qc_by' => JwtHelper::getSesUserId()
                        ));
                        $updateStatus = TaskManagerHistory::where('task_manager.task_manager_id', $id)->where('task_manager_history.production_status', 1)
                            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->leftjoin('employee', 'employee.employee_id', '=', 'employee_task_history.employee_id')->select('task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id', 'task_manager.qc_status', 'task_manager.task_code', 'task_manager.task_name', 'employee.employee_name')->first();

                        $desc = 'This ' . $updateStatus->task_code . ' - ' . $updateStatus->task_name . ' qc rejected by ' . JwtHelper::getSesUserNameWithType() . '';
                        $activitytype = Config('activitytype.Task Manager');
                        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $updateTaskStatus = TaskManager::find($updateStatus->task_manager_id);
                        $updateTaskStatus->current_task_stage = 2;
                        $updateTaskStatus->save();

                        $onlyQc = $this->OnlyTaskQcRejectedPushNotification($taskManDetails->task_manager_id);
                        Log::channel("attachedImageRejected")->info('** end the attachedImageRejected method **');
                    }

                    if ($request->qc_status == 1) {
                        Log::channel("attachedimages")->info("save value :: attachedimages_id :: $id :: attachedimages inactive successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Approved successfully'),
                            'data' => []
                        ]);
                    } else if ($request->qc_status == 2) {
                        Log::channel("attachedimages")->info("save value :: attachedimages_id :: $id :: attachedimages active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Rejected successfully'),
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
            Log::channel("attachedimages")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //Employee only qc approved
    public function OnlyTaskQcApprovedPushNotification($taskManagerId)
    {
        $orderDetails = TaskManager::where('task_manager.task_manager_id', $taskManagerId)->where('task_manager_history.production_status', 1)
            ->leftJoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->select('task_manager.task_manager_id', 'task_manager.task_name', 'task_manager.task_code', 'task_manager_history.task_manager_history_id', 'task_manager_history.employee_id', 'task_manager_history.orderitem_stage_id')->first();

        $title = "QC Approved" . " - " . $orderDetails->task_code;
        $body = "Your product $orderDetails->task_code - $orderDetails->task_name has been approved. The item is ready for the next stage!";

        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'QC Approved';
        $portal = "employee";
        $page = 'qc_approved';

        $data = [
            'employee_id' => $orderDetails->employee_id,
            'task_code' => $orderDetails->task_code,
            'task_name' => $orderDetails->task_name,
            'task_manager_history_id' => $orderDetails->task_manager_history_id,
            'orderitem_stage_id' => $orderDetails->orderitem_stage_id,
            'random_id' => $random_id,
            'page' => $page,
            'url' => "employee/employee-task-manger/employee-task-detail?"
        ];
        $message = [
            'title' => $title,
            'module' => $module,
            'portal' => $portal,
            'body' => $body,
            'page' => $page,
            'data' => $data
        ];

        $token = Employee::where('employee_id', $orderDetails->employee_id)->where('fcm_token', '!=', NULL)->select('fcm_token', 'employee_id')->first();

        $employeeDetail = Employee::where('employee_id', $orderDetails->employee_id)->first();
        if (!empty($token)) {
            $push = Firebase::sendSingle($token->fcm_token, $message);
        }
        $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $employeeDetail->employee_id, $module, $page, $portal, $data, $random_id);
    }

    public function OnlyTaskQcRejectedPushNotification($taskManagerId)
    {
        $orderDetails = TaskManager::where('task_manager.task_manager_id', $taskManagerId)->where('task_manager_history.production_status', 1)
            ->leftJoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->select('task_manager.task_manager_id', 'task_manager.task_name', 'task_manager.task_code', 'task_manager_history.task_manager_history_id', 'task_manager_history.employee_id', 'task_manager_history.orderitem_stage_id')->first();

        $title = "QC Rejected" . " - " . $orderDetails->task_code;
        $body = "Your product $orderDetails->task_code - $orderDetails->task_name has been rejected. Please check and update it.";

        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'QC Rejected';
        $portal = "employee";
        $page = 'qc_rejected';

        $data = [
            'employee_id' => $orderDetails->employee_id,
            'task_code' => $orderDetails->task_code,
            'task_name' => $orderDetails->task_name,
            'task_manager_history_id' => $orderDetails->task_manager_history_id,
            'orderitem_stage_id' => $orderDetails->orderitem_stage_id,
            'random_id' => $random_id,
            'page' => $page,
            'url' => "employee/employee-task-manger/employee-task-detail?"
        ];
        $message = [
            'title' => $title,
            'module' => $module,
            'portal' => $portal,
            'body' => $body,
            'page' => $page,
            'data' => $data
        ];

        $token = Employee::where('employee_id', $orderDetails->employee_id)->where('fcm_token', '!=', NULL)->select('fcm_token', 'employee_id')->first();

        $employeeDetail = Employee::where('employee_id', $orderDetails->employee_id)->first();
        if (!empty($token)) {
            $push = Firebase::sendSingle($token->fcm_token, $message);
        }
        $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $employeeDetail->employee_id, $module, $page, $portal, $data, $random_id);
    }

    public function deliveryList(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $column_search = array('orders.order_code', 'order_items.product_code', 'order_items.product_name', 'task_manager.task_code', 'task_manager.task_name', 'task_manager.description');

        $id = JwtHelper::getSesUserId();

        $orders = TaskManager::where('task_manager.current_task_stage', 4)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftjoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')
            ->select('order_items.order_id', 'order_items.product_id', 'order_items.product_variant_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'orders.order_code', 'orders.customer_id', 'orders.payment_status', 'orders.order_date', 'task_manager.order_items_id', 'task_manager.task_manager_id', 'task_manager.task_code', 'task_manager.task_type', 'task_manager.task_name', 'task_manager.description', 'task_manager.attachment_image', 'task_manager.current_task_stage', 'task_manager.created_on', 'task_manager.folder', 'task_manager.is_dispatch', 'task_manager_history.task_manager_history_id', 'task_manager_history.assigned_on', 'task_manager_history.completed_on', 'task_manager_history.expected_on', 'task_manager_history.taken_on', 'task_manager_history.work_stage', 'employee.employee_name')->groupBy('order_items.order_items_id')->orderBy('task_manager_history.completed_on', 'desc');

        $orders->where(function ($query) use ($searchval, $column_search, $orders) {

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

        $count = count($orders->get());

        if ($offset) {
            $offset = $offset * $limit;
            $orders->offset($offset);
        }
        if ($limit) {
            $orders->limit($limit);
        }

        $orders = $orders->get();

        $orderAry = [];
        foreach ($orders as $value) {
            $ary = [];
            if (!empty($value['order_items_id'])) {
                $ary['group_id'] = "order|" . $value['order_code'] . "|" . $value['order_date'];
                $ary['task_manager_id'] = $value->task_manager_id;
                $ary['order_type'] = !empty($value->customer_id) ? "Order" : "Bulk Order";
                $ary['payment_status'] = $value->payment_status;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['order_id'] = $value->order_id;
                $ary['order_items_id'] = $value->order_items_id;
                $ary['product_id'] = $value->product_id;
                $ary['product_variant_id'] = $value->product_variant_id;
                $ary['product_code'] = $value->product_code;
                $ary['product_name'] = $value->product_name;
                if (!empty($value->service_id)) {
                    $ary['task_stages'] = $this->orderItemtaskStageDetails($value->order_items_id, $value->task_manager_id);
                    $ary['no_of_stages_involve'] = count($this->orderItemtaskStageDetails($value->order_items_id, $value->task_manager_id));
                }
                $ary['service_id'] = $value->service_id;
                $ary['task_code'] = $value->task_code;
                $ary['task_type'] = $value->task_type;
                $ary['task_name'] = $value->task_name;
                $ary['description'] = $value->description;
                $gTImage = json_decode($value['attachment_image'], true);
                $ary['attachment_image'] = $this->getdefaultImages_allImages($gTImage, $value->folder);
                $ary['current_task_stage'] = $value->current_task_stage;
                $ary['created_on'] = $value->created_on;
                $ary['is_dispatch'] = $value->is_dispatch;
            } else {
                $ary['group_id'] = "task|" . $value['task_code'] . "|" . $value['created_on'];
                $ary['task_manager_id'] = $value->task_manager_id;
                $ary['order_type'] = !empty($value->customer_id) ? "Order" : "Bulk Order";
                $ary['payment_status'] = $value->payment_status;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['order_id'] = $value->order_id;
                $ary['order_items_id'] = $value->order_items_id;
                $ary['product_id'] = $value->product_id;
                $ary['product_variant_id'] = $value->product_variant_id;
                $ary['product_code'] = $value->product_code;
                $ary['product_name'] = $value->product_name;
                if (!empty($value->service_id)) {
                    $ary['task_stages'] = $this->orderItemtaskStageDetails($value->order_items_id, $value->task_manager_id);
                    $ary['no_of_stages_involve'] = count($this->orderItemtaskStageDetails($value->order_items_id, $value->task_manager_id));
                }
                $ary['service_id'] = $value->service_id;
                $ary['task_code'] = $value->task_code;
                $ary['task_type'] = $value->task_type;
                $ary['task_name'] = $value->task_name;
                $ary['description'] = $value->description;
                $gTImage = json_decode($value['attachment_image'], true);
                $ary['attachment_image'] = $this->getdefaultImages_allImages($gTImage, $value->folder);
                $ary['current_task_stage'] = $value->current_task_stage;
                $ary['created_on'] = $value->created_on;
                $ary['is_dispatch'] = $value->is_dispatch;
                $ary['assigned_on'] = $value['assigned_on'];
                $ary['assigned_to'] = $value['employee_name'];
                $ary['completed_on'] = $value['completed_on'];
                $ary['expected_on'] = $value['expected_on'];
                $ary['taken_on'] = $value['taken_on'];
                $ary['work_stage'] = $value['work_stage'];
            }
            $orderAry[] = $ary;
        }

        if (!empty($orderAry)) {
            $final = collect($orderAry)->groupBy('group_id')->all();
        }
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Delivery listed successfully'),
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => []
            ]);
        }
    }

    public function getOrderGroupBy($list, $id)
    {

        $rs = [];

        foreach ($list as $act) {

            if ($act['order_id'] == $id) {;
                $rs[] = $act;
            }
        }
        return  $rs;
    }

    // public function taskStageDepartment($id)
    // {
    //     return Taskstage::where('service_id', $id)->where('status', 1)->first();
    // }


    public function operationList_original(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $uniqueOrd = OrderItems::select('orders.order_id', 'orders.order_code', 'orders.order_date')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->groupBy('orders.order_id')->whereIn('order_items.service_id', [3, 4, 6])->where('order_items.order_status', 10)->where('orders.payment_status', 1)->get();

        $data1 = collect($uniqueOrd);

        $uniqueTask = TaskManager::select('task_manager_id', 'task_code', 'created_on')->where('current_task_stage', 1)->get();
        $data2 = collect($uniqueTask);

        $uniqueOrders = !empty($data2) ? $data1->merge($data2) : $data1;

        $order = "SELECT 
            `order_items`.`order_items_id`,
            `order_items`.`order_id`,
            `order_items`.`product_id`,
            `order_items`.`product_variant_id`,
            `order_items`.`service_id`,
            `order_items`.`product_name`,
            `order_items`.`product_code`,
            `orders`.`order_code`,
            null AS `task_manager_id`,
            null AS `task_code`,
            null AS `task_type`,
            null AS `task_name`,
            null AS `description`,
            null AS `attachment_image`,
            null AS `current_task_stage`,
            null AS `status`,
            null AS `created_on`
        FROM
            `order_items`
                LEFT JOIN
            `orders` ON `orders`.`order_id` = `order_items`.`order_id` ";

        $order .= " WHERE `orders`.`payment_status` = 1
                            AND `order_items`.`order_status` = 10
                            AND `order_items`.`production_status` = 0";

        // $order .=  " group by `order_items`.`order_items_id`";

        if ($searchval) {

            $order .= " AND `orders`.`order_code` LIKE '%" . $searchval . "%'
                                   OR `order_items`.`product_name` LIKE '%" . $searchval . "%'
                                   OR `order_items`.`product_code` LIKE '%" . $searchval . "%' ";
        }

        $direct = "SELECT 
            `task_manager`.`order_items_id`,
            null AS `order_id`,
            null AS `product_id`,
            null AS `product_variant_id`,
            null AS `service_id`,
            null AS `product_name`,
            null AS `product_code`,
            null AS `order_code`,
            `task_manager`.`task_manager_id`,
            `task_manager`.`task_code`,
            `task_manager`.`task_type`,
            `task_manager`.`task_name`,
            `task_manager`.`description`,
            `task_manager`.`attachment_image`,
            `task_manager`.`current_task_stage`,
            `task_manager`.`status`,
            `task_manager`.`created_on`
        FROM
            `task_manager` ";

        $direct .= " WHERE
`task_manager`.`current_task_stage` = 1";

        if ($searchval) {

            $direct .= " AND `task_manager`.`task_code` LIKE '%" . $searchval . "%'
                           OR `task_manager`.`task_name` LIKE '%" . $searchval . "%'
                           OR `task_manager`.`description` LIKE '%" . $searchval . "%' ";
        }

        $qry = " ($order ) UNION ($direct )";

        // print_r($order);exit;

        // $qry .= " ORDER BY `order_id` ASC";

        $get_qry_count = DB::select(DB::raw($qry));

        $get_qry_count = json_decode(json_encode($get_qry_count), true);

        $count = Count($get_qry_count);


        if ($limit) {
            $qry .= " LIMIT $limit";
        }

        if ($offset) {
            $offset = $limit * $offset;
            $qry .= " OFFSET $offset";
        }

        //   DB::connection()->enableQueryLog();

        $get_qry_new = DB::select(DB::raw($qry));

        // $queries = DB::getQueryLog();

        // print_r($queries);die;

        $get_qry_new = json_decode(json_encode($get_qry_new), true);
        // print_r($get_qry_new);exit;
        if (!empty($get_qry_new)) {
            $orderAry = [];
            foreach ($get_qry_new as $value) {
                $ary = [];
                if (!empty($value['order_id'])) {
                    $ary['order_id'] = $value['order_id'];
                    $ary['order_items_id'] = $value['order_items_id'];
                    $ary['order_code'] = $value['order_code'];
                    $ary['product_id'] = $value['product_id'];
                    $ary['product_variant_id'] = $value['product_variant_id'];
                    $ary['service_id'] = $value['service_id'];
                    $ary['product_name'] = $value['product_name'];
                    $ary['product_code'] = $value['product_code'];
                } else {
                    $ary['order_id'] = $value['order_id'];
                    $ary['task_manager_id'] = $value['task_manager_id'];
                    $ary['task_code'] = $value['task_code'];
                    $ary['order_items_id'] = $value['order_items_id'];
                    $ary['task_type'] = $value['task_type'];
                    $ary['task_name'] = $value['task_name'];
                    $ary['description'] = $value['description'];
                    $ary['current_task_stage'] = $value['current_task_stage'];
                    $gTImage = json_decode($value['attachment_image'], true);
                    $ary['attachment_image'] = $this->getdefaultImages_allImages($gTImage, $value->folder);
                    $ary['status'] = $value['status'];
                    $ary['created_on'] = $value['created_on'];
                }
                $orderAry[] = $ary;
            }
        }
        if (!empty($orderAry)) {
            $result = $uniqueOrders->map(function ($c) use ($orderAry) {
                $c['task_type'] = !empty($c['order_id']) ? "order" : "task";
                $c['list'] = $this->getOrderGroupBy($orderAry, $c['order_id']);

                return $c;
            });
        }
        if (!empty($result)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Operation listed successfully'),
                'data' => $result,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => []
            ]);
        }
    }

    public function operationList_original_new(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $order = "SELECT 
            `order_items`.`order_items_id`,
            `order_items`.`order_id`,
            `order_items`.`product_id`,
            `order_items`.`product_variant_id`,
            `order_items`.`service_id`,
            `order_items`.`product_name`,
            `order_items`.`product_code`,
            `orders`.`order_code`,
            `orders`.`order_date`,
            null AS `task_manager_id`,
            null AS `task_code`,
            null AS `task_type`,
            null AS `task_name`,
            null AS `description`,
            null AS `attachment_image`,
            null AS `current_task_stage`,
            null AS `status`,
            null AS `created_on`,
            null AS `folder`
        FROM
            `order_items`
                LEFT JOIN
            `orders` ON `orders`.`order_id` = `order_items`.`order_id` ";

        $order .= " WHERE `order_items`.`order_status` = 10
                            AND `order_items`.`production_status` = 0";

        // $order .=  " group by `order_items`.`order_items_id`";

        if ($searchval) {

            $order .= " AND `orders`.`order_code` LIKE '%" . $searchval . "%'
                                   OR `order_items`.`product_name` LIKE '%" . $searchval . "%'
                                   OR `order_items`.`product_code` LIKE '%" . $searchval . "%' ";
        }

        $direct = "SELECT 
            `task_manager`.`order_items_id`,
            null AS `order_id`,
            null AS `product_id`,
            null AS `product_variant_id`,
            null AS `service_id`,
            null AS `product_name`,
            null AS `product_code`,
            null AS `order_code`,
            null AS `order_date`,
            `task_manager`.`task_manager_id`,
            `task_manager`.`task_code`,
            `task_manager`.`task_type`,
            `task_manager`.`task_name`,
            `task_manager`.`description`,
            `task_manager`.`attachment_image`,
            `task_manager`.`current_task_stage`,
            `task_manager`.`status`,
            `task_manager`.`created_on`,
            `task_manager`.`folder`
        FROM
            `task_manager` ";

        $direct .= " WHERE
`task_manager`.`current_task_stage` = 1";

        if ($searchval) {

            $direct .= " AND `task_manager`.`task_code` LIKE '%" . $searchval . "%'
                           OR `task_manager`.`task_name` LIKE '%" . $searchval . "%'
                           OR `task_manager`.`description` LIKE '%" . $searchval . "%' ";
        }

        $qry = " ($order ) UNION ($direct )";

        print_r($qry);
        exit;

        // $qry .= " ORDER BY `order_id` ASC";

        $get_qry_count = DB::select(DB::raw($qry));

        $get_qry_count = json_decode(json_encode($get_qry_count), true);

        $count = Count($get_qry_count);


        if ($limit) {
            $qry .= " LIMIT $limit";
        }

        if ($offset) {
            $offset = $limit * $offset;
            $qry .= " OFFSET $offset";
        }

        //   DB::connection()->enableQueryLog();

        $get_qry_new = DB::select(DB::raw($qry));

        // $queries = DB::getQueryLog();

        // print_r($queries);die;

        $get_qry_new = json_decode(json_encode($get_qry_new), true);
        // print_r($get_qry_new);exit;
        if (!empty($get_qry_new)) {
            $orderAry = [];
            foreach ($get_qry_new as $value) {
                $ary = [];
                if (!empty($value['order_id'])) {
                    $ary['group_id'] = "order|" . $value['order_code'] . "|" . $value['order_date'];

                    $ary['order_id'] = $value['order_id'];
                    $ary['order_items_id'] = $value['order_items_id'];
                    $ary['order_code'] = $value['order_code'];
                    $ary['product_id'] = $value['product_id'];
                    $ary['product_variant_id'] = $value['product_variant_id'];
                    $ary['service_id'] = $value['service_id'];
                    $ary['product_name'] = $value['product_name'];
                    $ary['product_code'] = $value['product_code'];
                } else {
                    $ary['group_id'] = "task|" . $value['task_code'] . "|" . $value['created_on'];

                    $ary['order_id'] = $value['order_id'];
                    $ary['task_manager_id'] = $value['task_manager_id'];
                    $ary['task_code'] = $value['task_code'];
                    $ary['order_items_id'] = $value['order_items_id'];
                    $ary['task_type'] = $value['task_type'];
                    $ary['task_name'] = $value['task_name'];
                    $ary['description'] = $value['description'];
                    $ary['current_task_stage'] = $value['current_task_stage'];
                    $gTImage = json_decode($value['attachment_image'], true);
                    $ary['attachment_image'] = $this->getdefaultImages_allImages($gTImage, $value['folder']);
                    $ary['status'] = $value['status'];
                    $ary['created_on'] = $value['created_on'];
                }
                $orderAry[] = $ary;
            }
        }
        if (!empty($orderAry)) {
            $final = collect($orderAry)->groupBy('group_id')->all();
        }
        // $grouped = $orderAry->mapToGroups(function ($item, $key) {
        //     return [$item['group_id'] => $item];
        // });
        // if (!empty($orderAry)) {
        //     $result = $uniqueOrders->map(function ($c) use ($orderAry) {
        //         $c['task_type'] = !empty($c['order_id']) ? "order" : "task";
        //         $c['list'] = $this->getOrderGroupBy($orderAry, $c['order_id']);

        //         return $c;
        //     });
        // }
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Operation listed successfully'),
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => []
            ]);
        }
    }

    public function operationList(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $column_search = array('operation_task_manager.order_code', 'operation_task_manager.product_code', 'operation_task_manager.product_name', 'operation_task_manager.task_code', 'operation_task_manager.task_name', 'operation_task_manager.description');

        $id = JwtHelper::getSesUserId();

        $orders = OperationTaskManagerView::select('*');

        $orders->where(function ($query) use ($searchval, $column_search, $orders) {

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

        $count = count($orders->get());

        if ($offset) {
            $offset = $offset * $limit;
            $orders->offset($offset);
        }
        if ($limit) {
            $orders->limit($limit);
        }

        $orders = $orders->get();

        $orderAry = [];
        foreach ($orders as $value) {
            $ary = [];
            if (!empty($value['order_id'])) {
                $ary['group_id'] = "order|" . $value['order_code'] . "|" . $value['order_date'];

                $ary['order_id'] = $value['order_id'];
                $ary['order_items_id'] = $value['order_items_id'];
                $ary['order_code'] = $value['order_code'];
                $ary['product_id'] = $value['product_id'];
                $ary['product_variant_id'] = $value['product_variant_id'];
                $ary['service_id'] = $value['service_id'];
                $ary['product_name'] = $value['product_name'];
                $ary['product_code'] = $value['product_code'];
            } else {
                $ary['group_id'] = "task|" . $value['task_code'] . "|" . $value['created_on'];

                $ary['order_id'] = $value['order_id'];
                $ary['task_manager_id'] = $value['task_manager_id'];
                $ary['task_code'] = $value['task_code'];
                $ary['order_items_id'] = $value['order_items_id'];
                $ary['task_type'] = $value['task_type'];
                $ary['task_name'] = $value['task_name'];
                $ary['description'] = $value['description'];
                $ary['current_task_stage'] = $value['current_task_stage'];
                $gTImage = json_decode($value['attachment_image'], true);
                $ary['attachment_image'] = $this->getdefaultImages_allImages($gTImage, $value['folder']);
                $ary['status'] = $value['status'];
                $ary['created_on'] = $value['created_on'];
            }
            $orderAry[] = $ary;
        }
        if (!empty($orderAry)) {
            $final = collect($orderAry)->groupBy('group_id')->all();
        }
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Production listed successfully'),
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
                'data' => []
            ]);
        }
    }

    public function statusChange(Request $request)
    {
        try {
            if (!empty($request->order_item_id)) {
                $order_item_ids = json_decode($request->order_item_id, true);
                foreach ($order_item_ids as $order_item_id) {
                    $orderItemId = $order_item_id['id'];
                    $stageDetails = OrderItems::where('order_items_id', $orderItemId)->first();
                    if (!empty($stageDetails)) {
                        $slabDetails = Taskstage::where('service_id', $stageDetails->service_id)->where('status', 1)->first();

                        if (empty($slabDetails)) {
                            return response()->json([
                                'keyword' => 'failed',
                                'message' => __("Don't have a task stage for this order, kindly create the task stage."),
                                'data' => []
                            ]);
                        }
                    }
                }
            }
            if (!empty($request->order_item_id)) {
                Log::channel("statusChange")->info('** started the move to production method **');
                $order_item_ids = json_decode($request->order_item_id, true);

                foreach ($order_item_ids as $order_item_id) {
                    $taskmanager = new TaskManager();
                    $taskmanager->order_items_id = $order_item_id['id'];
                    $taskmanager->task_type = 2;
                    $taskmanager->current_task_stage = $request->current_task_stage;
                    $taskmanager->created_on = Server::getDateTime();
                    $taskmanager->created_by = JwtHelper::getSesUserId();
                    $taskmanager->save();

                    $orderItems = OrderItems::find($order_item_id['id']);
                    $orderItems->production_status = 1;
                    $orderItems->save();

                    //Order Item Stage insert
                    if (!empty($order_item_id['id'])) {
                        $Stage = OrderItemStage::where('order_items_id', $order_item_id['id'])->first();
                        if (empty($Stage)) {
                            $orderItemStage = $this->orderItemStage($order_item_id['id']);
                        }
                    }

                    $OrdItemId = $this->OrderItemsId($order_item_id['id']);
                    Log::channel("statusChange")->info("move to production order item id request value :: $taskmanager->order_items_id");
                    // log activity
                    $desc = 'This ' . $OrdItemId->order_code . ' - ' . $OrdItemId->product_name . ' moved to production by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Task Manager');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                }
            }

            if (!empty($request->task_manager_id)) {
                Log::channel("statusChange")->info('** started the statusChange method **');

                $taskmanager = TaskManager::find($request->task_manager_id);
                $taskmanager->current_task_stage = $request->current_task_stage;
                $taskmanager->updated_on = Server::getDateTime();
                $taskmanager->updated_by = JwtHelper::getSesUserId();
                $taskmanager->save();
                Log::channel("statusChange")->info("move to production order item id request value :: $taskmanager");

                $taskOrderDetail = TaskManager::where('task_manager.task_manager_id', $request->task_manager_id)->where('task_manager.order_items_id', '!=', '')->select('orders.order_code', 'order_items.*')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->groupBy('orders.order_id')->first();

                $taskDetail = TaskManager::where('task_manager.task_manager_id', $request->task_manager_id)->where('task_manager.order_items_id', '=', NULL)->select('task_manager.*')->first();

                // log activity
                if (!empty($taskOrderDetail)) {
                    if ($request->current_task_stage == 2) {
                        Log::channel("statusChange")->info('** started the move to production method **');
                        Log::channel("statusChange")->info("taskOrderDetail status update id in task manager table task_manager_id request value :: $request->task_manager_id");
                        Log::channel("statusChange")->info("taskOrderDetail status update id in task manager table current_task_stage request value :: $request->current_task_stage");

                        $desc = 'This ' . $taskOrderDetail->order_code . ' - ' . $taskOrderDetail->product_name . ' moved to production by ' . JwtHelper::getSesUserNameWithType() . '';
                        $activitytype = Config('activitytype.Task Manager');
                        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        Log::channel("statusChange")->info('** started the move to production method **');
                    }
                    if ($request->current_task_stage == 1) {
                        Log::channel("statusChange")->info('** started the move to qc method **');
                        Log::channel("statusChange")->info("taskOrderDetail status update id in task manager table task_manager_id request value :: $request->task_manager_id");
                        Log::channel("statusChange")->info("taskOrderDetail status update id in task manager table current_task_stage request value :: $request->current_task_stage");

                        $desc = 'This ' . $taskOrderDetail->order_code . ' - ' . $taskOrderDetail->product_name . ' moved to operation by ' . JwtHelper::getSesUserNameWithType() . '';
                        $activitytype = Config('activitytype.Task Manager');
                        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        Log::channel("statusChange")->info('** started the move to qc method **');
                    }
                }
                if (!empty($taskDetail)) {
                    if ($request->current_task_stage == 2) {
                        Log::channel("statusChange")->info('** started the move to production method **');
                        Log::channel("statusChange")->info("taskDetail status update id in task manager table task_manager_id request value :: $request->task_manager_id");
                        Log::channel("statusChange")->info("taskDetail status update id in task manager table current_task_stage request value :: $request->current_task_stage");

                        $desc = 'This Direct task - ' . $taskDetail->task_code . ' - ' . $taskDetail->task_name . ' - ' . ' moved to production by ' . JwtHelper::getSesUserNameWithType() . '';
                        $activitytype = Config('activitytype.Task Manager');
                        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                        Log::channel("statusChange")->info('** started the move to production method **');
                    }
                    if ($request->current_task_stage == 1) {
                        Log::channel("statusChange")->info('** started the move to qc method **');
                        Log::channel("statusChange")->info("taskDetail status update id in task manager table task_manager_id request value :: $request->task_manager_id");
                        Log::channel("statusChange")->info("taskDetail status update id in task manager table current_task_stage request value :: $request->current_task_stage");
                        $desc = 'This Direct task - ' . $taskDetail->task_code . ' - ' . $taskDetail->task_name . ' - ' . ' moved to operation by ' . JwtHelper::getSesUserNameWithType() . '';
                        $activitytype = Config('activitytype.Task Manager');
                        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                        Log::channel("statusChange")->info('** started the move to operation method **');
                    }
                }
            }
            // $task = TaskManager::where('task_manager_id', $id)->first();

            // log activity
            // $implode = implode(",", $ids);
            // $desc =  ' Task '  . $task->task_name  . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
            // $activitytype = Config('activitytype.Task Manager');
            // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
            // Log::channel("directtask")->info("save value :: task_manager_id :: $id :: task deleted successfully");
            if (!empty($request->order_item_id) || !empty($request->task_manager_id)) {
                if ($request->current_task_stage == 2) {
                    Log::channel("statusChange")->info("save value :: $request->current_task_stage");
                    Log::channel("statusChange")->info('** end task move to production method **');
                    Log::channel("directtask")->info('** end the move to production method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Task move to production successfully'),
                        'data' => []
                    ]);
                } else if ($request->current_task_stage == 1) {
                    Log::channel("statusChange")->info("save value :: $request->current_task_stage");
                    Log::channel("statusChange")->info('** end task move to production method **');
                    Log::channel("directtask")->info('** end the move to production method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Task move to operation successfully'),
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
            Log::channel("statusChange")->error($exception);
            Log::channel("statusChange")->info('** end the statusChange method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //production to operation delete
    public function productionToOperationDelete(Request $request)
    {
        try {
            if (!empty($request->order_item_id)) {
                Log::channel("statusChange")->info('** started the move to production method **');
                $order_item_ids = json_decode($request->order_item_id, true);

                foreach ($order_item_ids as $order_item_id) {
                    $manager = TaskManagerHistory::where('task_manager.order_items_id', $order_item_id['id'])->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->where('production_status', 1)->first();

                    if (empty($manager)) {
                        $task_manager = TaskManager::where('order_items_id', $order_item_id['id'])->delete();
                        $orderItemStage = OrderItemStage::where('order_items_id', $order_item_id['id'])->delete();
                        $orderItemTableUpdate = OrderItems::find($order_item_id['id']);
                        $orderItemTableUpdate->production_status = 0;
                        $orderItemTableUpdate->save();
                    }
                    // $taskmanager = new TaskManager();
                    // $taskmanager->order_items_id = $order_item_id['id'];
                    // $taskmanager->task_type = 2;
                    // $taskmanager->current_task_stage = $request->current_task_stage;
                    // $taskmanager->created_on = Server::getDateTime();
                    // $taskmanager->created_by = JwtHelper::getSesUserId();
                    // $taskmanager->save();

                    // $orderItems = OrderItems::find($order_item_id['id']);
                    // $orderItems->production_status = 1;
                    // $orderItems->save();

                    // //Order Item Stage insert
                    // if (!empty($order_item_id['id'])) {
                    //     $Stage = OrderItemStage::where('order_items_id', $order_item_id['id'])->first();
                    //     if (empty($Stage)) {
                    //         $orderItemStage = $this->orderItemStage($order_item_id['id']);
                    //     }
                    // }

                    // $OrdItemId = $this->OrderItemsId($order_item_id['id']);
                    // Log::channel("statusChange")->info("move to production order item id request value :: $taskmanager->order_items_id");
                    // // log activity
                    // $desc = 'This ' . $OrdItemId->order_code . ' - ' . $OrdItemId->product_name . ' moved to production by ' . JwtHelper::getSesUserNameWithType() . '';
                    // $activitytype = Config('activitytype.Task Manager');
                    // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                }
            }
            // $task = TaskManager::where('task_manager_id', $id)->first();

            // log activity
            // $implode = implode(",", $ids);
            // $desc =  ' Task '  . $task->task_name  . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
            // $activitytype = Config('activitytype.Task Manager');
            // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
            // Log::channel("directtask")->info("save value :: task_manager_id :: $id :: task deleted successfully");
            if (!empty($manager)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Your order item is assigned so you dont move to operation'),
                    'data' => []
                ]);
            } else if (empty($manager)) {
                if ($request->current_task_stage == 1) {
                    Log::channel("statusChange")->info("save value :: $request->current_task_stage");
                    Log::channel("statusChange")->info('** end task move to production method **');
                    Log::channel("directtask")->info('** end the move to production method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Task move to operation successfully'),
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
            Log::channel("statusChange")->error($exception);
            Log::channel("statusChange")->info('** end the statusChange method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function orderItemStage($ordItemId)
    {
        $orderItem = OrderItems::where('order_items_id', $ordItemId)->whereIn('service_id', [1, 2, 3, 4, 6])->first();
        $taskStage = Taskstage::where('service_id', $orderItem->service_id)->where('status', 1)->first();
        $taskStageDetails = json_decode($taskStage->stage_details, true);
        if (!empty($taskStageDetails)) {

            foreach ($taskStageDetails as $stage) {
                $orderItemStage = new OrderItemStage();
                $orderItemStage->order_items_id = $ordItemId;
                $orderItemStage->stage = $stage['satge_no'];
                $orderItemStage->department_id = $stage['department_id'];
                $orderItemStage->is_customer_preview = $stage['is_customer_preview'];
                $orderItemStage->is_qc = $stage['is_qc'];
                $orderItemStage->save();
            }
        }
    }

    public function OrderItemsId($OrderItemsId)
    {

        $OrdItem = OrderItems::where('order_items_id', $OrderItemsId)->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('order_items.*', 'orders.order_code')->first();

        if (!empty($OrdItem)) {

            return $OrdItem;
        } else {

            $value = "";

            return $value;
        }
    }

    public function qcstatusChange_old(Request $request)
    {
        try {
            $type = $request->type;

            if ($type == "approved") {
                Log::channel("qcApprove")->info('** started the qc status approved method **');

                $taskManagerHistoryDetails = TaskManagerHistory::where('task_manager_history_id', $request->task_manager_history_id)->select('task_manager_history.*')->first();

                $taskManagerDetails = TaskManagerHistory::where('task_manager.order_items_id', '!=', '')
                    ->where('task_manager_history.task_manager_id', $taskManagerHistoryDetails->task_manager_id)
                    ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')
                    ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
                    ->leftjoin('taskstage', 'taskstage.service_id', '=', 'order_items.service_id')
                    ->where('task_manager_history.production_status', 1)
                    ->select('order_items.service_id', 'taskstage.stage_details')
                    ->first();

                $qcApprovedCount = TaskManagerHistory::where('task_manager_history.task_manager_id', $taskManagerHistoryDetails->task_manager_id)
                    ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')
                    ->where('task_manager_history.qc_status', 1)
                    ->select('order_items.service_id')
                    ->count();

                $stageCount = count($this->departmentGetAll(json_decode($taskManagerDetails->stage_details, true)));

                $taskmanagerhistory = TaskManagerHistory::find($request->task_manager_history_id);
                $taskmanagerhistory->qc_status = $request->qc_status;
                $taskmanagerhistory->approved_on = Server::getDateTime();
                $taskmanagerhistory->approved_by = JwtHelper::getSesUserId();
                $taskmanagerhistory->save();
                Log::channel("qcApprove")->info("task manager history Qc_status request value :: $taskmanagerhistory");


                // log activity
                // $desc = $assignDetails->employee_name . ' Employee' . ' assigned by ' . JwtHelper::getSesUserNameWithType() . '';
                // $activitytype = Config('activitytype.Task Manager');
                // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                $qctaskhistory = new QcTaskHistory();
                $qctaskhistory->task_manager_history_id = $request->task_manager_history_id;
                $qctaskhistory->status = 1;
                $qctaskhistory->approved_on = Server::getDateTime();
                $qctaskhistory->approved_by = JwtHelper::getSesUserId();
                $qctaskhistory->save();
                Log::channel("qcApprove")->info("qc task history status request value :: $qctaskhistory");

                if ($stageCount == $qcApprovedCount + 1) {
                    Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                    $taskDeliveryStatusUpdate = TaskManager::find($taskManagerHistoryDetails->task_manager_id);
                    $taskDeliveryStatusUpdate->current_task_stage = 4;
                    $taskDeliveryStatusUpdate->save();
                }

                // $taskOrderDetail = TaskManagerHistory::where('task_manager_history_id', $request->task_manager_history_id)->where('task_manager.order_items_id', '!=', '')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftjoin('department', 'department.department_id', '=', 'task_manager_history.department_id')->groupBy('orders.order_id')->select('orders.order_code', 'order_items.*','department.department_name', 'employee.employee_name')->first();
                // echo($taskOrderDetail);exit;

            }

            if ($type == "rejected") {
                Log::channel("qcReject")->info('** started the qc status rejected method **');
                $taskmanager = TaskManagerHistory::where('task_manager_history_id', $request->task_manager_history_id)->first();
                $taskmanagerUpdate = TaskManager::find($taskmanager->task_manager_id);
                $taskmanagerUpdate->current_task_stage = 2;
                $taskmanagerUpdate->updated_on = Server::getDateTime();
                $taskmanagerUpdate->updated_by = JwtHelper::getSesUserId();
                $taskmanagerUpdate->save();
                Log::channel("qcReject")->info("task manager current_task_stage change the value 2 production moved request value :: $taskmanagerUpdate");

                $taskmanagerhistory = TaskManagerHistory::find($request->task_manager_history_id);
                $taskmanagerhistory->qc_status = $request->qc_status;
                $taskmanagerhistory->rejected_reason = $request->rejected_reason;
                $taskmanagerhistory->rejected_on = Server::getDateTime();
                $taskmanagerhistory->rejected_by = JwtHelper::getSesUserId();
                $taskmanagerhistory->save();
                Log::channel("qcReject")->info("task manager history Qc_status request value :: $taskmanagerhistory");

                $qctaskhistory = new QcTaskHistory();
                $qctaskhistory->task_manager_history_id = $request->task_manager_history_id;
                $qctaskhistory->rejected_reason = $request->rejected_reason;
                $qctaskhistory->status = 2;
                $qctaskhistory->rejected_on = Server::getDateTime();
                $qctaskhistory->rejected_by = JwtHelper::getSesUserId();
                $qctaskhistory->save();
                Log::channel("qcReject")->info("qc task history status request value :: $qctaskhistory");
            }
            // $task = TaskManager::where('task_manager_id', $id)->first();

            // log activity
            // $implode = implode(",", $ids);
            // $desc =  ' Task '  . $task->task_name  . ' is' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
            // $activitytype = Config('activitytype.Task Manager');
            // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
            // Log::channel("directtask")->info("save value :: task_manager_id :: $id :: task deleted successfully");
            //     if(!empty($request->order_item_id) || !empty($request->task_manager_id)){

            //     Log::channel("directtask")->info('** end the qc move to production method **');
            //     return response()->json([
            //         'keyword' => 'success',
            //         'message' =>  __('Qc approved successfully'),
            //         'data' => []
            //     ]);
            // } 
            if ($request->qc_status == 1) {
                Log::channel("qcApprove")->info("save value :: $request->qc_status");
                Log::channel("qcApprove")->info('** end the qc status approved method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Qc approved successfully'),
                    'data' => []
                ]);
            } else if ($request->qc_status == 2) {
                Log::channel("qcReject")->info("save value :: $request->qc_status");
                Log::channel("qcReject")->info('** end the qc status rejected method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Qc rejected successfully'),
                    'data' => []
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.failed'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("qcApprove")->error($exception);
            Log::channel("qcApprove")->info('** end the qcApprove and rejected method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //assign
    public function assign(Request $request)
    {
        try {
            Log::channel("productiontaskassign")->info('** started the production task assign method **');
            $taskDuration = TaskDuration::select('*')->first();
            $assign = new TaskManagerHistory();

            $assign->task_manager_id = $request->task_manager_id;
            $assign->employee_type = $request->employee_type;
            $assign->employee_id = $request->employee_id;
            $assign->department_id = $request->department_id;
            $assign->orderitem_stage_id = $request->orderitem_stage_id;
            $assign->expected_on = $request->expected_on;
            $date = Carbon::createFromFormat('Y-m-d', $request->expected_on);
            $daysToAdd = $taskDuration->duration;
            $date = $date->addDays($daysToAdd);
            $assign->extra_expected_on = $date;
            $assign->stage = 2;
            $assign->assigned_on = Server::getDateTime();
            $assign->assigned_by = JwtHelper::getSesUserId();
            Log::channel("productiontaskassign")->info("task manager history request value :: $assign");


            if ($assign->save()) {

                $managerStageUpdate = TaskManager::find($request->task_manager_id);
                $managerStageUpdate->orderitem_stage_id = $request->orderitem_stage_id;
                $managerStageUpdate->department_id = $request->department_id;
                $managerStageUpdate->save();

                if (!empty($request->orderitem_stage_id)) {
                    $StageUpdate = OrderItemStage::find($request->orderitem_stage_id);
                    $StageUpdate->is_status_check = 1;
                    $StageUpdate->save();
                }

                $assignDetails = TaskManagerHistory::where('task_manager_history_id', $assign->task_manager_history_id)->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->select('task_manager_history.*', 'employee.employee_name')->first();

                //assignemployeetable
                $employeeTaskHistoryTable = new EmployeeTaskHistory();
                $employeeTaskHistoryTable->task_manager_history_id = $assignDetails->task_manager_history_id;
                $employeeTaskHistoryTable->employee_id = $assignDetails->employee_id;
                $employeeTaskHistoryTable->created_on = Server::getDateTime();
                $employeeTaskHistoryTable->save();
                Log::channel("productiontaskassign")->info("employee task history store request value :: $employeeTaskHistoryTable");

                $taskOrderDetail = TaskManager::where('task_manager.task_manager_id', $request->task_manager_id)->where('task_manager.order_items_id', '!=', '')->where('task_manager_history.production_status', 1)->leftJoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')
                    ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->select('orders.order_code', 'order_items.*', 'orderitem_stage.stage', 'task_manager.task_manager_id', 'task_manager_history.task_manager_history_id', 'task_manager.orderitem_stage_id')->groupBy('orders.order_id')->first();

                $taskDetail = TaskManager::where('task_manager.task_manager_id', $request->task_manager_id)->where('task_manager.order_items_id', '=', NULL)->where('task_manager_history.production_status', 1)->leftJoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->select('task_manager.*', 'task_manager_history.task_manager_history_id')->first();

                // log activity
                if (!empty($taskOrderDetail)) {
                    // $desc = 'This' . ' stage' . $taskOrderDetail->stage . ' - ' . $taskOrderDetail->order_code . ' - ' . $taskOrderDetail->product_name . ' ' . $assignDetails->employee_name . ' assigned by ' . JwtHelper::getSesUserNameWithType() . '';
                    $desc = $taskOrderDetail->order_code . ' - ' . $taskOrderDetail->product_name . ' ' . 'stage ' . $taskOrderDetail->stage . ' is assigned to ' . $assignDetails->employee_name . ' assigned by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Task Manager');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                }
                if (!empty($taskDetail)) {
                    $desc = 'This' . $taskDetail->task_code . ' - ' . $taskDetail->task_name . $assignDetails->employee_name . ' assigned by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Task Manager');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                }

                Log::channel("productiontaskassign")->info("save value :: $assignDetails");
                Log::channel("productiontaskassign")->info('** end the production task assign method **');

                // Send notification for task assigned employee
                $senderId = JwtHelper::getSesUserId();

                if ($request->orderitem_stage_id != '') {

                    $title = "Order task assigned by admin" . " - " . $taskOrderDetail->order_code;
                    $body = "Order $taskOrderDetail->order_code task assigned by admin";


                    $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);

                    $module = 'task_assign';
                    $page = 'task_assign';
                    $portal = 'employee';

                    $data = [
                        'order_id' => $taskOrderDetail->order_id,
                        'order_items_id' => $taskOrderDetail->order_items_id,
                        'order_code' => $taskOrderDetail->order_code,
                        'task_manager_id' => $taskOrderDetail->task_manager_id,
                        'task_manager_history_id' => $taskOrderDetail->task_manager_history_id,
                        'orderitem_stage_id' => $taskOrderDetail->orderitem_stage_id,
                        'random_id' => $random_id,
                        'page' => $page,
                        'url' => "employee/employee-task-manger/employee-task-detail?"
                    ];

                    $message = [
                        'title' => $title,
                        'body' => $body,
                        'page' => $page,
                        'data' => $data,
                        'portal' => $portal
                    ];

                    $employee_recipient = Employee::where('employee_id', $request->employee_id)->first();
                    $employee_key = $employee_recipient->fcm_token;
                    $receiver_id = $employee_recipient->employee_id;
                    if (!empty($employee_key)) {
                        $push = Firebase::sendSingle($employee_key, $message);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 3, $senderId, $receiver_id, $module, $page, $portal, $data, $random_id);
                } else {

                    $title = "Direct task assigned by admin" . " - " . $taskDetail->task_code;
                    $body = "Direct $taskDetail->task_code task assigned by admin";
                    // $body = "Direct task assigned by admin";

                    $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);

                    $module = 'task_assign';
                    $page = 'task_assign';
                    $portal = 'employee';

                    $data = [
                        'task_manager_id' => $taskDetail->task_manager_id,
                        'task_manager_history_id' => $taskDetail->task_manager_history_id,
                        'task_code' => $taskDetail->task_code,
                        'task_name' => $taskDetail->task_name,
                        'orderitem_stage_id' => $taskDetail->orderitem_stage_id,
                        'random_id' => $random_id,
                        'page' => $page,
                        'url' => "employee/employee-task-manger/employee-task-detail?"
                    ];

                    $message = [
                        'title' => $title,
                        'body' => $body,
                        'page' => $page,
                        'data' => $data,
                        'portal' => $portal
                    ];

                    $employee_recipient = Employee::where('employee_id', $request->employee_id)->first();
                    $employee_key = $employee_recipient->fcm_token;
                    $receiver_id = $employee_recipient->employee_id;
                    if (!empty($employee_key)) {
                        $push = Firebase::sendSingle($employee_key, $message);
                    }
                    $getdata = GlobalHelper::notification_create($title, $body, 3, $senderId, $receiver_id, $module, $page, $portal, $data, $random_id);
                }

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Task assigned successfully'),
                    'data'        => [$assignDetails]

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Task assigned failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("productiontaskassign")->error($exception);
            Log::channel("productiontaskassign")->error('** end the production task assign method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //revoke
    public function revoke_original(Request $request)
    {
        try {

            Log::channel("productiontaskrevoke")->info('** started the production task revoke method **');
            $task_manager_id = $request->task_manager_id;
            // $revoke = TaskManagerHistory::find($request->task_manager_history_id);
            // $revoke->production_status = 2;
            // $revoke->revoked_on = Server::getDateTime();
            // $revoke->revoked_by = JwtHelper::getSesUserId();

            // if ($revoke->save()) {
            // Log::channel("productiontaskrevoke")->info("task manager history revoke request value :: $revoke");

            if ($request->service_id == 3) {
                $taskManager =  TaskManager::where('task_manager_id', $task_manager_id)->first();

                //framelabel
                $updateFrameLabel = PhotoFrameLabelModel::where('order_items_id', $taskManager->order_items_id)->update(array(
                    'qc_image' => null,
                    'qc_on' => null,
                    'qc_by' => null,
                    'qc_reason' => null,
                    'qc_reason_on' => null,
                    'qc_status' => 0,
                    'preview_image' => null,
                    'preview_on' => null,
                    'preview_by' => null,
                    'preview_reason' => null,
                    'preview_reason_on' => null,
                    'preview_status' => 0
                ));

                //frameupload
                $updateFrameLabel = PhotoFrameUploadModel::where('order_items_id', $taskManager->order_items_id)->update(array(
                    'updated_on' => null,
                    'updated_by' => null,
                    'status' => 0,
                    'reject_reason' => null,
                    'rejected_on' => null,
                    'is_chat' => 0
                ));

                //framehistoryupload
                $uploadDetails = PhotoFrameUploadModel::where('order_items_id', $taskManager->order_items_id)->get();

                if (!empty($uploadDetails)) {
                    foreach ($uploadDetails as $his) {
                        $uploadHis[] = $his['order_photoframe_upload_id'];
                    }
                }

                if (!empty($uploadHis)) {
                    $updateFrameHistory = PhotoFrameUploadHistoryModel::whereIn('order_photoframe_upload_id', $uploadHis)->delete();
                }

                //frameqcuploadhistory and framepreviewuploadhistory
                $uploadLabelDetails = PhotoFrameLabelModel::where('order_items_id', $taskManager->order_items_id)->get();

                if (!empty($uploadLabelDetails)) {
                    foreach ($uploadLabelDetails as $his) {
                        $uploadlabel[] = $his['order_photoframe_upload_label_id'];
                    }
                }

                if (!empty($uploadlabel)) {
                    $updateFrameQcHistory = PhotoFrameQcHistory::whereIn('order_photoframe_upload_label_id', $uploadlabel)->delete();
                    $updateFramePreviewHistory = PhotoFramePreviewHistory::whereIn('order_photoframe_upload_label_id', $uploadlabel)->delete();
                }

                //Orderstage update
                $updateOrderItemStage = OrderItemStage::where('order_items_id', $taskManager->order_items_id)->update(array(
                    'completed_reason' => null,
                    'completed_on' => null,
                    'completed_by' => null,
                    'qc_description' => null,
                    'qc_on' => null,
                    'qc_by' => null,
                    'qc_reason' => null,
                    'qc_reason_on' => null,
                    'qc_reason_by' => null,
                    'qc_status' => 0,
                    'status' => 1
                ));

                //Taskmanager history 
                $taskManHistory = TaskManagerHistory::where('task_manager_id', $task_manager_id)->get();

                if (!empty($taskManHistory)) {
                    foreach ($taskManHistory as $his) {
                        $ary[] = $his['task_manager_history_id'];
                    }
                }
                if (!empty($ary)) {
                    $update = EmployeeTaskHistory::whereIn('task_manager_history_id', $ary)->delete();
                    $update = TaskManagerHistory::where('task_manager_id', $task_manager_id)->delete();
                }

                //TaskManager tabele
                $taskMan = TaskManager::find($task_manager_id);
                $taskMan->qc_image = null;
                $taskMan->qc_on = null;
                $taskMan->qc_by = null;
                $taskMan->qc_reason = null;
                $taskMan->qc_reason_on = null;
                $taskMan->qc_status = 0;
                $taskMan->preview_image = null;
                $taskMan->preview_on = null;
                $taskMan->preview_by = null;
                $taskMan->preview_reason = null;
                $taskMan->preview_reason_on = null;
                $taskMan->preview_status = 0;
                $taskMan->qc_message = null;
                $taskMan->current_task_stage = 2;
                $taskMan->save();
            }


            //personalized
            if ($request->service_id == 4) {
                $taskManager =  TaskManager::where('task_manager_id', $task_manager_id)->first();

                //personalized upload update
                $updateFrameLabel = PersonalizedUploadModel::where('order_items_id', $taskManager->order_items_id)->update(array(
                    'updated_on' => null,
                    'updated_by' => null,
                    'status' => 0,
                    'reject_reason' => null,
                    'rejected_on' => null,
                    'is_chat' => 0
                ));

                //personalizedhistoryupload
                $uploadDetails = PersonalizedUploadModel::where('order_items_id', $taskManager->order_items_id)->get();

                if (!empty($uploadDetails)) {
                    foreach ($uploadDetails as $his) {
                        $uploadHis[] = $his['order_personalized_upload_id'];
                    }
                }

                if (!empty($uploadHis)) {
                    $updatepersonalizedHistory = PersonalizedUploadHistoryModel::whereIn('order_personalized_upload_id', $uploadHis)->delete();
                }

                $updateQcHistory = TaskManagerQcHistory::where('task_manager_id', $taskManager->order_items_id)->delete();
                $updatePreviewHistory = TaskManagerPreviewHistory::where('task_manager_id', $taskManager->order_items_id)->delete();

                //Orderstage update
                $updateOrderItemStage = OrderItemStage::where('order_items_id', $taskManager->order_items_id)->update(array(
                    'completed_reason' => null,
                    'completed_on' => null,
                    'completed_by' => null,
                    'qc_description' => null,
                    'qc_on' => null,
                    'qc_by' => null,
                    'qc_reason' => null,
                    'qc_reason_on' => null,
                    'qc_reason_by' => null,
                    'qc_status' => 0,
                    'status' => 1
                ));

                //Taskmanager history 
                $taskManHistory = TaskManagerHistory::where('task_manager_id', $task_manager_id)->get();

                if (!empty($taskManHistory)) {
                    foreach ($taskManHistory as $his) {
                        $ary[] = $his['task_manager_history_id'];
                    }
                }
                if (!empty($ary)) {
                    $update = EmployeeTaskHistory::whereIn('task_manager_history_id', $ary)->delete();
                    $update = TaskManagerHistory::where('task_manager_id', $task_manager_id)->delete();
                }

                //TaskManager tabele
                $taskMan = TaskManager::find($task_manager_id);
                $taskMan->qc_image = null;
                $taskMan->qc_on = null;
                $taskMan->qc_by = null;
                $taskMan->qc_reason = null;
                $taskMan->qc_reason_on = null;
                $taskMan->qc_status = 0;
                $taskMan->preview_image = null;
                $taskMan->preview_on = null;
                $taskMan->preview_by = null;
                $taskMan->preview_reason = null;
                $taskMan->preview_reason_on = null;
                $taskMan->preview_status = 0;
                $taskMan->qc_message = null;
                $taskMan->current_task_stage = 2;
                $taskMan->save();
            }

            //Selfie
            if ($request->service_id == 6) {
                $taskManager =  TaskManager::where('task_manager_id', $task_manager_id)->first();

                //personalized upload update
                $updateFrameLabel = SelfieUploadModel::where('order_items_id', $taskManager->order_items_id)->update(array(
                    'updated_on' => null,
                    'updated_by' => null,
                    'status' => 0,
                    'reject_reason' => null,
                    'rejected_on' => null,
                    'qc_image' => null,
                    'qc_on' => null,
                    'qc_by' => null,
                    'qc_reason' => null,
                    'qc_reason_on' => null,
                    'qc_status' => 0,
                    'preview_image' => null,
                    'preview_on' => null,
                    'preview_by' => null,
                    'preview_reason' => null,
                    'preview_reason_on' => null,
                    'preview_status' => 0,
                    'is_chat' => 0
                ));

                //personalizedhistoryupload
                $uploadDetails = SelfieUploadModel::where('order_items_id', $taskManager->order_items_id)->get();

                if (!empty($uploadDetails)) {
                    foreach ($uploadDetails as $his) {
                        $uploadHis[] = $his['order_selfie_upload_id'];
                    }
                }

                if (!empty($uploadHis)) {
                    $selfieHistoryHistory = SelfieUploadHistoryModel::whereIn('order_selfie_upload_id', $uploadHis)->delete();
                    $selfieHistoryHistory = SelfieUploadQcModel::whereIn('order_selfie_upload_id', $uploadHis)->delete();
                    $selfieHistoryHistory = SelfieUploadPreviewHistoryModel::whereIn('order_selfie_upload_id', $uploadHis)->delete();
                }

                //Orderstage update
                $updateOrderItemStage = OrderItemStage::where('order_items_id', $taskManager->order_items_id)->update(array(
                    'completed_reason' => null,
                    'completed_on' => null,
                    'completed_by' => null,
                    'qc_description' => null,
                    'qc_on' => null,
                    'qc_by' => null,
                    'qc_reason' => null,
                    'qc_reason_on' => null,
                    'qc_reason_by' => null,
                    'qc_status' => 0,
                    'status' => 1
                ));

                //Taskmanager history 
                $taskManHistory = TaskManagerHistory::where('task_manager_id', $task_manager_id)->get();

                if (!empty($taskManHistory)) {
                    foreach ($taskManHistory as $his) {
                        $ary[] = $his['task_manager_history_id'];
                    }
                }
                if (!empty($ary)) {
                    $update = EmployeeTaskHistory::whereIn('task_manager_history_id', $ary)->delete();
                    $update = TaskManagerHistory::where('task_manager_id', $task_manager_id)->delete();
                }

                //TaskManager tabele
                $taskMan = TaskManager::find($task_manager_id);
                $taskMan->qc_image = null;
                $taskMan->qc_on = null;
                $taskMan->qc_by = null;
                $taskMan->qc_reason = null;
                $taskMan->qc_reason_on = null;
                $taskMan->qc_status = 0;
                $taskMan->preview_image = null;
                $taskMan->preview_on = null;
                $taskMan->preview_by = null;
                $taskMan->preview_reason = null;
                $taskMan->preview_reason_on = null;
                $taskMan->preview_status = 0;
                $taskMan->qc_message = null;
                $taskMan->current_task_stage = 2;
                $taskMan->save();
            }

            // $taskOrderDetail = TaskManagerHistory::where('task_manager_history_id', $revoke->task_manager_history_id)->where('task_manager.order_items_id', '!=', '')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->groupBy('orders.order_id')->select('orders.order_code', 'order_items.*', 'employee.employee_name')->first();

            // $taskDetail = TaskManagerHistory::where('task_manager_history_id', $request->task_manager_history_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->where('task_manager.order_items_id', '=', NULL)->select('task_manager.*')->first();

            // // log activity
            // if (!empty($taskOrderDetail)) {
            //     $desc = 'This Employee ' . $taskOrderDetail->employee_name . $taskOrderDetail->order_code . ' - ' . $taskOrderDetail->product_name . ' revoked by ' . JwtHelper::getSesUserNameWithType() . '';
            //     $activitytype = Config('activitytype.Task Manager');
            //     GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
            // }

            // if (!empty($taskDetail)) {
            //     $desc = 'This Employee ' . $taskDetail->task_code . ' - ' . $taskDetail->task_name . ' revoked by ' . JwtHelper::getSesUserNameWithType() . '';
            //     $activitytype = Config('activitytype.Task Manager');
            //     GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
            // }

            // Log::channel("productiontaskrevoke")->info("save value :: $taskOrderDetail");
            if (!empty($task_manager_id)) {
                Log::channel("productiontaskrevoke")->info('** end the production task revoke method **');
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Task revoked successfully'),
                    'data'        => []

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Task revoked failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("productiontaskrevoke")->error($exception);
            Log::channel("productiontaskrevoke")->error('** end the production task revoke method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //revoke
    public function revoke(Request $request)
    {
        try {

            Log::channel("productiontaskrevoke")->info('** started the production task revoke method **');
            $task_manager_id = $request->task_manager_id;
            $getEmployeeid = TaskManagerHistory::where('task_manager_id',$request->task_manager_id)->where('production_status',1)->first();
            $this->sendRevokePushNotification($task_manager_id,$request->service_id,$getEmployeeid->employee_id);

            //PassportSize Photo
            if ($request->service_id == 1) {
                $taskManager =  TaskManager::where('task_manager_id', $task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('task_manager.orderitem_stage_id', 'orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc')->first();

                $orderItemStage = OrderItemStage::where('orderitem_stage_id', $taskManager->orderitem_stage_id)->update(array(
                    'status' => 1,
                    'qc_status' => 0
                ));

                //Taskmanager history 
                $taskManHistory = TaskManagerHistory::where('orderitem_stage_id', $taskManager->orderitem_stage_id)->where('production_status', '!=', 2)->first();

                $taskManagerHistory = TaskManagerHistory::where('task_manager_history_id', $taskManHistory->task_manager_history_id)->update(array(
                    'revoked_on' => Server::getDateTime(),
                    'revoked_by' => JwtHelper::getSesUserId(),
                    'production_status' => 2
                ));
                $employeeTaskHistory = EmployeeTaskHistory::where('task_manager_history_id', $taskManHistory->task_manager_history_id)->update(array(
                    'employee_status' => 2
                ));

                //TaskManager tabele
                $taskMan = TaskManager::find($task_manager_id);
                $taskMan->current_task_stage = 2;
                $taskMan->save();
            }

            //Photo print
            if ($request->service_id == 2) {
                $taskManager =  TaskManager::where('task_manager_id', $task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('task_manager.orderitem_stage_id', 'orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc')->first();

                $orderItemStage = OrderItemStage::where('orderitem_stage_id', $taskManager->orderitem_stage_id)->update(array(
                    'status' => 1,
                    'qc_status' => 0
                ));

                //Taskmanager history 
                $taskManHistory = TaskManagerHistory::where('orderitem_stage_id', $taskManager->orderitem_stage_id)->where('production_status', '!=', 2)->first();

                $taskManagerHistory = TaskManagerHistory::where('task_manager_history_id', $taskManHistory->task_manager_history_id)->update(array(
                    'revoked_on' => Server::getDateTime(),
                    'revoked_by' => JwtHelper::getSesUserId(),
                    'production_status' => 2
                ));
                $employeeTaskHistory = EmployeeTaskHistory::where('task_manager_history_id', $taskManHistory->task_manager_history_id)->update(array(
                    'employee_status' => 2
                ));

                //TaskManager tabele
                $taskMan = TaskManager::find($task_manager_id);
                $taskMan->current_task_stage = 2;
                $taskMan->save();
            }

            //Photo Frame
            if ($request->service_id == 3) {
                $taskManager =  TaskManager::where('task_manager_id', $task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('task_manager.orderitem_stage_id', 'orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc')->first();

                $orderItemStage = OrderItemStage::where('orderitem_stage_id', $taskManager->orderitem_stage_id)->update(array(
                    'status' => 1,
                    'qc_status' => 0
                ));

                //Taskmanager history 
                $taskManHistory = TaskManagerHistory::where('orderitem_stage_id', $taskManager->orderitem_stage_id)->where('production_status', '!=', 2)->first();

                $taskManagerHistory = TaskManagerHistory::where('task_manager_history_id', $taskManHistory->task_manager_history_id)->update(array(
                    'revoked_on' => Server::getDateTime(),
                    'revoked_by' => JwtHelper::getSesUserId(),
                    'production_status' => 2
                ));
                $employeeTaskHistory = EmployeeTaskHistory::where('task_manager_history_id', $taskManHistory->task_manager_history_id)->update(array(
                    'employee_status' => 2
                ));

                //TaskManager tabele
                $taskMan = TaskManager::find($task_manager_id);
                $taskMan->current_task_stage = 2;
                $taskMan->save();
            }


            //personalized
            if ($request->service_id == 4) {
                $taskManager =  TaskManager::where('task_manager_id', $task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('task_manager.orderitem_stage_id', 'orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc')->first();

                $orderItemStage = OrderItemStage::where('orderitem_stage_id', $taskManager->orderitem_stage_id)->update(array(
                    'status' => 1
                ));

                //Taskmanager history 
                $taskManHistory = TaskManagerHistory::where('orderitem_stage_id', $taskManager->orderitem_stage_id)->where('production_status', '!=', 2)->first();

                $taskManagerHistory = TaskManagerHistory::where('task_manager_history_id', $taskManHistory->task_manager_history_id)->update(array(
                    'revoked_on' => Server::getDateTime(),
                    'revoked_by' => JwtHelper::getSesUserId(),
                    'production_status' => 2
                ));
                $employeeTaskHistory = EmployeeTaskHistory::where('task_manager_history_id', $taskManHistory->task_manager_history_id)->update(array(
                    'employee_status' => 2
                ));


                //TaskManager tabele
                $taskMan = TaskManager::find($task_manager_id);
                $taskMan->current_task_stage = 2;
                $taskMan->save();
            }

            //Selfie
            if ($request->service_id == 6) {
                $taskManager =  TaskManager::where('task_manager_id', $task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('task_manager.orderitem_stage_id', 'orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc')->first();

                $orderItemStage = OrderItemStage::where('orderitem_stage_id', $taskManager->orderitem_stage_id)->update(array(
                    'status' => 1
                ));

                //Taskmanager history 
                $taskManHistory = TaskManagerHistory::where('orderitem_stage_id', $taskManager->orderitem_stage_id)->where('production_status', '!=', 2)->first();

                $taskManagerHistory = TaskManagerHistory::where('task_manager_history_id', $taskManHistory->task_manager_history_id)->update(array(
                    'revoked_on' => Server::getDateTime(),
                    'revoked_by' => JwtHelper::getSesUserId(),
                    'production_status' => 2
                ));
                $employeeTaskHistory = EmployeeTaskHistory::where('task_manager_history_id', $taskManHistory->task_manager_history_id)->update(array(
                    'employee_status' => 2
                ));


                //TaskManager tabele
                $taskMan = TaskManager::find($task_manager_id);
                $taskMan->current_task_stage = 2;
                $taskMan->save();
            }

            // $taskOrderDetail = TaskManagerHistory::where('task_manager_history_id', $revoke->task_manager_history_id)->where('task_manager.order_items_id', '!=', '')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->groupBy('orders.order_id')->select('orders.order_code', 'order_items.*', 'employee.employee_name')->first();

            // $taskDetail = TaskManagerHistory::where('task_manager_history_id', $request->task_manager_history_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->where('task_manager.order_items_id', '=', NULL)->select('task_manager.*')->first();

            // // log activity
            // if (!empty($taskOrderDetail)) {
            //     $desc = 'This Employee ' . $taskOrderDetail->employee_name . $taskOrderDetail->order_code . ' - ' . $taskOrderDetail->product_name . ' revoked by ' . JwtHelper::getSesUserNameWithType() . '';
            //     $activitytype = Config('activitytype.Task Manager');
            //     GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
            // }

            // if (!empty($taskDetail)) {
            //     $desc = 'This Employee ' . $taskDetail->task_code . ' - ' . $taskDetail->task_name . ' revoked by ' . JwtHelper::getSesUserNameWithType() . '';
            //     $activitytype = Config('activitytype.Task Manager');
            //     GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
            // }

            // Log::channel("productiontaskrevoke")->info("save value :: $taskOrderDetail");
            if (!empty($task_manager_id)) {
                Log::channel("productiontaskrevoke")->info('** end the production task revoke method **');
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Task revoked successfully'),
                    'data'        => []

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Task revoked failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("productiontaskrevoke")->error($exception);
            Log::channel("productiontaskrevoke")->error('** end the production task revoke method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function sendRevokePushNotification($taskmanagerId,$serviceId,$employeeId)
    {

        $get_details = TaskManager::where('task_manager_id',$taskmanagerId)->leftJoin('order_items','task_manager.order_items_id','=','order_items.order_items_id')->leftJoin('orders','orders.order_id','=','order_items.order_id')->select('task_manager.*','orders.order_code')->first();
        
        $code = !empty($get_details->order_items_id) ? $get_details->order_code : $get_details->task_code;

        $title = "Task revoked"." - ".$code;
            $body = "Production task $code revoked";
            $module = 'Task Revoked';
            $page = 'task_revoked';
            $portal = 'employee';
            $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);

            $data = [
                'task_manager_id' => $taskmanagerId,
                'platform' => "employee",
                'code'=> $code,
                'employee_id'=>$employeeId,
                'service_id'=> $serviceId,
                'random_id' => $random_id,
                'page' => 'task_revoked',
                'url' => ''
            ];

            $message = [
                'title' => $title,
                'body' => $body,
                'page' => $page,
                'data' => $data,
                'portal' => $portal,
                'module' => $module
            ];
            
            $get_employee_token = Employee::where('employee_id',$employeeId)->where('fcm_token','!=',null)->first();

            if (!empty($get_employee_token)) {
                $push = Firebase::sendSingle($get_employee_token->fcm_token, $message);
            }
            $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $employeeId, $module, $page, $portal, $data, $random_id);
    }


    //custom task revoke
    public function custom_revoke(Request $request)
    {
        try {

            Log::channel("productiontaskrevoke")->info('** started the production task revoke method **');
            $task_manager_id = $request->task_manager_id;

            //===============
            // $updateQc = TaskManager::where('task_manager_id', $task_manager_id)->where('qc_image', '=', '')->update(array(
            //     'qc_image' => null,
            //     'qc_on' => null,
            //     'qc_by' => null,
            //     'qc_reason' => null,
            //     'qc_reason_on' => null,
            //     'qc_status' => 0,
            //     'qc_message' => null,
            //     'current_task_stage' => 2
            // ));

            // $updatePreview = TaskManager::where('task_manager_id', $task_manager_id)->where('preview_image', '=', '')->update(array(
            //     'preview_image' => null,
            //     'preview_on' => null,
            //     'preview_by' => null,
            //     'preview_reason' => null,
            //     'preview_reason_on' => null,
            //     'preview_status' => 0,
            //     'qc_message' => null,
            //     'current_task_stage' => 2
            // ));

            // $taskManager =  TaskManager::where('task_manager_id', $task_manager_id)->where('qc_image', '=', '')->first();

            // //customtaskhistory
            // if (!empty($taskManager)) {
            //     $uploadDetails = CustomTaskQcHistory::where('task_manager_id', $task_manager_id)->delete();
            // }
            //===========

            //Taskmanager history 
            $taskManHistory = TaskManagerHistory::where('task_manager_id', $task_manager_id)->get();

            if (!empty($taskManHistory)) {
                foreach ($taskManHistory as $his) {
                    $ary[] = $his['task_manager_history_id'];
                }
            }
            if (!empty($ary)) {
                // $update = EmployeeTaskHistory::whereIn('task_manager_history_id', $ary)->delete();
                // $update = TaskManagerHistory::where('task_manager_id', $task_manager_id)->delete();
                $taskManagerHistory = TaskManagerHistory::where('task_manager_id', $task_manager_id)->update(array(
                    'revoked_on' => Server::getDateTime(),
                    'revoked_by' => JwtHelper::getSesUserId(),
                    'production_status' => 2
                ));
                $employeeTaskHistory = EmployeeTaskHistory::where('task_manager_history_id', $ary)->update(array(
                    'employee_status' => 2
                ));
            }

            //TaskManager tabele
            // $taskMan = TaskManager::find($task_manager_id);
            // $taskMan->qc_image = null;
            // $taskMan->qc_on = null;
            // $taskMan->qc_by = null;
            // $taskMan->qc_reason = null;
            // $taskMan->qc_reason_on = null;
            // $taskMan->qc_status = 0;
            // $taskMan->preview_image = null;
            // $taskMan->preview_on = null;
            // $taskMan->preview_by = null;
            // $taskMan->preview_reason = null;
            // $taskMan->preview_reason_on = null;
            // $taskMan->preview_status = 0;
            // $taskMan->qc_message = null;
            // $taskMan->current_task_stage = 2;
            // $taskMan->save();

            // $taskOrderDetail = TaskManagerHistory::where('task_manager_history_id', $revoke->task_manager_history_id)->where('task_manager.order_items_id', '!=', '')->leftjoin('employee', 'employee.employee_id', '=', 'task_manager_history.employee_id')->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->groupBy('orders.order_id')->select('orders.order_code', 'order_items.*', 'employee.employee_name')->first();

            // $taskDetail = TaskManagerHistory::where('task_manager_history_id', $request->task_manager_history_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->where('task_manager.order_items_id', '=', NULL)->select('task_manager.*')->first();

            // // log activity
            // if (!empty($taskOrderDetail)) {
            //     $desc = 'This Employee ' . $taskOrderDetail->employee_name . $taskOrderDetail->order_code . ' - ' . $taskOrderDetail->product_name . ' revoked by ' . JwtHelper::getSesUserNameWithType() . '';
            //     $activitytype = Config('activitytype.Task Manager');
            //     GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
            // }

            // if (!empty($taskDetail)) {
            //     $desc = 'This Employee ' . $taskDetail->task_code . ' - ' . $taskDetail->task_name . ' revoked by ' . JwtHelper::getSesUserNameWithType() . '';
            //     $activitytype = Config('activitytype.Task Manager');
            //     GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
            // }

            // Log::channel("productiontaskrevoke")->info("save value :: $taskOrderDetail");
            if (!empty($task_manager_id)) {
                Log::channel("productiontaskrevoke")->info('** end the production task revoke method **');
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Task revoked successfully'),
                    'data'        => []

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Task revoked failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("productiontaskrevoke")->error($exception);
            Log::channel("productiontaskrevoke")->error('** end the production task revoke method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function serviceBasedDepartmentGetAll(Request $request, $id)
    {
        $get_department = OrderItemStage::where('order_items_id', $id)->where('status', 1)->first();

        $final = [];
        if (!empty($get_department)) {

            // foreach ($get_department as $data) {
            $ary = [];
            // $ary = $this->departmentGetAll(json_decode($data['stage_details'], true));
            $ary['orderitem_stage_id'] = $get_department['orderitem_stage_id'];
            $ary['department_id'] = $get_department['department_id'];
            $ary['department_name'] = $this->getDepartmentName($get_department['department_id']);
            $final[] = $ary;
            // }
        }

        if (!empty($final)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Department name listed successfully'),
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

    public function departmentGetAll($stageDetails)
    {

        $cusArray = [];
        $resultArray = [];

        if (!empty($stageDetails)) {

            foreach ($stageDetails as $cm) {
                $cusArray['stage_no'] = $cm['satge_no'];
                $cusArray['department_id'] = $cm['department_id'];
                $cusArray['department_name'] = $this->getDepartmentName($cm['department_id']);
                $resultArray[] = $cusArray;
            }
        }


        return $resultArray;
    }

    public function getDepartmentName($departmentId)
    {

        $dep = Department::where('department_id', $departmentId)->first();

        if (!empty($dep)) {

            return $dep->department_name;
        } else {

            $value = "";

            return $value;
        }
    }

    public function employeeGetAll(Request $request, $dep_id, $type)
    {
        $employeeDetails = Employee::where('status', 1)->where('department_id', $dep_id)->where('employee_type', $type)->get();

        $final = [];
        if (!empty($employeeDetails)) {

            foreach ($employeeDetails as $data) {
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
                    'message' => __('Employee name listed successfully'),
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

    //
    public function orderItemHistoryView($id)
    {
        try {
            Log::channel("employeeitemview")->info('** started the employeeitemview view method **');

            $orderItem = TaskManagerHistory::where('task_manager_history.task_manager_history_id', $id)->where('task_manager.order_items_id', '!=', '')->where('task_manager_history.production_status', 1)
                ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')
                ->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')
                ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')
                ->select('orders.order_id', 'orders.customer_id', 'orders.order_code', 'orders.order_date', 'order_items.order_items_id', 'order_items.pv_variant_attributes', 'order_items.service_id', 'order_items.thumbnail_image', 'order_items.product_name', 'order_items.product_code', 'order_items.quantity', 'order_items.background_color', 'order_items.image', 'order_items.images', 'order_items.photoprint_variant', 'order_items.frames', 'order_items.variant_attributes', 'order_items.variant_type_name', 'order_items.variant_label', 'order_items.designer_description', 'service.service_name', 'task_manager_history.*', 'task_manager.qc_status', 'task_manager.qc_image', 'task_manager.qc_reason', 'task_manager.qc_reason_on', 'task_manager.qc_on', 'task_manager.preview_status', 'task_manager.preview_image', 'task_manager.preview_reason', 'task_manager.preview_reason_on', 'task_manager.preview_on', 'task_manager.task_manager_id', 'task_manager.is_dispatch', 'orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc', 'orderitem_stage.qc_description', 'orderitem_stage.qc_on as only_qc_on', 'orderitem_stage.qc_reason as only_qc_reason', 'orderitem_stage.qc_reason_on as only_qc_reason_on', 'orderitem_stage.qc_status as only_qc_status', 'orderitem_stage.completed_reason as only_completed_reason', 'orderitem_stage.completed_on as only_completed_on')
                ->first();

            Log::channel("employeeitemview")->info("request value task_manager_id:: $id");



            $final = [];

            if (!empty($orderItem)) {
                $ary = [];
                $ary['order_id'] = $orderItem['order_id'];
                if (!empty($orderItem['customer_id'])) {
                    $ary['order_type'] = "Order";
                } else {
                    $ary['order_type'] = "Bulk Order";
                }
                $ary['order_items_id'] = $orderItem['order_items_id'];
                $ary['order_code'] = $orderItem['order_code'];
                $ary['order_date'] = $orderItem['order_date'];
                $ary['product_name'] = $orderItem['product_name'];
                $ary['product_code'] = $orderItem['product_code'];
                $ary['quantity'] = $orderItem['quantity'];
                $ary['background_color'] = $orderItem['background_color'];
                $ary['service_id'] = $orderItem['service_id'];
                $ary['thumbnail_image'] = $orderItem['thumbnail_image'];
                $ary['designer_description'] = $orderItem['designer_description'];
                if ($orderItem['service_id'] == 1) {
                    $ary['thumbnail_image_url'] = ($orderItem['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $orderItem['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                }
                if ($orderItem['service_id'] == 2) {
                    $ary['thumbnail_image_url'] = ($orderItem['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $orderItem['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                }
                if ($orderItem['service_id'] == 3) {
                    $ary['thumbnail_image_url'] = ($orderItem['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $orderItem['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                }
                if ($orderItem['service_id'] == 4) {
                    $ary['thumbnail_image_url'] = ($orderItem['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $orderItem['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                }
                if ($orderItem['service_id'] == 5) {
                    $ary['thumbnail_image_url'] = ($orderItem['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $orderItem['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                }
                if ($orderItem['service_id'] == 6) {
                    $ary['thumbnail_image_url'] = ($orderItem['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $orderItem['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                }
                $ary['image'] = $orderItem['image'];
                $ary['image_url'] = ($orderItem['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $orderItem['image'] : env('APP_URL') . "avatar.jpg";
                $ary['images'] = $this->getSelfieUpload($orderItem->order_items_id);
                $ary['selfieQc_images'] = $this->getSelfieQcUpload($orderItem->order_items_id);
                $ary['previewQc_images'] = $this->getSelfiePreviewUpload($orderItem->order_items_id);
                $ary['photoprint_variant'] = $this->getPhotoPrintUpload($orderItem->order_items_id);
                $ary['frames'] = $this->getPhotoFrameUpload($orderItem->order_items_id);
                $ary['variant_attributes'] = $this->getPersonalizedUpload($orderItem->order_items_id, json_decode($orderItem->variant_attributes, true));
                $ary['passportsize_variant'] = $this->getPassportSizeUpload($orderItem->order_items_id);
                $ary['variant_details'] = json_decode($orderItem->pv_variant_attributes, true);
                $ary['variant_type_name'] = $orderItem['variant_type_name'];
                $ary['variant_label'] = $orderItem['variant_label'];
                $ary['service_name'] = $orderItem['service_name'];
                $ary['assigned_on'] = $orderItem['assigned_on'];
                $ary['completed_on'] = $orderItem['completed_on'];
                $ary['expected_on'] = $orderItem['expected_on'];
                $ary['taken_on'] = $orderItem['taken_on'];
                $ary['work_stage'] = $orderItem['work_stage'];
                $ary['task_manager_id'] = $orderItem['task_manager_id'];
                $ary['qc_image'] = $orderItem['qc_image'];
                $ary['qc_image_url'] = ($orderItem['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $orderItem['qc_image'] : env('APP_URL') . "avatar.jpg";
                $ary['qc_on'] = $orderItem['qc_on'];
                $ary['qc_reason'] = $orderItem['qc_reason'];
                $ary['qc_reason_on'] = $orderItem['qc_reason_on'];
                $ary['qc_status'] = $orderItem['qc_status'];
                $ary['qc_image_history'] = $this->qcImageHistory($orderItem['task_manager_id']);

                $ary['preview_image'] = $orderItem['preview_image'];
                $ary['preview_image_url'] = ($orderItem['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $orderItem['preview_image'] : env('APP_URL') . "avatar.jpg";
                $ary['preview_on'] = $orderItem['preview_on'];
                $ary['preview_reason'] = $orderItem['preview_reason'];
                $ary['preview_reason_on'] = $orderItem['preview_reason_on'];
                $ary['preview_status'] = $orderItem['preview_status'];
                $ary['preview_image_history'] = $this->previewImageHistory($orderItem['task_manager_id']);
                $ary['is_customer_preview'] = $orderItem['is_customer_preview'];
                $ary['is_qc'] = $orderItem['is_qc'];
                $ary['completed_reason'] = $orderItem['only_completed_reason'];
                $ary['only_completed_on'] = $orderItem['only_completed_on'];
                $ary['qc_description'] = $orderItem['qc_description'];
                $ary['only_qc_on'] = $orderItem['only_qc_on'];
                $ary['only_qc_reason'] = $orderItem['only_qc_reason'];
                $ary['only_qc_reason_on'] = $orderItem['only_qc_reason_on'];
                $ary['only_qc_status'] = $orderItem['only_qc_status'];
                $ary['is_dispatch'] = $orderItem['is_dispatch'];
                if (!empty($orderItem->service_id)) {
                    $ary['task_stages'] = $this->orderItemtaskStageDetails($orderItem->order_items_id, $orderItem->task_manager_id);
                    $ary['no_of_stages_involve'] = count($this->orderItemtaskStageDetails($orderItem->order_items_id, $orderItem->task_manager_id));
                }
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("employeeitemview")->info("view value :: $log");
                Log::channel("employeeitemview")->info('** end the employeeitemview view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Order item viewed successfully'),
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
            Log::channel("employeeitemview")->error($exception);
            Log::channel("employeeitemview")->info('** end the employeeitemview view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function qcDetails($id)
    {
        try {
            Log::channel("employeeitemview")->info('** started the employeeitemview view method **');

            $orderItem = TaskManagerHistory::where('task_manager_history.task_manager_history_id', $id)->where('task_manager.order_items_id', '!=', '')->where('task_manager_history.production_status', 1)
                ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')
                ->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')
                ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
                ->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')
                ->select('service.service_id', 'task_manager.order_items_id')
                ->first();

            Log::channel("employeeitemview")->info("request value task_manager_id:: $id");



            $final = [];

            if (!empty($orderItem)) {

                if ($orderItem['service_id'] == 1) {
                    $final = $this->getPassportSizeAdminQcUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 2) {
                    $final = $this->getPhotoPrintAdminQcUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 3) {
                    $final = $this->getPhotoFrameAdminQcUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 4) {
                    $final = $this->getPersonalizedAdminQcUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 6) {
                    $final = $this->getSelfieAdminQcUpload($orderItem['order_items_id']);
                }
            }

            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("employeeitemview")->info("view value :: $log");
                Log::channel("employeeitemview")->info('** end the employeeitemview view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Qc listed successfully'),
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
            Log::channel("employeeitemview")->error($exception);
            Log::channel("employeeitemview")->info('** end the employeeitemview view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function previewDetails($id)
    {
        try {
            Log::channel("employeeitemview")->info('** started the employeeitemview view method **');

            $orderItem = TaskManagerHistory::where('task_manager_history.task_manager_history_id', $id)->where('task_manager.order_items_id', '!=', '')->where('task_manager_history.production_status', 1)
                ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')
                ->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')
                ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
                ->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')
                ->select('service.service_id', 'task_manager.order_items_id')
                ->first();

            Log::channel("employeeitemview")->info("request value task_manager_id:: $id");



            $final = [];

            if (!empty($orderItem)) {
                if ($orderItem['service_id'] == 1) {
                    $final = $this->getPassportSizePreviewUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 2) {
                    $final = $this->getPhotoPrintPreviewUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 3) {
                    $final = $this->getPhotoFramePreviewUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 4) {
                    $final = $this->getPersonalizedPreviewUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 6) {
                    $final = $this->getSelfiePreviewUpload($orderItem['order_items_id']);
                }
            }

            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("employeeitemview")->info("view value :: $log");
                Log::channel("employeeitemview")->info('** end the employeeitemview view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Preview listed successfully'),
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
            Log::channel("employeeitemview")->error($exception);
            Log::channel("employeeitemview")->info('** end the employeeitemview view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //myorderitem view
    public function myorderItem_view(Request $request, $ordId)
    {
        try {
            $orderView = OrderItems::where('order_items.order_items_id', $ordId)
                ->select('orders.order_code', 'orders.order_date', 'orders.customer_id', 'order_items.*', 'rating_review.review', 'rating_review.rating', 'task_manager.qc_status', 'task_manager.qc_image', 'task_manager.qc_reason', 'task_manager.qc_reason_on', 'task_manager.qc_on', 'task_manager.preview_status', 'task_manager.preview_image', 'task_manager.preview_reason', 'task_manager.preview_reason_on', 'task_manager.preview_on', 'task_manager.task_manager_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_items.order_items_id')
                ->leftJoin('rating_review', function ($leftJoin) use ($ordId) {
                    $leftJoin->on('rating_review.product_id', '=', 'order_items.product_id')
                        ->where('rating_review.order_id', $ordId);
                })->get();
            if (!empty($orderView)) {
                $orderAry = [];
                foreach ($orderView as $value) {
                    $ary = [];
                    $ary['order_id'] = $value->order_id;
                    $ary['customer_id'] = $value->customer_id;
                    if (!empty($value->customer_id)) {
                        $ary['order_type'] = "Order";
                    } else {
                        $ary['order_type'] = "Bulk Order";
                    }
                    $ary['order_items_id'] = $value->order_items_id;
                    $ary['service_id'] = $value->service_id;
                    $ary['order_date'] = $value->order_date;
                    $ary['order_code'] = $value->order_code;
                    $ary['product_id'] = $value->product_id;
                    $ary['product_name'] = $value->product_name;
                    $ary['product_code'] = $value->product_code;
                    $ary['designer_description'] = $value->designer_description;
                    $ary['image'] = $value['image'];
                    $ary['image_url'] = ($value['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['thumbnail_image'] = $value['thumbnail_image'];
                    if ($value->service_id == 1) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PASSPORTSIZEPHOTO_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value->service_id == 2) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOPRINT_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value->service_id == 3) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PHOTOFRAME_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value->service_id == 4) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('PERSONALIZED_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value->service_id == 5) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('ECOMMERCE_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    if ($value->service_id == 6) {
                        $ary['thumbnail_image_url'] = ($value['thumbnail_image'] != '') ? env('APP_URL') . env('SELFIEALBUM_URL') . $value['thumbnail_image'] : env('APP_URL') . "avatar.jpg";
                    }
                    $ary['background_color'] = $value->background_color;
                    // $ary['variant_attributes'] = $this->getPersonalizedVariant(json_decode($value->variant_attributes, true));
                    $ary['variant_attributes'] = $this->getPersonalizedUpload($value->order_items_id, json_decode($value->variant_attributes, true));
                    $ary['variant_details'] = json_decode($value->pv_variant_attributes, true);
                    // $ary['frames'] = $this->getFrames(json_decode($value->frames, true));
                    $ary['frames'] = $this->getPhotoFrameUpload($value->order_items_id);
                    // $ary['photoprint_variant'] = $this->getPhotoPrintVariant(json_decode($value->photoprint_variant, true));
                    $ary['photoprint_variant'] = $this->getPhotoPrintUpload($value->order_items_id);
                    $ary['passportsize_variant'] = $this->getPassportSizeUpload($value->order_items_id);
                    // $ary['images'] = $this->getProductImage(json_decode($value->images, true));
                    $ary['images'] = $this->getSelfieUpload($value->order_items_id);
                    $ary['quantity'] = $value->quantity;
                    $ary['sub_total'] = $value->sub_total;
                    $ary['order_status'] = $value->order_status;
                    $ary['photoprint_width'] = $value->photoprint_width;
                    $ary['photoprint_height'] = $value->photoprint_height;
                    $ary['first_copy_selling_price'] = $value->first_copy_selling_price;
                    $ary['additional_copy_selling_price'] = $value->additional_copy_selling_price;
                    $ary['variant_type_name'] = $value->variant_type_name;
                    $ary['variant_label'] = $value->variant_label;
                    $expectDeliveryDate = ExpectedDays::where('status', 1)->select('expected_delivery_days')->first();
                    $ary['expected_days'] = $expectDeliveryDate->expected_delivery_days;
                    $cancelItem = TaskManager::where('task_manager.order_items_id', $ordId)->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('orders.customer_id', $value->customer_id)->whereIn('task_manager.current_task_stage', [2, 3, 4])->first();
                    $ary['cancel_production'] = !empty($cancelItem) ? false : true;
                    $ary['task_manager_id'] = $value['task_manager_id'];
                    $ary['qc_image'] = $value['qc_image'];
                    $ary['qc_image_url'] = ($value['qc_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['qc_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['qc_on'] = $value['qc_on'];
                    $ary['qc_reason'] = $value['qc_reason'];
                    $ary['qc_reason_on'] = $value['qc_reason_on'];
                    $ary['qc_status'] = $value['qc_status'];
                    $ary['preview_image'] = $value['preview_image'];
                    $ary['preview_image_url'] = ($value['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['preview_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['preview_on'] = $value['preview_on'];
                    $ary['preview_reason'] = $value['preview_reason'];
                    $ary['preview_reason_on'] = $value['preview_reason_on'];
                    $ary['preview_status'] = $value['preview_status'];
                    $orderAry[] = $ary;
                }
            }

            if (!empty($orderAry)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('My order item viewed successfully'),
                    'data' => $orderAry
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No Data'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function downloadSingle_file(Request $request)
    {
        $attachment = $request->attachment;
        $module = $request->module;

        if (!empty($attachment)) {

            $fileName = $attachment;

            $myFile = base_path('public/public' . '/' . $module . '/') . $attachment;
            // echo("hi");exit;
            $headers = [
                'Content-Description: File Transfer',
                'Content-Type: application/octet-stream',
                'Content-Disposition: attachment; filename="' . basename($myFile) . '"',
                'Expires: 0',
                'Cache-Control: must-revalidate',
                'Pragma: public',
                'Content-Length: ' . filesize($myFile)
            ];

            $newName = $fileName;
            return response()->download($myFile, $newName, $headers);
        } else {
            return response()->json([
                'keyword'      => 'failed',
                'message'      => __('message.failed'),
                'data'        => [],

            ]);
        }
    }

    public function downloadZip(Request $request)
    {
        $zip = new ZipArchive;
        $folder = $request->folder;
        $fileName = $folder . '.zip';
        if ($zip->open(public_path($fileName), ZipArchive::CREATE) === TRUE) {
            $files = File::files(public_path('public/customtask/' . $folder));
            foreach ($files as $key => $value) {
                $relativeNameInZipFile = basename($value);
                $zip->addFile($value, $relativeNameInZipFile);
            }
            $zip->close();
        }
        return response()->download(public_path($fileName));
    }

    public function deliveryToDispatch(Request $request)
    {
        $id = $request->id;
        $order = OrderItems::where('order_items_id', $id)->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->select('order_items.*', 'orders.order_code')->first();
        $taskManagerDetails = TaskManager::where('order_items_id', $id)->first();
        $taskManager = TaskManager::find($taskManagerDetails->task_manager_id);
        $taskManager->is_dispatch = 1;
        $taskManager->updated_on = Server::getDateTime();
        $taskManager->updated_by = JwtHelper::getSesUserId();
        $taskManager->save();

        $desc = 'This ' . $order->order_code . ' - ' . $order->product_name . ' move to dispatch by ' . JwtHelper::getSesUserNameWithType() . '';
        $activitytype = Config('activitytype.Task Manager');
        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
        //Send notification for Customer
        $userId = JwtHelper::getSesUserId();
        $getOrderid = Orders::where('order_id', $order->order_id)->first();
        $title = "Order Ready for Dispatch" . " - " . $getOrderid->order_code;
        $body = "Your order $getOrderid->order_code is now ready to dispatch, we will process the order packaging soon.";
        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'order_ready_for_dispatch';
        $page = 'order_ready_for_dispatch';
        $url = "track-order?";
        $data = [
            'order_id' => $getOrderid->order_id,
            'order_code' => $getOrderid->order_code,
            'random_id' => $random_id,
            'page' => $page,
            'url' => $url,
        ];
        $data2 = [
            'order_id' => $getOrderid->order_id,
            'order_code' => $getOrderid->order_code,
            'random_id' => $random_id2,
            'url' => $url,
            'page' => $page
        ];
        $portal1 = 'mobile';
        $portal2 = 'website';
        $customer_recipient = Customer::where('customer_id', $getOrderid->customer_id)->first();
        if ($getOrderid->customer_id != "") {

            if ($customer_recipient->token != '') {
                $message = [
                    'title' => $title,
                    'body' => $body,
                    'page' => $page,
                    'data' => $data2,
                    'portal' => $portal2
                ];
                $customer_key = $customer_recipient->token;
                $receiver_id = $customer_recipient->customer_id;
                $push = Firebase::sendSingle($customer_key, $message);
                $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $receiver_id, $module, $page, $portal2, $data2, $random_id2);
            }
            if ($customer_recipient->mbl_token != '') {
                $message2 = [
                    'title' => $title,
                    'body' => $body,
                    'page' => $page,
                    'data' => $data,
                    'portal' => $portal1
                ];
                $customer_key = $customer_recipient->mbl_token;
                $receiver_id = $customer_recipient->customer_id;
                $push = Firebase::sendSingleMbl($customer_key, $message2);
                $getdata = GlobalHelper::notification_create($title, $body, 2, $userId, $receiver_id, $module, $page, $portal1, $data, $random_id);
            }
        }
        if ($order->is_cod == 1) {
            $update = OrderItems::where('order_items_id', $id)->update(array(
                'order_status' => 9,
                'cod_status' => 2,
                'updated_on' => Server::getDateTime(),
                'updated_by' => JwtHelper::getSesUserId()
            ));
            return response()->json([
                'keyword' => 'success',
                'message' => 'Order item move to dispatch successfully',
                'data' => []
            ]);
        }
        if ($order->is_cod == 2) {
            $update = OrderItems::where('order_items_id', $id)->update(array(
                'order_status' => 2,
                'updated_on' => Server::getDateTime(),
                'updated_by' => JwtHelper::getSesUserId()
            ));
            return response()->json([
                'keyword' => 'success',
                'message' => 'Order item move to dispatch successfully',
                'data' => []
            ]);
        }
        //$order = Orders::where('order_id', $id)->select('orders.*')->first();
        //     $mail_data = [];
        //     $mail_data['email'] = $order->billing_email;
        //     $mail_data['name'] = $dispatch->dispatch_courier_name;
        //     $mail_data['no'] = $dispatch->dispatch_courier_no;
        //     $mail_data['url'] = $dispatch->dispatch_courier_tracking_url;
        //     if ($order->billing_email != '') {
        //         event(new SendEmail($mail_data));
        //     }
        //     $msg = "DEAR $order->billing_customer_first_name$order->billing_customer_last_name , U R PRODUCT IS DISPATCHED VIA COURIER $dispatch->dispatch_courier_name, TRK ID $dispatch->dispatch_courier_no , COPY U R TRK ID PASTE BELOW LINK $dispatch->dispatch_courier_tracking_url AND TRK U R PRODUCT, FOR SUPPORT CALL - 04567220705, 11AM TO 6PM. THANKS FOR CHOOSING NR INFOTECH.";
        //         $isSmsSent = GlobalHelper::sendSms($order->billing_mobile_number, $msg);
        // }
        // }
        else {
            return response()
                ->json([
                    'keyword' => 'failed',
                    'message' => __('No Data Found'),
                    'data' => []
                ]);
        }
    }

    public function countSummaryTaskManager()
    {
        //Total Products

        $total_task = OrderItems::leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_items.order_items_id')->where('order_items.production_status', 0)->where('order_items.order_status', 10)->groupBy('order_items.order_id')->get();

        $total_task = $total_task->count();

        $direct_task = TaskManager::where('task_manager.current_task_stage', 1)->get();

        $direct_task = $direct_task->count();

        $direct_task = !empty($direct_task) ? $direct_task : 0;

        // if (!empty($total_task)) {
        return response()->json([
            'keyword' => 'success',
            'message' => __('Task manager count showed successfully'),
            'data' => [],
            'count' => $total_task + $direct_task
        ]);
        // } else {
        //     return response()
        //         ->json([
        //             'keyword' => 'failed',
        //             'message' => __('No Data Found'),
        //             'data' => []
        //         ]);
        // }
    }
}
