<?php

namespace App\Http\Controllers\API\V1\AP;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Server;
use App\Helpers\JwtHelper;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\UserModel;
use App\Helpers\GlobalHelper;
use \Firebase\JWT\JWT;
use App\Http\Requests\RoleRequest;



class RoleController extends Controller
{
  public function role_create(RoleRequest $request)
  {
    $role = new Role();
    $exist = Role::where([['role_name', $request->input('role_name')], ['status', '!=', 2]])->first();

    if (empty($exist)) {
      $role->role_name = $request->input('role_name');
      $role->created_at = Server::getDateTime();
      $role->created_by = JwtHelper::getSesUserId();

      if ($role->save()) {
        $roles = Role::where('acl_role_id', $role->acl_role_id)->first();

        // log activity
          $desc = 'Role '  .  $role->role_name .   ' is created by ' . JwtHelper::getSesUserNameWithType() . '';
          $activitytype = Config('activitytype.Manage Role');
          GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

        return response()->json([
          'keyword'      => 'success',
          'message'      => __('Role created successfully'),
          'data'        => [$roles]
        ]);
      } else {
        return response()->json([
          'keyword'      => 'failed',
          'message'      => __('Role creation failed'),
          'data'        => []
        ]);
      }
    } else {
      return response()->json([
        'keyword'      => 'failed',
        'message'      => __('Role name already exist'),
        'data'        => []
      ]);
    }
  }

