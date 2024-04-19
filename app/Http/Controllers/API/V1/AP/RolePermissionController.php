<?php

namespace App\Http\Controllers\API\V1\AP;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\RolePermission;
use App\Models\Role;
use App\Models\Menu;
use App\Models\MenuModule;

class RolePermissionController extends Controller
{
	public function role_permissionlist(Request $request)
	{
			



		
// list view
		$crm_menu = MenuModule::where([['status', '=', 1]])->whereNotIn('acl_menu_module_id', [27,28,29,30,31,32,33,34,35,36,37])->where([['view_type', '=', 1]])->orderBy('acl_menu_module_id','ASC')->get();

			

			$crm_record_list_view = [];
			if (!empty($crm_menu)) {
				foreach ($crm_menu as $row) {
					$crm_data = [];				
					$crm_data['name'] = $row->name;				
					$crm_data['status'] = $row->status;
					$crm_data['view_type'] = $row->view_type == 1 ? "list-view" : "tablular-view";
					$crm_data['acl_menu_module_id'] = $row->acl_menu_module_id;
					$crm_data['action'] =  Menu::where([['active', '=', 1]])
											->where([['acl_menu_module_id', '=', 
											$row->acl_menu_module_id]])
											->orderBy('acl_menu_module_id','ASC')->get();

					$crm_record_list_view[] = $crm_data;
				}
			}

// tabular view
			$crm_menu = MenuModule::where([['status', '=', 1]])->where([['view_type', '=', 2]])->orderBy('acl_menu_module_id','ASC')->get();
	

			$crm_record_tablular_view = [];
			if (!empty($crm_menu)) {
				foreach ($crm_menu as $row) {
					$crm_data = [];				
					$crm_data['name'] = $row->name;				
					$crm_data['status'] = $row->status;
					$crm_data['view_type'] = $row->view_type == 1 ? "list-view" : "tablular-view";
					$crm_data['acl_menu_module_id'] = $row->acl_menu_module_id;
					$crm_data['action'] =  Menu::where([['active', '=', 1]])->where([['acl_menu_module_id', '=', $row->acl_menu_module_id]])->orderBy('acl_menu_module_id','ASC')->get();

					$crm_record_tablular_view[] = $crm_data;
				}
			}


			//portal_permission
			// tabular view
			$portal_permission = MenuModule::where([['status', '=', 1]])->whereIn('acl_menu_module_id', [27,28,29,30,31,32,33,34,35,36,37])->orderBy('acl_menu_module_id','ASC')->get();
	

			$portal_permission_view = [];
			if (!empty($portal_permission)) {
				foreach ($portal_permission as $row) {
					$crm_data = [];				
					$crm_data['name'] = $row->name;				
					$crm_data['status'] = $row->status;
					$crm_data['view_type'] = $row->view_type == 1 ? "list-view" : "tablular-view";
					$crm_data['acl_menu_module_id'] = $row->acl_menu_module_id;
					$crm_data['action'] =  Menu::where([['active', '=', 1]])->where([['acl_menu_module_id', '=', $row->acl_menu_module_id]])->orderBy('acl_menu_module_id','ASC')->get();

					$portal_permission_view[] = $crm_data;
				}
			}

			$role_id = ($request->role_id) ? $request->role_id : "";

		
		$role_permission = $role_id != "" ? RolePermission::where([['acl_role_id', '=', $role_id]])->select('acl_permission_id', 'acl_role_id', 'acl_menu_id', 'acl_menu_module_id')
				->get() : [];


		
			$data = [];		
			$data['list_view'] = $crm_record_list_view;
			$data['tabular_view'] = $crm_record_tablular_view;
			$data['role_permissions'] = $role_permission;
			$data['portal_permission'] = $portal_permission_view;


			return response()->json([
				'keyword' => 'success',
				'message' => __('Role permission list'),
				'data' => [$data]
			]);
		
	}

	///////////////////////////End of Line//////////////////

	// public function role_permissionlist(Request $request)
	// {
	// 		$roles = Role::where([
	// 			['status', '=', '1'],
	// 			['acl_role_id', '!=', '1']
	// 		])
	// 			->select('acl_role_id', 'role_name', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by')
	// 			->get();

	// 		$crm_menu = Menu::where([['active', '=', 1]])->orderBy('acl_menu_module_id','ASC')->get();


	// 		$crm_record = [];
	// 		if (!empty($crm_menu)) {
	// 			foreach ($crm_menu as $row) {
	// 				$crm_data = [];
	// 				$crm_data['acl_menu_id'] = $row->acl_menu_id;
	// 				$crm_data['menu_name'] = $row->menu_name;
	// 				$crm_data['url'] = $row->url;
	// 				$crm_data['active'] = $row->active;
	// 				$crm_data['acl_menu_module_id'] = $row->acl_menu_module_id;
	// 				$crm_record[] = $crm_data;
	// 			}
	// 		}

	// 		$role_permission = RolePermission::select('acl_permission_id', 'acl_role_id', 'acl_menu_id')
	// 			->get();

	// 		$data = [];
	// 		$data['roles'] = $roles;
	// 		$data['crm_menu'] = $crm_record;
	// 		$data['role_permissions'] = $role_permission;


	// 		return response()->json([
	// 			'keyword' => 'success',
	// 			'message' => __('Role permission list'),
	// 			'data' => [$data]
	// 		]);
		
	// }


	public function role_permissionupdate(Request $request)
	{
			$role_menu_ids = $request->role_menu_id;
			$acl_role_id = $request->acl_role_id ;
			$role_menu_ids = json_decode($role_menu_ids, true);

			//RolePermission::truncate();
			//role id based trucate
			RolePermission::where('acl_role_id', $acl_role_id)->delete();

			$this->updateRolePermission($role_menu_ids, $acl_role_id);
				// for ($i = 0; $i < count($role_menu_ids); $i++) {
				// 	$menu = explode(',', $role_menu_ids[$i]);
				// 	$permission = new RolePermission();
				// 	$permission->acl_menu_id = $menu[0];
				// 	$permission->acl_role_id = $menu[1];
				// 	$permission->save();
				// }
				// if ($permission->save()) {

					$permission_data = RolePermission::select('acl_role_id', 'acl_menu_id', 'acl_menu_module_id')->get();

					return response()->json([
						'keyword' => 'success',
						'message' => __('Role permission created successfully'),
						'data' => $permission_data
					]);
	}

	public function updateRolePermission($role_menu_ids, $acl_role_id)
    {
        foreach ($role_menu_ids as $roleId) {

            $rolePermission = new RolePermission();
            $rolePermission->acl_role_id = $acl_role_id;
            $rolePermission->acl_menu_id = $roleId['acl_menu_id'];
            $rolePermission->acl_menu_module_id = $roleId['acl_menu_module_id'];
            $rolePermission->save();
        }
    }
}
