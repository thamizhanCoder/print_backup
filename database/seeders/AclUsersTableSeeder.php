<?php

namespace Database\Seeders;

use App\Helpers\Server;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AclUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('acl_user')->insert([
            'acl_role_id' => 1,
            'name' => 'admin',
            'password' => md5('12345678'),
            'email' => "admin@technogenesis.in",
            'status' => 1,
            'mobile_no' => 9940726633,
            "created_on" =>  Server::getDateTime(),
            "updated_on" =>  Server::getDateTime(),
        ]);
    }
}
