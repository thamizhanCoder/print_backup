<?php

namespace App\Http\Controllers\API\V1\AP;

use App\Helpers\GlobalHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use App\Models\UserModel;
use App\Http\Requests\UserRequest;
use App\Http\Requests\UserUpdateRequest;

class UserController extends Controller
{

    public function user_list(Request $request)
    {
        $limit = ($request->limit) ? $request->limit : '';
        $offset = ($request->offset) ? $request->offset : '';
        $searchval = ($request->searchWith) ? $request->searchWith : "";

        $order_by_key = [
            // 'mention the api side' => 'mention the mysql side column'
            'name' => 'acl_user.name',
            'role_name' => 'acl_role.role_name',
            'mobile_no' => 'acl_user.mobile_no',
            'email' => 'acl_user.email',
        ];

        $sort_dir = ['ASC', 'DESC'];

        $sortByKey = ($request->sortByKey) ? $request->sortByKey : "acl_user_id";
        $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

        $column_search = array('acl_user.name', 'acl_role.role_name', 'acl_user.mobile_no', 'acl_user.email');



        $get_user = UserModel::where([
            ['acl_user.status', '!=', 2],
            ['acl_user.acl_user_id', '!=', '1']
        ])
            ->leftjoin('acl_role', 'acl_role.acl_role_id', '=', 'acl_user.acl_role_id')
            ->select('acl_user.acl_user_id', 'acl_user.name', 'acl_user.email', 'acl_user.mobile_no', 'acl_role.acl_role_id', 'acl_role.role_name', 'acl_user.created_on', 'acl_user.created_by', 'acl_user.updated_on', 'acl_user.updated_by', 'acl_user.status');

        $get_user->where(function ($query) use ($searchval, $column_search, $get_user) {
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
            $get_user->orderBy($order_by_key[$sortByKey], $sortType);
        }
        $count = $get_user->count();
        if ($offset) {
            $offset = $offset * $limit;
            $get_user->offset($offset);
        }

        if ($limit) {
            $get_user->limit($limit);
        }

        $get_user->orderBy('acl_user.acl_user_id', 'desc');

        $get_user = $get_user->get();


        if (!empty($get_user)) {


            return response()->json(
                [
                    'keyword' => 'success',
                    'message' => __('User listed successfully'),
                    'data' => $get_user,
                    'count' => $count
                ]
            );
        } else {
            return response()->json(
                [
                    'keyword' => 'failure',
                    'message' => __('No data found'),
                    'data' => [],
                    'count' => $count
                ]
            );
        }
    }
    public function user_create(UserRequest $request)
    {

        $email = UserModel::where([
            ['email', '=', $request->email],
            ['status', '!=', 2]
        ])->first();
        if (empty($email)) {

            $mobile = UserModel::where([
                ['mobile_no', '=', $request->mobile_no],
                ['status', '!=', 2]
            ])->first();

            if (empty($mobile)) {


                $user = new UserModel();

                $user->acl_role_id = $request->acl_role_id;
                $user->name = $request->name;
                $user->email = $request->email;
                $user->mobile_no = $request->mobile_no;
                $user->password = md5($request->password);
                $user->created_on = Server::getDateTime();
                $user->created_by = JwtHelper::getSesUserId();

                if ($user->save()) {

                    $users = UserModel::where('acl_user_id', $user->acl_user_id)
                        ->leftjoin('acl_role', 'acl_role.acl_role_id', '=', 'acl_user.acl_role_id')
                        ->select('acl_user.acl_user_id', 'acl_role.acl_role_id', 'acl_role.role_name', 'acl_user.email', 'acl_user.name', 'acl_user.mobile_no', 'acl_user.status', 'acl_user.created_on', 'acl_user.created_by', 'acl_user.updated_on', 'acl_user.updated_by')
                        ->first();

                        // log activity
                    $desc = $user->name . ' User' . ' created by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Manage User');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                    // $mail_data = [];
                    // $mail_data['name'] = $request->input('name');
                    // $mail_data['role_name'] = $users->role_name;
                    // $mail_data['email'] = $request->input('email');
                    // $mail_data['password'] = $request->input('password');
                    // event(new UserWelcome($mail_data));

                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('User created successfully'),
                        'data'        => [$users]

                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failure',
                        'message'      => __('User creation failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('User mobile number already exist'),
                    'data'        => []
                ]);
            }
        } else {
            return response()->json([
                'keyword'      => 'failure',
                'message'      => __('User email already exist'),
                'data'        => []
            ]);
        }
    }

