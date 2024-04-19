<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Events\BulkOrderEnquiryCustomer;
use App\Events\BulkOrderEnquiryEvent;
use App\Helpers\Firebase;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Http\Requests\BulkOrderEnquiryRequest;
use App\Models\BulkOrderEnquiry;
use App\Models\BulkOrderTrackHistory;
use App\Models\Service;
use App\Models\UserModel;

class BulkOrderController extends Controller
{

    public function servicetypegetcall(Request $request)
    {
        $get_servicetype = Service::select('service_id', 'service_name')->get();

        if (!empty($get_servicetype)) {
            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('Service type listed successfully'),
                    'data' => $get_servicetype,
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

    public function bulkOrderCreate(BulkOrderEnquiryRequest $request)
    {
        try {
            Log::channel("bulkordermobile")->info('** started the bulkordermobile create method **');

            $enquiry = new BulkOrderEnquiry();
            $enquiry->platform = "Mobile";
            $enquiry->customer_type = $request->customer_type;
            $enquiry->company_name = $request->company_name;
            $enquiry->contact_person_name = $request->contact_person_name;
            $enquiry->email = $request->email;
            $enquiry->mobile_no = $request->mobile_no;
            $enquiry->alternative_mobile_no = $request->alternative_mobile_no;
            $enquiry->address = $request->address;
            $enquiry->message = $request->message;
            $enquiry->service_id = $request->service_id;
            $enquiry->state_id = $request->state_id;
            $enquiry->district_id = $request->district_id;
            $enquiry->created_on = Server::getDateTime();
            $enquiry->created_by = JwtHelper::getSesUserId();

            if ($enquiry->save()) {

                $enquiry_code = 'ENQ' . str_pad($enquiry->bulk_order_enquiry_id , 3, '0', STR_PAD_LEFT);
                $enquiry->enquiry_code = $enquiry_code;
                $enquiry->save();

                //Track History Create - New Enquiry created status
                $trackHistoryInsert = new BulkOrderTrackHistory();
                $trackHistoryInsert->bulk_order_enquiry_id = $enquiry->bulk_order_enquiry_id;
                $trackHistoryInsert->status = 1;
                $trackHistoryInsert->portal_type = 4;
                $trackHistoryInsert->created_on = Server::getDateTime();
                $trackHistoryInsert->save();
                Log::channel("bulkordermobile")->info("track history save value :: $trackHistoryInsert");

                $bulkOrderEnquiry = BulkOrderEnquiry::where('bulk_order_enquiry_id', $enquiry->bulk_order_enquiry_id)->first();

                if(!empty($request->service_id))
                {
                $service_name = $this->getServiceNameforList($request->service_id);
                }
                else
                {
                $service_name = "Nil";
                }
                // print_r($service_name);die;
                //mail send to Customer
                if($bulkOrderEnquiry->email != ""){
                    $mail_data = [];
                    $mail_data['contact_person_name'] = !empty($bulkOrderEnquiry->contact_person_name) ? $bulkOrderEnquiry->contact_person_name : $bulkOrderEnquiry->contact_person_name;
                    $mail_data['email'] = $bulkOrderEnquiry->email;
                    $mail_data['enquiry_id'] = $enquiry->enquiry_code;
                    
                    if ($bulkOrderEnquiry->email != '') {
                        event(new BulkOrderEnquiryCustomer($mail_data));
                    }
                }
                    //mail send to Admin

                    
                    $get_admin_email = UserModel::where('acl_user_id',1)->select('email')->first();
                    if($get_admin_email->email != ""){
                    $mail_data = [];
                    $mail_data['contact_person_name'] = !empty($bulkOrderEnquiry->contact_person_name) ? $bulkOrderEnquiry->contact_person_name : $bulkOrderEnquiry->contact_person_name;
                    $mail_data['email'] = $bulkOrderEnquiry->email;
                    $mail_data['admin_email'] = $get_admin_email->email;
                    $mail_data['mobile_no'] = $bulkOrderEnquiry->mobile_no;
                    $mail_data['address'] = $bulkOrderEnquiry->address;
                    $mail_data['enquiry_code'] = $bulkOrderEnquiry->enquiry_code;
                    $mail_data['company_name'] = $bulkOrderEnquiry->company_name;
                    $mail_data['service_id'] = $service_name;
                    
                    if ($get_admin_email->email != '') {
                        event(new BulkOrderEnquiryEvent($mail_data));
                    }
                } 

                        $title = "Bulk Order Enquiry created - $bulkOrderEnquiry->enquiry_code";
                        $body = "You have received a new bulk order enquiry from customer $enquiry_code";
                        $module = 'Bulk Order Enquiry create';
                        $page = 'bulk_order_enquiry_create_mobile';
                        $portal = 'admin';
                        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 9);
                        $data = [
                            'bulk_order_enquiry_id' => $enquiry->bulk_order_enquiry_id,
                            'platform' => "Mobile",
                            'customer_type' => $request->customer_type,
                            'company_name' => $request->company_name,
                            'random_id' => $random_id,
                            'page' => 'bulk_order_enquiry_create_mobile',
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
                        $token = UserModel::where('acl_user_id',1)->where('token', '!=', NULL)->select('token')->first();
                        $userId = JwtHelper::getSesUserId();
                        if (!empty($token)) {
                            $push = Firebase::sendSingle($token->token, $message);
                        }
                        $getdata = GlobalHelper::notification_create($title, $body, 1, $userId, 1, $module, $page, $portal, $data, $random_id);

                Log::channel("bulkordermobile")->info("save value :: $enquiry");
                Log::channel("bulkordermobile")->info('** end the bulkordermobile create method **');

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Bulk order created successfully'),
                    'data'        => []

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Bulk order creation failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("bulkordermobile")->info('** start the bulkordermobile error create method **');
            Log::channel("bulkordermobile")->error($exception);
            Log::channel("bulkordermobile")->info('** end the bulkordermobile error create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
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
}