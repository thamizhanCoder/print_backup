<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AclMenuTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('acl_menu')->insert(
            [
                [
                    'acl_menu_module_id' => 1,
                    'menu_name' => "List",
                    'url' => "category_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 1,
                    'menu_name' => "Create",
                    'url' => "category_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 1,
                    'menu_name' => "Update",
                    'url' => "category_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 1,
                    'menu_name' => "Delete",
                    'url' => "category_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 2,
                    'menu_name' => "List",
                    'url' => "variant_type_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 2,
                    'menu_name' => "Create",
                    'url' => "variant_type_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 2,
                    'menu_name' => "Update",
                    'url' => "variant_type_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 2,
                    'menu_name' => "Delete",
                    'url' => "variant_type_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 3,
                    'menu_name' => "Create",
                    'url' => "delivery_charge_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 3,
                    'menu_name' => "Update",
                    'url' => "delivery_charge_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 4,
                    'menu_name' => "List",
                    'url' => "exp_days_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 4,
                    'menu_name' => "Update",
                    'url' => "exp_days_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 5,
                    'menu_name' => "List",
                    'url' => "shippedVendorDetails_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 5,
                    'menu_name' => "Create",
                    'url' => "shippedVendorDetails_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 5,
                    'menu_name' => "Update",
                    'url' => "shippedVendorDetails_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 5,
                    'menu_name' => "Delete",
                    'url' => "shippedVendorDetails_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 5,
                    'menu_name' => "Status",
                    'url' => "shippedVendorDetails_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 6,
                    'menu_name' => "List",
                    'url' => "rating_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 6,
                    'menu_name' => "Status",
                    'url' => "rating_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 7,
                    'menu_name' => "List",
                    'url' => "dept_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 7,
                    'menu_name' => "Create",
                    'url' => "dept_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 7,
                    'menu_name' => "Update",
                    'url' => "dept_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 7,
                    'menu_name' => "Delete",
                    'url' => "dept_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 8,
                    'menu_name' => "List",
                    'url' => "task_stage_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 8,
                    'menu_name' => "Create",
                    'url' => "task_stage_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 8,
                    'menu_name' => "Update",
                    'url' => "task_stage_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 8,
                    'menu_name' => "Delete",
                    'url' => "task_stage_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 8,
                    'menu_name' => "Status",
                    'url' => "task_stage_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 8,
                    'menu_name' => "View",
                    'url' => "taskstage_view",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 9,
                    'menu_name' => "Qrcode",
                    'url' => "qrcode",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 10,
                    'menu_name' => "Update",
                    'url' => "taskduration_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 11,
                    'menu_name' => "List",
                    'url' => "photoprintsettings_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 11,
                    'menu_name' => "Create",
                    'url' => "photoprintsettings_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 11,
                    'menu_name' => "Update",
                    'url' => "photoprintsettings_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 11,
                    'menu_name' => "Delete",
                    'url' => "photoprintsettings_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 12,
                    'menu_name' => "List",
                    'url' => "gstpercentage_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 12,
                    'menu_name' => "Create",
                    'url' => "gstpercentage_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 12,
                    'menu_name' => "Update",
                    'url' => "gstpercentage_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 12,
                    'menu_name' => "Delete",
                    'url' => "gstpercentage_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 13,
                    'menu_name' => "List",
                    'url' => "otherdistrict_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 13,
                    'menu_name' => "Update",
                    'url' => "otherdistrict_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 15,
                    'menu_name' => "List",
                    'url' => "coupon_code_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 15,
                    'menu_name' => "Create",
                    'url' => "coupon_code_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 15,
                    'menu_name' => "Update",
                    'url' => "coupon_code_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 15,
                    'menu_name' => "Delete",
                    'url' => "coupon_code_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 15,
                    'menu_name' => "View",
                    'url' => "coupon_code_view",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 15,
                    'menu_name' => "Status",
                    'url' => "coupon_code_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 16,
                    'menu_name' => "List",
                    'url' => "role_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 16,
                    'menu_name' => "Create",
                    'url' => "role_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 16,
                    'menu_name' => "Update",
                    'url' => "role_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 16,
                    'menu_name' => "Delete",
                    'url' => "role_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 16,
                    'menu_name' => "Status",
                    'url' => "role_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 17,
                    'menu_name' => "List",
                    'url' => "user_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 17,
                    'menu_name' => "Create",
                    'url' => "user_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 17,
                    'menu_name' => "Update",
                    'url' => "user_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 17,
                    'menu_name' => "Delete",
                    'url' => "user_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 17,
                    'menu_name' => "Status",
                    'url' => "user_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 18,
                    'menu_name' => "List",
                    'url' => "cmsBanner_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 18,
                    'menu_name' => "Create",
                    'url' => "cmsBanner_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 18,
                    'menu_name' => "Delete",
                    'url' => "cmsBanner_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 18,
                    'menu_name' => "Reorder",
                    'url' => "OrderUpdateBannerItems",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 19,
                    'menu_name' => "List",
                    'url' => "cmsvideo_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 19,
                    'menu_name' => "Create",
                    'url' => "cmsvideo_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 19,
                    'menu_name' => "Delete",
                    'url' => "cmsvideo_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 19,
                    'menu_name' => "Update",
                    'url' => "cmsvideo_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 20,
                    'menu_name' => "List",
                    'url' => "cmsGreet_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 20,
                    'menu_name' => "Create",
                    'url' => "cmsGreet_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 20,
                    'menu_name' => "Delete",
                    'url' => "cmsGreet_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 21,
                    'menu_name' => "List",
                    'url' => "passportsizephoto_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 21,
                    'menu_name' => "Create",
                    'url' => "passportsizephoto_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 21,
                    'menu_name' => "Update",
                    'url' => "passportsizephoto_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 21,
                    'menu_name' => "Delete",
                    'url' => "passportsizephoto_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 21,
                    'menu_name' => "View",
                    'url' => "passportsizephoto_view",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 21,
                    'menu_name' => "Status",
                    'url' => "passportsizephoto_status1",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 22,
                    'menu_name' => "List",
                    'url' => "photoprint_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 22,
                    'menu_name' => "Create",
                    'url' => "photoprint_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 22,
                    'menu_name' => "Update",
                    'url' => "photoprint_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 22,
                    'menu_name' => "Delete",
                    'url' => "photoprint_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 22,
                    'menu_name' => "View",
                    'url' => "photoprint_view",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 22,
                    'menu_name' => "Status",
                    'url' => "photoprint_status1",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 23,
                    'menu_name' => "List",
                    'url' => "photoframe_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 23,
                    'menu_name' => "Create",
                    'url' => "photoframe_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 23,
                    'menu_name' => "Update",
                    'url' => "photoframe_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 23,
                    'menu_name' => "Delete",
                    'url' => "photoframe_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 23,
                    'menu_name' => "View",
                    'url' => "photoframe_view",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 23,
                    'menu_name' => "Status",
                    'url' => "photoframe_status1",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 24,
                    'menu_name' => "List",
                    'url' => "personalizedList",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 24,
                    'menu_name' => "Create",
                    'url' => "personalizedCreate",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 24,
                    'menu_name' => "Update",
                    'url' => "personalizedUpdate",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 24,
                    'menu_name' => "Delete",
                    'url' => "personalizedStatus",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 24,
                    'menu_name' => "View",
                    'url' => "personalizedView",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 24,
                    'menu_name' => "Status",
                    'url' => "personalizedStatus1",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 25,
                    'menu_name' => "List",
                    'url' => "ecommerce_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 25,
                    'menu_name' => "Create",
                    'url' => "ecommerce_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 25,
                    'menu_name' => "Update",
                    'url' => "ecommerce_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 25,
                    'menu_name' => "Delete",
                    'url' => "ecommerce_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 25,
                    'menu_name' => "View",
                    'url' => "ecommerce_view",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 25,
                    'menu_name' => "Status",
                    'url' => "ecommerce_status1",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 26,
                    'menu_name' => "List",
                    'url' => "selfiealbumList",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 26,
                    'menu_name' => "Create",
                    'url' => "selfiealbumCreate",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 26,
                    'menu_name' => "Update",
                    'url' => "selfiealbumUpdate",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 26,
                    'menu_name' => "Delete",
                    'url' => "selfiealbumStatus",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 26,
                    'menu_name' => "View",
                    'url' => "selfiealbumView",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 26,
                    'menu_name' => "Status",
                    'url' => "selfiealbumStatus1",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 27,
                    'menu_name' => "Order.report",
                    'url' => "order_report",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 28,
                    'menu_name' => "Refund.report",
                    'url' => "refund_report",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 29,
                    'menu_name' => "Tickets.report",
                    'url' => "tickets_report",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 30,
                    'menu_name' => "Gst.report",
                    'url' => "gst_report",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 31,
                    'menu_name' => "Task.report",
                    'url' => "task_report",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 32,
                    'menu_name' => "Ratingsreview.report",
                    'url' => "ratingsreview_report",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 33,
                    'menu_name' => "Customer.report",
                    'url' => "customer_report",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 34,
                    'menu_name' => "Employee.report",
                    'url' => "employee_report",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 35,
                    'menu_name' => "Product.report",
                    'url' => "product_report",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 36,
                    'menu_name' => "Stock.report",
                    'url' => "stock_report",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 37,
                    'menu_name' => "Paymenttransaction.report",
                    'url' => "paymenttransaction_report",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 38,
                    'menu_name' => "List",
                    'url' => "contest_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 38,
                    'menu_name' => "Create",
                    'url' => "contest_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 38,
                    'menu_name' => "Update",
                    'url' => "contest_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 38,
                    'menu_name' => "Delete",
                    'url' => "contest_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 38,
                    'menu_name' => "View",
                    'url' => "contest_view",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 38,
                    'menu_name' => "Participants details",
                    'url' => "contest_participant_view",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 39,
                    'menu_name' => "List",
                    'url' => "refund_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 40,
                    'menu_name' => "List",
                    'url' => "communication_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 40,
                    'menu_name' => "View",
                    'url' => "task_view",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 41,
                    'menu_name' => "List",
                    'url' => "ticket_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 41,
                    'menu_name' => "View",
                    'url' => "ticket_view",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 42,
                    'menu_name' => "List",
                    'url' => "transaction_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 43,
                    'menu_name' => "List",
                    'url' => "employee_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 43,
                    'menu_name' => "Create",
                    'url' => "employee_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 43,
                    'menu_name' => "Update",
                    'url' => "employee_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 43,
                    'menu_name' => "Delete",
                    'url' => "employee_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 43,
                    'menu_name' => "View",
                    'url' => "employee_view",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 43,
                    'menu_name' => "Status",
                    'url' => "employee_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 43,
                    'menu_name' => "Task Detail",
                    'url' => "countSummaryTaskManager",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 44,
                    'menu_name' => "List",
                    'url' => "operationList",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 44,
                    'menu_name' => "Create",
                    'url' => "task_create",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 45,
                    'menu_name' => "View",
                    'url' => "overview",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 45,
                    'menu_name' => "Feedback Reply",
                    'url' => "recentfeedback_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 46,
                    'menu_name' => "List",
                    'url' => "customer_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 46,
                    'menu_name' => "Delete",
                    'url' => "customer_delete",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 46,
                    'menu_name' => "View",
                    'url' => "customer_view_info",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 46,
                    'menu_name' => "Status",
                    'url' => "customer_status",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 47,
                    'menu_name' => "List",
                    'url' => "waitingcod_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 47,
                    'menu_name' => "Status",
                    'url' => "codorderStatusUpdate",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 48,
                    'menu_name' => "List",
                    'url' => "waitingpayment_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 48,
                    'menu_name' => "Status",
                    'url' => "orderStatusUpdate",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 49,
                    'menu_name' => "List",
                    'url' => "waitingdispatch_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 49,
                    'menu_name' => "Status",
                    'url' => "orderDispatch_update",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 50,
                    'menu_name' => "List",
                    'url' => "waitingdelivery_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 50,
                    'menu_name' => "Status",
                    'url' => "updateDeliveredStatus",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 51,
                    'menu_name' => "List",
                    'url' => "cancelledList",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 52,
                    'menu_name' => "List",
                    'url' => "billing_management_list",
                    'active' => 1
                ],
                [
                    'acl_menu_module_id' => 53,
                    'menu_name' => "List",
                    'url' => "trackOrder",
                    'active' => 1
                ],
            ]
        );
    }
}
