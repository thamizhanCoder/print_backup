<?php

namespace App\Http\Controllers\API\V1\EP;

use App\Events\CustomerPreviewApprovalEmployee;
use App\Events\CustomerPreviewEmployee;
use App\Events\CustomerPreviewRejectionEmployee;
use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Controllers\Controller;
use App\Http\Traits\OrderResponseTrait;
use App\Models\BulkOrderEnquiry;
use App\Models\Customer;
use App\Models\CustomTaskQcHistory;
use App\Models\EmployeeTaskHistory;
use App\Models\OrderItems;
use App\Models\OrderItemStage;
use App\Models\Orders;
use App\Models\PassportSizeUploadHistoryModel;
use App\Models\PassportSizeUploadModel;
use App\Models\PassportSizeUploadPreviewHistoryModel;
use App\Models\PersonalizedUploadHistoryModel;
use App\Models\PersonalizedUploadModel;
use App\Models\PersonalizedUploadPreviewHistoryModel;
use App\Models\PhotoFrameLabelModel;
use App\Models\PhotoFramePreviewHistory;
use App\Models\PhotoFrameQcHistory;
use App\Models\PhotoFrameUploadHistoryModel;
use App\Models\PhotoFrameUploadModel;
use App\Models\PhotoPrintUploadHistoryModel;
use App\Models\PhotoPrintUploadModel;
use App\Models\PhotoPrintUploadPreviewHistoryModel;
use App\Models\SelfieUploadHistoryModel;
use App\Models\SelfieUploadModel;
use App\Models\SelfieUploadPreviewHistoryModel;
use App\Models\SelfieUploadQcModel;
use App\Models\TaskDuration;
use App\Models\TaskManager;
use App\Models\TaskManagerHistory;
use App\Models\TaskManagerPreviewHistory;
use App\Models\TaskManagerQcHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Illuminate\Support\Facades\Mail;

