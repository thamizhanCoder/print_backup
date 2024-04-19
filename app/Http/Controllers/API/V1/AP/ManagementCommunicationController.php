<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\Firebase;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Controller\FileUploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Requests\ManageCommRequest;
use App\Http\Traits\OrderResponseTrait;
use App\Models\OrderItems;
use App\Models\Communication;
use App\Models\Service;
use App\Models\CommunicationInbox;
use App\Models\CustomTaskQcHistory;
use App\Models\Employee;
use App\Models\ManagementListModel;
use App\Models\Orders;
use App\Models\TaskManager;
use App\Models\TaskManagerHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class ManagementCommunicationController extends Controller
{
    use OrderResponseTrait;
    public function getdefaultImages_allImages($gTImage, $folder)
    {
        $imG = [];
        if (!empty($gTImage)) {
            foreach ($gTImage as $im) {
                $ary = [];
                $ary['image'] = $im['image'];
                $ary['image_url'] = ($im['image'] != '') ? env('APP_URL') . env('MANAGEMENT_URL') . $folder . '/' . $im['image'] : env('APP_URL') . "avatar.jpg";
                $imG[] = $ary;
            }
        }
        return $imG;
    }

    public function communication_list_old(Request $request)
    {
        try {
            Log::channel("managementcommunications")->info('** started the managementcommunications list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";

            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';


            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'created_on' => 'orders.created_on',
                'order_id' => 'orders.order_id',
                'order_code' => 'orders.order_code',
                'service_id' => 'service.service_id',
                'service_name' => 'service.service_name',
                'subject' => 'communication.subject',
                'status' => 'communication.status',

            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "communication_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'orders.created_on', 'order_items.order_id', 'orders.order_code',
                'service.service_id', 'service.service_name',
                'communication.subject', 'communication.status'
            );


            // $managementcommunicationss = Communication::leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'communication.task_manager_id')
            //     ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
            //     ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
            //     ->leftjoin('employee', 'employee.employee_id', '=', 'communication.created_by')
            //     ->leftjoin('department', 'department.department_id', '=', 'employee.department_id')
            //     ->leftjoin('communication_inbox', 'communication_inbox.communication_id', '=', 'communication.communication_id')
            //     ->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')
            //     ->leftjoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'communication.orderitem_stage_id')
            //     ->leftjoin('task_manager_history as th', 'th.task_manager_id', '=', 'communication.task_manager_id')
            //     // ->where('task_manager_history.production_status', 1)->where('th.production_status', 1)
            //     ->select('communication.*', 'communication_inbox.employee_id', 'employee.employee_name', 'order_items.product_id', 'order_items.product_code', 'order_items.product_name', 'service.service_name', 'orders.order_code', 'order_items.order_id', 'task_manager.task_code', 'task_manager.task_name', 'task_manager_history.work_stage', 'th.work_stage as custom_stage', 'department.department_name')
            //     ->groupBy('communication.communication_id');

            //     $managementcommunicationss->where(function ($query) {
            //     $query->whereIn('task_manager_history.production_status', [1]);
            //     $query->orwhereIn('th.production_status', [1]);
            // });
            DB::connection()->enableQueryLog();
            $get_first = Communication::leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'communication.task_manager_id')
                ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftjoin('employee', 'employee.employee_id', '=', 'communication.created_by')
                ->leftjoin('department', 'department.department_id', '=', 'employee.department_id')
                ->leftjoin('communication_inbox', 'communication_inbox.communication_id', '=', 'communication.communication_id')
                ->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')
                ->leftjoin('task_manager_history', 'task_manager_history.orderitem_stage_id', '=', 'communication.orderitem_stage_id')
                ->where('task_manager_history.production_status', 1)
                ->select('communication.*', 'communication_inbox.employee_id', 'employee.employee_name', 'order_items.product_id', 'order_items.product_code', 'order_items.product_name', 'service.service_name', 'orders.order_code', 'order_items.order_id', 'task_manager.task_code', 'task_manager.task_name', 'task_manager_history.work_stage', 'department.department_name')
                ->groupBy('communication.communication_id')->get();
            $data1 = collect($get_first);


            $get_two = Communication::leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'communication.task_manager_id')->where('communication.orderitem_stage_id', null)
                ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftjoin('employee', 'employee.employee_id', '=', 'communication.created_by')
                ->leftjoin('department', 'department.department_id', '=', 'employee.department_id')
                ->leftjoin('communication_inbox', 'communication_inbox.communication_id', '=', 'communication.communication_id')
                ->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')
                ->leftjoin('task_manager_history', 'task_manager_history.task_manager_id', '=', 'communication.task_manager_id')
                ->where('task_manager_history.production_status', 1)
                ->select('communication.*', 'communication_inbox.employee_id', 'employee.employee_name', 'order_items.product_id', 'order_items.product_code', 'order_items.product_name', 'service.service_name', 'orders.order_code', 'order_items.order_id', 'task_manager.task_code', 'task_manager.task_name', 'task_manager_history.work_stage', 'department.department_name')
                ->groupBy('communication.communication_id')->get();
            $data2 = collect($get_two);
            $merged = !empty($data2) ? $data1->merge($data2) : $data1;
            $queries = DB::getQueryLog();

            print_r($queries);
            die;
            // print_r($merged);exit;

            $managementcommunicationss->where(function ($query) use ($searchval, $column_search, $managementcommunicationss) {
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
                $managementcommunicationss->orderBy($order_by_key[$sortByKey], $sortType);
            }

            if (array_key_exists($sortByKey, $order_by_key) && in_array($sortType, $sort_dir)) {
                $managementcommunicationss->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $managementcommunicationss->where(function ($query) use ($from_date) {
                    $query->whereDate('communication.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $managementcommunicationss->where(function ($query) use ($to_date) {
                    $query->whereDate('communication.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
                $filterByStatus = json_decode($filterByStatus, true);
                $managementcommunicationss->whereIn('task_manager_history.work_stage', $filterByStatus);
                $managementcommunicationss->whereIn('th.work_stage', $filterByStatus);
            }
            $count = count($managementcommunicationss->get());
            if ($offset) {
                $offset = $offset * $limit;
                $managementcommunicationss->offset($offset);
            }
            if ($limit) {
                $managementcommunicationss->limit($limit);
            }
            Log::channel("managementcommunications")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $managementcommunicationss->orderBy('communication_id', 'DESC');
            $managementcommunicationss = $managementcommunicationss->get();

            if ($count > 0) {
                $final = [];
                foreach ($managementcommunicationss as $value) {
                    // $ary = [];
                    // $ary['communication_id'] = $value['communication_id'];
                    // $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    // $ary['from'] = $value['employee_id'];
                    // $ary['order_id'] = $value['order_code'];
                    // $ary['product_id'] = $value['product_code'];
                    // $ary['task_manager_id'] = $value['task_manager_id'];
                    // $ary['orderitem_stage_id'] = $value['orderitem_stage_id'];
                    // $ary['product_name'] = $value['product_name'];
                    // $ary['service_type'] = $value['service_name'];
                    // $ary['subject'] = $value['subject'];
                    // $ary['isCheckChatHistory'] = !empty($value['orderitem_stage_id']) ? "order" : "customtask";
                    // if ($value['status'] == 1) {
                    //     $ary['status'] = "completed";
                    // }
                    // if ($value['status'] == 0) {
                    //     $ary['status'] = "inprogress";
                    // }
                    $ary = [];
                    $ary['communication_id'] = $value['communication_id'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['from'] = $value['employee_name'];
                    $ary['code'] = !empty($value['order_code']) ? $value['order_code'] : $value['task_code'];
                    $ary['product_id'] = $value['product_code'];
                    $ary['name'] = !empty($value['product_name']) ? $value['product_name'] : $value['task_name'];
                    // $ary['service_type'] = $value['service_name'];
                    $ary['subject'] = $value['subject'];
                    $ary['department_name'] = $value['department_name'];
                    $ary['task_manager_id'] = $value['task_manager_id'];
                    $ary['orderitem_stage_id'] = $value['orderitem_stage_id'];
                    $ary['isCheckChatHistory'] = !empty($value['orderitem_stage_id']) ? "order" : "customtask";
                    $ary['work_stage'] = !empty($value['work_stage']) ? $value['work_stage'] : $value['custom_stage'];
                    // if ($value['status'] == 1) {
                    //     $ary['status'] = "completed";
                    // }
                    // if ($value['status'] == 0) {
                    //     $ary['status'] = "inprogress";
                    // }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("managementcommunications")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Management communications listed successfully'),
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
            Log::channel("managementcommunications")->error($exception);
            Log::channel("managementcommunications")->error('** end the managementcommunications list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function communication_list(Request $request)
    {
        try {
            Log::channel("managementcommunications")->info('** started the managementcommunications list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";

            $from_date = ($request->from_date) ? $request->from_date : '';
            $to_date = ($request->to_date) ? $request->to_date : '';
            $filterByStatus = ($request->filterByStatus) ? $request->filterByStatus : '';


            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'date' => 'management_list.created_on',
                'from' => 'management_list.employee_name',
                // 'code' => DB::raw('GROUP_CONCAT(CONCAT_WS(" ", management_list.order_code, management_list.task_code) SEPARATOR " ")'),
                'code' => 'management_list.order_code',
                'name' => DB::raw('GROUP_CONCAT(CONCAT_WS(" ", management_list.product_name, management_list.task_name) SEPARATOR " ")'),
                'product_id' => 'management_list.product_code',
                'subject' => 'management_list.subject',
                'department_name' => 'management_list.department_name'

            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "communication_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array(
                'management_list.created_on', 'management_list.employee_name', 'management_list.order_code', 'management_list.task_code', 'management_list.product_code', 'management_list.product_name', 'management_list.task_name',
                'management_list.subject', 'management_list.department_name'
            );
            $managementcommunicationss = ManagementListModel::select('*');


            $managementcommunicationss->where(function ($query) use ($searchval, $column_search, $managementcommunicationss) {
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
                $managementcommunicationss->orderBy($order_by_key[$sortByKey], $sortType);
            }
            if (!empty($from_date)) {
                $managementcommunicationss->where(function ($query) use ($from_date) {
                    $query->whereDate('management_list.created_on', '>=', $from_date);
                });
            }
            if (!empty($to_date)) {
                $managementcommunicationss->where(function ($query) use ($to_date) {
                    $query->whereDate('management_list.created_on', '<=', $to_date);
                });
            }
            if (!empty($filterByStatus) && $filterByStatus != ' ' && $filterByStatus != 'all') {
                $filterByStatus = json_decode($filterByStatus, true);
                $managementcommunicationss->whereIn('management_list.work_stage', $filterByStatus);
            }
            $count = count($managementcommunicationss->get());
            if ($offset) {
                $offset = $offset * $limit;
                $managementcommunicationss->offset($offset);
            }
            if ($limit) {
                $managementcommunicationss->limit($limit);
            }

            $managementcommunicationss->orderBy('communication_id', 'DESC');
            $managementcommunicationss = $managementcommunicationss->get();

            if ($count > 0) {
                $final = [];
                foreach ($managementcommunicationss as $value) {
                    $ary = [];
                    $ary['communication_id'] = $value['communication_id'];
                    $ary['date'] = date('d-m-Y', strtotime($value['created_on']));
                    $ary['from'] = $value['employee_name'];
                    $ary['code'] = !empty($value['order_code']) ? $value['order_code'] : $value['task_code'];
                    $ary['product_id'] = $value['product_code'];
                    $ary['name'] = !empty($value['product_name']) ? $value['product_name'] : $value['task_name'];
                    // $ary['service_type'] = $value['service_name'];
                    $ary['subject'] = $value['subject'];
                    $ary['department_name'] = $value['department_name'];
                    $ary['task_manager_id'] = $value['task_manager_id'];
                    $ary['orderitem_stage_id'] = $value['orderitem_stage_id'];
                    $ary['isCheckChatHistory'] = !empty($value['orderitem_stage_id']) ? "order" : "customtask";
                    $ary['work_stage'] = !empty($value['work_stage']) ? $value['work_stage'] : null;
                    // if ($value['status'] == 1) {
                    //     $ary['status'] = "completed";
                    // }
                    // if ($value['status'] == 0) {
                    //     $ary['status'] = "inprogress";
                    // }
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("managementcommunications")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Management communications listed successfully'),
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
            Log::channel("managementcommunications")->error($exception);
            Log::channel("managementcommunications")->error('** end the managementcommunications list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function communication_view(Request $request)
    {
        try {
            $id = $request->id;
            $type = $request->type;

            Log::channel("managementcommunication")->info('** started the managementcommunication view method **');
            Log::channel("managementcommunication")->info("request value managementcommunication_id:: $id");

            if ($type == "order") {
                $managementcommunication_view = Communication::where('communication.orderitem_stage_id', $id)
                    // ->whereIn('communication.status', [0, 1])
                    // ->whereIn('service.service_id', [6, 3, 4])
                    ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'communication.task_manager_id')
                    ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
                    ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                    ->leftjoin('communication_inbox', 'communication_inbox.communication_id', '=', 'communication.communication_id')
                    ->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')
                    ->select('communication.*', 'communication_inbox.employee_id', 'order_items.product_id', 'order_items.product_code', 'order_items.product_name', 'service.service_name', 'service.service_id', 'orders.order_code', 'order_items.order_id')->first();
            } else if ($type == "customtask") {
                $managementcommunication_view = Communication::where('communication.task_manager_id', $id)
                    // ->whereIn('communication.status', [0, 1])
                    // ->whereIn('service.service_id', [6, 3, 4])
                    ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'communication.task_manager_id')
                    ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
                    ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                    ->leftjoin('communication_inbox', 'communication_inbox.communication_id', '=', 'communication.communication_id')
                    ->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')
                    ->select('communication.*', 'communication_inbox.employee_id', 'order_items.product_id', 'order_items.product_code', 'order_items.product_name', 'service.service_name', 'service.service_id', 'orders.order_code', 'order_items.order_id')->first();
            }

            $final = [];
            if (!empty($managementcommunication_view)) {
                $ary = [];
                $ary['communication_id'] = $managementcommunication_view['communication_id'];
                $ary['date'] = date('d-m-Y', strtotime($managementcommunication_view['created_on']));
                $ary['from'] = $managementcommunication_view['employee_id'];
                $ary['order_id'] = $managementcommunication_view['order_code'];
                $ary['product_id'] = $managementcommunication_view['product_code'];
                $ary['product_name'] = $managementcommunication_view['product_name'];
                $ary['service_type'] = $managementcommunication_view['service_name'];
                $ary['subject'] = $managementcommunication_view['subject'];

                if ($managementcommunication_view['status'] == 1) {
                    $ary['status'] = "completed";
                }
                if ($managementcommunication_view['status'] == 0) {
                    $ary['status'] = "inprogress";
                }

                $managementcommunication_history = CommunicationInbox::where('communication_id', $managementcommunication_view['communication_id'])->leftjoin('employee', 'employee.employee_id', '=', 'communication_inbox.employee_id')->leftjoin('department', 'department.department_id', '=', 'employee.department_id')->leftjoin('acl_user', 'acl_user.acl_user_id', '=', 'communication_inbox.acl_user_id')->leftjoin('acl_role', 'acl_role.acl_role_id', '=', 'acl_user.acl_role_id')
                    ->select('communication_inbox.*', 'employee.employee_name', 'employee.employee_image', 'acl_user.name', 'acl_role.role_name', 'department.department_name')->get();


                $arye = [];
                $resultArray = [];
                if (!empty($managementcommunication_history)) {
                    // $gTImage = json_decode('attachments', true);
                    // if (!empty($gTImage)) {
                    foreach ($managementcommunication_history as $im) {
                        $arye['communication_inbox_id'] = $im['communication_inbox_id'];
                        $arye['communication_id'] = $im['communication_id'];
                        $arye['messages'] = $im['messages'];
                        $arye['employee_id'] = $im['employee_id'];
                        $arye['acl_user_id'] = $im['acl_user_id'];
                        $arye['reply_on'] = $im['reply_on'];
                        $arye['folder'] = $im['folder'];
                        $gTImage = json_decode($im['attachments'], true);
                        $arye['attachments'] = $this->getdefaultImages_allImages($gTImage, $im['folder']);
                        $arye['name'] = !empty($im['employee_name']) ? $im['employee_name'] : $im['name'];
                        $arye['role'] = !empty($im['department_name']) ? $im['department_name'] : $im['role_name'];
                        if (!empty($im['employee_id'])) {
                            $arye['profile_image'] = ($im['employee_image'] != '') ? env('APP_URL') . env('EMPLOYEE_URL') . $im['employee_image'] : env('APP_URL') . "avatar.jpg";
                        } else {
                            $arye['profile_image'] = env('APP_URL') . "avatar.jpg";
                        }


                        $resultArray[] = $arye;
                    }
                }

                $ary['history'] = $resultArray;
                $final[] = $ary;
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("managementcommunication")->info("view value :: $log");
                Log::channel("managementcommunication")->info('** end the managementcommunication view method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Management communications viewed successfully'),
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
            Log::channel("managementcommunication")->error($exception);
            Log::channel("managementcommunication")->info('** end the managementcommunication view method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function downloadZip(Request $request)
    {
        $zip = new ZipArchive;
        $folder = $request->folder;
        $fileName = $folder . '.zip';
        if ($zip->open(public_path($fileName), ZipArchive::CREATE) === TRUE) {
            $files = File::files(public_path('public/management/' . $folder));
            foreach ($files as $key => $value) {
                $relativeNameInZipFile = basename($value);
                $zip->addFile($value, $relativeNameInZipFile);
            }
            $zip->close();
        }
        return response()->download(public_path($fileName));
    }

    public function adminreply_create(Request $request)
    {
        try {
            $replystatus = new CommunicationInbox();
            $replystatus->communication_id = $request->communication_id;
            $replystatus->messages = $request->messages;
            $replystatus->attachments = $request->attachments;
            $replystatus->folder = $request->folder;
            $replystatus->acl_user_id = JwtHelper::getSesUserId();
            $replystatus->reply_on = Server::getDateTime();

            if ($replystatus->save()) {

                $manageDetails = CommunicationInbox::where('communication_id', $replystatus->communication_id)->where('communication_inbox.employee_id', '!=', NULL)->orderby('communication_inbox_id', 'desc')->leftjoin('employee', 'employee.employee_id', '=', 'communication_inbox.employee_id')->select('employee.employee_name', 'employee.employee_code')->first();

                $desc = JwtHelper::getSesUserNameWithType() . ' ' .'Replied to the ' . $manageDetails->employee_code . ' - ' . $manageDetails->employee_name;
                $activitytype = Config('activitytype.Management Communication');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                $title = "New message from admin";

                $body = "You received a new message from the admin";


                $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);

                $getCommDetails = Communication::where('communication_id', $request->communication_id)->first();

                $module = 'management_communication';
                $page = 'chat_with_admin';
                $portal = 'employee';

                $data = [
                    'communication_id' => $getCommDetails->communication_id,
                    'task_manager_id' => $getCommDetails->task_manager_id,
                    'orderitem_stage_id' => $getCommDetails->orderitem_stage_id,
                    'type' => !empty($getCommDetails->orderitem_stage_id) ? "order" : "customtask",
                    'page' => $page,
                    'url' => "employee/employee-task-manger/chat-with-admin?"
                ];

                $message = [
                    'title' => $title,
                    'body' => $body,
                    'page' => $page,
                    'data' => $data,
                    'portal' => $portal
                ];

                $token = Employee::where('employee_id', $getCommDetails->created_by)->where('fcm_token', '!=', NULL)->select('fcm_token', 'employee_id')->first();

                $employeeDetail = Employee::where('employee_id', $getCommDetails->created_by)->first();
                if (!empty($token)) {
                    $push = Firebase::sendSingle($token->fcm_token, $message);
                }
                $getdata = GlobalHelper::notification_create($title, $body, 1, 1, $employeeDetail->employee_id, $module, $page, $portal, $data, $random_id);
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Admin replied successfully'),
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Admin reply failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("replystatus")->error($exception);
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function stageHistoryDetails($id)
    {
        try {
            Log::channel("employeeitemview")->info('** started the employeeitemview view method **');
            $orderItem = TaskManagerHistory::where('task_manager_history.orderitem_stage_id', $id)->where('task_manager.order_items_id', '!=', '')->where('task_manager_history.production_status', 1)
                ->leftjoin('task_manager', 'task_manager.task_manager_id', '=', 'task_manager_history.task_manager_id')
                ->leftjoin('orderitem_stage', 'orderitem_stage.orderitem_stage_id', '=', 'task_manager_history.orderitem_stage_id')
                ->leftjoin('order_items', 'order_items.order_items_id', '=', 'task_manager.order_items_id')
                ->leftjoin('orders', 'orders.order_id', '=', 'order_items.order_id')
                ->leftjoin('service', 'service.service_id', '=', 'order_items.service_id')
                ->select('orders.order_id', 'orders.order_code', 'orders.order_date', 'order_items.order_items_id', 'order_items.pv_variant_attributes', 'order_items.service_id', 'order_items.thumbnail_image', 'order_items.product_name', 'order_items.product_code', 'order_items.quantity', 'order_items.background_color', 'order_items.image', 'order_items.images', 'order_items.photoprint_variant', 'order_items.frames', 'order_items.variant_attributes', 'order_items.variant_type_name', 'order_items.variant_label', 'order_items.designer_description', 'service.service_name', 'task_manager_history.*')
                ->first();
            Log::channel("employeeitemview")->info("request value task_manager_id:: $id");
            $final = [];
            if (!empty($orderItem)) {
                $ary = [];
                $ary['order_id'] = $orderItem['order_id'];
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
                $ary['photoprint_variant'] = $this->getPhotoPrintUpload($orderItem->order_items_id);
                $ary['frames'] = $this->getPhotoFrameUpload($orderItem->order_items_id);
                $ary['variant_attributes'] = $this->getPersonalizedUpload($orderItem->order_items_id, json_decode($orderItem->variant_attributes, true));
                $ary['variant_details'] = json_decode($orderItem->pv_variant_attributes, true);
                $ary['variant_type_name'] = $orderItem['variant_type_name'];
                $ary['variant_label'] = $orderItem['variant_label'];
                $ary['service_name'] = $orderItem['service_name'];
                $ary['assigned_on'] = $orderItem['assigned_on'];
                $ary['completed_on'] = $orderItem['completed_on'];
                $ary['expected_on'] = $orderItem['expected_on'];
                $ary['taken_on'] = $orderItem['taken_on'];
                $ary['work_stage'] = $orderItem['work_stage'];
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
                $ary['attachment_image'] = $this->getCustomdefaultImages_allImages($gTImage, $task_view['folder']);
                $ary['current_task_stage'] = $task_view['current_task_stage'];
                $ary['created_on'] = $task_view['created_on'];
                $ary['created_by'] = $task_view['created_by'];
                $ary['updated_on'] = $task_view['updated_on'];
                $ary['updated_by'] = $task_view['updated_by'];
                $ary['status'] = $task_view['status'];
                $gTImage = json_decode($task_view['qc_image'], true);
                $ary['currentQc_attachment_image'] = $this->getCustomdefaultImages_allImages($gTImage, $task_view['folder']);
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
    public function getCustomdefaultImages_allImages($gTImage, $folder)
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
}
