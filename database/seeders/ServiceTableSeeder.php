<?php

namespace Database\Seeders;

use App\Helpers\Server;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('service')->insert([
            [
                "service_name" => "Passport Photo",
                "delivery_charge" => 0.00,
                "updated_on" => Server::getDateTime()
            ],
            [
                "service_name" => "Photo Print",
                "delivery_charge" => 0.00,
                "updated_on" => Server::getDateTime()
            ],
            [
                "service_name" => "Photo Frame",
                "delivery_charge" => 0.00,
                "updated_on" => Server::getDateTime()
            ],
            [
                "service_name" => "Personalized Products",
                "delivery_charge" => 0.00,
                "updated_on" => Server::getDateTime()
            ],
            [
                "service_name" => "E-Commerce",
                "delivery_charge" => 0.00,
                "updated_on" => Server::getDateTime()
            ],
            [
                "service_name" => "Selfie Album",
                "delivery_charge" => 0.00,
                "updated_on" => Server::getDateTime()
            ],

        ]);
    }
}
