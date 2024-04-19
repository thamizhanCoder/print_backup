<?php

namespace App\Http\Controllers\API\V1\AP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use Illuminate\Support\Str;
use \Firebase\JWT\JWT;
use App\Helpers\GlobalHelper;
use App\Models\Category;
use App\Models\Cod;
use Illuminate\Support\Facades\Log;
use App\Models\ShippedVendorDetails;
use App\Http\Requests\ShippedVendorDetailsRequest;

class ShippedVendorDetailsController extends Controller
{
    public function shippedVendorDetails_create(ShippedVendorDetailsRequest $request)
    {
        try {
            Log::channel("shippedvendordetails")->info('** started the shippedvendordetails create method **');
            $courier_name = ShippedVendorDetails::where([
                ['courier_name', '=', $request->courier_name],
                ['status', '!=', 2]
            ])->first();
            if (empty($courier_name)) {

                $courier_url = ShippedVendorDetails::where([
                    ['courier_url', '=', $request->courier_url],
                    ['status', '!=', 2]
                ])->first();
                if (empty($courier_url)) {

                    $vendor = new ShippedVendorDetails();
                    $vendor->courier_name = $request->input('courier_name');
                    $vendor->courier_url = $request->input('courier_url');
                    $vendor->created_on = Server::getDateTime();
                    $vendor->created_by = JwtHelper::getSesUserId();

                    if ($vendor->save()) {

                        $vendors = ShippedVendorDetails::where('shipped_vendor_details_id', $vendor->shipped_vendor_details_id)
                            ->select('shipped_vendor_details.*')
                            ->first();
                        Log::channel("photoprintsettings")->info("save value :: $vendors");

                        // log activity
                        $desc = $vendor->courier_name . ' Shipped Vendor' . ' created by ' . JwtHelper::getSesUserNameWithType() . '';
                        $activitytype = Config('activitytype.Shipped Vendor');
                        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        // $mail_data = [];
                        // $mail_data['name'] = $request->input('name');
                        // $mail_data['role_name'] = $users->role_name;
                        // $mail_data['email'] = $request->input('email');
                        // $mail_data['password'] = $request->input('password');
                        // event(new UserWelcome($mail_data));

                        return response()->json([
                            'keyword'      => 'success',
                            'message'      => __('Shipped vendor details created successfully'),
                            'data'        => [$vendors]

                        ]);
                    } else {
                        return response()->json([
                            'keyword'      => 'failure',
                            'message'      => __('Shipped vendor details creation failed'),
                            'data'        => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword'      => 'failure',
                        'message'      => __('Shipped vendor details URL already exist'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Shipped vendor details name already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("shippedvendordetails")->error($exception);
            Log::channel("shippedvendordetails")->error('** end the shippedvendordetails create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    public function shippedVendorDetails_update(ShippedVendorDetailsRequest $request)
    {
        try {
            Log::channel("shippedvendordetails")->info('** started the shippedvendordetails update method **');
            $courier_name = ShippedVendorDetails::where([
                ['shipped_vendor_details_id', '!=', $request->shipped_vendor_details_id],
                ['courier_name', '=', $request->courier_name],
                ['status', '!=', 2],
                ['shipped_vendor_details_id', '!=', $request->input('shipped_vendor_details_id')]
            ])->first();
            if (empty($courier_name)) {

                $courier_url = ShippedVendorDetails::where([
                    ['shipped_vendor_details_id', '!=', $request->shipped_vendor_details_id],
                    ['courier_url', '=', $request->courier_url],
                    ['status', '!=', 2],
                    ['shipped_vendor_details_id', '!=', $request->input('shipped_vendor_details_id')]
                ])->first();
                if (empty($courier_url)) {

                    $vendor = new ShippedVendorDetails();
                    $ids = $request->shipped_vendor_details_id;
                    $vendor = ShippedVendorDetails::find($ids);
                    $vendor->courier_name = $request->input('courier_name');
                    $vendor->courier_url = $request->input('courier_url');
                    $vendor->updated_on = Server::getDateTime();
                    $vendor->updated_by = JwtHelper::getSesUserId();

                    if ($vendor->save()) {
                        $vendors = ShippedVendorDetails::where('shipped_vendor_details_id', $vendor->shipped_vendor_details_id)->select('shipped_vendor_details.*')->first();

                        // log activity
                        $desc = $vendor->courier_name . ' Shipped vendor details' . ' updated by ' . JwtHelper::getSesUserNameWithType() . '';
                        $activitytype = Config('activitytype.Shipped Vendor');
                        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                        Log::channel("photoprintsettings")->info("save value :: $vendors");
                        Log::channel("photoprintsettings")->info('** end the photoprintsettings update method **');


                        return response()->json([
                            'keyword'      => 'success',
                            'message'      => __('Shipped vendor details updated successfully'),
                            'data'        => [$vendors]
                        ]);
                    } else {
                        return response()->json([
                            'keyword'      => 'failure',
                            'message'      => __('Shipped vendor details updated failed'),
                            'data'        => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'keyword'      => 'failure',
                        'message'      => __('Shipped vendor details url already exist'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('Shipped vendor details name already exist'),
                    'data'        => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("shippedvendordetails")->error($exception);
            Log::channel("shippedvendordetails")->error('** end the shippedvendordetails create method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function shippedVendorDetails_list(Request $request)
    {
        try {
            Log::channel("shippedvendordetails")->info('** started the shippedvendordetails list method **');
            $limit = ($request->limit) ? $request->limit : '';
            $offset = ($request->offset) ? $request->offset : '';
            $searchval = ($request->searchWith) ? $request->searchWith : "";

            $order_by_key = [
                // 'mention the api side' => 'mention the mysql side column'
                'courier_name' => 'shipped_vendor_details.courier_name',
                'courier_url' => 'shipped_vendor_details.courier_url'
            ];

            $sort_dir = ['ASC', 'DESC'];

            $sortByKey = ($request->sortByKey) ? $request->sortByKey : "shipped_vendor_details_id";
            $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

            $column_search = array('shipped_vendor_details.courier_name', 'shipped_vendor_details.courier_url');



            $get_cod = ShippedVendorDetails::where([
                ['shipped_vendor_details.status', '!=', 2]
            ])
                ->select('shipped_vendor_details.*');

            $get_cod->where(function ($query) use ($searchval, $column_search, $get_cod) {
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
                $get_cod->orderBy($order_by_key[$sortByKey], $sortType);
            }

            $count = $get_cod->count();

            if ($offset) {
                $offset = $offset * $limit;
                $get_cod->offset($offset);
            }

            if ($limit) {
                $get_cod->limit($limit);
            }
            Log::channel("shippedvendordetails")->info("request value :: $limit :: $offset :: $searchval :: $sortByKey :: $sortType");

            $get_cod->orderBy('shipped_vendor_details.created_on', 'desc');
            $get_cod = $get_cod->get();


            if ($count > 0) {
                $final = [];
                foreach ($get_cod as $value) {
                    $ary = [];
                    $ary['shipped_vendor_details_id'] = $value['shipped_vendor_details_id'];
                    $ary['courier_name'] = $value['courier_name'];
                    $ary['courier_url'] = $value['courier_url'];
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
                Log::channel("shippedvendordetails")->info("list value :: $log");
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('Shipped vendor details listed successfully'),
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
            Log::channel("shippedvendordetails")->error($exception);
            Log::channel("shippedvendordetails")->error('** end the shippedvendordetails list method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function shippedVendorDetails_status(Request $request)
    {
        try {
            Log::channel("shippedvendordetails")->info('** started the shippedvendordetails status method **');
            // if (!empty($request)) {
            $ids = $request->id;
            $ids = json_decode($ids, true);
            if (!empty($ids)) {

                Log::channel("shippedvendordetails")->info('** started the shippedvendordetails status method **');



                $vendor = ShippedVendorDetails::where('shipped_vendor_details_id', $ids)->first();
                $update = ShippedVendorDetails::whereIn('shipped_vendor_details_id', $ids)->update(array(
                    'status' => $request->status,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));

                // log activity
                if ($request->status == 0) {
                    $activity_status = 'inactivated';
                } else if ($request->status == 1) {
                    $activity_status = 'activated';
                } else if ($request->status == 2) {
                    $activity_status = 'deleted';
                }
                $implode = implode(",", $ids);
                $desc = $vendor->courier_name . ' Shipped vendor details ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Shipped Vendor');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                if ($request->status == 0) {
                    Log::channel("shippedvendordetails")->info("shipped vendor inactive successfull");
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('Shipped vendor details inactivated successfully'),
                        'data' => []
                    ]);
                } else if ($request->status == 1) {
                    Log::channel("shippedvendordetails")->info("shipped vendor active successfull");
                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  __('Shipped vendor details activated successfully'),
                        'data' => []
                    ]);
                } else if ($request->status == 2) {
                    Log::channel("shippedvendordetails")->info("shipped vendor delete successfull");
                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  __('Shipped vendor details deleted successfully'),
                        'data' => []
                    ]);
                }
            } else {
                return response()
                    ->json([
                        'keyword' => 'failed',
                        'message' => __('Shipped vendor details failed'),
                        'data' => []
                    ]);
            }
        } catch (\Exception $exception) {
            Log::channel("shippedvendordetails")->error($exception);
            Log::channel("shippedvendordetails")->info('** end the shippedvendordetails status method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function shippedVendorDetails_delete(Request $request)
    {
        try {
            Log::channel("shippedvendordetails")->info('** started the shippedvendordetails delete method **');
            $ids = $request->id;
            $ids = json_decode($ids, true);
            if (!empty($ids)) {
                $vendor = ShippedVendorDetails::where('shipped_vendor_details_id', $ids)->first();
                $update = ShippedVendorDetails::whereIn('shipped_vendor_details_id', $ids)->update(array(
                    'status' => 2,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));
                // log activity
                $implode = implode(",", $ids);
                $desc = $vendor->name . ' Shipped vendor details' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Shipped Vendor');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);
                return response()->json([
                    'keyword' => 'success',
                    'message' =>  __('Shipped vendor details deleted successfully'),
                    'data' => []
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.failed'),
                    'data' => []
                ]);
            }
        } catch (\Exception $exception) {
            Log::channel("shippedvendordetails")->error($exception);
            Log::channel("shippedvendordetails")->info('** end the shippedvendordetails delete method **');

            return response()->json([
                'error' => 'Internal server error.',
                'message' => $exception->getMessage()
            ], 500);
        }
    }
}