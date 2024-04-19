<?php

namespace App\Http\Controllers\API\V1\AP;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Helpers\GlobalHelper;
use App\Models\Couriercharge;
use App\Models\Service;

class DeliveryChargeController extends Controller
{

    public function DeliveryChargeUpdate(Request $request)
    {

        try {
            Log::channel("deliverCharge")->info('** started the deliverCharge update method **');
        
        $service_data = json_decode($request->delivery_charge_data, true);

        if (!empty($service_data)) {
           
            for ($i = 0; $i < count($service_data); $i++) {
                
                $deliverCharge = Service::find($service_data[$i]['service_id']);
                $deliverCharge->delivery_charge = $service_data[$i]['delivery_charge'];
                $deliverCharge->updated_on = Server::getDateTime();
                $deliverCharge->save();
             
        }

        Log::channel("deliverCharge")->info("request value :: $request->delivery_charge_data");
        }
           
        if (!empty($deliverCharge)) {

        $service = Service::get();

        //log activity
        $desc =  'Delivery charge is updated by ' . JwtHelper::getSesUserNameWithType() . '';
        $activitytype = Config('activitytype.Delivery charge');
        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


        Log::channel("deliverCharge")->info("save value :: $service");
        Log::channel("deliverCharge")->info('** end the delivery charge update method **');
        

        if (!empty($service)) {

            return response()->json(['keyword' => 'success', 'message' =>('Delivery charge updated successfully'), 'data' => $service]);
        }
    }
    else {

        return response()->json(['keyword' => 'failure', 'message' => __('message.failed'), 'data' => []]);
    }

    } catch (\Exception $exception) {
        Log::channel("deliverCharge")->error($exception);
        Log::channel("deliverCharge")->error('** error occured in deliverCharge update method **');

        return response()->json([
            'error' => 'Internal server error.',
            'message' => $exception->getMessage()
        ], 500);
    }

    }

    public function getDeliveryCharge(Request $request)
    {
            $data = Service::get();

            if (!empty($data)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => 'Delivery charge viewed successfully',
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failure',
                    'message' =>  __('No data found'),
                    'data' => []
                ]);
            }
    }


}