<?php

namespace App\Http\Controllers\API\V1\MP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Models\Contest;
use App\Helpers\GlobalHelper;
use Illuminate\Support\Facades\Log;
use App\Models\Selfiecontest;

class ContestController extends Controller
{
    public function contest_create(Request $request)
    {
        try {
            Log::channel("contest")->info('** started the contest create method **');
            $contest = new Contest();

            $contest->contest_name = $request->contest_name;
            $contest->contest_image = $request->contest_image;
            $contest->valid_from = ($request->input('valid_from')) ? date('Y-m-d H:i:s', strtotime($request->input('valid_from'))) : '';
            $contest->valid_to = $request->valid_to;
            $contest->requirement_details = $request->requirement_details;
            $contest->status = $request->status;
            $contest->created_on = Server::getDateTime();
            $contest->created_by = JwtHelper::getSesUserId();
            Log::channel("contest")->info("request value :: $contest->contest_name");

            if ($contest->save()) {
                $contests = Contest::where('contest_id', $contest->contest_id)->first();

                // log activity
                // $desc =  'contest ' . $contest->contest_name . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                // $activitytype = Config('activitytype.Contest');
                // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                Log::channel("contest")->info("save value :: $contests->contest_name");
                return response()->json([
                    'keyword'      => 'success',
                    'message'      => __('Contest created successfully'),
                    'data'        => [$contests]
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('Contest creation failed'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("contest")->error($exception);
            Log::channel("contest")->error('** end the contest create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function contest_update(Request $request)
    {
        try {
            Log::channel("contest")->info('** started the contest update method **');

            $ids = $request->contest_id;
            $contest = Contest::find($ids);
            $contest->contest_name = $request->contest_name;
            $contest->contest_image = $request->contest_image;
            $contest->valid_from = ($request->input('valid_from')) ? date('Y-m-d H:i:s', strtotime($request->input('valid_from'))) : '';
            $contest->valid_to = $request->valid_to;
            $contest->requirement_details = $request->requirement_details;
            $contest->status = $request->status;
            $contest->updated_on = Server::getDateTime();
            $contest->updated_by = JwtHelper::getSesUserId();
            Log::channel("contest")->info("request value :: $contest->contest_name");

            if ($contest->save()) {
                $contests = Contest::where('contest_id', $contest->contest_id)->first();

                // log activity
                // $desc =   $contest->contest_name . ' contest ' . ' is updated by ' . JwtHelper::getSesUserNameWithType() . '';
                // $activitytype = Config('activitytype.Contest');
                // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                Log::channel("contest")->info("save value :: $contests->contest_name");
                Log::channel("contest")->info('** end the contest update method **');

                return response()->json([
                    'keyword'      => 'success',
                    'data'        => [$contests],
                    'message'      => __('Contest updated successfully')
                ]);
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'data'        => [],
                    'message'      => __('Contest update failed')
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("contest")->error($exception);
            Log::channel("contest")->error('** end the contest update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}
