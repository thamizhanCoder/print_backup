<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RevertStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('revert_status')->insert([
            [
                "status" => "To Do",
            ],
            [
                "status" => "Inprogress",
            ],
            [
                "status" => "Preview(To Customer)",
            ]
        ]);
    }
}