class TaskManagerController extends Controller
{
    use OrderResponseTrait;
    //Todo list
    public function todoList(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $column_search = array('orders.order_code', 'order_items.product_code', 'order_items.product_name', 'task_manager.task_code', 'task_manager.task_name', 'task_manager.description');

        $emp_id = JwtHelper::getSesEmployeeId();

        // $uniqueOrd = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 1)->where('task_manager_history.production_status', 1)
        //     ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
        //     ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
        //     ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->groupBy('orders.order_id')->get();
        // $data1 = collect($uniqueOrd);

        // $uniqueTask = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 1)->where('task_manager_history.production_status', 1)
        //     ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
        //     ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
        //     ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '=', NULL)->select('task_manager.task_manager_id', 'task_manager.task_code', 'task_manager.created_on')->get();
        // $data2 = collect($uniqueTask);

        // $uniqueOrders = !empty($data2) ? $data1->merge($data2) : $data1;

        $orders = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 1)->where('task_manager_history.production_status', 1)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->select('order_items.order_id', 'order_items.product_id', 'order_items.product_variant_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'orders.order_code', 'orders.order_date', 'task_manager.order_items_id', 'task_manager.task_manager_id', 'task_manager.task_code', 'task_manager.task_type', 'task_manager.task_name', 'task_manager.description', 'task_manager.attachment_image', 'task_manager.current_task_stage', 'task_manager.created_on', 'task_manager.folder', 'task_manager_history.task_manager_history_id', 'task_manager_history.assigned_on', 'task_manager_history.expected_on', 'task_manager_history.taken_on', 'task_manager_history.completed_on', 'task_manager_history.work_stage', 'employee_task_history.employee_task_history_id')
            ->orderBy('employee_task_history.created_on', 'desc')
            ->groupby('task_manager.task_manager_id');

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
                $ary['employee_task_history_id'] = $value->employee_task_history_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['task_manager_id'] = $value->task_manager_id;
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
                $ary['assigned_on'] = $value->assigned_on;
                $ary['expected_on'] = $value->expected_on;
                $ary['taken_on'] = $value->taken_on;
                $ary['completed_on'] = $value->completed_on;
                $ary['work_stage'] = $value->work_stage;
            } else {
                $ary['group_id'] = "task|" . $value['task_code'] . "|" . $value['created_on'];
                $ary['employee_task_history_id'] = $value->employee_task_history_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['task_manager_id'] = $value->task_manager_id;
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
                $ary['assigned_on'] = $value->assigned_on;
                $ary['expected_on'] = $value->expected_on;
                $ary['taken_on'] = $value->taken_on;
                $ary['completed_on'] = $value->completed_on;
                $ary['work_stage'] = $value->work_stage;
            }
            $orderAry[] = $ary;
        }

        if (!empty($orderAry)) {
            $final = collect($orderAry)->groupBy('group_id')->all();
        }
        // $result = $uniqueOrders->map(function ($c) use ($orderAry) {
        //     $c['task_type'] = !empty($c['order_id']) ? "order" : "task";
        //     $c['list'] = $this->getOrderGroupBy($orderAry, $c['order_id']);

        //     return $c;
        // });
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Todo listed successfully'),
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No Data'),
                'data' => []
            ]);
        }
    }

    //Inprogress list
    public function inprogressList(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $column_search = array('orders.order_code', 'order_items.product_code', 'order_items.product_name', 'task_manager.task_code', 'task_manager.task_name', 'task_manager.description');

        $emp_id = JwtHelper::getSesEmployeeId();

        // $uniqueOrd = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 2)->where('task_manager_history.production_status', 1)
        //     ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
        //     ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
        //     ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->groupBy('orders.order_id')->get();
        // $data1 = collect($uniqueOrd);

        // $uniqueTask = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 2)->where('task_manager_history.production_status', 1)
        //     ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
        //     ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
        //     ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '=', NULL)->select('task_manager.task_manager_id', 'task_manager.task_code', 'task_manager.created_on')->get();
        // $data2 = collect($uniqueTask);

        // $uniqueOrders = !empty($data2) ? $data1->merge($data2) : $data1;

        $orders = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 2)->where('task_manager_history.production_status', 1)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->select('order_items.order_id', 'order_items.product_id', 'order_items.product_variant_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'orders.order_code', 'orders.order_date', 'task_manager.order_items_id', 'task_manager.task_manager_id', 'task_manager.task_code', 'task_manager.task_type', 'task_manager.task_name', 'task_manager.description', 'task_manager.attachment_image', 'task_manager.current_task_stage', 'task_manager.created_on', 'task_manager.folder', 'task_manager.qc_status as task_qc_status', 'task_manager_history.task_manager_history_id', 'task_manager_history.assigned_on', 'task_manager_history.expected_on', 'task_manager_history.taken_on', 'task_manager_history.completed_on', 'task_manager_history.work_stage', 'employee_task_history.employee_task_history_id', 'orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc', 'orderitem_stage.qc_status')
            ->orderBy('employee_task_history.updated_on', 'desc');

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
                $ary['employee_task_history_id'] = $value->employee_task_history_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['task_manager_id'] = $value->task_manager_id;
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
                $ary['assigned_on'] = $value->assigned_on;
                $ary['expected_on'] = $value->expected_on;
                $ary['taken_on'] = $value->taken_on;
                $ary['completed_on'] = $value->completed_on;
                $ary['work_stage'] = $value->work_stage;
                $ary['board_status'] = $value->qc_status;
                $ary['is_customer_preview'] = $value->is_customer_preview;
                $ary['is_qc'] = $value->is_qc;
            } else {
                $ary['group_id'] = "task|" . $value['task_code'] . "|" . $value['created_on'];
                $ary['employee_task_history_id'] = $value->employee_task_history_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['task_manager_id'] = $value->task_manager_id;
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
                $ary['assigned_on'] = $value->assigned_on;
                $ary['expected_on'] = $value->expected_on;
                $ary['taken_on'] = $value->taken_on;
                $ary['completed_on'] = $value->completed_on;
                $ary['work_stage'] = $value->work_stage;
                $ary['is_customer_preview'] = $value->is_customer_preview;
                $ary['is_qc'] = $value->is_qc;
                $ary['board_status'] = $value->task_qc_status;
            }
            $orderAry[] = $ary;
        }

        if (!empty($orderAry)) {
            $final = collect($orderAry)->groupBy('group_id')->all();
        }
        // $result = $uniqueOrders->map(function ($c) use ($orderAry) {
        //     $c['task_type'] = !empty($c['order_id']) ? "order" : "task";
        //     $c['list'] = $this->getOrderGroupBy($orderAry, $c['order_id']);

        //     return $c;
        // });
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Completed listed successfully'),
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No Data'),
                'data' => []
            ]);
        }
    }

    //Preview list
    public function previewList(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $column_search = array('orders.order_code', 'order_items.product_code', 'order_items.product_name', 'task_manager.task_code', 'task_manager.task_name', 'task_manager.description');

        $emp_id = JwtHelper::getSesEmployeeId();

        // $uniqueOrd = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 3)->where('task_manager_history.production_status', 1)
        //     ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
        //     ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
        //     ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->groupBy('orders.order_id')->get();
        // $data1 = collect($uniqueOrd);

        // $uniqueTask = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 3)->where('task_manager_history.production_status', 1)
        //     ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
        //     ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
        //     ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '=', NULL)->select('task_manager.task_manager_id', 'task_manager.task_code', 'task_manager.created_on')->get();
        // $data2 = collect($uniqueTask);

        // $uniqueOrders = !empty($data2) ? $data1->merge($data2) : $data1;

        $orders = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 3)->where('task_manager_history.production_status', 1)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->select('order_items.order_id', 'order_items.product_id', 'order_items.product_variant_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'orders.order_code', 'orders.order_date', 'task_manager.order_items_id', 'task_manager.task_manager_id', 'task_manager.task_code', 'task_manager.task_type', 'task_manager.task_name', 'task_manager.description', 'task_manager.attachment_image', 'task_manager.current_task_stage', 'task_manager.created_on', 'task_manager.folder', 'task_manager.qc_status as task_qc_status', 'task_manager.preview_on', 'task_manager_history.task_manager_history_id', 'task_manager_history.assigned_on', 'task_manager_history.expected_on', 'task_manager_history.taken_on', 'task_manager_history.completed_on', 'task_manager_history.work_stage', 'employee_task_history.employee_task_history_id', 'orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc', 'orderitem_stage.qc_status')
            ->orderBy('task_manager_history.qc_approved_on', 'desc');

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
                $ary['employee_task_history_id'] = $value->employee_task_history_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['task_manager_id'] = $value->task_manager_id;
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
                $ary['assigned_on'] = $value->assigned_on;
                $ary['expected_on'] = $value->expected_on;
                $ary['taken_on'] = $value->taken_on;
                $ary['completed_on'] = $value->completed_on;
                if ($value->service_id == 1) {
                    $label = PassportSizeUploadModel::where('order_items_id', $value->order_items_id)->where('qc_status', 1)->where('preview_status', 0)->first();
                    $ary['send_to_preview'] = !empty($label->preview_on) ? $label->preview_on : null;
                }
                if ($value->service_id == 2) {
                    $label = PhotoPrintUploadModel::where('order_items_id', $value->order_items_id)->where('qc_status', 1)->where('preview_status', 0)->first();
                    $ary['send_to_preview'] = !empty($label->preview_on) ? $label->preview_on : null;
                }
                if ($value->service_id == 4) {
                    $ary['send_to_preview'] = $value->preview_on;
                }
                if ($value->service_id == 3) {
                    $label = PhotoFrameLabelModel::where('order_items_id', $value->order_items_id)->where('qc_status', 1)->where('preview_status', 0)->first();
                    $ary['send_to_preview'] = !empty($label->preview_on) ? $label->preview_on : null;
                }
                if ($value->service_id == 6) {
                    $selfie = SelfieUploadModel::where('order_items_id', $value->order_items_id)->where('qc_status', 1)->where('preview_status', 0)->first();
                    $ary['send_to_preview'] = !empty($selfie->preview_on) ? $selfie->preview_on : null;
                }
                $ary['work_stage'] = $value->work_stage;
                $ary['board_status'] = $value->qc_status;
                $ary['is_customer_preview'] = $value->is_customer_preview;
                $ary['is_qc'] = $value->is_qc;
            } else {
                $ary['group_id'] = "task|" . $value['task_code'] . "|" . $value['created_on'];
                $ary['employee_task_history_id'] = $value->employee_task_history_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['task_manager_id'] = $value->task_manager_id;
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
                $ary['assigned_on'] = $value->assigned_on;
                $ary['expected_on'] = $value->expected_on;
                $ary['taken_on'] = $value->taken_on;
                $ary['completed_on'] = $value->completed_on;
                $ary['work_stage'] = $value->work_stage;
                $ary['board_status'] = $value->task_qc_status;
            }
            $orderAry[] = $ary;
        }

        if (!empty($orderAry)) {
            $final = collect($orderAry)->groupBy('group_id')->all();
        }
        // $result = $uniqueOrders->map(function ($c) use ($orderAry) {
        //     $c['task_type'] = !empty($c['order_id']) ? "order" : "task";
        //     $c['list'] = $this->getOrderGroupBy($orderAry, $c['order_id']);

        //     return $c;
        // });
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Completed listed successfully'),
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No Data'),
                'data' => []
            ]);
        }
    }

    //Completed list
    public function completedList(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $column_search = array('orders.order_code', 'order_items.product_code', 'order_items.product_name', 'task_manager.task_code', 'task_manager.task_name', 'task_manager.description');

        $emp_id = JwtHelper::getSesEmployeeId();

        // $uniqueOrd = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 4)->where('task_manager_history.production_status', 1)
        //     ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
        //     ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
        //     ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->groupBy('orders.order_id')->get();
        // $data1 = collect($uniqueOrd);

        // $uniqueTask = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 4)->where('task_manager_history.production_status', 1)
        //     ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
        //     ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
        //     ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '=', NULL)->select('task_manager.task_manager_id', 'task_manager.task_code', 'task_manager.created_on')->get();
        // $data2 = collect($uniqueTask);

        // $uniqueOrders = !empty($data2) ? $data1->merge($data2) : $data1;

        $orders = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('employee_task_history.status', 4)->where('task_manager_history.production_status', 1)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->select('order_items.order_id', 'order_items.product_id', 'order_items.product_variant_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'orders.order_code', 'orders.order_date', 'task_manager.order_items_id', 'task_manager.task_manager_id', 'task_manager.task_code', 'task_manager.task_type', 'task_manager.task_name', 'task_manager.description', 'task_manager.attachment_image', 'task_manager.current_task_stage', 'task_manager.created_on', 'task_manager.folder', 'task_manager_history.task_manager_history_id', 'task_manager_history.assigned_on', 'task_manager_history.expected_on', 'task_manager_history.taken_on', 'task_manager_history.completed_on', 'task_manager_history.work_stage', 'employee_task_history.employee_task_history_id')
            ->orderBy('task_manager_history.completed_on', 'desc');

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
                $ary['employee_task_history_id'] = $value->employee_task_history_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['task_manager_id'] = $value->task_manager_id;
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
                $ary['assigned_on'] = $value->assigned_on;
                $ary['expected_on'] = $value->expected_on;
                $ary['taken_on'] = $value->taken_on;
                $ary['completed_on'] = $value->completed_on;
                $ary['work_stage'] = $value->work_stage;
            } else {
                $ary['group_id'] = "task|" . $value['task_code'] . "|" . $value['created_on'];
                $ary['employee_task_history_id'] = $value->employee_task_history_id;
                $ary['task_manager_history_id'] = $value->task_manager_history_id;
                $ary['task_manager_id'] = $value->task_manager_id;
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
                $ary['assigned_on'] = $value->assigned_on;
                $ary['expected_on'] = $value->expected_on;
                $ary['taken_on'] = $value->taken_on;
                $ary['completed_on'] = $value->completed_on;
                $ary['work_stage'] = $value->work_stage;
            }
            $orderAry[] = $ary;
        }

        if (!empty($orderAry)) {
            $final = collect($orderAry)->groupBy('group_id')->all();
        }
        // $result = $uniqueOrders->map(function ($c) use ($orderAry) {
        //     $c['task_type'] = !empty($c['order_id']) ? "order" : "task";
        //     $c['list'] = $this->getOrderGroupBy($orderAry, $c['order_id']);

        //     return $c;
        // });
        if (!empty($final)) {
            return response()->json([
                'keyword' => 'success',
                'message' => __('Completed listed successfully'),
                'data' => $final,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No Data'),
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

    public function statusChange(Request $request)
    {
        try {

            if (!empty($request->employee_task_history_id)) {
                $employee_task_history_ids = json_decode($request->employee_task_history_id, true);

                Log::channel("employeestatusChange")->info('** started the employeestatusChange method **');


                if ($request->status == 1) {
                    Log::channel("employeestatusChange")->info('** started the move to todo method **');
                    foreach ($employee_task_history_ids as $employee_task_history_id) {
                        $empTaskHistory = EmployeeTaskHistory::find($employee_task_history_id['id']);
                        $empTaskHistory->status = $request->status;
                        $empTaskHistory->updated_on = Server::getDateTime();
                        $empTaskHistory->save();
                        Log::channel("employeestatusChange")->info("move to inprogress employee_task_history_id request value :: $empTaskHistory");

                        $empHistory = EmployeeTaskHistory::where('employee_task_history_id', $employee_task_history_id['id'])->first();

                        $updateTaskHistoryDate = TaskManagerHistory::find($empHistory->task_manager_history_id);
                        $updateTaskHistoryDate->taken_on = NULL;
                        $updateTaskHistoryDate->work_stage = $request->status;
                        $updateTaskHistoryDate->save();
                        Log::channel("employeestatusChange")->info("move to inprogress task_manager_history_id request value :: $updateTaskHistoryDate");
                    }
                }

                if ($request->status == 2) {
                    Log::channel("employeestatusChange")->info('** started the move to inprogress method **');
                    foreach ($employee_task_history_ids as $employee_task_history_id) {
                        $empTaskHistory = EmployeeTaskHistory::find($employee_task_history_id['id']);
                        $empTaskHistory->status = $request->status;
                        $empTaskHistory->updated_on = Server::getDateTime();
                        $empTaskHistory->save();
                        Log::channel("employeestatusChange")->info("move to inprogress employee_task_history_id request value :: $empTaskHistory");

                        $empHistory = EmployeeTaskHistory::where('employee_task_history_id', $employee_task_history_id['id'])->first();

                        $updateTaskHistoryDate = TaskManagerHistory::find($empHistory->task_manager_history_id);
                        $updateTaskHistoryDate->taken_on = Server::getDateTime();
                        $updateTaskHistoryDate->work_stage = $request->status;
                        $updateTaskHistoryDate->save();
                        Log::channel("employeestatusChange")->info("move to inprogress task_manager_history_id request value :: $updateTaskHistoryDate");
                    }
                }

                if ($request->status == 3) {
                    Log::channel("employeestatusChange")->info('** started the move to preview method **');
                    foreach ($employee_task_history_ids as $employee_task_history_id) {
                        $empTaskHistory = EmployeeTaskHistory::find($employee_task_history_id['id']);
                        $empTaskHistory->status = $request->status;
                        $empTaskHistory->updated_on = Server::getDateTime();
                        $empTaskHistory->save();
                        Log::channel("employeestatusChange")->info("move to inprogress employee_task_history_id request value :: $empTaskHistory");

                        $empHistory = EmployeeTaskHistory::where('employee_task_history_id', $employee_task_history_id['id'])->first();

                        $updateTaskHistoryDate = TaskManagerHistory::find($empHistory->task_manager_history_id);
                        $updateTaskHistoryDate->taken_on = Server::getDateTime();
                        $updateTaskHistoryDate->work_stage = $request->status;
                        $updateTaskHistoryDate->save();
                        Log::channel("employeestatusChange")->info("move to inprogress task_manager_history_id request value :: $updateTaskHistoryDate");
                    }
                }

                if ($request->status == 4) {
                    Log::channel("employeestatusChange")->info('** started the move to completed method **');
                    foreach ($employee_task_history_ids as $employee_task_history_id) {
                        $empTaskHistory = EmployeeTaskHistory::find($employee_task_history_id['id']);
                        $empTaskHistory->status = $request->status;
                        $empTaskHistory->updated_on = Server::getDateTime();
                        $empTaskHistory->save();
                        Log::channel("employeestatusChange")->info("move to inprogress employee_task_history_id request value :: $empTaskHistory");

                        $empHistory = EmployeeTaskHistory::where('employee_task_history_id', $employee_task_history_id['id'])->first();

                        $updateTaskHistoryDate = TaskManagerHistory::find($empHistory->task_manager_history_id);
                        $updateTaskHistoryDate->taken_on = Server::getDateTime();
                        $updateTaskHistoryDate->work_stage = $request->status;
                        $updateTaskHistoryDate->save();
                        Log::channel("employeestatusChange")->info("move to inprogress task_manager_history_id request value :: $updateTaskHistoryDate");
                    }
                }
            }
            if (!empty($request->employee_task_history_id)) {
                if ($request->status == 1) {
                    Log::channel("employeestatusChange")->info("save value :: $request->status");
                    Log::channel("employeestatusChange")->info('** end task move to todo method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Moved to todo successfully'),
                        'data' => []
                    ]);
                } else if ($request->status == 2) {
                    Log::channel("employeestatusChange")->info("save value :: $request->status");
                    Log::channel("employeestatusChange")->info('** end task move to inprogress method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Moved to inprogress successfully'),
                        'data' => []
                    ]);
                } else if ($request->status == 3) {
                    Log::channel("employeestatusChange")->info("save value :: $request->status");
                    Log::channel("employeestatusChange")->info('** end task move to preview method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Moved to preview successfully'),
                        'data' => []
                    ]);
                } else if ($request->status == 4) {
                    Log::channel("employeestatusChange")->info("save value :: $request->status");
                    Log::channel("employeestatusChange")->info('** end task move to completed method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Moved to completed successfully'),
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
            Log::channel("employeestatusChange")->error($exception);
            Log::channel("employeestatusChange")->info('** end the employeestatusChange method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function orderItemHistoryView($id)
    {
        try {
            Log::channel("employeeitemview")->info('** started the employeeitemview view method **');
            // $orderItem = TaskManager::where('task_manager.task_manager_id', $id)->where('task_manager.order_items_id', '!=', '')->where('task_manager_history.production_status', 1)
            //     ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            //     ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            //     ->leftjoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')
            //     ->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')
            //     ->select('orders.order_id', 'orders.order_code', 'orders.order_date', 'order_items.order_items_id', 'order_items.pv_variant_attributes', 'order_items.service_id', 'order_items.thumbnail_image', 'order_items.product_name', 'order_items.product_code', 'order_items.quantity', 'order_items.background_color', 'order_items.image', 'order_items.images', 'order_items.photoprint_variant', 'order_items.frames', 'order_items.variant_attributes', 'order_items.variant_type_name', 'order_items.variant_label', 'service.service_name', 'task_manager_history.*', 'task_manager.qc_status', 'task_manager.qc_image', 'task_manager.qc_reason', 'task_manager.qc_reason_on', 'task_manager.qc_on', 'task_manager.preview_status', 'task_manager.preview_image', 'task_manager.preview_reason', 'task_manager.preview_reason_on', 'task_manager.preview_on', 'task_manager.task_manager_id')
            //     ->first();

            $orderItem = TaskManagerHistory::where('task_manager_history.task_manager_history_id', $id)->where('task_manager.order_items_id', '!=', '')->where('task_manager_history.production_status', 1)
                ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')
                ->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')
                ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')
                ->select('orders.order_id', 'orders.customer_id', 'orders.order_code', 'orders.order_date', 'order_items.order_items_id', 'order_items.pv_variant_attributes', 'order_items.service_id', 'order_items.thumbnail_image', 'order_items.product_name', 'order_items.product_code', 'order_items.quantity', 'order_items.background_color', 'order_items.image', 'order_items.images', 'order_items.photoprint_variant', 'order_items.frames', 'order_items.variant_attributes', 'order_items.variant_type_name', 'order_items.variant_label', 'order_items.designer_description', 'order_items.is_customized', 'service.service_name', 'task_manager_history.*', 'task_manager.qc_status', 'task_manager.qc_image', 'task_manager.qc_reason', 'task_manager.qc_reason_on', 'task_manager.qc_on', 'task_manager.preview_status', 'task_manager.preview_image', 'task_manager.preview_reason', 'task_manager.preview_reason_on', 'task_manager.preview_on', 'task_manager.task_manager_id', 'orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc', 'orderitem_stage.qc_description', 'orderitem_stage.qc_on as only_qc_on', 'orderitem_stage.qc_reason as only_qc_reason', 'orderitem_stage.qc_reason_on as only_qc_reason_on', 'orderitem_stage.qc_status as only_qc_status', 'orderitem_stage.completed_reason as only_completed_reason', 'orderitem_stage.completed_on as only_completed_on')
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
                $ary['is_customized'] = $orderItem['is_customized'];
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
                $ary['variant_details'] = json_decode($orderItem->pv_variant_attributes, true);
                $ary['passportsize_variant'] = $this->getPassportSizeUpload($orderItem->order_items_id);
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
                $ary['only_completed_reason'] = $orderItem['only_completed_reason'];
                $ary['only_completed_on'] = $orderItem['only_completed_on'];
                $ary['qc_description'] = $orderItem['qc_description'];
                $ary['only_qc_on'] = $orderItem['only_qc_on'];
                $ary['only_qc_reason'] = $orderItem['only_qc_reason'];
                $ary['only_qc_reason_on'] = $orderItem['only_qc_reason_on'];
                $ary['only_qc_status'] = $orderItem['only_qc_status'];
                $ary['orderitem_stage_id'] = $orderItem['orderitem_stage_id'];
                $ary['isCheckChatHistory'] = !empty($orderItem['orderitem_stage_id']) ? "order" : "customtask";
                $seconds_ago = (strtotime($orderItem['completed_on']) - strtotime($orderItem['taken_on']));

                if ($seconds_ago >= 31536000) {
                    $date1 = intval($seconds_ago / 31536000);
                    $posted_date = ($date1 == 1) ? $date1 . " year" : $date1 . " years";
                } elseif ($seconds_ago >= 2419200) {
                    $date2 = intval($seconds_ago / 2419200);
                    $posted_date = ($date2 == 1) ? $date2 . " month" : $date2 . " months";
                } elseif ($seconds_ago >= 604800) {
                    $date3 = intval($seconds_ago / 604800);
                    $posted_date = ($date3 == 1) ? $date3 . " week" : $date3 . " weeks";
                } elseif ($seconds_ago >= 86400) {
                    $date4 = intval($seconds_ago / 86400);
                    $posted_date = ($date4 == 1) ? $date4 . " day" : $date4 . " days";
                } elseif ($seconds_ago >= 3600) {
                    $date5 = intval($seconds_ago / 3600);
                    $posted_date = ($date5 == 1) ? $date5 . " hour" : $date5 . " hours";
                } elseif ($seconds_ago >= 60) {
                    $date6 = intval($seconds_ago / 60);
                    $posted_date = ($date6 == 1) ? $date6 . " minute" : $date6 . " minutes";
                } else {
                    $posted_date = "Just now";
                }
                $ary['completed_time'] = $posted_date;
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
                $ary['attachment_image'] = $this->getdefaultImages_allImages($gTImage, $task_view->folder);
                $ary['current_task_stage'] = $task_view['current_task_stage'];
                $ary['created_on'] = $task_view['created_on'];
                $ary['created_by'] = $task_view['created_by'];
                $ary['updated_on'] = $task_view['updated_on'];
                $ary['updated_by'] = $task_view['updated_by'];
                $ary['status'] = $task_view['status'];
                $gTImage = json_decode($task_view['qc_image'], true);
                $ary['currentQc_attachment_image'] = $this->getdefaultImages_allImages($gTImage, $task_view->folder);
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
                $seconds_ago = (strtotime($task_view['completed_on']) - strtotime($task_view['taken_on']));

                if ($seconds_ago >= 31536000) {
                    $date1 = intval($seconds_ago / 31536000);
                    $posted_date = ($date1 == 1) ? $date1 . " year" : $date1 . " years";
                } elseif ($seconds_ago >= 2419200) {
                    $date2 = intval($seconds_ago / 2419200);
                    $posted_date = ($date2 == 1) ? $date2 . " month" : $date2 . " months";
                } elseif ($seconds_ago >= 604800) {
                    $date3 = intval($seconds_ago / 604800);
                    $posted_date = ($date3 == 1) ? $date3 . " week" : $date3 . " weeks";
                } elseif ($seconds_ago >= 86400) {
                    $date4 = intval($seconds_ago / 86400);
                    $posted_date = ($date4 == 1) ? $date4 . " day" : $date4 . " days";
                } elseif ($seconds_ago >= 3600) {
                    $date5 = intval($seconds_ago / 3600);
                    $posted_date = ($date5 == 1) ? $date5 . " hour" : $date5 . " hours";
                } elseif ($seconds_ago >= 60) {
                    $date6 = intval($seconds_ago / 60);
                    $posted_date = ($date6 == 1) ? $date6 . " minute" : $date6 . " minutes";
                } else {
                    $posted_date = "Just now";
                }
                $ary['completed_time'] = $posted_date;
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
                    $final = $this->getPassportPhotoQcUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 2) {
                    $final = $this->getPhotoPrintQcUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 3) {
                    $final = $this->getPhotoFrameQcUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 4) {
                    $final = $this->getPersonalizedQcUpload($orderItem['order_items_id']);
                }
                if ($orderItem['service_id'] == 6) {
                    $final = $this->getSelfieQcUpload($orderItem['order_items_id']);
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

    public function photoFrameimageVerificationList(Request $request, $ordId)
    {
        try {
            Log::channel("employeeOrderItemViewImagaeList")->info('** started the employeeOrderItemViewImagaeList list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';

            $photoframe = PhotoFrameUploadModel::where('order_photoframe_upload.order_items_id', $ordId)
                ->leftjoin('order_photoframe_upload_label', 'order_photoframe_upload_label.order_photoframe_upload_label_id', '=', 'order_photoframe_upload.order_photoframe_upload_label_id')->select('order_photoframe_upload.*', 'order_photoframe_upload_label.label_name');

            $count = count($photoframe->get());

            if ($offset) {
                $offset = $offset * $limit;
                $photoframe->offset($offset);
            }
            if ($limit) {
                $photoframe->limit($limit);
            }

            $photoframe->orderBy('order_photoframe_upload.order_photoframe_upload_id', 'asc');
            $photoframe = $photoframe->get();
            if ($count > 0) {
                $final = [];
                foreach ($photoframe as $value) {
                    $ary = [];
                    $ary['order_photoframe_upload_id'] = $value['order_photoframe_upload_id'];
                    $ary['order_items_id'] = $value['order_items_id'];
                    $ary['label_name'] = $value['label_name'];
                    $ary['image'] = $value['image'];
                    $ary['image_url'] = ($value['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['created_on'] = $value['created_on'];
                    // $ary['updated_on'] = $value['updated_on'];
                    $ary['status'] = $value['status'];
                    $ary['reject_reason'] = $value['reject_reason'];
                    $ary['rejected_on'] = $value['rejected_on'];
                    $ary['preview_image'] = $value['preview_image'];
                    $ary['preview_image_url'] = ($value['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $value['preview_image'] : env('APP_URL') . "avatar.jpg";
                    $ary['previewed_on'] = $value['previewed_on'];
                    // $ary['updated_previewed_on'] = $value['updated_previewed_on'];
                    $ary['preview_status'] = $value['preview_status'];
                    $ary['preview_reason'] = $value['preview_reason'];
                    $ary['preview_rejected_on'] = $value['preview_rejected_on'];
                    $ary['image_history'] = $this->photoframeImage($value['order_photoframe_upload_id']);
                    $ary['preview_image_history'] = $this->photoframePreviewImage($value['order_photoframe_upload_id']);
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("employeeOrderItemViewImagaeList")->info("list value :: $log");
                Log::channel("employeeOrderItemViewImagaeList")->info('** end the employeeOrderItemViewImagaeList list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Order item listed successfully'),
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
            Log::channel("employeeOrderItemViewImagaeList")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function photoframeImage($photoframeUploadId)
    {
        $photoframeImage = PhotoFrameUploadHistoryModel::where('order_photoframe_upload_id', $photoframeUploadId)->select('order_photoframe_upload_history.*')->get();

        $frameArray = [];
        $resultArray = [];

        if (!empty($photoframeImage)) {

            foreach ($photoframeImage as $pd) {

                $frameArray['order_photoframe_upload_history_id'] = $pd['order_photoframe_upload_history_id'];
                $frameArray['order_photoframe_upload_id'] = $pd['order_photoframe_upload_id'];
                $frameArray['image'] = $pd['image'];
                $frameArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $frameArray['created_on'] = $pd['created_on'];
                $frameArray['reject_reason'] = $pd['reject_reason'];
                $frameArray['rejected_on'] = $pd['rejected_on'];
                $frameArray['status'] = $pd['status'];
                $resultArray[] = $frameArray;
            }
        }


        return $resultArray;
    }

    public function photoframePreviewImage($photoframeUploadId)
    {
        $photoframePreviewImage = PhotoFramePreviewHistory::where('order_photoframe_upload_id', $photoframeUploadId)->select('order_photoframeupload_preview_history.*')->get();

        $frameArray = [];
        $resultArray = [];

        if (!empty($photoframePreviewImage)) {

            foreach ($photoframePreviewImage as $pd) {

                $frameArray['order_photoframeupload_preview_history_id'] = $pd['order_photoframeupload_preview_history_id'];
                $frameArray['order_photoframe_upload_id'] = $pd['order_photoframe_upload_id'];
                $frameArray['preview_image'] = $pd['preview_image'];
                $frameArray['preview_image_url'] = ($pd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $frameArray['previewed_on'] = $pd['previewed_on'];
                $frameArray['preview_reason'] = $pd['preview_reason'];
                $frameArray['preview_rejected_on'] = $pd['preview_rejected_on'];
                $frameArray['preview_status'] = $pd['preview_status'];
                $resultArray[] = $frameArray;
            }
        }


        return $resultArray;
    }

    public function personalizedUploadImage($personalizedUploadId)
    {
        $personalizedImage = PersonalizedUploadHistoryModel::where('order_personalized_upload_id', $personalizedUploadId)->select('order_personalized_upload_history.*')->get();

        $personalizedArray = [];
        $resultArray = [];

        if (!empty($personalizedImage)) {

            foreach ($personalizedImage as $pd) {

                $personalizedArray['order_personalized_upload_history_id'] = $pd['order_personalized_upload_history_id'];
                $personalizedArray['order_personalized_upload_id'] = $pd['order_personalized_upload_id'];
                $personalizedArray['reference_image'] = $pd['reference_image'];
                $personalizedArray['reference_image_url'] = ($pd['reference_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['reference_image'] : env('APP_URL') . "avatar.jpg";
                $personalizedArray['image'] = $pd['image'];
                $personalizedArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $personalizedArray['is_customized'] = $pd['is_customized'];
                $personalizedArray['created_on'] = $pd['created_on'];
                $personalizedArray['reject_reason'] = $pd['reject_reason'];
                $personalizedArray['rejected_on'] = $pd['rejected_on'];
                $personalizedArray['status'] = $pd['status'];
                $resultArray[] = $personalizedArray;
            }
        }


        return $resultArray;
    }

    public function personalizedUploadPreviewImage($personalizedUploadId)
    {
        $personalizedImage = PersonalizedUploadPreviewHistoryModel::where('order_personalized_upload_id', $personalizedUploadId)->select('order_personalizedupload_preview_history.*')->get();

        $personalizedArray = [];
        $resultArray = [];

        if (!empty($personalizedImage)) {

            foreach ($personalizedImage as $pd) {

                $personalizedArray['order_personalizedupload_preview_history_id'] = $pd['order_personalizedupload_preview_history_id'];
                $personalizedArray['order_personalized_upload_id'] = $pd['order_personalized_upload_id'];
                $personalizedArray['preview_reference_image'] = $pd['preview_reference_image'];
                $personalizedArray['preview_reference_image_url'] = ($pd['preview_reference_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_reference_image'] : env('APP_URL') . "avatar.jpg";
                $personalizedArray['preview_image'] = $pd['preview_image'];
                $personalizedArray['preview_image_url'] = ($pd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $personalizedArray['is_customized'] = $pd['is_customized'];
                $personalizedArray['previewed_on'] = $pd['previewed_on'];
                $personalizedArray['preview_reason'] = $pd['preview_reason'];
                $personalizedArray['preview_rejected_on'] = $pd['preview_rejected_on'];
                $personalizedArray['preview_status'] = $pd['preview_status'];
                $resultArray[] = $personalizedArray;
            }
        }


        return $resultArray;
    }

    public function selfieImage($photoframeUploadId)
    {
        $selfieImage = SelfieUploadHistoryModel::where('order_selfie_upload_id', $photoframeUploadId)->select('order_selfie_upload_history.*')->get();

        $selfieArray = [];
        $resultArray = [];

        if (!empty($selfieImage)) {

            foreach ($selfieImage as $pd) {

                $selfieArray['order_selfie_upload_history_id'] = $pd['order_selfie_upload_history_id'];
                $selfieArray['order_selfie_upload_id'] = $pd['order_selfie_upload_id'];
                $selfieArray['image'] = $pd['image'];
                $selfieArray['image_url'] = ($pd['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['image'] : env('APP_URL') . "avatar.jpg";
                $selfieArray['created_on'] = $pd['created_on'];
                $selfieArray['reject_reason'] = $pd['reject_reason'];
                $selfieArray['rejected_on'] = $pd['rejected_on'];
                $selfieArray['status'] = $pd['status'];
                $resultArray[] = $selfieArray;
            }
        }


        return $resultArray;
    }

    public function selfiePreviewImage($photoframeUploadId)
    {
        $photoframePreviewImage = SelfieUploadPreviewHistoryModel::where('order_selfie_upload_id', $photoframeUploadId)->select('order_selfieupload_preview_history.*')->get();

        $frameArray = [];
        $resultArray = [];

        if (!empty($photoframePreviewImage)) {

            foreach ($photoframePreviewImage as $pd) {

                $frameArray['order_selfieupload_preview_history_id'] = $pd['order_selfieupload_preview_history_id'];
                $frameArray['order_selfie_upload_id'] = $pd['order_selfie_upload_id'];
                $frameArray['preview_image'] = $pd['preview_image'];
                $frameArray['preview_image_url'] = ($pd['preview_image'] != '') ? env('APP_URL') . env('ORDER_URL') . $pd['preview_image'] : env('APP_URL') . "avatar.jpg";
                $frameArray['previewed_on'] = $pd['previewed_on'];
                $frameArray['preview_reason'] = $pd['preview_reason'];
                $frameArray['preview_rejected_on'] = $pd['preview_rejected_on'];
                $frameArray['preview_status'] = $pd['preview_status'];
                $resultArray[] = $frameArray;
            }
        }


        return $resultArray;
    }

    //attachedImageUpload
    public function previewAttachedImageUpload(Request $request)
    {
        try {
            //PassportPhoto
            if ($request->service_id == 1) {
                Log::channel("previewAttachedImageUpload")->info('** started the Passport previewAttachedImageUpload method **');

                $passportData = PassportSizeUploadModel::where('order_passport_upload_id', $request->id)->first();

                if (!empty($passportData->preview_image)) {
                    $passportHistory = new PassportSizeUploadPreviewHistoryModel();
                    $passportHistory->order_passport_upload_id = $passportData->order_passport_upload_id;
                    $passportHistory->preview_image = $passportData->preview_image;
                    $passportHistory->previewed_on = $passportData->previewed_on;
                    $passportHistory->previewed_by = $passportData->previewed_by;
                    $passportHistory->status = $passportData->status;
                    $passportHistory->save();
                }


                Log::channel("previewAttachedImageUpload")->info("request value PassportSizeUploadPreviewHistoryModel previewAttachedImageUpload_id:: $passportData");

                Log::channel("previewAttachedImageUpload")->info("Passport upload successfully");

                $upload = PassportSizeUploadModel::find($request->id);
                $upload->preview_image = $request->preview_image;
                $upload->previewed_on = Server::getDateTime();
                $upload->previewed_by = JwtHelper::getSesEmployeeId();
                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the Passport previewAttachedImageUpload method **');
            }

            //PhotoPrint
            if ($request->service_id == 2) {
                Log::channel("previewAttachedImageUpload")->info('** started the PhotoPrint previewAttachedImageUpload method **');

                $photoPrintData = PhotoPrintUploadModel::where('order_photoprint_upload_id', $request->id)->first();

                if (!empty($photoPrintData->preview_image)) {
                    $photoPrintHistory = new PhotoPrintUploadPreviewHistoryModel();
                    $photoPrintHistory->order_photoprint_upload_id = $photoPrintData->order_photoprint_upload_id;
                    $photoPrintHistory->preview_image = $photoPrintData->preview_image;
                    $photoPrintHistory->previewed_on = $photoPrintData->previewed_on;
                    $photoPrintHistory->previewed_by = $photoPrintData->previewed_by;
                    $photoPrintHistory->status = $photoPrintData->status;
                    $photoPrintHistory->save();
                }


                Log::channel("previewAttachedImageUpload")->info("request value PhotoPrintUploadPreviewHistoryModel previewAttachedImageUpload_id:: $photoPrintData");

                Log::channel("previewAttachedImageUpload")->info("PhotoPrint upload successfully");

                $upload = PhotoPrintUploadModel::find($request->id);
                $upload->preview_image = $request->preview_image;
                $upload->previewed_on = Server::getDateTime();
                $upload->previewed_by = JwtHelper::getSesEmployeeId();
                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the PhotoPrint previewAttachedImageUpload method **');
            }
            //Photo frame
            if ($request->service_id == 3) {

                Log::channel("previewAttachedImageUpload")->info('** started the photo frame previewAttachedImageUpload method **');

                $photoframeData = PhotoFrameUploadModel::where('order_photoframe_upload_id', $request->id)->first();

                if (!empty($photoframeData->preview_image)) {
                    $photoframeHistory = new PhotoFramePreviewHistory();
                    $photoframeHistory->order_photoframe_upload_id = $photoframeData->order_photoframe_upload_id;
                    $photoframeHistory->preview_image = $photoframeData->preview_image;
                    $photoframeHistory->previewed_on = $photoframeData->previewed_on;
                    $photoframeHistory->previewed_by = $photoframeData->previewed_by;
                    $photoframeHistory->preview_status = $photoframeData->preview_status;
                    $photoframeHistory->save();
                }

                Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $photoframeData");

                Log::channel("previewAttachedImageUpload")->info("Photo frame upload successfully");
                $upload = PhotoFrameUploadModel::find($request->id);

                $upload->preview_image = $request->preview_image;
                $upload->previewed_on = Server::getDateTime();
                $upload->previewed_by = JwtHelper::getSesEmployeeId();
                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the photo frame previewAttachedImageUpload method **');
            }

            //Personalized
            if ($request->service_id == 4) {
                Log::channel("previewAttachedImageUpload")->info('** started the Personalized previewAttachedImageUpload method **');

                $personalizedData = PersonalizedUploadModel::where('order_personalized_upload_id', $request->id)->first();

                if (!empty($personalizedData->preview_reference_image) || !empty($personalizedData->preview_image)) {
                    $personalizedHistory = new PersonalizedUploadPreviewHistoryModel();
                    $personalizedHistory->order_personalized_upload_id = $personalizedData->order_personalized_upload_id;
                    $personalizedHistory->preview_reference_image = $personalizedData->preview_reference_image;
                    $personalizedHistory->preview_image = $personalizedData->preview_image;
                    $personalizedHistory->previewed_on = $personalizedData->previewed_on;
                    $personalizedHistory->previewed_by = $personalizedData->previewed_by;
                    $personalizedHistory->preview_status = $personalizedData->preview_status;
                    $personalizedHistory->save();
                }

                Log::channel("previewAttachedImageUpload")->info("request value PersonalizedUploadHistoryModel previewAttachedImageUpload_id:: $personalizedData");

                Log::channel("previewAttachedImageUpload")->info("Personalized upload successfully");

                $upload = PersonalizedUploadModel::find($request->id);
                $upload->preview_reference_image = $request->preview_reference_image;
                $upload->preview_image = $request->preview_image;
                $upload->previewed_on = Server::getDateTime();
                $upload->previewed_by = JwtHelper::getSesEmployeeId();
                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the personalized previewAttachedImageUpload method **');
            }

            //Selfie
            if ($request->service_id == 6) {
                Log::channel("previewAttachedImageUpload")->info('** started the Selfie previewAttachedImageUpload method **');

                $selfieData = SelfieUploadModel::where('order_selfie_upload_id', $request->id)->first();

                if (!empty($selfieData->preview_image)) {
                    $selfieHistory = new SelfieUploadPreviewHistoryModel();
                    $selfieHistory->order_selfie_upload_id = $selfieData->order_selfie_upload_id;
                    $selfieHistory->preview_image = $selfieData->preview_image;
                    $selfieHistory->previewed_on = $selfieData->previewed_on;
                    $selfieHistory->previewed_by = $selfieData->previewed_by;
                    $selfieHistory->status = $selfieData->status;
                    $selfieHistory->save();
                }


                Log::channel("previewAttachedImageUpload")->info("request value SelfieUploadHistoryModel previewAttachedImageUpload_id:: $selfieData");

                Log::channel("previewAttachedImageUpload")->info("Selfie upload successfully");

                $upload = SelfieUploadModel::find($request->id);
                $upload->preview_image = $request->preview_image;
                $upload->previewed_on = Server::getDateTime();
                $upload->previewed_by = JwtHelper::getSesEmployeeId();
                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the personalized previewAttachedImageUpload method **');
            }

            if ($upload->save()) {
                Log::channel("previewAttachedImageUpload")->info("request value previewAttachedImageUpload_id:: $upload");
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Uploaded successfully'),
                    'data'        => []

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Uploaded failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("previewAttachedImageUpload")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function attachmentApprovedPushNotification($orderItemId)
    {
        $order = OrderItems::where('order_items_id', $orderItemId)->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'service.service_name', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'order_items.order_items_id')->first();


        // $emp_info = [
        //     'first_name' => $order->customer_first_name,
        //     'last_name' => $order->customer_last_name,
        //     'product_name' => $order->product_name
        // ];

        $title = "Attachment Approved" . " - " . $order->order_code;
        $body = "Your order $order->order_code & $order->product_code attachment has been approved. We’ll process the next stage.";
        // $body = GlobalHelper::mergeFields($body, $emp_info);
        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'Attachment Approved';
        $portal = "website";
        $portal2 = "mobile";
        $page = 'attachment_approved';
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

        if ($order->customer_id != '') {


            $token = Customer::where('customer_id', $order->customer_id)->select('token', 'mbl_token', 'customer_id')->get();
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
                                $getdata = GlobalHelper::notification_create($title, $body, 3, 3, $prod[$i][$j], $module, $page, "website", $data2, $random_id2);
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
                                $getdata = GlobalHelper::notification_create($title, $body, 3, 3, $prod[$i][$j], $module, $page, "mobile", $data, $random_id);
                            }
                        }
                    }
                }
            }
        }
    }

    public function attachmentRejectedPushNotification($orderItemId)
    {
        $order = OrderItems::where('order_items_id', $orderItemId)->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', 'customer.customer_id', '=', 'orders.customer_id')->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'service.service_name', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'order_items.product_name', 'order_items.service_id', 'order_items.order_items_id')->first();


        // $emp_info = [
        //     'first_name' => $order->customer_first_name,
        //     'last_name' => $order->customer_last_name,
        //     'product_name' => $order->product_name
        // ];

        $title = "Attachment Rejection" . " - " . $order->order_code;
        $body = "We’re sorry that Your attachment has been rejected from the order $order->order_code & $order->product_name. Please attach the alternate image.";
        // $body = GlobalHelper::mergeFields($body, $emp_info);
        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $random_id2 = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
        $module = 'Attachment Rejection';
        $portal = "website";
        $portal2 = "mobile";
        $page = 'attachment_rejection';
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

        if ($order->customer_id != '') {
            $token = Customer::where('customer_id', $order->customer_id)->select('token', 'mbl_token', 'customer_id')->get();
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
                                $getdata = GlobalHelper::notification_create($title, $body, 3, 3, $prod[$i][$j], $module, $page, "website", $data2, $random_id2);
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
                                $getdata = GlobalHelper::notification_create($title, $body, 3, 3, $prod[$i][$j], $module, $page, "mobile", $data, $random_id);
                            }
                        }
                    }
                }
            }
        }
    }

    public function approvedRejectedStatus(Request $request)
    {
        try {

            if (!empty($request)) {

                $type = $request->type;

                $id = $request->id;

                if (!empty($id)) {
                    if ($type == "approved") {
                        Log::channel("attachedImageApproved")->info('** started the attachedImageApproved method **');

                        if ($request->service_id == 1) {
                            Log::channel("attachedImageApproved")->info("request value PassportSizeUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Passport approved successfully");

                            $update = PassportSizeUploadModel::find($id);
                            $update->status = $request->status;
                            $update->updated_on = Server::getDateTime();
                            $update->updated_by = JwtHelper::getSesEmployeeId();
                            $update->save();
                            $details = PassportSizeUploadModel::where('order_passport_upload_id', $id)->first();
                            $pushNotification = $this->attachmentApprovedPushNotification($details->order_items_id);

                            //Bulk Order Flow Email
                            $concatImage = ($details->image != '') ? env('APP_URL') . env('ORDER_URL') . $details->image : env('APP_URL') . "avatar.jpg";
                            $getOrderId = OrderItems::where('order_items_id', $details->order_items_id)->first();
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

                                    Mail::send('mail.sendcustomerpreviewapprovalemployee', $mail_data, function ($message) use ($mail_data, $concatImage) {
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

                        if ($request->service_id == 2) {
                            Log::channel("attachedImageApproved")->info("request value PhotoPrintUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Photoprint approved successfully");

                            $update = PhotoPrintUploadModel::find($id);
                            $update->status = $request->status;
                            $update->updated_on = Server::getDateTime();
                            $update->updated_by = JwtHelper::getSesEmployeeId();
                            $update->save();
                            $details = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->first();
                            $pushNotification = $this->attachmentApprovedPushNotification($details->order_items_id);

                            //Bulk Order Flow Email
                            $concatImage = ($details->image != '') ? env('APP_URL') . env('ORDER_URL') . $details->image : env('APP_URL') . "avatar.jpg";
                            $getOrderId = OrderItems::where('order_items_id', $details->order_items_id)->first();
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

                                    Mail::send('mail.sendcustomerpreviewapprovalemployee', $mail_data, function ($message) use ($mail_data, $concatImage) {
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

                        if ($request->service_id == 3) {
                            Log::channel("attachedImageApproved")->info("request value PhotoFrameUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Photo frame approved successfully");
                            // $update = PhotoFrameUploadModel::where('order_photoframe_upload_id', $id)->update(array(
                            //     'status' => $request->status,
                            //     'updated_on' => Server::getDateTime(),
                            //     'updated_by' => JwtHelper::getSesEmployeeId()
                            // ));
                            $update = PhotoFrameUploadModel::find($id);
                            $update->status = $request->status;
                            $update->updated_on = Server::getDateTime();
                            $update->updated_by = JwtHelper::getSesEmployeeId();
                            $update->save();

                            $details = PhotoFrameUploadModel::where('order_photoframe_upload_id', $id)->first();
                            $pushNotification = $this->attachmentApprovedPushNotification($details->order_items_id);


                            //Email for Bulk Order
                            $concatImage = ($details->image != '') ? env('APP_URL') . env('ORDER_URL') . $details->image : env('APP_URL') . "avatar.jpg";
                            $getOrderId = OrderItems::where('order_items_id', $details->order_items_id)->first();
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

                                    Mail::send('mail.sendcustomerpreviewapprovalemployee', $mail_data, function ($message) use ($mail_data, $concatImage) {
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

                        if ($request->service_id == 4) {
                            Log::channel("attachedImageApproved")->info("request value PersonalizedUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Personalized approved successfully");
                            // $update = PersonalizedUploadModel::where('order_personalized_upload_id', $id)->update(array(
                            //     'status' => $request->status,
                            //     'updated_on' => Server::getDateTime(),
                            //     'updated_by' => JwtHelper::getSesEmployeeId()
                            // ));
                            $update = PersonalizedUploadModel::find($id);
                            $update->status = $request->status;
                            $update->updated_on = Server::getDateTime();
                            $update->updated_by = JwtHelper::getSesEmployeeId();
                            $update->save();
                            $details = PersonalizedUploadModel::where('order_personalized_upload_id', $id)->first();
                            $pushNotification = $this->attachmentApprovedPushNotification($details->order_items_id);


                            //Bulk Order Flow Email
                            $concatImage = ($details->image != '') ? env('APP_URL') . env('ORDER_URL') . $details->image : env('APP_URL') . "avatar.jpg";
                            $getOrderId = OrderItems::where('order_items_id', $details->order_items_id)->first();
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

                                    Mail::send('mail.sendcustomerpreviewapprovalemployee', $mail_data, function ($message) use ($mail_data, $concatImage) {
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

                        if ($request->service_id == 6) {
                            Log::channel("attachedImageApproved")->info("request value SelfieUploadModel attachedImageApproved_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageApproved")->info("Selfie approved successfully");
                            // $update = SelfieUploadModel::where('order_selfie_upload_id', $id)->update(array(
                            //     'status' => $request->status,
                            //     'updated_on' => Server::getDateTime(),
                            //     'updated_by' => JwtHelper::getSesEmployeeId()
                            // ));
                            $update = SelfieUploadModel::find($id);
                            $update->status = $request->status;
                            $update->updated_on = Server::getDateTime();
                            $update->updated_by = JwtHelper::getSesEmployeeId();
                            $update->save();
                            $details = SelfieUploadModel::where('order_selfie_upload_id', $id)->first();
                            $pushNotification = $this->attachmentApprovedPushNotification($details->order_items_id);

                            //Bulk Order Flow Email
                            $concatImage = ($details->image != '') ? env('APP_URL') . env('ORDER_URL') . $details->image : env('APP_URL') . "avatar.jpg";
                            $getOrderId = OrderItems::where('order_items_id', $details->order_items_id)->first();
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

                                    Mail::send('mail.sendcustomerpreviewapprovalemployee', $mail_data, function ($message) use ($mail_data, $concatImage) {
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
                        Log::channel("attachedImageApproved")->info('** end the attachedImageApproved method **');
                    }

                    if ($type == "rejected") {
                        Log::channel("attachedImageRejected")->info('** started the attachedImageRejected method **');

                        if ($request->service_id == 1) {
                            Log::channel("attachedImageRejected")->info("request value PassportSizeUploadModel attachedImageRejected_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageRejected")->info("Passport rejected successfully");

                            $update = PassportSizeUploadModel::find($id);
                            $update->status = $request->status;
                            $update->reject_reason = $request->reason;
                            $update->is_chat = $request->is_chat;
                            $update->rejected_on = Server::getDateTime();
                            $update->save();
                            $details = PassportSizeUploadModel::where('order_passport_upload_id', $id)->first();
                            $pushNotification = $this->attachmentRejectedPushNotification($details->order_items_id);

                            $order = OrderItems::where('order_items_id', $details->order_items_id)->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', function ($leftJoin) {
                                $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                                    ->where('orders.customer_id', '!=', NULL);
                            })
                                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                                        ->where('orders.customer_id', '=', NULL);
                                })->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'service.service_name', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no as bulk_order_mobile_no')->first();

                            if (!empty($order->customer_id)) {
                                $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                                $mobileNo = $order->mobile_no;
                            } else {
                                $customerName = $order->contact_person_name;
                                $mobileNo = $order->bulk_order_mobile_no;
                            }

                            $WEBSITE_URL = env('WEBSITE_URL');
                            // $msg =  "Dear $customerName,We are completed your order $order->service_name for getting image confirmation, and we are waiting for your reply.For more detail: $WEBSITE_URL Team Print App";
                            // $msg = "Dear $customerName,We’re sorry that, due to this reason $details->reject_reason Your attachment has been rejected from the order $details->order_code & product $details->product_code. Please attach the alternate image.For more detail: $WEBSITE_URL Team Print App";
                            $msg = "Dear #VAR1#,We’re sorry that, due to this reason #VAR2# Your   attachment has been rejected from the order #VAR3# & product #VAR4#. Please attach the alternate image.For more detail: #VAR5# Team Print App";
                            $isSmsSent = GlobalHelper::sendSMS($mobileNo, $msg);

                            //Bulk Order Flow Email
                            $concatImage = ($details->image != '') ? env('APP_URL') . env('ORDER_URL') . $details->image : env('APP_URL') . "avatar.jpg";
                            $getOrderId = OrderItems::where('order_items_id', $details->order_items_id)->first();
                            $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                            $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();

                            if (!empty($getBulkOrderEmail)) {
                                if ($getBulkOrderEmail->email != null) {
                                    $mail_data = [
                                        'email' => $getBulkOrderEmail->email,
                                        'image' => $concatImage,
                                        'order_id' => $getBulkOrderId->order_code,
                                        'customer_name' => $getBulkOrderEmail->contact_person_name,
                                        'reason' => $request->reason
                                    ];

                                    Mail::send('mail.sendcustomerpreviewrejectionemployee', $mail_data, function ($message) use ($mail_data, $concatImage) {
                                        $message->to($mail_data['email'])
                                            ->subject('Request for New Image')
                                            ->attach($concatImage, [
                                                'as' => 'preview_image.jpg',
                                                'mime' => 'image/jpeg'
                                            ]);
                                    });
                                }
                            }
                        }

                        if ($request->service_id == 2) {
                            Log::channel("attachedImageRejected")->info("request value PhotoPrintUploadModel attachedImageRejected_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageRejected")->info("Photoprint rejected successfully");

                            $update = PhotoPrintUploadModel::find($id);
                            $update->status = $request->status;
                            $update->reject_reason = $request->reason;
                            $update->is_chat = $request->is_chat;
                            $update->rejected_on = Server::getDateTime();
                            $update->save();
                            $details = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->first();
                            $pushNotification = $this->attachmentRejectedPushNotification($details->order_items_id);

                            $order = OrderItems::where('order_items_id', $details->order_items_id)->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', function ($leftJoin) {
                                $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                                    ->where('orders.customer_id', '!=', NULL);
                            })
                                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                                        ->where('orders.customer_id', '=', NULL);
                                })->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'service.service_name', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no as bulk_order_mobile_no')->first();

                            if (!empty($order->customer_id)) {
                                $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                                $mobileNo = $order->mobile_no;
                            } else {
                                $customerName = $order->contact_person_name;
                                $mobileNo = $order->bulk_order_mobile_no;
                            }

                            $WEBSITE_URL = env('WEBSITE_URL');
                            // $msg =  "Dear $customerName,We are completed your order $order->service_name for getting image confirmation, and we are waiting for your reply.For more detail: $WEBSITE_URL Team Print App";
                            // $msg = "Dear $customerName,We’re sorry that, due to this reason $details->reject_reason Your attachment has been rejected from the order $details->order_code & product $details->product_code. Please attach the alternate image.For more detail: $WEBSITE_URL Team Print App";
                            $msg = "Dear #VAR1#,We’re sorry that, due to this reason #VAR2# Your   attachment has been rejected from the order #VAR3# & product #VAR4#. Please attach the alternate image.For more detail: #VAR5# Team Print App";
                            $isSmsSent = GlobalHelper::sendSMS($mobileNo, $msg);

                            //Bulk Order Flow Email
                            $concatImage = ($details->image != '') ? env('APP_URL') . env('ORDER_URL') . $details->image : env('APP_URL') . "avatar.jpg";
                            $getOrderId = OrderItems::where('order_items_id', $details->order_items_id)->first();
                            $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                            $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();

                            if (!empty($getBulkOrderEmail)) {
                                if ($getBulkOrderEmail->email != null) {
                                    $mail_data = [
                                        'email' => $getBulkOrderEmail->email,
                                        'image' => $concatImage,
                                        'order_id' => $getBulkOrderId->order_code,
                                        'customer_name' => $getBulkOrderEmail->contact_person_name,
                                        'reason' => $request->reason
                                    ];

                                    Mail::send('mail.sendcustomerpreviewrejectionemployee', $mail_data, function ($message) use ($mail_data, $concatImage) {
                                        $message->to($mail_data['email'])
                                            ->subject('Request for New Image')
                                            ->attach($concatImage, [
                                                'as' => 'preview_image.jpg',
                                                'mime' => 'image/jpeg'
                                            ]);
                                    });
                                }
                            }
                        }

                        if ($request->service_id == 3) {
                            Log::channel("attachedImageRejected")->info("request value PhotoFrameUploadModel attachedImageRejected_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageRejected")->info("Photo frame rejected successfully");

                            // $update = PhotoFrameUploadModel::where('order_photoframe_upload_id', $id)->update(array(
                            //     'status' => $request->status,
                            //     'reject_reason' => $request->reason,
                            //     'is_chat' => $request->is_chat,
                            //     'rejected_on' => Server::getDateTime()
                            // ));
                            $update = PhotoFrameUploadModel::find($id);
                            $update->status = $request->status;
                            $update->reject_reason = $request->reason;
                            $update->is_chat = $request->is_chat;
                            $update->rejected_on = Server::getDateTime();
                            $update->save();
                            $details = PhotoFrameUploadModel::where('order_photoframe_upload_id', $id)->first();
                            $pushNotification = $this->attachmentRejectedPushNotification($details->order_items_id);

                            $order = OrderItems::where('order_items_id', $details->order_items_id)->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', function ($leftJoin) {
                                $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                                    ->where('orders.customer_id', '!=', NULL);
                            })
                                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                                        ->where('orders.customer_id', '=', NULL);
                                })->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'service.service_name', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no as bulk_order_mobile_no')->first();

                            if (!empty($order->customer_id)) {
                                $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                                $mobileNo = $order->mobile_no;
                            } else {
                                $customerName = $order->contact_person_name;
                                $mobileNo = $order->bulk_order_mobile_no;
                            }

                            $WEBSITE_URL = env('WEBSITE_URL');
                            // $msg =  "Dear $customerName,We are completed your order $order->service_name for getting image confirmation, and we are waiting for your reply.For more detail: $WEBSITE_URL Team Print App";
                            // $msg = "Dear $customerName,We’re sorry that, due to this reason $details->reject_reason Your attachment has been rejected from the order $details->order_code & product $details->product_code. Please attach the alternate image.For more detail: $WEBSITE_URL Team Print App";
                            $msg = "Dear #VAR1#,We’re sorry that, due to this reason #VAR2# Your   attachment has been rejected from the order #VAR3# & product #VAR4#. Please attach the alternate image.For more detail: #VAR5# Team Print App";
                            $isSmsSent = GlobalHelper::sendSMS($mobileNo, $msg);

                            //Bulk Order Flow Email
                            $concatImage = ($details->image != '') ? env('APP_URL') . env('ORDER_URL') . $details->image : env('APP_URL') . "avatar.jpg";
                            $getOrderId = OrderItems::where('order_items_id', $details->order_items_id)->first();
                            $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                            $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();

                            if (!empty($getBulkOrderEmail)) {
                                if ($getBulkOrderEmail->email != null) {
                                    $mail_data = [
                                        'email' => $getBulkOrderEmail->email,
                                        'image' => $concatImage,
                                        'order_id' => $getBulkOrderId->order_code,
                                        'customer_name' => $getBulkOrderEmail->contact_person_name,
                                        'reason' => $request->reason
                                    ];

                                    Mail::send('mail.sendcustomerpreviewrejectionemployee', $mail_data, function ($message) use ($mail_data, $concatImage) {
                                        $message->to($mail_data['email'])
                                            ->subject('Request for New Image')
                                            ->attach($concatImage, [
                                                'as' => 'preview_image.jpg',
                                                'mime' => 'image/jpeg'
                                            ]);
                                    });
                                }
                            }
                        }

                        if ($request->service_id == 4) {
                            Log::channel("attachedImageRejected")->info("request value PhotoFrameUploadModel attachedImageRejected_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageRejected")->info("Personalized rejected successfully");

                            // $update = PersonalizedUploadModel::where('order_personalized_upload_id', $id)->update(array(
                            //     'status' => $request->status,
                            //     'reject_reason' => $request->reason,
                            //     'is_chat' => $request->is_chat,
                            //     'rejected_on' => Server::getDateTime()
                            // ));
                            $update = PersonalizedUploadModel::find($id);
                            $update->status = $request->status;
                            $update->reject_reason = $request->reason;
                            $update->is_chat = $request->is_chat;
                            $update->rejected_on = Server::getDateTime();
                            $update->save();
                            $details = PersonalizedUploadModel::where('order_personalized_upload_id', $id)->first();
                            $pushNotification = $this->attachmentRejectedPushNotification($details->order_items_id);

                            $order = OrderItems::where('order_items_id', $details->order_items_id)->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', function ($leftJoin) {
                                $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                                    ->where('orders.customer_id', '!=', NULL);
                            })
                                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                                        ->where('orders.customer_id', '=', NULL);
                                })->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'service.service_name', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no as bulk_order_mobile_no')->first();

                            if (!empty($order->customer_id)) {
                                $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                                $mobileNo = $order->mobile_no;
                            } else {
                                $customerName = $order->contact_person_name;
                                $mobileNo = $order->bulk_order_mobile_no;
                            }

                            $WEBSITE_URL = env('WEBSITE_URL');
                            // $msg =  "Dear $customerName,We are completed your order $order->service_name for getting image confirmation, and we are waiting for your reply.For more detail: $WEBSITE_URL Team Print App";
                            // $msg = "Dear $customerName,We’re sorry that, due to this reason $details->reject_reason Your attachment has been rejected from the order $details->order_code & product $details->product_code. Please attach the alternate image.For more detail: $WEBSITE_URL Team Print App";
                            $msg = "Dear #VAR1#,We’re sorry that, due to this reason #VAR2# Your   attachment has been rejected from the order #VAR3# & product #VAR4#. Please attach the alternate image.For more detail: #VAR5# Team Print App";
                            $isSmsSent = GlobalHelper::sendSMS($mobileNo, $msg);

                            //Bulk Order Flow Email
                            $concatImage = ($details->image != '') ? env('APP_URL') . env('ORDER_URL') . $details->image : env('APP_URL') . "avatar.jpg";
                            $getOrderId = OrderItems::where('order_items_id', $details->order_items_id)->first();
                            $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                            $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();

                            if (!empty($getBulkOrderEmail)) {
                                if ($getBulkOrderEmail->email != null) {
                                    $mail_data = [
                                        'email' => $getBulkOrderEmail->email,
                                        'image' => $concatImage,
                                        'order_id' => $getBulkOrderId->order_code,
                                        'customer_name' => $getBulkOrderEmail->contact_person_name,
                                        'reason' => $request->reason
                                    ];

                                    Mail::send('mail.sendcustomerpreviewrejectionemployee', $mail_data, function ($message) use ($mail_data, $concatImage) {
                                        $message->to($mail_data['email'])
                                            ->subject('Request for New Image')
                                            ->attach($concatImage, [
                                                'as' => 'preview_image.jpg',
                                                'mime' => 'image/jpeg'
                                            ]);
                                    });
                                }
                            }
                        }

                        if ($request->service_id == 6) {
                            Log::channel("attachedImageRejected")->info("request value PhotoFrameUploadModel attachedImageRejected_id:: $id :: status :: $request->status");
                            Log::channel("attachedImageRejected")->info("Selfie rejected successfully");

                            // $update = SelfieUploadModel::where('order_selfie_upload_id', $id)->update(array(
                            //     'status' => $request->status,
                            //     'reject_reason' => $request->reason,
                            //     'is_chat' => $request->is_chat,
                            //     'rejected_on' => Server::getDateTime()
                            // ));
                            $update = SelfieUploadModel::find($id);
                            $update->status = $request->status;
                            $update->reject_reason = $request->reason;
                            $update->is_chat = $request->is_chat;
                            $update->rejected_on = Server::getDateTime();
                            $update->save();
                            $details = SelfieUploadModel::where('order_selfie_upload_id', $id)->first();
                            $pushNotification = $this->attachmentRejectedPushNotification($details->order_items_id);

                            $order = OrderItems::where('order_items_id', $details->order_items_id)->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->leftJoin('customer', function ($leftJoin) {
                                $leftJoin->on('customer.customer_id', '=', 'orders.customer_id')
                                    ->where('orders.customer_id', '!=', NULL);
                            })
                                ->leftJoin('bulk_order_enquiry', function ($leftJoin) {
                                    $leftJoin->on('bulk_order_enquiry.bulk_order_enquiry_id', '=', 'orders.bulk_order_enquiry_id')
                                        ->where('orders.customer_id', '=', NULL);
                                })->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')->select('customer.customer_first_name', 'customer.customer_last_name', 'customer.mobile_no', 'service.service_name', 'orders.order_id', 'orders.order_code', 'orders.customer_id', 'order_items.product_code', 'bulk_order_enquiry.contact_person_name', 'bulk_order_enquiry.mobile_no as bulk_order_mobile_no')->first();

                            if (!empty($order->customer_id)) {
                                $customerName = !empty($order->customer_last_name) ? $order->customer_first_name . ' ' . $order->customer_last_name : $order->customer_first_name;
                                $mobileNo = $order->mobile_no;
                            } else {
                                $customerName = $order->contact_person_name;
                                $mobileNo = $order->bulk_order_mobile_no;
                            }

                            $WEBSITE_URL = env('WEBSITE_URL');
                            // $msg =  "Dear $customerName,We are completed your order $order->service_name for getting image confirmation, and we are waiting for your reply.For more detail: $WEBSITE_URL Team Print App";
                            // $msg = "Dear $customerName,We’re sorry that, due to this reason $details->reject_reason Your attachment has been rejected from the order $details->order_code & product $details->product_code. Please attach the alternate image.For more detail: $WEBSITE_URL Team Print App";
                            $msg = "Dear #VAR1#,We’re sorry that, due to this reason #VAR2# Your   attachment has been rejected from the order #VAR3# & product #VAR4#. Please attach the alternate image.For more detail: #VAR5# Team Print App";
                            $isSmsSent = GlobalHelper::sendSMS($mobileNo, $msg);

                            //Bulk Order Flow Email
                            $concatImage = ($details->image != '') ? env('APP_URL') . env('ORDER_URL') . $details->image : env('APP_URL') . "avatar.jpg";
                            $getOrderId = OrderItems::where('order_items_id', $details->order_items_id)->first();
                            $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                            $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();

                            if (!empty($getBulkOrderEmail)) {
                                if ($getBulkOrderEmail->email != null) {
                                    $mail_data = [
                                        'email' => $getBulkOrderEmail->email,
                                        'image' => $concatImage,
                                        'order_id' => $getBulkOrderId->order_code,
                                        'customer_name' => $getBulkOrderEmail->contact_person_name,
                                        'reason' => $request->reason
                                    ];

                                    Mail::send('mail.sendcustomerpreviewrejectionemployee', $mail_data, function ($message) use ($mail_data, $concatImage) {
                                        $message->to($mail_data['email'])
                                            ->subject('Request for New Image')
                                            ->attach($concatImage, [
                                                'as' => 'preview_image.jpg',
                                                'mime' => 'image/jpeg'
                                            ]);
                                    });
                                }
                            }
                        }
                        Log::channel("attachedImageRejected")->info('** end the attachedImageRejected method **');
                    }

                    if ($request->status == 1) {
                        Log::channel("attachedimages")->info("save value :: attachedimages_id :: $id :: attachedimages inactive successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Accepted successfully'),
                            'data' => [$update]
                        ]);
                    } else if ($request->status == 2) {
                        Log::channel("attachedimages")->info("save value :: attachedimages_id :: $id :: attachedimages active successfully");
                        return response()->json([
                            'keyword' => 'success',
                            'message' => __('Rejected successfully'),
                            'data' => [$update]
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

    //attachedImageUpload
    public function moveToQc(Request $request)
    {
        try {
            //Passport
            if ($request->service_id == 1) {

                Log::channel("previewAttachedImageUpload")->info('** started the passport size previewAttachedImageUpload method **');

                Log::channel("previewAttachedImageUpload")->info("Passport Size upload successfully");
                $upload = PassportSizeUploadModel::find($request->id);

                $upload->qc_image = $request->qc_image;
                $upload->qc_status = 3;
                $upload->qc_on = Server::getDateTime();
                $upload->qc_by = JwtHelper::getSesEmployeeId();

                $updateStatus = PassportSizeUploadModel::where('order_passport_upload_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_passport_upload.order_items_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*', 'task_manager.task_manager_id')->first();
                $updateStageStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                $updateStageStatus->qc_status = 3;
                $updateStageStatus->qc_on = Server::getDateTime();
                $updateStageStatus->qc_by = JwtHelper::getSesEmployeeId();
                $updateStageStatus->save();

                $updatePreviewStatus = TaskManager::find($updateStatus->task_manager_id);
                $updatePreviewStatus->qc_on = Server::getDateTime();
                $updatePreviewStatus->qc_by = JwtHelper::getSesEmployeeId();
                $updatePreviewStatus->current_task_stage = 3;
                $updatePreviewStatus->save();


                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the passport Size previewAttachedImageUpload method **');
            }

            //PhotoPrint
            if ($request->service_id == 2) {

                Log::channel("previewAttachedImageUpload")->info('** started the photo print previewAttachedImageUpload method **');

                Log::channel("previewAttachedImageUpload")->info("Photo print upload successfully");
                $upload = PhotoPrintUploadModel::find($request->id);

                $upload->qc_image = $request->qc_image;
                $upload->qc_status = 3;
                $upload->qc_on = Server::getDateTime();
                $upload->qc_by = JwtHelper::getSesEmployeeId();

                $updateStatus = PhotoPrintUploadModel::where('order_photoprint_upload_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoprint_upload.order_items_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*', 'task_manager.task_manager_id')->first();
                $updateStageStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                $updateStageStatus->qc_status = 3;
                $updateStageStatus->qc_on = Server::getDateTime();
                $updateStageStatus->qc_by = JwtHelper::getSesEmployeeId();
                $updateStageStatus->save();

                $updatePreviewStatus = TaskManager::find($updateStatus->task_manager_id);
                $updatePreviewStatus->qc_on = Server::getDateTime();
                $updatePreviewStatus->qc_by = JwtHelper::getSesEmployeeId();
                $updatePreviewStatus->current_task_stage = 3;
                $updatePreviewStatus->save();


                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the photo print previewAttachedImageUpload method **');
            }
            //Photoframe
            if ($request->service_id == 3) {

                Log::channel("previewAttachedImageUpload")->info('** started the photo frame previewAttachedImageUpload method **');

                $photoframeQc = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $request->id)->first();

                // if (!empty($photoframeQc->qc_image)) {
                //     $qcHistory = new PhotoFrameQcHistory();
                //     $qcHistory->order_photoframe_upload_label_id = $request->id;
                //     $qcHistory->qc_image = $photoframeQc->qc_image;
                //     $qcHistory->qc_on = $photoframeQc->qc_on;
                //     $qcHistory->qc_by = $photoframeQc->qc_by;
                //     $qcHistory->qc_reason = $photoframeQc->qc_reason;
                //     $qcHistory->qc_reason_on = $photoframeQc->qc_reason_on;
                //     $qcHistory->qc_status = $photoframeQc->qc_status;
                //     $qcHistory->save();
                //     Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $qcHistory");
                // }

                Log::channel("previewAttachedImageUpload")->info("Photo frame upload successfully");
                $upload = PhotoFrameLabelModel::find($request->id);

                $upload->qc_image = $request->qc_image;
                $upload->qc_status = 3;
                $upload->qc_on = Server::getDateTime();
                $upload->qc_by = JwtHelper::getSesEmployeeId();

                $updateStatus = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*', 'task_manager.task_manager_id')->first();
                $updateStageStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                $updateStageStatus->qc_status = 3;
                $updateStageStatus->qc_on = Server::getDateTime();
                $updateStageStatus->qc_by = JwtHelper::getSesEmployeeId();
                $updateStageStatus->save();

                $updatePreviewStatus = TaskManager::find($updateStatus->task_manager_id);
                $updatePreviewStatus->qc_on = Server::getDateTime();
                $updatePreviewStatus->qc_by = JwtHelper::getSesEmployeeId();
                $updatePreviewStatus->current_task_stage = 3;
                $updatePreviewStatus->save();


                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the photo frame previewAttachedImageUpload method **');
            }

            //Personalized
            if ($request->service_id == 4) {

                Log::channel("previewAttachedImageUpload")->info('** started the photo frame previewAttachedImageUpload method **');

                $personalizedQc = TaskManager::where('task_manager_id', $request->id)->first();

                // if (!empty($personalizedQc->qc_image)) {
                //     $personalizedQc = new TaskManagerQcHistory();
                //     $personalizedQc->task_manager_id = $request->id;
                //     $personalizedQc->qc_image = $personalizedQc->qc_image;
                //     $personalizedQc->qc_on = $personalizedQc->qc_on;
                //     $personalizedQc->qc_by = $personalizedQc->qc_by;
                //     $personalizedQc->qc_reason = $personalizedQc->qc_reason;
                //     $personalizedQc->qc_reason_on = $personalizedQc->qc_reason_on;
                //     $personalizedQc->qc_status = $personalizedQc->qc_status;
                //     $personalizedQc->save();
                //     Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $personalizedQc");
                // }

                Log::channel("previewAttachedImageUpload")->info("Photo frame upload successfully");
                $upload = TaskManager::find($request->id);

                $upload->qc_image = $request->qc_image;
                $upload->qc_status = 3;
                $upload->current_task_stage = 3;
                $upload->qc_on = Server::getDateTime();
                $upload->qc_by = JwtHelper::getSesEmployeeId();

                $updateStatus = TaskManager::where('task_manager_id', $request->id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*')->first();
                $updateStageStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                $updateStageStatus->qc_status = 3;
                $updateStageStatus->qc_on = Server::getDateTime();
                $updateStageStatus->qc_by = JwtHelper::getSesEmployeeId();
                $updateStageStatus->save();
                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the photo frame previewAttachedImageUpload method **');
            }

            //Selfie
            if ($request->service_id == 6) {

                Log::channel("previewAttachedImageUpload")->info('** started the photo frame previewAttachedImageUpload method **');

                // $selfieQc = SelfieUploadModel::where('order_selfie_upload_id', $request->id)->first();

                // if (!empty($selfieQc->qc_image)) {
                //     $qcHistory = new SelfieUploadQcModel();
                //     $qcHistory->order_selfie_upload_id = $request->id;
                //     $qcHistory->qc_image = $selfieQc->qc_image;
                //     $qcHistory->qc_on = $selfieQc->qc_on;
                //     $qcHistory->qc_by = $selfieQc->qc_by;
                //     $qcHistory->qc_reason = $selfieQc->qc_reason;
                //     $qcHistory->qc_reason_on = $selfieQc->qc_reason_on;
                //     $qcHistory->qc_status = $selfieQc->qc_status;
                //     $qcHistory->save();
                //     Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $qcHistory");
                // }

                Log::channel("previewAttachedImageUpload")->info("Photo frame upload successfully");
                $upload = SelfieUploadModel::find($request->id);

                $upload->qc_image = $request->qc_image;
                $upload->qc_status = 3;
                $upload->qc_on = Server::getDateTime();
                $upload->qc_by = JwtHelper::getSesEmployeeId();

                $updateStatus = SelfieUploadModel::where('order_selfie_upload_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_selfie_upload.order_items_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*', 'task_manager.task_manager_id')->first();
                $updateStageStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                $updateStageStatus->qc_status = 3;
                $updateStageStatus->qc_on = Server::getDateTime();
                $updateStageStatus->qc_by = JwtHelper::getSesEmployeeId();
                $updateStageStatus->save();

                $updatePreviewStatus = TaskManager::find($updateStatus->task_manager_id);
                $updatePreviewStatus->qc_on = Server::getDateTime();
                $updatePreviewStatus->qc_by = JwtHelper::getSesEmployeeId();
                $updatePreviewStatus->current_task_stage = 3;
                $updatePreviewStatus->save();


                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the photo frame previewAttachedImageUpload method **');
            }

            if ($upload->save()) {
                Log::channel("previewAttachedImageUpload")->info("request value previewAttachedImageUpload_id:: $upload");
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Moved to QC successfully'),
                    'data'        => []

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Uploaded failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("previewAttachedImageUpload")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function moveToTaskQc(Request $request)
    {
        try {
            //Photoframe

            Log::channel("previewAttachedImageUpload")->info('** started the photo frame previewAttachedImageUpload method **');

            $taskQc = TaskManager::where('task_manager_id', $request->id)->first();

            // if (!empty($taskQc->qc_image)) {
            $qcHistory = new CustomTaskQcHistory();
            $qcHistory->task_manager_id = $request->id;
            $qcHistory->attachment_image = $request->attachment_image;
            $qcHistory->qc_message = $request->qc_message;
            $qcHistory->qc_on = Server::getDateTime();
            $qcHistory->qc_by = JwtHelper::getSesEmployeeId();
            $qcHistory->save();
            Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $qcHistory");
            // }

            Log::channel("previewAttachedImageUpload")->info("Photo frame upload successfully");
            $upload = TaskManager::find($request->id);
            $upload->qc_image = $request->attachment_image;
            $upload->qc_message = $request->qc_message;
            $upload->qc_status = 3;
            $upload->qc_on = Server::getDateTime();
            $upload->qc_by = JwtHelper::getSesEmployeeId();

            $updatePreviewStatus = TaskManager::find($request->id);
            $updatePreviewStatus->current_task_stage = 3;
            $updatePreviewStatus->save();

            $taskHistory = $this->customtaskQchistory($taskQc->task_manager_id);
            // $upload->save();
            Log::channel("previewAttachedImageUpload")->info('** end the photo frame previewAttachedImageUpload method **');

            if ($upload->save()) {
                Log::channel("previewAttachedImageUpload")->info("request value previewAttachedImageUpload_id:: $upload");
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Moved to task qc successfully'),
                    'data'        => $taskHistory

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Uploaded failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("previewAttachedImageUpload")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //Only Qc
    public function onlyQc(Request $request)
    {
        try {
            //Photoframe
            // if ($request->service_id == 3) {

            $taskManDetails = TaskManager::where('task_manager_id', $request->id)->first();

            $updateqcStatus = OrderItemStage::find($taskManDetails->orderitem_stage_id);
            $updateqcStatus->qc_description = $request->qc_description;
            $updateqcStatus->qc_status = 3;
            $updateqcStatus->qc_on = Server::getDateTime();
            $updateqcStatus->qc_by = JwtHelper::getSesEmployeeId();
            $updateqcStatus->save();

            $updatePreviewStatus = TaskManager::find($taskManDetails->task_manager_id);
            $updatePreviewStatus->qc_on = Server::getDateTime();
            $updatePreviewStatus->qc_by = JwtHelper::getSesEmployeeId();
            $updatePreviewStatus->current_task_stage = 3;
            $updatePreviewStatus->save();
            // }

            $qcResponse = OrderItemStage::where('orderitem_stage_id', $taskManDetails->orderitem_stage_id)->select('orderitem_stage.qc_description', 'orderitem_stage.qc_on as only_qc_on', 'orderitem_stage.qc_reason as only_qc_reason', 'orderitem_stage.qc_reason_on as only_qc_reason_on', 'orderitem_stage.qc_status as only_qc_status')->first();

            if (!empty($request->id)) {
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Moved to qc successfully'),
                    'data'        => [$qcResponse]

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Uploaded failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("previewAttachedImageUpload")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }



    //attachedImageUpload
    public function moveToPreview(Request $request)
    {
        try {
            //Photo frame
            if ($request->service_id == 4) {

                Log::channel("previewAttachedImageUpload")->info('** started the photo frame previewAttachedImageUpload method **');

                $personalizedPreview = TaskManager::where('task_manager_id', $request->id)->first();

                if (!empty($personalizedPreview->preview_image)) {
                    $previewHistory = new TaskManagerPreviewHistory();
                    $previewHistory->task_manager_id = $request->id;
                    $previewHistory->preview_image = $personalizedPreview->preview_image;
                    $previewHistory->preview_on = $personalizedPreview->preview_on;
                    $previewHistory->preview_by = $personalizedPreview->preview_by;
                    $previewHistory->preview_reason = $personalizedPreview->preview_reason;
                    $previewHistory->preview_reason_on = $personalizedPreview->preview_reason_on;
                    $previewHistory->preview_status = $personalizedPreview->preview_status;
                    $previewHistory->save();
                }

                Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $personalizedPreview");

                Log::channel("previewAttachedImageUpload")->info("Photo frame upload successfully");
                $upload = TaskManager::find($request->id);

                $upload->preview_image = $request->preview_image;
                $upload->preview_on = Server::getDateTime();
                $upload->preview_by = JwtHelper::getSesEmployeeId();

                // $updateStatus = TaskManager::where('task_manager_id', $request->id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*')->first();
                // $updateqcStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                // $updateqcStatus->qc_on = Server::getDateTime();
                // $updateqcStatus->qc_by = JwtHelper::getSesEmployeeId();
                // $updateqcStatus->save();
                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the photo frame previewAttachedImageUpload method **');
            }

            //Photoframe
            if ($request->service_id == 3) {

                Log::channel("previewAttachedImageUpload")->info('** started the photo frame previewAttachedImageUpload method **');

                $photoFramePreview = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $request->id)->first();

                if (!empty($photoFramePreview->preview_image)) {
                    $previewHistory = new PhotoFramePreviewHistory();
                    $previewHistory->order_photoframe_upload_label_id = $request->id;
                    $previewHistory->preview_image = $photoFramePreview->preview_image;
                    $previewHistory->preview_on = $photoFramePreview->preview_on;
                    $previewHistory->preview_by = $photoFramePreview->preview_by;
                    $previewHistory->preview_reason = $photoFramePreview->preview_reason;
                    $previewHistory->preview_reason_on = $photoFramePreview->preview_reason_on;
                    $previewHistory->preview_status = $photoFramePreview->preview_status;
                    $previewHistory->save();
                    Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $photoFramePreview");
                }

                Log::channel("previewAttachedImageUpload")->info("Photo frame upload successfully");
                $upload = PhotoFrameLabelModel::find($request->id);

                $upload->preview_image = $request->preview_image;
                $upload->preview_on = Server::getDateTime();
                $upload->preview_by = JwtHelper::getSesEmployeeId();

                // $updateStatus = TaskManager::where('task_manager_id', $request->id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->select('orderitem_stage.*')->first();
                // $updateqcStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                // $updateqcStatus->qc_on = Server::getDateTime();
                // $updateqcStatus->qc_by = JwtHelper::getSesEmployeeId();
                // $updateqcStatus->save();
                // $upload->save();
                Log::channel("previewAttachedImageUpload")->info('** end the photo frame previewAttachedImageUpload method **');
            }

            if ($upload->save()) {
                Log::channel("previewAttachedImageUpload")->info("request value previewAttachedImageUpload_id:: $upload");
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Moved to qc successfully'),
                    'data'        => []

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Uploaded failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("previewAttachedImageUpload")->error($exception);

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    //Qc Approved or Rejected
    public function moveToCompleted(Request $request)
    {
        // try {

        $id = $request->id;

        if (!empty($id)) {
            Log::channel("attachedImageApproved")->info('** started the attachedImageApproved method **');
            // if ($request->service_id == 4) {
            Log::channel("attachedImageApproved")->info("request value PhotoFrameUploadModel attachedImageApproved_id:: $id");
            Log::channel("attachedImageApproved")->info("Photo frame approved successfully");

            $taskManDetails = TaskManager::where('task_manager_id', $id)->first();

            $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id')->first();

            if ($updateStatus->is_customer_preview == 0 && $updateStatus->is_qc == 0) {
                $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                $orderItemStatus->completed_reason = $request->completed_reason;
                $orderItemStatus->completed_on = Server::getDateTime();
                $orderItemStatus->completed_by = JwtHelper::getSesEmployeeId();
                $orderItemStatus->status = 2;
                $orderItemStatus->save();

                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                $taskHisStatusUpdate->completed_by = JwtHelper::getSesEmployeeId();
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

            Log::channel("attachedImageApproved")->info('** end the attachedImageApproved method **');

            Log::channel("attachedImageRejected")->info('** end the attachedImageRejected method **');

            if (!empty($id)) {
                Log::channel("attachedimages")->info("save value :: attachedimages_id :: $id :: attachedimages inactive successfully");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Moved to completed successfully'),
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
        // } catch (\Exception $exception) {
        //     Log::channel("attachedimages")->error($exception);

        //     return response()->json([
        //         'error' => 'Internal server error.',
        //         'message' => $exception->getMessage()
        //     ], 500);
        // }
    }

    public function countSummaryTaskManager()
    {
        //Total Products
        $emp_id = JwtHelper::getSesEmployeeId();

        $total_task = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('task_manager_history.production_status', 1)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.order_items_id', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->count();

        $direct_task = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('task_manager_history.production_status', 1)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')
            ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')->where('task_manager.task_code', '!=', '')->select('orders.order_id', 'orders.order_code', 'orders.order_date')->groupBy('orders.order_id')->count();


        $todoCount = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('task_manager_history.production_status', 1)->where('employee_task_history.status', 1)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')->count();

        $inprogressCount = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('task_manager_history.production_status', 1)->where('employee_task_history.status', 2)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')->count();

        $previewCount = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('task_manager_history.production_status', 1)->where('employee_task_history.status', 3)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')->count();

        $completedCount = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('task_manager_history.production_status', 1)->where('employee_task_history.status', 4)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')->count();

        $empDetails = EmployeeTaskHistory::where('employee_id', $emp_id)->first();

        $today = date('Y-m-d');

        $overdueCount = EmployeeTaskHistory::where('employee_task_history.employee_id', $emp_id)->where('task_manager_history.production_status', 1)
            ->leftjoin('task_manager_history', 'task_manager_history.task_manager_history_id', '=', 'employee_task_history.task_manager_history_id')->where('task_manager_history.expected_on', '<', $today);
        $taskDuration = TaskDuration::select('*')->first();

        $filterByName = json_decode($taskDuration->revert_status, true);
        $overdueCount->whereIn('task_manager_history.work_stage', $filterByName);
        $overdueCount = $overdueCount->count();
        // $date = Carbon::createFromFormat('Y-m-d H:i:s', $cus->created_on);
        //             $daysToAdd = $freeAds->no_of_days;
        //             $date = $date->addDays($daysToAdd);
        //             if ($cus->total_free_ads_date == '') {
        //                 $cus->total_free_ads_date = $date;
        //             }

        $count = [
            'order_count' => $total_task,
            'directTask_count' => $direct_task,
            'total_task' => $total_task + $direct_task,
            'todo' => $todoCount,
            'inprogress' => $inprogressCount,
            'overdue' => $overdueCount,
            'preview' => $previewCount,
            'completed' => $completedCount
        ];
        return response()->json([
            'keyword' => 'success',
            'message' => __('Count showed successfully'),
            'data' => [$count],
        ]);
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


    public function previewApprovedRejectedStatus(Request $request)
    {
        // try {

        if (!empty($request)) {

            $type = $request->type;

            $id = $request->id;

            if (!empty($id)) {
                if ($type == "approved") {
                    Log::channel("previewImageApproved")->info('** started the previewImageApproved method **');

                    if ($request->service_id == 4) {
                        Log::channel("previewImageApproved")->info("request value PhotoFrameUploadModel previewImageApproved_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageApproved")->info("Photo frame approved successfully");
                        $update = TaskManager::where('task_manager_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_on' => Server::getDateTime(),
                            'preview_by' => JwtHelper::getSesEmployeeId()
                        ));

                        //Order item stage check update
                        // $updateStatus = TaskManager::where('task_manager.task_manager_id', $id)->leftjoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->where('task_manager_history.production_status', 1)->select('orderitem_stage.*', 'task_manager.qc_status', 'task_manager.preview_status', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $id)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc', 'orderitem_stage.orderitem_stage_id', 'task_manager.qc_status', 'task_manager.task_manager_id', 'task_manager.preview_status', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id')->first();

                        // echo($updateStatus);exit;
                        if ($updateStatus->is_customer_preview == 1 && $updateStatus->is_qc == 1) {
                            if ($updateStatus->qc_status == 1 && $updateStatus->preview_status == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesEmployeeId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesEmployeeId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();

                                $taskManStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                                $taskManStatusUpdate->updated_on = Server::getDateTime();
                                $taskManStatusUpdate->updated_by = JwtHelper::getSesEmployeeId();
                                $taskManStatusUpdate->current_task_stage = 2;
                                $taskManStatusUpdate->save();
                            }
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
                                            'customer_name' => $getBulkOrderEmail->contact_person_name
                                        ];

                                        Mail::send('mail.sendcustomerpreviewApprove', $mail_data, function ($message) use ($mail_data) {
                                            $message->to($mail_data['email'])
                                                ->subject('Reply to Image Approval');
                                        });
                                    }
                                }
                            }
                        }
                    }

                    //Photoframe
                    if ($request->service_id == 3) {
                        Log::channel("previewImageApproved")->info("request value PhotoFrameUploadModel previewImageApproved_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageApproved")->info("Photo frame approved successfully");
                        $update = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_on' => Server::getDateTime(),
                            'preview_by' => JwtHelper::getSesEmployeeId()
                        ));

                        // $updateStatus = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->leftjoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'order_photoframe_upload_label.qc_status', 'order_photoframe_upload_label.preview_status', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id')->first();

                        $labelDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->where('task_type', 2)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id', 'task_manager.task_manager_id')->first();

                        //total stage count
                        $totalStageCount = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->select('task_manager.task_manager_id', 'task_manager.order_items_id')->first();

                        //approved after check the label details
                        $labelItemQcCount = PhotoFrameLabelModel::where('order_items_id', $totalStageCount->order_items_id)->count();

                        Log::channel("previewImageApproved")->info("labelItemQcCount PhotoFrameUploadModel:: $labelItemQcCount");

                        $labelTotalQcCount = PhotoFrameLabelModel::where('order_items_id', $totalStageCount->order_items_id)->where('qc_status', 1)->count();

                        Log::channel("previewImageApproved")->info("labelTotalQcCount PhotoFrameUploadModel:: $labelTotalQcCount");

                        if ($labelItemQcCount != $labelTotalQcCount) {
                            $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskHisStatusUpdate->work_stage = 2;
                            $taskHisStatusUpdate->save();

                            $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHisUpdate->status = 2;
                            $employeetaskManagerHisUpdate->save();

                            $taskStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 2;
                            $taskStatusUpdate->save();
                        }



                        $labelTotalItemCount = PhotoFrameLabelModel::where('order_items_id', $totalStageCount->order_items_id)->count();

                        $labelTotalPreviewCount = PhotoFrameLabelModel::where('order_items_id', $totalStageCount->order_items_id)->where('preview_status', 1)->count();
                        Log::channel("previewImageApproved")->info("labelTotalItemCount PhotoFrameUploadModel:: $labelTotalItemCount");
                        Log::channel("previewImageApproved")->info("labelTotalPreviewCount PhotoFrameUploadModel:: $labelTotalPreviewCount");

                        if ($labelTotalItemCount == $labelTotalPreviewCount) {
                            if ($updateStatus->is_customer_preview == 1 && $updateStatus->is_qc == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesEmployeeId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesEmployeeId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();

                                $taskManStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                                $taskManStatusUpdate->updated_on = Server::getDateTime();
                                $taskManStatusUpdate->updated_by = JwtHelper::getSesEmployeeId();
                                $taskManStatusUpdate->current_task_stage = 2;
                                $taskManStatusUpdate->save();
                            }
                        }

                        $orderitemstageCount = TaskManager::where('task_manager_id', $totalStageCount->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        $stageCompletedCount = TaskManager::where('task_manager_id', $totalStageCount->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();


                        Log::channel("previewImageApproved")->info("orderitemstageCount PhotoFrameUploadModel:: $orderitemstageCount");

                        Log::channel("previewImageApproved")->info("stageCompletedCount PhotoFrameUploadModel:: $stageCompletedCount");

                        if ($orderitemstageCount == $stageCompletedCount) {
                            Log::channel("previewImageApproved")->info("delivered PhotoFrameUploadModel:: $stageCompletedCount");
                            Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                            $taskStatusUpdate = TaskManager::find($totalStageCount->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 4;
                            $taskStatusUpdate->save();
                        }

                        //Email for Bulk Order
                        if (!empty($taskManDetails)) {
                            if ($taskManDetails->order_items_id != null) {
                                $getOrderId = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->first();
                                $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();

                                $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();
                                if (!empty($getBulkOrderEmail)) {
                                    if ($getBulkOrderEmail->email != null) {
                                        $mail_data = [
                                            'email' => $getBulkOrderEmail->email,
                                            'order_id' => $getBulkOrderId->order_code,
                                            'customer_name' => $getBulkOrderEmail->contact_person_name
                                        ];

                                        Mail::send('mail.sendcustomerpreviewApprove', $mail_data, function ($message) use ($mail_data) {
                                            $message->to($mail_data['email'])
                                                ->subject('Reply to Image Approval');
                                        });
                                    }
                                }
                            }
                        }
                    }

                    //Selfie
                    if ($request->service_id == 6) {
                        Log::channel("previewImageApproved")->info("request value PhotoFrameUploadModel previewImageApproved_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageApproved")->info("Photo frame approved successfully");
                        $update = SelfieUploadModel::where('order_selfie_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_on' => Server::getDateTime(),
                            'preview_by' => JwtHelper::getSesEmployeeId()
                        ));

                        // $updateStatus = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->leftjoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'order_photoframe_upload_label.qc_status', 'order_photoframe_upload_label.preview_status', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id')->first();

                        $selfieDetails = SelfieUploadModel::where('order_selfie_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_selfie_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->where('task_type', 2)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'task_manager.task_manager_id')->first();

                        //total stage count
                        $totalStageCount = SelfieUploadModel::where('order_selfie_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_selfie_upload.order_items_id')->select('task_manager.task_manager_id', 'task_manager.order_items_id')->first();

                        //approved after check the label details
                        $labelItemQcCount = SelfieUploadModel::where('order_items_id', $totalStageCount->order_items_id)->count();

                        $labelTotalQcCount = SelfieUploadModel::where('order_items_id', $totalStageCount->order_items_id)->where('qc_status', 1)->count();

                        if ($labelItemQcCount != $labelTotalQcCount) {
                            $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskHisStatusUpdate->work_stage = 2;
                            $taskHisStatusUpdate->save();

                            $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHisUpdate->status = 2;
                            $employeetaskManagerHisUpdate->save();

                            $taskStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 2;
                            $taskStatusUpdate->save();
                        }

                        $labelTotalItemCount = SelfieUploadModel::where('order_items_id', $selfieDetails->order_items_id)->count();

                        $labelTotalPreviewCount = SelfieUploadModel::where('order_items_id', $selfieDetails->order_items_id)->where('preview_status', 1)->count();

                        if ($labelTotalItemCount == $labelTotalPreviewCount) {
                            if ($updateStatus->is_customer_preview == 1 && $updateStatus->is_qc == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesEmployeeId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesEmployeeId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();

                                $taskManStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                                $taskManStatusUpdate->updated_on = Server::getDateTime();
                                $taskManStatusUpdate->updated_by = JwtHelper::getSesEmployeeId();
                                $taskManStatusUpdate->current_task_stage = 2;
                                $taskManStatusUpdate->save();
                            }
                        }

                        $orderitemstageCount = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        $stageCompletedCount = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        if ($orderitemstageCount == $stageCompletedCount) {
                            // print_r("hi");exit;
                            Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                            $taskStatusUpdate = TaskManager::find($selfieDetails->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 4;
                            $taskStatusUpdate->save();
                        }

                        //Email for Bulk Order
                        if (!empty($taskManDetails)) {
                            if ($taskManDetails->order_items_id != null) {
                                $getOrderId = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->first();
                                $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();
                                if (!empty($getBulkOrderEmail)) {
                                    if ($getBulkOrderEmail->email != null) {
                                        $mail_data = [
                                            'email' => $getBulkOrderEmail->email,
                                            'order_id' => $getBulkOrderId->order_code,
                                            'customer_name' => $getBulkOrderEmail->contact_person_name
                                        ];

                                        Mail::send('mail.sendcustomerpreviewApprove', $mail_data, function ($message) use ($mail_data) {
                                            $message->to($mail_data['email'])
                                                ->subject('Reply to Image Approval');
                                        });
                                    }
                                }
                            }
                        }
                    }

                    //Passport Size
                    if ($request->service_id == 1) {
                        Log::channel("previewImageApproved")->info("request value PassportSizeUploadModel previewImageApproved_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageApproved")->info("Passport Size approved successfully");
                        $update = PassportSizeUploadModel::where('order_passport_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_on' => Server::getDateTime(),
                            'preview_by' => JwtHelper::getSesEmployeeId()
                        ));


                        $selfieDetails = PassportSizeUploadModel::where('order_passport_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_passport_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->where('task_type', 2)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'task_manager.task_manager_id')->first();

                        //total stage count
                        $totalStageCount = PassportSizeUploadModel::where('order_passport_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_passport_upload.order_items_id')->select('task_manager.task_manager_id', 'task_manager.order_items_id')->first();

                        //approved after check the label details
                        $labelItemQcCount = PassportSizeUploadModel::where('order_items_id', $totalStageCount->order_items_id)->count();

                        $labelTotalQcCount = PassportSizeUploadModel::where('order_items_id', $totalStageCount->order_items_id)->where('qc_status', 1)->count();

                        if ($labelItemQcCount != $labelTotalQcCount) {
                            $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskHisStatusUpdate->work_stage = 2;
                            $taskHisStatusUpdate->save();

                            $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHisUpdate->status = 2;
                            $employeetaskManagerHisUpdate->save();

                            $taskStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 2;
                            $taskStatusUpdate->save();
                        }

                        $labelTotalItemCount = PassportSizeUploadModel::where('order_items_id', $selfieDetails->order_items_id)->count();

                        $labelTotalPreviewCount = PassportSizeUploadModel::where('order_items_id', $selfieDetails->order_items_id)->where('preview_status', 1)->count();

                        if ($labelTotalItemCount == $labelTotalPreviewCount) {
                            if ($updateStatus->is_customer_preview == 1 && $updateStatus->is_qc == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesEmployeeId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesEmployeeId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();

                                $taskManStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                                $taskManStatusUpdate->updated_on = Server::getDateTime();
                                $taskManStatusUpdate->updated_by = JwtHelper::getSesEmployeeId();
                                $taskManStatusUpdate->current_task_stage = 2;
                                $taskManStatusUpdate->save();
                            }
                        }

                        $orderitemstageCount = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        $stageCompletedCount = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        if ($orderitemstageCount == $stageCompletedCount) {
                            // print_r("hi");exit;
                            Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                            $taskStatusUpdate = TaskManager::find($selfieDetails->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 4;
                            $taskStatusUpdate->save();
                        }

                        //Email for Bulk Order
                        if (!empty($taskManDetails)) {
                            if ($taskManDetails->order_items_id != null) {
                                $getOrderId = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->first();
                                $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();
                                if (!empty($getBulkOrderEmail)) {
                                    if ($getBulkOrderEmail->email != null) {
                                        $mail_data = [
                                            'email' => $getBulkOrderEmail->email,
                                            'order_id' => $getBulkOrderId->order_code,
                                            'customer_name' => $getBulkOrderEmail->contact_person_name
                                        ];

                                        Mail::send('mail.sendcustomerpreviewApprove', $mail_data, function ($message) use ($mail_data) {
                                            $message->to($mail_data['email'])
                                                ->subject('Reply to Image Approval');
                                        });
                                    }
                                }
                            }
                        }
                    }

                    //PhotoPrint
                    if ($request->service_id == 2) {
                        Log::channel("previewImageApproved")->info("request value PhotoPrintUploadModel previewImageApproved_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageApproved")->info("Photo frame approved successfully");
                        $update = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_on' => Server::getDateTime(),
                            'preview_by' => JwtHelper::getSesEmployeeId()
                        ));

                        // $updateStatus = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager.orderitem_stage_id')->leftjoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'order_photoframe_upload_label.qc_status', 'order_photoframe_upload_label.preview_status', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id')->first();

                        $selfieDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoprint_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->where('task_type', 2)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id', 'task_manager.task_manager_id')->first();

                        //total stage count
                        $totalStageCount = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoprint_upload.order_items_id')->select('task_manager.task_manager_id', 'task_manager.order_items_id')->first();

                        //approved after check the label details
                        $labelItemQcCount = PhotoPrintUploadModel::where('order_items_id', $totalStageCount->order_items_id)->count();

                        $labelTotalQcCount = PhotoPrintUploadModel::where('order_items_id', $totalStageCount->order_items_id)->where('qc_status', 1)->count();

                        if ($labelItemQcCount != $labelTotalQcCount) {
                            $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                            $taskHisStatusUpdate->work_stage = 2;
                            $taskHisStatusUpdate->save();

                            $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                            $employeetaskManagerHisUpdate->status = 2;
                            $employeetaskManagerHisUpdate->save();

                            $taskStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 2;
                            $taskStatusUpdate->save();
                        }

                        $labelTotalItemCount = PhotoPrintUploadModel::where('order_items_id', $selfieDetails->order_items_id)->count();

                        $labelTotalPreviewCount = PhotoPrintUploadModel::where('order_items_id', $selfieDetails->order_items_id)->where('preview_status', 1)->count();

                        if ($labelTotalItemCount == $labelTotalPreviewCount) {
                            if ($updateStatus->is_customer_preview == 1 && $updateStatus->is_qc == 1) {
                                $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                                $orderItemStatus->completed_on = Server::getDateTime();
                                $orderItemStatus->completed_by = JwtHelper::getSesEmployeeId();
                                $orderItemStatus->status = 2;
                                $orderItemStatus->save();

                                $taskHisStatusUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                                $taskHisStatusUpdate->completed_on = Server::getDateTime();
                                $taskHisStatusUpdate->completed_by = JwtHelper::getSesEmployeeId();
                                $taskHisStatusUpdate->work_stage = 4;
                                $taskHisStatusUpdate->save();

                                $employeetaskManagerHisUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                                $employeetaskManagerHisUpdate->status = 4;
                                $employeetaskManagerHisUpdate->save();

                                $taskManStatusUpdate = TaskManager::find($updateStatus->task_manager_id);
                                $taskManStatusUpdate->updated_on = Server::getDateTime();
                                $taskManStatusUpdate->updated_by = JwtHelper::getSesEmployeeId();
                                $taskManStatusUpdate->current_task_stage = 2;
                                $taskManStatusUpdate->save();
                            }
                        }

                        $orderitemstageCount = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        $stageCompletedCount = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->where('orderitem_stage.status', 2)->leftjoin('orderitem_stage', 'orderitem_stage.order_items_id', '=', 'task_manager.order_items_id')->count();

                        if ($orderitemstageCount == $stageCompletedCount) {
                            // print_r("hi");exit;
                            Log::channel("qcApprove")->info('** current_task_stage 4 is delivered in last stage request value **');
                            $taskStatusUpdate = TaskManager::find($selfieDetails->task_manager_id);
                            $taskStatusUpdate->current_task_stage = 4;
                            $taskStatusUpdate->save();
                        }

                        //Email for Bulk Order
                        if (!empty($taskManDetails)) {
                            if ($taskManDetails->order_items_id != null) {
                                $getOrderId = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->first();
                                $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();
                                if (!empty($getBulkOrderEmail)) {
                                    if ($getBulkOrderEmail->email != null) {
                                        $mail_data = [
                                            'email' => $getBulkOrderEmail->email,
                                            'order_id' => $getBulkOrderId->order_code,
                                            'customer_name' => $getBulkOrderEmail->contact_person_name
                                        ];

                                        Mail::send('mail.sendcustomerpreviewApprove', $mail_data, function ($message) use ($mail_data) {
                                            $message->to($mail_data['email'])
                                                ->subject('Reply to Image Approval');
                                        });
                                    }
                                }
                            }
                        }
                    }
                    Log::channel("previewImageApproved")->info('** end the previewImageApproved method **');
                }

                if ($type == "rejected") {

                    Log::channel("previewImageRejected")->info('** started the previewImageRejected method **');

                    if ($request->service_id == 4) {
                        Log::channel("previewImageRejected")->info("request value PhotoFrameUploadModel previewImageRejected_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageRejected")->info("Photo frame rejected successfully");

                        $update = TaskManager::where('task_manager_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_reason' => $request->preview_reason,
                            'preview_reason_on' => Server::getDateTime(),
                            'qc_status' => $request->preview_status,
                            'qc_reason' => $request->preview_reason,
                            'qc_reason_on' => Server::getDateTime()
                        ));

                        $taskManagerDetails = TaskManager::where('task_manager_id', $id)->first();

                        if (!empty($taskManagerDetails->preview_image)) {
                            $previewHistory = new TaskManagerPreviewHistory();
                            $previewHistory->task_manager_id = $request->id;
                            $previewHistory->preview_image = $taskManagerDetails->preview_image;
                            $previewHistory->preview_on = $taskManagerDetails->preview_on;
                            $previewHistory->preview_by = $taskManagerDetails->preview_by;
                            $previewHistory->preview_reason = $taskManagerDetails->preview_reason;
                            $previewHistory->preview_reason_on = $taskManagerDetails->preview_reason_on;
                            $previewHistory->preview_status = $taskManagerDetails->preview_status;
                            $previewHistory->save();
                        }

                        // $previewDetails = TaskManager::where('task_manager.task_manager_id', $id)->leftjoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->where('task_manager_history.production_status', 1)->select('task_manager_history.*', 'employee_task_history.employee_task_history_id')->first();
                        $taskManDetails = TaskManager::where('task_manager_id', $id)->where('task_type', 2)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.orderitem_stage_id', 'orderitem_stage.is_customer_preview', 'orderitem_stage.is_qc', 'task_manager.qc_status', 'task_manager.preview_status', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id')->first();

                        $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                        $orderItemStatus->qc_on = Server::getDateTime();
                        $orderItemStatus->qc_by = JwtHelper::getSesEmployeeId();
                        $orderItemStatus->qc_status = 2;
                        $orderItemStatus->save();

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $taskManagerUpdate = TaskManager::find($updateStatus->task_manager_id);
                        $taskManagerUpdate->current_task_stage = 2;
                        $taskManagerUpdate->save();


                        //Email for Bulk Order
                        // $taskManCheck = TaskManager::where('task_manager_id', $id)->where('task_type',2)->first();

                        if ($taskManDetails->order_items_id != null) {
                            $getOrderId = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->first();
                            $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                            $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();
                            if (!empty($getBulkOrderEmail)) {
                                if ($getBulkOrderEmail->email != null) {
                                    $mail_data = [
                                        'email' => $getBulkOrderEmail->email,
                                        'order_id' => $getBulkOrderId->order_code,
                                        'customer_name' => $getBulkOrderEmail->contact_person_name
                                    ];

                                    Mail::send('mail.sendcustomerpreviewReject', $mail_data, function ($message) use ($mail_data) {
                                        $message->to($mail_data['email'])
                                            ->subject('Reply to Image Rejection');
                                    });
                                }
                            }
                        }
                    }

                    if ($request->service_id == 3) {
                        Log::channel("previewImageRejected")->info("request value PhotoFrameUploadModel previewImageRejected_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageRejected")->info("Photo frame rejected successfully");

                        $update = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_reason' => $request->preview_reason,
                            'preview_reason_on' => Server::getDateTime(),
                            'qc_status' => $request->preview_status,
                            'qc_reason' => $request->preview_reason,
                            'qc_reason_on' => Server::getDateTime()
                        ));

                        $framePreviewDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->first();
                        if (!empty($framePreviewDetails->preview_image)) {
                            $previewHistory = new PhotoFramePreviewHistory();
                            $previewHistory->order_photoframe_upload_label_id = $request->id;
                            $previewHistory->preview_image = $framePreviewDetails->preview_image;
                            $previewHistory->preview_on = $framePreviewDetails->preview_on;
                            $previewHistory->preview_by = $framePreviewDetails->preview_by;
                            $previewHistory->preview_reason = $framePreviewDetails->preview_reason;
                            $previewHistory->preview_reason_on = $framePreviewDetails->preview_reason_on;
                            $previewHistory->preview_status = $framePreviewDetails->preview_status;
                            $previewHistory->save();
                            Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $previewHistory");
                        }

                        // $previewDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $request->id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->leftjoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'task_manager.task_manager_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->where('task_manager_history.production_status', 1)->select('task_manager_history.*', 'employee_task_history.employee_task_history_id')->first();

                        $labelDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->where('task_type', 2)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id')->first();

                        $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                        $orderItemStatus->qc_on = Server::getDateTime();
                        $orderItemStatus->qc_by = JwtHelper::getSesEmployeeId();
                        $orderItemStatus->qc_status = 2;
                        $orderItemStatus->save();

                        // $taskManLabelDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->first();

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $taskManagerUpdate = TaskManager::find($updateStatus->task_manager_id);
                        $taskManagerUpdate->current_task_stage = 2;
                        $taskManagerUpdate->save();


                        //Email for Bulk Order
                        if (!empty($taskManDetails)) {
                            if ($taskManDetails->order_items_id != null) {
                                $getOrderId = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->first();
                                $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();
                                if (!empty($getBulkOrderEmail)) {
                                    if ($getBulkOrderEmail->email != null) {
                                        $mail_data = [
                                            'email' => $getBulkOrderEmail->email,
                                            'order_id' => $getBulkOrderId->order_code,
                                            'customer_name' => $getBulkOrderEmail->contact_person_name
                                        ];

                                        Mail::send('mail.sendcustomerpreviewReject', $mail_data, function ($message) use ($mail_data) {
                                            $message->to($mail_data['email'])
                                                ->subject('Reply to Image Rejection');
                                        });
                                    }
                                }
                            }
                        }
                    }

                    //Selfie
                    if ($request->service_id == 6) {
                        Log::channel("previewImageRejected")->info("request value PhotoFrameUploadModel previewImageRejected_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageRejected")->info("Photo frame rejected successfully");

                        $update = SelfieUploadModel::where('order_selfie_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_reason' => $request->preview_reason,
                            'preview_reason_on' => Server::getDateTime(),
                            'qc_status' => $request->preview_status,
                            'qc_reason' => $request->preview_reason,
                            'qc_reason_on' => Server::getDateTime()
                        ));

                        $selfiePreviewDetails = SelfieUploadModel::where('order_selfie_upload_id', $id)->first();
                        if (!empty($selfiePreviewDetails->preview_image)) {
                            $previewHistory = new SelfieUploadPreviewHistoryModel();
                            $previewHistory->order_selfie_upload_id = $request->id;
                            $previewHistory->preview_image = $selfiePreviewDetails->preview_image;
                            $previewHistory->preview_on = $selfiePreviewDetails->preview_on;
                            $previewHistory->preview_by = $selfiePreviewDetails->preview_by;
                            $previewHistory->preview_reason = $selfiePreviewDetails->preview_reason;
                            $previewHistory->preview_reason_on = $selfiePreviewDetails->preview_reason_on;
                            $previewHistory->preview_status = $selfiePreviewDetails->preview_status;
                            $previewHistory->save();
                            Log::channel("previewAttachedImageUpload")->info("request value PhotoFrameUploadHistoryModel previewAttachedImageUpload_id:: $previewHistory");
                        }

                        // $labelDetails = PhotoFrameLabelModel::where('order_photoframe_upload_label_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoframe_upload_label.order_items_id')->select('task_manager.*')->first();

                        // $taskManDetails = TaskManager::where('task_manager_id', $labelDetails->task_manager_id)->first();

                        // $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'employee_task_history.employee_task_history_id')->first();

                        $selfieDetails = SelfieUploadModel::where('order_selfie_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_selfie_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->where('task_type', 2)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id')->first();

                        $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                        $orderItemStatus->qc_on = Server::getDateTime();
                        $orderItemStatus->qc_by = JwtHelper::getSesEmployeeId();
                        $orderItemStatus->qc_status = 2;
                        $orderItemStatus->save();

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $taskManagerUpdate = TaskManager::find($updateStatus->task_manager_id);
                        $taskManagerUpdate->current_task_stage = 2;
                        $taskManagerUpdate->save();


                        //Email for Bulk Order
                        if (!empty($taskManDetails)) {
                            if ($taskManDetails->order_items_id != null) {
                                $getOrderId = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->first();
                                $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();
                                if (!empty($getBulkOrderEmail)) {
                                    if ($getBulkOrderEmail->email != null) {
                                        $mail_data = [
                                            'email' => $getBulkOrderEmail->email,
                                            'order_id' => $getBulkOrderId->order_code,
                                            'customer_name' => $getBulkOrderEmail->contact_person_name
                                        ];

                                        Mail::send('mail.sendcustomerpreviewReject', $mail_data, function ($message) use ($mail_data) {
                                            $message->to($mail_data['email'])
                                                ->subject('Reply to Image Rejection');
                                        });
                                    }
                                }
                            }
                        }
                    }

                    //Passport Size
                    if ($request->service_id == 1) {
                        Log::channel("previewImageRejected")->info("request value PassportSizeUploadModel previewImageRejected_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageRejected")->info("Passport Size rejected successfully");

                        $update = PassportSizeUploadModel::where('order_passport_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_reason' => $request->preview_reason,
                            'preview_reason_on' => Server::getDateTime(),
                            'qc_status' => $request->preview_status,
                            'qc_reason' => $request->preview_reason,
                            'qc_reason_on' => Server::getDateTime()
                        ));

                        $selfiePreviewDetails = PassportSizeUploadModel::where('order_passport_upload_id', $id)->first();
                        if (!empty($selfiePreviewDetails->preview_image)) {
                            $previewHistory = new PassportSizeUploadPreviewHistoryModel();
                            $previewHistory->order_passport_upload_id = $request->id;
                            $previewHistory->preview_image = $selfiePreviewDetails->preview_image;
                            $previewHistory->preview_on = $selfiePreviewDetails->preview_on;
                            $previewHistory->preview_by = $selfiePreviewDetails->preview_by;
                            $previewHistory->preview_reason = $selfiePreviewDetails->preview_reason;
                            $previewHistory->preview_reason_on = $selfiePreviewDetails->preview_reason_on;
                            $previewHistory->preview_status = $selfiePreviewDetails->preview_status;
                            $previewHistory->save();
                            Log::channel("previewAttachedImageUpload")->info("request value PassportSizeUploadPreviewHistoryModel previewAttachedImageUpload_id:: $previewHistory");
                        }

                        $selfieDetails = PassportSizeUploadModel::where('order_passport_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_passport_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->where('task_type', 2)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id')->first();

                        $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                        $orderItemStatus->qc_on = Server::getDateTime();
                        $orderItemStatus->qc_by = JwtHelper::getSesEmployeeId();
                        $orderItemStatus->qc_status = 2;
                        $orderItemStatus->save();

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $taskManagerUpdate = TaskManager::find($updateStatus->task_manager_id);
                        $taskManagerUpdate->current_task_stage = 2;
                        $taskManagerUpdate->save();


                        //Email for Bulk Order
                        if (!empty($taskManDetails)) {
                            if ($taskManDetails->order_items_id != null) {
                                $getOrderId = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->first();
                                $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();
                                if (!empty($getBulkOrderEmail)) {
                                    if ($getBulkOrderEmail->email != null) {
                                        $mail_data = [
                                            'email' => $getBulkOrderEmail->email,
                                            'order_id' => $getBulkOrderId->order_code,
                                            'customer_name' => $getBulkOrderEmail->contact_person_name
                                        ];

                                        Mail::send('mail.sendcustomerpreviewReject', $mail_data, function ($message) use ($mail_data) {
                                            $message->to($mail_data['email'])
                                                ->subject('Reply to Image Rejection');
                                        });
                                    }
                                }
                            }
                        }
                    }

                    //PhotoPrint
                    if ($request->service_id == 2) {
                        Log::channel("previewImageRejected")->info("request value PhotoPrintUploadModel previewImageRejected_id:: $id :: status :: $request->preview_status");
                        Log::channel("previewImageRejected")->info("Photo print rejected successfully");

                        $update = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->update(array(
                            'preview_status' => $request->preview_status,
                            'preview_reason' => $request->preview_reason,
                            'preview_reason_on' => Server::getDateTime(),
                            'qc_status' => $request->preview_status,
                            'qc_reason' => $request->preview_reason,
                            'qc_reason_on' => Server::getDateTime()
                        ));

                        $selfiePreviewDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->first();
                        if (!empty($selfiePreviewDetails->preview_image)) {
                            $previewHistory = new PhotoPrintUploadPreviewHistoryModel();
                            $previewHistory->order_photoprint_upload_id = $request->id;
                            $previewHistory->preview_image = $selfiePreviewDetails->preview_image;
                            $previewHistory->preview_on = $selfiePreviewDetails->preview_on;
                            $previewHistory->preview_by = $selfiePreviewDetails->preview_by;
                            $previewHistory->preview_reason = $selfiePreviewDetails->preview_reason;
                            $previewHistory->preview_reason_on = $selfiePreviewDetails->preview_reason_on;
                            $previewHistory->preview_status = $selfiePreviewDetails->preview_status;
                            $previewHistory->save();
                            Log::channel("previewAttachedImageUpload")->info("request value PhotoPrintUploadPreviewHistoryModel previewAttachedImageUpload_id:: $previewHistory");
                        }


                        $selfieDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $id)->leftjoin('task_manager', 'task_manager.order_items_id', '=', 'order_photoprint_upload.order_items_id')->select('task_manager.*')->first();

                        $taskManDetails = TaskManager::where('task_manager_id', $selfieDetails->task_manager_id)->where('task_type', 2)->first();

                        $updateStatus = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->where('task_manager_history.production_status', 1)->where('orderitem_stage.orderitem_stage_id', $taskManDetails->orderitem_stage_id)->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')->leftjoin('employee_task_history', 'employee_task_history.task_manager_history_id', '=', 'task_manager_history.task_manager_history_id')->select('orderitem_stage.*', 'task_manager_history.task_manager_history_id', 'task_manager_history.task_manager_id', 'employee_task_history.employee_task_history_id')->first();

                        $orderItemStatus = OrderItemStage::find($updateStatus->orderitem_stage_id);
                        $orderItemStatus->qc_on = Server::getDateTime();
                        $orderItemStatus->qc_by = JwtHelper::getSesEmployeeId();
                        $orderItemStatus->qc_status = 2;
                        $orderItemStatus->save();

                        $taskManagerHistoryUpdate = TaskManagerHistory::find($updateStatus->task_manager_history_id);
                        $taskManagerHistoryUpdate->work_stage = 2;
                        $taskManagerHistoryUpdate->save();

                        $employeetaskManagerHistoryUpdate = EmployeeTaskHistory::find($updateStatus->employee_task_history_id);
                        $employeetaskManagerHistoryUpdate->status = 2;
                        $employeetaskManagerHistoryUpdate->save();

                        $taskManagerUpdate = TaskManager::find($updateStatus->task_manager_id);
                        $taskManagerUpdate->current_task_stage = 2;
                        $taskManagerUpdate->save();


                        //Email for Bulk Order
                        if (!empty($taskManDetails)) {
                            if ($taskManDetails->order_items_id != null) {
                                $getOrderId = OrderItems::where('order_items_id', $taskManDetails->order_items_id)->first();
                                $getBulkOrderId = Orders::where('order_id', $getOrderId->order_id)->first();
                                $getBulkOrderEmail = BulkOrderEnquiry::where('bulk_order_enquiry_id', $getBulkOrderId->bulk_order_enquiry_id)->first();
                                if (!empty($getBulkOrderEmail)) {
                                    if ($getBulkOrderEmail->email != null) {
                                        $mail_data = [
                                            'email' => $getBulkOrderEmail->email,
                                            'order_id' => $getBulkOrderId->order_code,
                                            'customer_name' => $getBulkOrderEmail->contact_person_name
                                        ];

                                        Mail::send('mail.sendcustomerpreviewReject', $mail_data, function ($message) use ($mail_data) {
                                            $message->to($mail_data['email'])
                                                ->subject('Reply to Image Rejection');
                                        });
                                    }
                                }
                            }
                        }
                    }
                    Log::channel("previewImageRejected")->info('** end the previewImageRejected method **');
                }

                if ($request->preview_status == 1) {
                    Log::channel("previewimages")->info("save value :: previewimages_id :: $id :: previewimages inactive successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Preview image approved successfully'),
                        'data' => []
                    ]);
                } else if ($request->preview_status == 2) {
                    Log::channel("previewimages")->info("save value :: previewimages_id :: $id :: previewimages active successfully");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Preview image rejected successfully'),
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
        // } catch (\Exception $exception) {
        //     Log::channel("previewImageApproved")->info('** end the previewImageApproved method **');
        //     return response()->json([
        //         'error' => 'Internal server error.',
        //         'message' => $exception->getMessage()
        //     ], 500);
        // }
    }

    //attachedImageUpload
    public function attachedImageUploadVerificationByEmployee(Request $request)
    {
        try {
            //Passport size
            if ($request->service_id == 1) {
                Log::channel("attachedImageUpload")->info('** started the passportsize attachedImageUpload method **');
                $photoframeData = PassportSizeUploadModel::where('order_passport_upload_id', $request->id)->first();
                if (!empty($photoframeData->image)) {
                    $photoframeHistory = new PassportSizeUploadHistoryModel();
                    $photoframeHistory->order_passport_upload_id = $photoframeData->order_passport_upload_id;
                    $photoframeHistory->image = $photoframeData->image;
                    $photoframeHistory->created_on = $photoframeData->updated_on;
                    $photoframeHistory->created_by = $photoframeData->updated_by;
                    $photoframeHistory->reject_reason = $photoframeData->reject_reason;
                    $photoframeHistory->rejected_on = $photoframeData->rejected_on;
                    $photoframeHistory->status = $photoframeData->status;
                    $photoframeHistory->save();
                }
                Log::channel("attachedImageUpload")->info("request value PassportSizeUploadHistoryModel attachedImageUpload_id:: $photoframeData");
                Log::channel("attachedImageUpload")->info("passportsize upload successfully");
                $upload = PassportSizeUploadModel::find($request->id);
                $upload->image = $request->image;
                $upload->updated_on = Server::getDateTime();
                $upload->updated_by = JwtHelper::getSesEmployeeId();
                $upload->status = 0;
                $upload->reject_reason = null;
                $upload->rejected_on = null;
                $upload->save();
                Log::channel("attachedImageUpload")->info('** end the passportsize attachedImageUpload method **');
                $uploadDetails = PassportSizeUploadModel::where('order_passport_upload_id', $request->id)->first();
                if (!empty($uploadDetails)) {
                    $ary = [];
                    $ary['id'] = $uploadDetails['order_passport_upload_id'];
                    $ary['image'] = $uploadDetails['image'];
                    $ary['image_url'] = ($uploadDetails['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $uploadDetails['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['status'] = $uploadDetails['status'];
                    $ary['approved_on'] = $uploadDetails['updated_on'];
                    $ary['received_on'] = $uploadDetails['updated_on'] == null ? $uploadDetails['created_on'] : $uploadDetails['updated_on'];
                    $ary['reject_reason'] = $uploadDetails['reject_reason'];
                    $ary['rejected_on'] = $uploadDetails['rejected_on'];
                    $ary['image_history'] = $this->passportSizeUploadHistory($uploadDetails['order_passport_upload_id']);
                    $final[] = $ary;
                }
            }
            //Photoprint
            if ($request->service_id == 2) {
                Log::channel("attachedImageUpload")->info('** started the Photoprint attachedImageUpload method **');
                $photoframeData = PhotoPrintUploadModel::where('order_photoprint_upload_id', $request->id)->first();
                if (!empty($photoframeData->image)) {
                    $photoframeHistory = new PhotoPrintUploadHistoryModel();
                    $photoframeHistory->order_photoprint_upload_id = $photoframeData->order_photoprint_upload_id;
                    $photoframeHistory->image = $photoframeData->image;
                    $photoframeHistory->created_on = $photoframeData->updated_on;
                    $photoframeHistory->created_by = $photoframeData->updated_by;
                    $photoframeHistory->reject_reason = $photoframeData->reject_reason;
                    $photoframeHistory->rejected_on = $photoframeData->rejected_on;
                    $photoframeHistory->status = $photoframeData->status;
                    $photoframeHistory->save();
                }
                Log::channel("attachedImageUpload")->info("request value PhotoPrintUploadHistoryModel attachedImageUpload_id:: $photoframeData");
                Log::channel("attachedImageUpload")->info("Photoprint upload successfully");
                $upload = PhotoPrintUploadModel::find($request->id);
                $upload->image = $request->image;
                $upload->updated_on = Server::getDateTime();
                $upload->updated_by = JwtHelper::getSesEmployeeId();
                $upload->status = 0;
                $upload->reject_reason = null;
                $upload->rejected_on = null;
                $upload->save();
                Log::channel("attachedImageUpload")->info('** end the Photoprint attachedImageUpload method **');
                $uploadDetails = PhotoPrintUploadModel::where('order_photoprint_upload_id', $request->id)->first();
                if (!empty($uploadDetails)) {
                    $ary = [];
                    $ary['id'] = $uploadDetails['order_photoprint_upload_id'];
                    $ary['image'] = $uploadDetails['image'];
                    $ary['image_url'] = ($uploadDetails['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $uploadDetails['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['status'] = $uploadDetails['status'];
                    $ary['approved_on'] = $uploadDetails['updated_on'];
                    $ary['received_on'] = $uploadDetails['updated_on'] == null ? $uploadDetails['created_on'] : $uploadDetails['updated_on'];
                    $ary['reject_reason'] = $uploadDetails['reject_reason'];
                    $ary['rejected_on'] = $uploadDetails['rejected_on'];
                    $ary['image_history'] = $this->photoPrintSizeUploadHistory($uploadDetails['order_photoprint_upload_id']);
                    $final[] = $ary;
                }
            }
            //Photo frame
            if ($request->service_id == 3) {
                Log::channel("attachedImageUpload")->info('** started the photo frame attachedImageUpload method **');
                $photoframeData = PhotoFrameUploadModel::where('order_photoframe_upload_id', $request->id)->first();
                if (!empty($photoframeData->image)) {
                    $photoframeHistory = new PhotoFrameUploadHistoryModel();
                    $photoframeHistory->order_photoframe_upload_id = $photoframeData->order_photoframe_upload_id;
                    $photoframeHistory->image = $photoframeData->image;
                    $photoframeHistory->created_on = $photoframeData->updated_on;
                    $photoframeHistory->created_by = $photoframeData->updated_by;
                    $photoframeHistory->reject_reason = $photoframeData->reject_reason;
                    $photoframeHistory->rejected_on = $photoframeData->rejected_on;
                    $photoframeHistory->status = $photoframeData->status;
                    $photoframeHistory->save();
                }
                Log::channel("attachedImageUpload")->info("request value PhotoFrameUploadHistoryModel attachedImageUpload_id:: $photoframeData");
                Log::channel("attachedImageUpload")->info("Photo frame upload successfully");
                $upload = PhotoFrameUploadModel::find($request->id);
                $upload->image = $request->image;
                $upload->updated_on = Server::getDateTime();
                $upload->updated_by = JwtHelper::getSesEmployeeId();
                $upload->status = 0;
                $upload->reject_reason = null;
                $upload->rejected_on = null;
                $upload->save();
                Log::channel("attachedImageUpload")->info('** end the photo frame attachedImageUpload method **');
            }
            //Personalized
            if ($request->service_id == 4) {
                Log::channel("attachedImageUpload")->info('** started the Personalized attachedImageUpload method **');
                $personalizedData = PersonalizedUploadModel::where('order_personalized_upload_id', $request->id)->first();
                if (!empty($personalizedData->image)) {
                    $personalizedHistory = new PersonalizedUploadHistoryModel();
                    $personalizedHistory->order_personalized_upload_id = $personalizedData->order_personalized_upload_id;
                    $personalizedHistory->image = $personalizedData->image;
                    $personalizedHistory->created_on = $personalizedData->updated_on;
                    $personalizedHistory->created_by = $personalizedData->updated_by;
                    $personalizedHistory->reject_reason = $personalizedData->reject_reason;
                    $personalizedHistory->rejected_on = $personalizedData->rejected_on;
                    $personalizedHistory->status = $personalizedData->status;
                    $personalizedHistory->save();
                }
                Log::channel("attachedImageUpload")->info("request value PersonalizedUploadHistoryModel attachedImageUpload_id:: $personalizedData");
                Log::channel("attachedImageUpload")->info("Personalized upload successfully");
                $upload = PersonalizedUploadModel::find($request->id);
                $upload->image = $request->image;
                $upload->reference_image = $request->reference_image;
                $upload->updated_on = Server::getDateTime();
                $upload->updated_by = JwtHelper::getSesEmployeeId();
                $upload->status = 0;
                $upload->reject_reason = null;
                $upload->rejected_on = null;
                $upload->save();
                Log::channel("attachedImageUpload")->info('** end the personalized attachedImageUpload method **');
            }
            //Selfie
            if ($request->service_id == 6) {
                Log::channel("attachedImageUpload")->info('** started the Selfie attachedImageUpload method **');
                $selfieData = SelfieUploadModel::where('order_selfie_upload_id', $request->id)->first();
                if (!empty($selfieData->image)) {
                    $selfieHistory = new SelfieUploadHistoryModel();
                    $selfieHistory->order_selfie_upload_id = $selfieData->order_selfie_upload_id;
                    $selfieHistory->image = $selfieData->image;
                    $selfieHistory->created_on = $selfieData->updated_on;
                    $selfieHistory->created_by = $selfieData->updated_by;
                    $selfieHistory->reject_reason = $selfieData->reject_reason;
                    $selfieHistory->rejected_on = $selfieData->rejected_on;
                    $selfieHistory->status = $selfieData->status;
                    $selfieHistory->save();
                }
                Log::channel("attachedImageUpload")->info("request value SelfieUploadHistoryModel attachedImageUpload_id:: $selfieData");
                Log::channel("attachedImageUpload")->info("Selfie upload successfully");
                $upload = SelfieUploadModel::find($request->id);
                $upload->image = $request->image;
                $upload->updated_on = Server::getDateTime();
                $upload->updated_by = JwtHelper::getSesEmployeeId();
                $upload->status = 0;
                $upload->reject_reason = null;
                $upload->rejected_on = null;
                $upload->save();
                Log::channel("attachedImageUpload")->info('** end the personalized attachedImageUpload method **');
            }
            if ($request->service_id == 3) {
                $uploadDetails = PhotoFrameUploadModel::where('order_photoframe_upload_id', $request->id)->first();
                if (!empty($uploadDetails)) {
                    $ary = [];
                    $ary['id'] = $uploadDetails['order_photoframe_upload_id'];
                    $ary['image'] = $uploadDetails['image'];
                    $ary['image_url'] = ($uploadDetails['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $uploadDetails['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['status'] = $uploadDetails['status'];
                    $ary['approved_on'] = $uploadDetails['updated_on'];
                    $ary['received_on'] = $uploadDetails['updated_on'] == null ? $uploadDetails['created_on'] : $uploadDetails['updated_on'];
                    $ary['reject_reason'] = $uploadDetails['reject_reason'];
                    $ary['rejected_on'] = $uploadDetails['rejected_on'];
                    $ary['image_history'] = $this->photoFrameUploadHistory($uploadDetails['order_photoframe_upload_id']);
                    $final[] = $ary;
                }
            }
            if ($request->service_id == 4) {
                $uploadDetails = PersonalizedUploadModel::where('order_personalized_upload_id', $request->id)->first();
                if (!empty($uploadDetails)) {
                    $ary = [];
                    $ary['id'] = $uploadDetails['order_personalized_upload_id'];
                    $ary['image'] = $uploadDetails['image'];
                    $ary['status'] = $uploadDetails['status'];
                    $ary['approved_on'] = $uploadDetails['updated_on'];
                    $ary['received_on'] = $uploadDetails['updated_on'] == null ? $uploadDetails['created_on'] : $uploadDetails['updated_on'];
                    $ary['reject_reason'] = $uploadDetails['reject_reason'];
                    $ary['rejected_on'] = $uploadDetails['rejected_on'];
                    $ary['image_url'] = ($uploadDetails['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $uploadDetails['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['image_history'] = $this->personalizedUploadHistory($uploadDetails['order_personalized_upload_id']);
                    $final[] = $ary;
                }
            }
            if ($request->service_id == 6) {
                $uploadDetails = SelfieUploadModel::where('order_selfie_upload_id', $request->id)->first();
                if (!empty($uploadDetails)) {
                    $ary = [];
                    $ary['id'] = $uploadDetails['order_selfie_upload_id'];
                    $ary['image'] = $uploadDetails['image'];
                    $ary['image_url'] = ($uploadDetails['image'] != '') ? env('APP_URL') . env('ORDER_URL') . $uploadDetails['image'] : env('APP_URL') . "avatar.jpg";
                    $ary['status'] = $uploadDetails['status'];
                    $ary['approved_on'] = $uploadDetails['updated_on'];
                    $ary['received_on'] = $uploadDetails['updated_on'] == null ? $uploadDetails['created_on'] : $uploadDetails['updated_on'];
                    $ary['reject_reason'] = $uploadDetails['reject_reason'];
                    $ary['rejected_on'] = $uploadDetails['rejected_on'];
                    $ary['image_history'] = $this->selfieUploadHistory($uploadDetails['order_selfie_upload_id']);
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                Log::channel("attachedImageUpload")->info("request value attachedImageUpload_id:: $upload");
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Uploaded successfully'),
                    'data'        => $final
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Uploaded failed'),
                    'data'        => []
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
}