    public function user_update(UserUpdateRequest $request)
    {
        $email = UserModel::where([
            ['email', '=', $request->email],
            ['status', '!=', 2],
            ['acl_user_id', '!=', $request->acl_user_id]
        ])->first();
        if (empty($email)) {

            $mobile = UserModel::where([
                ['mobile_no', '=', $request->mobile_no],
                ['status', '!=', 2],
                ['acl_user_id', '!=', $request->acl_user_id]
            ])->first();

            if (empty($mobile)) {

                $user = new UserModel();
                $ids = $request->acl_user_id;
                $user = UserModel::find($ids);
                $user->acl_role_id = $request->acl_role_id;
                $user->name = $request->name;
                $user->email = $request->email;
                $user->mobile_no = $request->mobile_no;
                if ($request->password != '') {
                    $user->password = md5($request->password);
                }
                $user->updated_on = Server::getDateTime();
                $user->updated_by = JwtHelper::getSesUserId();

                if ($user->save()) {
                    $users = UserModel::where('acl_user_id', $user->acl_user_id)->leftjoin('acl_role', 'acl_role.acl_role_id', '=', 'acl_user.acl_role_id')->select('acl_user.acl_user_id', 'acl_user.acl_role_id', 'acl_role.role_name', 'acl_user.email', 'acl_user.name', 'acl_user.mobile_no', 'acl_user.status', 'acl_user.created_on', 'acl_user.created_by', 'acl_user.updated_on', 'acl_user.updated_by')->first();

                    // log activity
                    $desc = $user->name . ' User' . ' updated by ' . JwtHelper::getSesUserNameWithType() . '';
                    $activitytype = Config('activitytype.Manage User');
                    GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                    return response()->json([
                        'keyword'      => 'success',
                        'message'      => __('User updated successfully'),
                        'data'        => [$users]
                    ]);
                } else {
                    return response()->json([
                        'keyword'      => 'failure',
                        'message'      => __('User updated failed'),
                        'data'        => []
                    ]);
                }
            } else {
                return response()->json([
                    'keyword'      => 'failure',
                    'message'      => __('User mobile number already exist'),
                    'data'        => []
                ]);
            }
        } else {
            return response()->json([
                'keyword'      => 'failure',
                'message'      => __('User email already exist'),
                'data'        => []
            ]);
        }
    }

    public function user_view(Request $request, $id)
    {

        if ($id != '' && $id > 0) {
            $data = [];

            $user = new UserModel();

            $data = UserModel::where('acl_user.acl_user_id', $id)
                ->leftjoin('acl_role', 'acl_role.acl_role_id', '=', 'acl_user.acl_role_id')->select('acl_user.acl_user_id', 'acl_role.acl_role_id', 'acl_role.role_name', 'acl_user.name', 'acl_user.mobile_no', 'acl_user.email', 'acl_user.status', 'acl_user.created_on', 'acl_user.created_by', 'acl_user.updated_on', 'acl_user.updated_by')->get();


            if (!empty($data)) {
                return response()->json([
                    'keyword' => 'success',
                    'message' => __('User viewed successfully'),
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failure',
                    'message' =>  __('No data found'), 'data' => $data
                ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' =>  __('No data found'), 'data' => []
            ]);
        }
    }
    public function user_status(Request $request)
    {

        if (!empty($request)) {

            $ids = $request->id;
            $ids = json_decode($ids, true);

            if (!empty($ids)) {
                $user = UserModel::where('acl_user_id', $ids)->first();
                $update = UserModel::whereIn('acl_user_id', $ids)->update(array(
                    'status' => $request->status,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));

                // log activity
                  $activity_status = ($request->status) ? 'activated' : 'deactivated';
                  $implode = implode(",", $ids);
                  $desc = $user->name . ' User ' . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
                  $activitytype = Config('activitytype.Manage User');
                  GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


                if ($request->status == 0) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' => __('User inactivated successfully'),
                        'data' => []
                    ]);
                } else if ($request->status == 1) {
                    return response()->json([
                        'keyword' => 'success',
                        'message' =>  __('User activated successfully'),
                        'data' => []
                    ]);
                }
            } else {
                return response()
                    ->json([
                        'keyword' => 'failed',
                        'message' => __('User failed'),
                        'data' => []
                    ]);
            }
        } else {
            return response()->json([
                'keyword' => 'failed',
                'message' => __('user failed'), 'data' => []
            ]);
        }
    }

    public function user_delete(Request $request)
    {

        if (!empty($request)) {

            $ids = $request->id;
            $ids = json_decode($ids, true);


            if (!empty($ids)) {
                $user = UserModel::where('acl_user_id', $ids)->first();
                $update = UserModel::whereIn('acl_user_id', $ids)->update(array(
                    'status' => 2,
                    'updated_on' => Server::getDateTime(),
                    'updated_by' => JwtHelper::getSesUserId()
                ));

                // log activity
                $implode = implode(",", $ids);
                $desc = $user->name . ' User' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
                $activitytype = Config('activitytype.Manage User');
                GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

                return response()->json([
                    'keyword' => 'success',
                    'message' =>  __('User deleted successfully'),
                    'data' => []
                ]);
            } else {
                return response()->json([
                    'keyword' => 'failed',
                    'message' => __('message.failed'),
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
    }
}