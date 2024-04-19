<?php

namespace Database\Seeders;

use App\Helpers\Server;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AclRoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('acl_role')->insert([
            "role_name" => "Admin",
            "status" => 1,
            "created_at" =>  Server::getDateTime(),
            "updated_at" =>  Server::getDateTime(),
        ]);
    }
}
