<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\ActivityType;
use Illuminate\Support\Facades\DB;

class LogActivityContoller extends Controller
{
    public function logactivitytype_list(Request $request)
    {
        $get = ActivityType::get();
        return response()->json([
            'keyword' => 'success',
            'message' => __('Activity log list'),
            'data' => $get,
        ]);
    }
    public function logactivity_list(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $filterType = ($request->filterType) ? $request->filterType : '[]';
        $filterUser = ($request->filterUser) ? $request->filterUser : '[]';

        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'description' => 'activity_log.description',
        ];

        $sort_dir = ['ASC', 'DESC'];

        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

        $column_search = array('activity_log.description');

        $uniqueDates = ActivityLog::select(DB::raw('DATE(`created_on`) as date'))->groupBy('date')->orderBy('date', 'desc')->get();

        $roles = ActivityLog::leftJoin('activity_type', 'activity_type.activity_type_id', '=', 'activity_logs.activity_type')
            ->leftJoin('activity_portal', 'activity_portal.activity_portal_id', '=', 'activity_logs.activity_portal')
            ->leftjoin('acl_user', 'activity_logs.created_by', '=', 'acl_user.acl_user_id')
            ->select('activity_portal.portal_name',  'acl_user.name', 'acl_user.email', 'acl_user.mobile_no', 'activity_type.activity_type_name', 'activity_logs.*', DB::raw('DATE(activity_logs.created_on) as date'));

        $roles->where(function ($query) use ($searchval, $column_search, $roles) {

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
            $roles->orderBy($order_by_key[$sortByKey], $sortType);
        }

        if (!empty($from_date)) {
            $roles->where(function ($query) use ($from_date) {
                $query->whereDate('activity_logs.created_on', '>=', $from_date);
            });
        }

        if (!empty($to_date)) {
            $roles->where(function ($query) use ($to_date) {
                $query->whereDate('activity_logs.created_on', '<=', $to_date);
            });
        }

        if ($filterType != '[]') {
            $filterType = json_decode($filterType, true);
            $roles->whereIn('activity_type', $filterType);
        }
        if ($filterUser != '[]') {
            $filterUser = json_decode($filterUser, true);
            $roles->whereIn('created_by', $filterUser);
        }
        $get_count = $roles->count();
        if ($offset) {
            $offset = $offset * $limit;
            $roles->offset($offset);
        }

        if ($limit) {
            $roles->limit($limit);
        }

        $roles->orderBy('id', 'desc');

        // $roles = $roles->get();

        $activityCollection = $roles->get()->map(function ($value) {

            return $value;
        });

        $result = $uniqueDates->map(function ($c) use ($activityCollection) {
            $c['list'] = $this->getByDate($activityCollection, $c['date']);

            return $c;
        });
        if($get_count > 0)
        {
        return response()->json([
            'keyword' => 'success',
            'message' => __('Activity log list'),
            'data' => $result,
            'count' => $get_count
        ]);
        }
        else
        {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('No data found'),
            ]);
        }
    }
    public function getByDate($activityList, $date)
    {

        $rs = [];

        foreach ($activityList as $act) {

            if ($act['date'] == $date) {;
                $rs[] = $act;
            }
        }
        return  $rs;
    }
}
