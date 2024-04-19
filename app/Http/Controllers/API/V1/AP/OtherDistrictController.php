<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\GlobalHelper;
use App\Helpers\JwtHelper;
use App\Models\OtherDistrict;
use App\Helpers\Server;
use App\Models\District;
use App\Models\Ticket;
use App\Http\Requests\OtherDistrictRequest;
use App\Models\Customer;

class OtherDistrictController extends Controller
{
    public function otherdistrict_list(Request $request)

    {
        try {
            Log::channel("otherdistrict")->info('** started the otherdistrict list method **');

            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'..
                'district' => 'other_district.district',
                'state_id' => 'other_district.state_id',
                'state_name' => 'state.state_name'
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "other_district_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('other_district.district', 'other_district.state_id', 'state.state_name');

            $otherdistrict =  OtherDistrict::where('status', '!=', 2)->select('other_district.*','state.state_name')
            ->leftjoin('state','state.state_id','=','other_district.state_id');

            $otherdistrict->where(function ($query) use ($searchval, $column_search, $otherdistrict) {
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
                $otherdistrict->orderBy($order_by_key[$sortByKey], $sortType);
            }
            $count = $otherdistrict->count();
            if ($offset) {
                $offset = $offset * $limit;
                $otherdistrict->offset($offset);
            }
            if ($limit) {
                $otherdistrict->limit($limit);
            }
            Log::channel("otherdistrict")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $otherdistrict->orderBy('other_district_id', 'desc');
            $otherdistrict = $otherdistrict->get();
            if ($count > 0) {
                $final = [];
                foreach ($otherdistrict as $value) {
                    $ary = [];
                    $ary['other_district_id'] = $value['other_district_id'];
                    $ary['district'] = $value['district'];
                    $ary['state_id'] = $value['state_id'];
                    $ary['state_name'] = $value['state_name'];
                    $ary['created_on'] = $value['created_on'];
                    $ary['created_by'] = $value['created_by'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['updated_by'] = $value['updated_by'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }

            if (!empty($final)) {
                $impl = json_encode($final, true);
                Log::channel("otherdistrict")->info("otherdistrict Controller end:: save values :: $impl ::::end");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Other district listed successfully'),
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
            Log::channel("otherdistrict")->error($exception);
            Log::channel("otherdistrict")->error('** end the otherdistrict list method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function otherdistrict_update(OtherDistrictRequest $request)
    {
        try {

            Log::channel("otherdistrictsetting")->info('** started the otherdistrictsetting create method **');

            $exist = District::where([
                ['district_name', '=', $request->district],
                ['state_id', '=', $request->state_id]
            ])->first();

            if (empty($exist)) {
                $otherdistrictsOldDetails = OtherDistrict::where('other_district_id', $request->input('other_district_id'))->select('other_district.*')->first();
                $ids = $request->input('other_district_id');
                $otherdistrict = OtherDistrict::find($ids);
                $otherdistrict->district = $request->district;
                $otherdistrict->state_id = $request->state_id;
                $otherdistrict->status = 1;
                $otherdistrict->updated_on = Server::getDateTime();
                $otherdistrict->updated_by = JwtHelper::getSesUserId();

                if ($otherdistrict->save()) {

                $district = new District();
                $district->district_name = $request->district;
                $district->state_id = $request->state_id;
                $district->save();

                //Update by muthuselvam
                $otherdistricts = OtherDistrict::where('other_district_id', $otherdistrict->other_district_id)
                ->select('other_district.*')->first();

                $getdistrict_Id = District::where('district_id',$district->district_id)->first();
                $getCus_id = OtherDistrict::where('other_district_id',$ids)->first();

                $otherDistrictUpd_cus = Customer::find($getCus_id->created_by);
                $otherDistrictUpd_cus->billing_city_id = $getdistrict_Id->district_id;
                $otherDistrictUpd_cus->save();

              // log activity
              $desc =  $otherdistricts->district  . ' is moved to live by ' . JwtHelper::getSesUserNameWithType() . '';
              $activitytype = Config('activitytype.Other District');
              GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

              if($request->status == 0){
                $desc =  $otherdistrictsOldDetails->district . ' is updated as ' . '(' . $otherdistricts->district . ')' . ' by ' .JwtHelper::getSesUserNameWithType() . '';
              $activitytype = Config('activitytype.Other District');
              GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
              }


                    Log::channel("otherdistrictsetting")->info("other district value :: $otherdistricts");
                    Log::channel("otherdistrictsetting")->info("district value :: $district");

                    Log::channel("otherdistrictsetting")->info("save value :: $otherdistricts");
                    Log::channel("otherdistrictsetting")->info('** end the otherdistrictsetting create method **');

                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('Other district updated successfully'),
                        'data'        => [$otherdistricts]
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('Other district update failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('District name already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("otherdistrictsetting")->error($exception);
            Log::channel("otherdistrictsetting")->error('** end the otherdistrictsetting create method **');
            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}