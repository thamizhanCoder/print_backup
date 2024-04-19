<?php

namespace Database\Seeders;

use App\Models\ContestFieldType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        $this->call(AclUsersTableSeeder::class);
        $this->call(AclRoleTableSeeder::class);
        $this->call(AclMenuModuleTableSeeder::class);
        $this->call(AclMenuTableSeeder::class);
        $this->call(RevertStatusTableSeeder::class);
        $this->call(CompanyInfoTableSeeder::class);
        $this->call(ServiceTableSeeder::class);
        $this->call(ContestFieldTypeTableSeeder::class);
    }
}
