<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Helpers\GlobalHelper;
use App\Http\Requests\ExpDeliveryDayRequest;
use Illuminate\Support\Facades\Log;
use App\Models\ExpectedDays;

class ExpecteddeliverydaysController extends Controller
{
    public function exp_days_update(ExpDeliveryDayRequest $request)
    {
        try {
            Log::channel("expdeliverydays")->info('** started the expdeliverydays update method **');

            ExpectedDays::truncate();
            
            $exp = new ExpectedDays();
            $exp->expected_delivery_days = $request->expected_delivery_days;
            $exp->updated_on = Server::getDateTime();
            $exp->updated_by = JwtHelper::getSesUserId();
            Log::channel("expdeliverydays")->info("request values ::expected_delivery_days => $exp->expected_delivery_days");


            if ($exp->save()) {

                $expected = ExpectedDays::where('expected_delivery_days_id', $exp->expected_delivery_days_id)->first();

                // log activity
                $desc =  'Expected delivery days ' . '(' . $exp->expected_delivery_days . ')' . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Expected Delivery Days');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                Log::channel("expdeliverydays")->info("save value :: $expected");
                Log::channel("expdeliverydays")->info('** end the expdeliverydays update method **');

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Expected delivery days updated successfully'),
                    'data'        => $expected

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Expected delivery days update failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("expdeliverydays")->error($exception);
            Log::channel("expdeliverydays")->info('** end the expdeliverydays update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function exp_days_list(Request $request)
    {
        try {
            Log::channel("expdeliverydays")->info('** started the expdeliverydays list method **');
            $exp_delivery = ExpectedDays::first();
            Log::channel("expdeliverydays")->info("result value :: $exp_delivery");
            Log::channel("expdeliverydays")->info('** end the expdeliverydays list method **');
            if (!empty($exp_delivery)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Expected delivery days listed successfully'),
                    'data' => $exp_delivery
                ]);
            } else {
            return response()->json([
                'keyword'      => 'success',
                'message'      => __('No data found'),
                'data'        => []
            ]);
        }
        } catch (\Exception $exception) {
            Log::channel("expdeliverydays")->error($exception);
            Log::channel("expdeliverydays")->info('** end the expdeliverydays list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
