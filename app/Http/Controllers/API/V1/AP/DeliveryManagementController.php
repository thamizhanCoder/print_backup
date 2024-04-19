<?php

namespace App\Http\Controllers\API\V1\AP;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Helpers\GlobalHelper;
use App\Http\Requests\DeliveryManagementRequest;
use App\Models\DeliveryManagement;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;

class DeliveryManagementController extends Controller
{

    public function DeliveryManagementUpdate(DeliveryManagementRequest $request)
    {
        try {
            Log::channel("deliveryManagement")->info('** started the delivery management update method **');

            $service_data = $this->slabDetailsValidation($request->delivery_charge_data);

            if ($service_data) {

                return response()->json(['keyword' => 'failed', 'message' => $service_data, 'data' => []]);
            }

            DeliveryManagement::where('service_id', $request->service_id)->delete();

            $deliverCharge = new DeliveryManagement();
            $deliverCharge->service_id = $request->service_id;
            $deliverCharge->slab_details = $request->delivery_charge_data;
            $deliverCharge->updated_on = Server::getDateTime();
            $deliverCharge->updated_by = JwtHelper::getSesUserId();
            $deliverCharge->save();

            Log::channel("deliveryManagement")->info("request value :: " . json_encode($request->delivery_charge_data, true));


            if ($deliverCharge->save()) {

                $getService_name = Service::where('service_id', $deliverCharge->service_id)->first();

                //log activity
                $desc =  'Delivery Management - ' . $getService_name->service_name . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Delivery Management');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                Log::channel("deliveryManagement")->info("save value :: $deliverCharge");
                Log::channel("deliveryManagement")->info('** end the delivery management update method **');

                return response()->json(['keyword' => 'success', 'message' => ('Delivery management updated successfully'), 'data' => $deliverCharge]);
            } else {

                return response()->json(['keyword' => 'failure', 'message' => __('message.failed'), 'data' => []]);
            }
        } catch (\Exception $exception) {
            Log::channel("deliveryManagement")->error($exception);
            Log::channel("deliveryManagement")->error('** error occured in deliver management update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function slabDetailsValidation($slabDetails)
    {

        $data = json_decode($slabDetails, true);

        if (!empty($data)) {

            foreach ($data as $d) {

                $validator = Validator::make($d, [
                    'weight_from' => 'required|numeric|regex:/^\d+(\.\d{1})?$/',
                    'weight_to' => 'required|numeric|regex:/^\d+(\.\d{1})?$/|gte:weight_from',
                    'amount' => 'required|numeric|regex:/^\d+(\.\d{2})?$/',
                ]);

                if ($validator->fails()) {
                    $errors = $validator->errors();
                    return $errors->first();
                }
            }
        }
    }

    public function deliveryManagement_view($id)
    {
        try {
            // log start *********
            Log::channel("deliveryManagement")->info("******* Delivery Management View Method Start *******");
            Log::channel("deliveryManagement")->info("Delivery Management Controller start:: find ID : $id");
            // log start *********

            if ($id != '' && $id > 0) {
                $data = DeliveryManagement::where('service_id', $id)->select('*')->first();

                if (!empty($data)) {

                    $ary = [];
                    $ary['delivery_management_id'] = $data->delivery_management_id;
                    $ary['service_id'] = $data->service_id;
                    $ary['slab_details'] = json_decode($data->slab_details, true);
                    $ary['updated_on'] = $data->updated_on;
                    $ary['updated_by'] = $data->updated_by;

                    // log end ***********
                    Log::channel("deliveryManagement")->info("Delivery Management Controller end:: save values :: id :: $id :: value:: " . json_encode($ary, true) . " ::::end");
                    Log::channel("deliveryManagement")->info("******* Delivery Management View Method End *******");
                    Log::channel("deliveryManagement")->info("********************************END !!!!!!!!!!!!!!******************************************** ");
                    // log end ***********

                    return response()->json([
                        'keyword' => 'success',
                        'message' => 'Delivery management viewed successfully',
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
        } catch (\Exception $exception) {

            // log for error start
            Log::channel("deliveryManagement")->error("******* Delivery Management View Method Error Start *******");
            Log::channel("deliveryManagement")->error($exception);
            Log::channel("deliveryManagement")->error("******* Delivery Management View Method Error End *******");
            // log for error end

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
