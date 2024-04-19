<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Models\GstPercentage;
use App\Helpers\GlobalHelper;
use App\Http\Requests\GstPercentageRequest;
use App\Models\ProductCatalogue;

class GstPercentageController extends Controller
{
    public function gstpercentage_create(GstPercentageRequest $request)
    {
        try {
            Log::channel("gstpercentage")->info('** started the gstpercentage create method **');
            $gstpercentage = new GstPercentage();
            $exist = GstPercentage::where([['gst_percentage', $request->gst_percentage], ['status', '!=', 2]])->first();

            if (empty($exist)) {
                $gstpercentage->gst_percentage = $request->gst_percentage;
                $gstpercentage->created_on = Server::getDateTime();
                $gstpercentage->created_by = JwtHelper::getSesUserId();
                Log::channel("gstpercentage")->info("request value :: $gstpercentage->gstpercentage");

                if ($gstpercentage->save()) {
                    $gstpercentages = GstPercentage::where('gst_percentage_id', $gstpercentage->gst_percentage_id)->first();

                    // log activity
                    $desc =  'GST Percentage '  . $gstpercentage->gst_percentage . ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Gst Percentage');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);




                    Log::channel("gstpercentage")->info("save value :: $gstpercentages");
                    Log::channel("gstpercentage")->info('** end the gstpercentage create method **');
                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('GST percentage created successfully'),
                        'data'        => [$gstpercentages]
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'message'      => __('GST percentage creation failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('GST percentage already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("gstpercentage")->error($exception);
            Log::channel("gstpercentage")->error('** end the gstpercentage create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function gstpercentage_list(Request $request)
    {
        try {
            Log::channel("gstpercentage")->info('** started the gstpercentage list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";
            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'gst_percentage' => 'gst_percentage.gst_percentage',
            ];
            $sort_dir = ['ASC', 'DESC'];
            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "gst_percentage_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");
            $column_search = array('gst_percentage.gst_percentage');
            $gstpercentages = GstPercentage::where([
                ['status', '!=', '2']
            ]);

            $gstpercentages->where(function ($query) use ($searchval, $column_search, $gstpercentages) {
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
                $gstpercentages->orderBy($order_by_key[$sortByKey], $sortType);
            }

            $count = $gstpercentages->count();

            if ($offset) {
                $offset = $offset * $limit;
                $gstpercentages->offset($offset);
            }
            if ($limit) {
                $gstpercentages->limit($limit);
            }
            Log::channel("gstpercentage")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");
            $gstpercentages->orderBy('gst_percentage_id', 'desc');
            $gstpercentages = $gstpercentages->get();
            if ($count > 0) {
                $final = [];
                foreach ($gstpercentages as $value) {
                    $ary = [];
                    $ary['gst_percentage_id'] = $value['gst_percentage_id'];
                    $ary['gst_percentage'] = $value['gst_percentage'];
                    $ary['created_on'] = $value['created_on'];
                    $ary['created_by'] = $value['created_by'];
                    $ary['updated_on'] = $value['updated_on'];
                    $ary['updated_by'] = $value['updated_by'];
                    $ary['status'] = $value['status'];
                    $final[] = $ary;
                }
            }
            if (!empty($final)) {
                $log = json_encode($final, true);
                Log::channel("gstpercentage")->info("list value :: $log");
                Log::channel("gstpercentage")->info('** end the gstpercentage list method **');
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('GST percentage listed successfully'),
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
            Log::channel("gstpercentage")->error($exception);
            Log::channel("gstpercentage")->error('** end the gstpercentage list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function gstpercentage_update(GstPercentageRequest $request)
    {
        try {
            Log::channel("gstpercentage")->info('** started the gstpercentage update method **');

            $exist = GstPercentage::where([['gst_percentage_id', '!=', $request->gst_percentage_id],['gst_percentage', $request->gst_percentage], ['status', '!=', 2]])->first();

            if (empty($exist)) {
                $gstpercentagesoldDetails = GstPercentage::where('gst_percentage_id', $request->gst_percentage_id)->first();

                $ids = $request->gst_percentage_id;
                $gstpercentage = GstPercentage::find($ids);
                $gstpercentage->gst_percentage = $request->gst_percentage;
                $gstpercentage->updated_on = Server::getDateTime();
                $gstpercentage->updated_by = JwtHelper::getSesUserId();
                Log::channel("gstpercentage")->info("request value :: $gstpercentage->gstpercentage");

                if ($gstpercentage->save()) {
                    $gstpercentages = GstPercentage::where('gst_percentage_id', $gstpercentage->gst_percentage_id)->first();

                    // log activity
                    $desc =  'GST Percentage ' . $gstpercentagesoldDetails->gst_percentage  . ' is updated as ' . $gstpercentage->gst_percentage  . ' by '. JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Gst Percentage');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);




                    Log::channel("gstpercentage")->info("save value :: $gstpercentages");
                    Log::channel("gstpercentage")->info('** end the gstpercentage update method **');

                    return response()->json([
                        'keyword'      => 'success',
                        'data'        => [$gstpercentages],
                        'message'      => __('GST percentage updated successfully')
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failed',
                        'data'        => [],
                        'message'      => __('GST percentage update failed')
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failed',
                    'message'      => __('GST percentage already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("gstpercentage")->error($exception);
            Log::channel("gstpercentage")->error('** end the gstpercentage update method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function gstpercentage_view($id)
    {
        try {
            Log::channel("gstpercentage")->info('** started the gstpercentage view method **');
            if ($id != '' && $id > 0) {
                $get_gstpercentage = GstPercentage::where('gst_percentage_id', $id)->get();
                Log::channel("gstpercentage")->info("request value gst_percentage_id:: $id");
                $count = $get_gstpercentage->count();
                if ($count > 0) {
                    $final = [];
                    foreach ($get_gstpercentage as $value) {
                        $ary = [];
                        $ary['gst_percentage_id'] = $value['gst_percentage_id'];
                        $ary['gst_percentage'] = $value['gst_percentage'];
                        $ary['created_on'] = $value['created_on'];
                        $ary['created_by'] = $value['created_by'];
                        $ary['updated_on'] = $value['updated_on'];
                        $ary['updated_by'] = $value['updated_by'];
                        $ary['status'] = $value['status'];
                        $final[] = $ary;
                    }
                }
                if (!empty($final)) {
                    $log = json_encode($final, true);
                    Log::channel("gstpercentage")->info("view value :: $log");
                    Log::channel("gstpercentage")->info('** end the gstpercentage view method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('GST percentage viewed successfully'),
                        'data' => $final
                    ]);
                } else {
                    return response()->json([
                        'keyword' => 'failed',
                        'message' => __('No data found'),
                        'data' => []
                    ]);
                }
            }
        } catch (\Exception $exception) {
            Log::channel("gstpercentage")->error($exception);
            Log::channel("gstpercentage")->info('** end the gstpercentage view method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function gstpercentage_delete(Request $request)
    {
        try {
            if (!empty($request)) {
                $ids = $request->id;

                $exist = ProductCatalogue::where('gst_percentage', $ids)->where('status', '!=', 2)->first();
        if (empty($exist)) {

                    Log::channel("gstpercentage")->info('** started the gstpercentage delete method **');
                    Log::channel("gstpercentage")->info("request value gst_percentage_id:: $ids :: ");
                    $gstpercentage = GstPercentage::where('gst_percentage_id', $ids)->first();
                    $update = GstPercentage::where('gst_percentage_id', $ids)->update(array(
                        'status' => 2,
                        'updated_on' => Server::getDateTime(),
                        'updated_by' => JwtHelper::getSesUserId()
                    ));

                    // log activity
    $desc =  'GST Percentage '  . $gstpercentage->gst_percentage  . ' is deleted by ' . JwtHelper::getSesUserNameWithType() . '';
    $activitytype = Config('activitytype.Gst Percentage');
    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);



                    Log::channel("gstpercentage")->info("save value :: gst_percentage_id :: $ids :: gstpercentage deleted successfully");
                    Log::channel("gstpercentage")->info('** end the gstpercentage delete method **');
                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  __('GST percentage deleted successfully'),
                        'data' => []
                    ]);
                } else {
                    return response()->json([
                      'keyword' => 'failed',
                      'message' =>  __('GST percentage is already used in product catalogue'),
                      'data' => []
                    ]);
                  }
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.failed'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("gstpercentage")->error($exception);
            Log::channel("gstpercentage")->info('** end the gstpercentage delete method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}