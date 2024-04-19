<?php

namespace App\Http\Controllers\API\V1\AP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use Illuminate\Support\Str;
use \Firebase\JWT\JWT;
use App\Helpers\GlobalHelper;
use App\Models\District;
use App\Models\State;

class LocationController extends Controller
{
    public function getState(Request $request)
    {
        $country_id = $request->country_id;
        $state_list = State::orderBy('state_name', 'ASC')->get();
        $state_ary = [];

        if (!empty($state_list)) {
            foreach ($state_list as $row) {

                $allData = [];

                $allData['country_id'] = $row->country_id;
                $allData['state_id'] = $row->state_id;
                $allData['name'] = $row->state_name;

                $state_ary[] = $allData; 
            }

            return response()->json(['keyword' => 'success', 'message' => 'State listed successfully', 'data' => $state_ary]);
        } else {

            return response()->json(['keyword' => 'success', 'message' => 'No data found', 'data' => $state_ary]);
        }
    }

    public function getCity(Request $request)
    {
        $state_id = $request->state_id;
        $city_list = District::where('state_id', $state_id)->get();
        $city_ary = [];

        if (!empty($city_list)) {
            foreach ($city_list as $row) {

                $allData = [];

                $allData['state_id'] = $row->state_id;
                $allData['district_id'] = $row->district_id;
                $allData['city_name'] = $row->district_name;

                $city_ary[] = $allData;
            }

            return response()->json(['keyword' => 'success', 'message' => 'City listed successfully', 'data' => $city_ary]);
        } else {

            return response()->json(['keyword' => 'success', 'message' => 'No data found', 'data' => $city_ary]);
        }
    }
    
    public function state(Request $request)
    {
        $get_state = DB::table("state")->select('state_id','state_name as name')->orderBy('state_name','asc')->get();

        if (!empty($get_state)) {
            return response()->json([
                'keyword' => 'success',
                'message' => 'State Viewed Success',
                'data' => $get_state,
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('message.no_data'),
                'data' => []
            ]);
        }
    }


    public function country(Request $request)
    {
        $get_country = DB::table("country")->select('country_id','name')->where('country_id', 101)->get();

        if (!empty($get_country)) {
            return response()->json([
                'keyword' => 'success',
                'message' => 'Country Viewed Success',
                'data' => $get_country,
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('message.no_data'),
                'data' => []
            ]);
        }
    }

    public function district(Request $request)
    {
        $get_district = DB::table("district")->select('district_id','district_name')->whereIn('state_id', ['2', '31', '36', '35', '19', '17'])->get();

        $count = $get_district->count();
        if (!empty($get_district)) {
            return response()->json([
                'keyword' => 'success',
                'message' => 'District Viewed Success',
                'data' => $get_district,
                'count' => $count
            ]);
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('message.no_data'),
                'data' => []
            ]);
        }
    }
}