  public function role_list(Request $request)
  {
    $limit = ($request->limit) ? $request->limit : '';
    $offset = ($request->offset) ? $request->offset : '';
    $searchval = ($request->searchWith) ? $request->searchWith : "";

    $order_by_key = [
      // 'mention the api side' => 'mention the mysql side column'
      'role_name' => 'role_name',
    ];

    $sort_dir = ['ASC', 'DESC'];

    $sortByKey = ($request->sortByKey) ? $request->sortByKey : "acl_role_id";
    $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

    $column_search = array('role_name');

    $roles = Role::where([
      ['status', '!=', '2'],
      ['acl_role_id', '!=', '1']
    ]);

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
    $count = $roles->count();
    if ($offset) {
      $offset = $offset * $limit;
      $roles->offset($offset);
    }

    if ($limit) {
      $roles->limit($limit);
    }

    $roles->orderBy('acl_role_id', 'desc');

    $roles = $roles->get();


    if ($count > 0) {
      return response()->json([
        'keyword' => 'success',
        'message' => __('Role listed successfully'),
        'data' => $roles,
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
  }


  public function role_update(RoleRequest $request)
  {
    $exist = Role::where([
      ['role_name', $request->input('role_name')],
      ['acl_role_id', '!=', $request->input('acl_role_id')], ['status', '!=', 2]
    ])->first();

    if (empty($exist)) {

      $rolesOldDetails = Role::where('acl_role_id', $request->input('acl_role_id'))->first();

      $ids = $request->input('acl_role_id');
      $role = Role::find($ids);
      $role->role_name = $request->input('role_name');
      $role->updated_at = Server::getDateTime();
      $role->updated_by = JwtHelper::getSesUserId();

      if ($role->save()) {
        $roles = Role::where('acl_role_id', $role->acl_role_id)->first();

        // log activity
        $desc =  'Role ' . '(' . $rolesOldDetails->role_name . ')' . ' is updated as ' . '(' . $role->role_name . ')' . ' by ' .JwtHelper::getSesUserNameWithType() . '';
        $activitytype = Config('activitytype.Manage Role');
        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);    


        return response()->json([
          'keyword'      => 'success',
          'data'        => [$roles],
          'message'      => __('Role updated successfully')
        ]);
      } else {
        return response()->json([
          'keyword'      => 'failed',
          'data'        => [],
          'message'      => __('Role update failed')
        ]);
      }
    } else {
      return response()->json([
        'keyword'      => 'failed',
        'message'      => __('Role name already exist'),
        'data'        => []
      ]);
    }
  }


  public function role_view($id)
  {

    if ($id != '' && $id > 0) {


      $role = new Role();

      $get_role = Role::where('acl_role_id', $id)->get();
      $count = $get_role->count();

      if ($count > 0) {
        return response()->json([
          'keyword' => 'success',
          'message' => __('Role viewed successfully'),
          'data' => [$get_role]
        ]);
      } else {
        return response()->json([
          'keyword' => 'failed',
          'message' => __('No data found'),
          'data' => []
        ]);
      }
    } else {
      return response()->json([
        'keyword' => 'failed',
        'message' => __('No data found'),
        'data' => []
      ]);
    }
  }

  public function role_status(Request $request)
  {

    if (!empty($request)) {

      $ids = $request->id;
      $ids = json_decode($ids, true);


      if (!empty($ids)) {
        $role = Role::where('acl_role_id', $ids)->first();
        $update = Role::whereIn('acl_role_id', $ids)->update(array(
          'status' => $request->status,
          'updated_at' => Server::getDateTime(),
          'updated_by' => JwtHelper::getSesUserId()
        ));

        // log activity
        $activity_status = ($request->status) ? ' is activated' : ' is inactivated';
        $implode = implode(",", $ids);
        $desc = $role->role_name . $activity_status . ' by ' . JwtHelper::getSesUserNameWithType() . '';
        $activitytype = Config('activitytype.Manage Role');
        GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);


        // $desc = 'Role '  .  $role->role_name .   ' is deleted by ' . JwtHelper::getSesUserNameWithType() . '';
        // $activitytype = Config('activitytype.Manage Role');
        // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1); 


        if ($request->status == 0) {
          return response()->json([
            'keyword' => 'success',
            'message' => __('Role inactivated successfully'),
            'data' => []
          ]);
        } else if ($request->status == 1) {
          return response()->json([
            'keyword' => 'success',
            'message' => __('Role activated successfully'),
            'data' => []
          ]);
        }
      } else {
        return response()->json([
          'keyword' => 'failed',
          'message' => __('No data found'),
          'data' => []
        ]);
      }
    } else {
      return response()->json([
        'keyword' => 'failed',
        'message' => __('message.no_data'),
        'data' => []
      ]);
    }
  }


  public function role_delete(Request $request)
  {
    if (!empty($request)) {

      $ids = $request->id;
      $ids = json_decode($ids, true);


      if (!empty($ids)) {

        $exist = UserModel::whereIn('acl_role_id', $ids)->where('status', '!=', 2)->first();
        if (empty($exist)) {
          $role = Role::where('acl_role_id', $ids)->first();
          $update = Role::whereIn('acl_role_id', $ids)->update(array(
            'status' => 2,
            'updated_at' => Server::getDateTime(),
            'updated_by' => JwtHelper::getSesUserId()
          ));

          // log activity
          $implode = implode(",", $ids);
          // $desc = $role->role_name . ' Role' . ' deleted by ' . JwtHelper::getSesUserNameWithType() . '';
          // $activitytype = Config('activitytype.role');
          // GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);

          $desc = 'Role '  .  $role->role_name .   ' is deleted by ' . JwtHelper::getSesUserNameWithType() . '';
          $activitytype = Config('activitytype.Manage Role');
          GlobalHelper::logActivity($desc, $activitytype, JwtHelper::getSesUserId(), 1);    


          return response()->json([
            'keyword' => 'success',
            'message' =>  __('Role deleted successfully'),
            'data' => []
          ]);
        } else {
          return response()->json([
            'keyword' => 'failed',
            'message' =>  __('This role is already used in user'),
            'data' => []
          ]);
        }
      } else {
        return response()->json([
          'keyword' => 'failed',
          'message' =>  __('Role failed'),
          'data' => []
        ]);
      }
    } else {
      return response()->json([
        'keyword' => 'failed',
        'message' =>  __('message.failed'),
        'data' => []
      ]);
    }
  }

  public function list(Request $request)
  {
    $limit = ($request->limit) ? $request->limit : '';
    $offset = ($request->offset) ? $request->offset : '';
    $searchval = ($request->searchWith) ? $request->searchWith : "";

    $order_by_key = [
      // 'mention the api side' => 'mention the mysql side column'
      'role_name' => 'role_name',
    ];

    $sort_dir = ['ASC', 'DESC'];

    $sortByKey = ($request->sortByKey) ? $request->sortByKey : "acl_role_id";
    $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

    $column_search = array('role_name');

    $roles = Role::where([
      ['status', '!=', '2'],
      ['acl_role_id', '!=', '1']
    ]);

    $get_count = $roles->count();

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
    if ($offset) {
      $offset = $offset * $limit;
      $roles->offset($offset);
    }

    if ($limit) {
      $roles->limit($limit);
    }

    $roles->orderBy('acl_role_id', 'desc');

    $roles = $roles->get();


    if ($get_count > 0) {
      return response()->json([
        'keyword' => 'success',
        'message' => __('Role listed successfully'),
        'data' => $roles,
        'count' => $get_count
      ]);
    } else {
      return response()->json([
        'keyword' => 'failed',
        'message' => __('No data found'),
        'data' => [],
        'count' => $get_count
      ]);
    }
  }

  public function activelist(Request $request)
  {
    $limit = ($request->limit) ? $request->limit : '';
    $offset = ($request->offset) ? $request->offset : '';
    $searchval = ($request->searchWith) ? $request->searchWith : "";

    $order_by_key = [
      // 'mention the api side' => 'mention the mysql side column'
      'role_name' => 'role_name',
    ];

    $sort_dir = ['ASC', 'DESC'];

    $sortByKey = ($request->sortByKey) ? $request->sortByKey : "acl_role_id";
    $sortType = strtoupper(($request->sortByType) ? $request->sortByType : "DESC");

    $column_search = array('role_name');

    $roles = Role::where([
      ['status', '=', '1'],
      ['acl_role_id', '!=', '1']
    ]);

    $get_count = $roles->count();

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
    if ($offset) {
      $offset = $offset * $limit;
      $roles->offset($offset);
    }

    if ($limit) {
      $roles->limit($limit);
    }

    $roles->orderBy('acl_role_id', 'desc');

    $roles = $roles->get();


    if ($get_count > 0) {
      return response()->json([
        'keyword' => 'success',
        'message' => __('Role listed successfully'),
        'data' => $roles,
        'count' => $get_count
      ]);
    } else {
      return response()->json([
        'keyword' => 'failed',
        'message' => __('No data found'),
        'data' => [],
        'count' => $get_count
      ]);
    }
  }
}