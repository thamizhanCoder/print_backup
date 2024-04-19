<?php

namespace Database\Seeders;

use App\Helpers\Server;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use function Ramsey\Uuid\v1;

class AclMenuModuleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('acl_menu_module')->insert(
            [
                [
                    'name' => "Category",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Variant Type",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Delivery Charge",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Expected Delivery Days",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Shipped Vendor",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Ratings & Review",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Department",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Task Stages",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "QR Code",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Task Duration",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Photo Print Setting",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "GST Percentage",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Other District Setting",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Change Password",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Coupon Code",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Manage Roles",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Manage Users",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "CMS Banner",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "CMS Video",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "CMS Greetings",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Passport Size photo",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Photo Print",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Photo Frame",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Personalized Products",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "E-commerce Products",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Selfie Album",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Order Report",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Refund Report",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Tickets Report",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Gst Report",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Task Report",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Ratings Review Report",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Customer Report",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Employee Report",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Product Report",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Stock Report",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Payment Transaction Report",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Contest",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Refund",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Management communication",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Tickets",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Payment Transaction",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Employee",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Task Manager",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Dashboard",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Customer",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Waiting COD",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Waiting Payments",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Waiting Dispatch",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Delivery Details",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Cancelled Details",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Billing Management",
                    'status' => 1,
                    'view_type' => 1,
                ],
                [
                    'name' => "Track Order",
                    'status' => 1,
                    'view_type' => 1,
                ],
            ]
        );
    }
}
