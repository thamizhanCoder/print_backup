<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\GlobalHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\JwtHelper;
use App\Helpers\Server;
use App\Http\Requests\TermsAndConditionsRequest;
use App\Models\TermsAndConditions;

class TermsAndConditionController extends Controller
{
    public function update(TermsAndConditionsRequest $request)
    {
        try {
            Log::channel("termsandcondition")->info('** started the termsandcondition update method **');

            TermsAndConditions::where('service_id', $request->service_id)->delete();

            $terms = new TermsAndConditions();
            $terms->service_id = $request->service_id;
            $terms->description = $request->description;
            $terms->updated_on = Server::getDateTime();
            $terms->updated_by = JwtHelper::getSesUserId();

            if ($terms->save()) {

                $termsResponse = TermsAndConditions::where('terms_and_conditions_id', $terms->terms_and_conditions_id)->leftjoin('service', 'service.service_id', '=', 'terms_and_conditions.service_id')->select('terms_and_conditions.*', 'service.service_name')->first();

                Log::channel("termsandcondition")->info("save value :: $termsResponse");
                Log::channel("termsandcondition")->info('** end the termsandcondition update method **');

                // log activity
                $desc =  'Terms and condition ' . '(' . $termsResponse->service_name . ')' . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Terms and conditions');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Terms and condition updated successfully'),
                    'data'        => $termsResponse

                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Terms and condition update failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("termsandcondition")->info('** start the termsandcondition error update method **');
            Log::channel("termsandcondition")->error($exception);
            Log::channel("termsandcondition")->info('** end the termsandcondition error update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function view($id)
    {
        try {
            Log::channel("termsandcondition")->info('** started the termsandcondition view method **');

            $checkDetails = TermsAndConditions::where('service_id', $id)->first();

            if (!empty($checkDetails)) {
                $getDetails = TermsAndConditions::where('terms_and_conditions.service_id', $id)->leftjoin('service', 'service.service_id', '=', 'terms_and_conditions.service_id')->select('terms_and_conditions.*', 'service.service_name')->first();

                Log::channel("termsandcondition")->info("view value :: $getDetails");
                Log::channel("termsandcondition")->info('** end the termsandcondition view method **');

                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Terms and condition viewed successfully'),
                    'data'        => $getDetails

                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('No data found'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("termsandcondition")->info('** start the termsandcondition error view method **');
            Log::channel("termsandcondition")->error($exception);
            Log::channel("termsandcondition")->info('** end the termsandcondition error view method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
