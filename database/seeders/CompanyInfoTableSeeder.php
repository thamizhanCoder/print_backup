<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyInfoTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('company_info')->insert([
            "name" => "The Print App Private Limited",
            "mobile_no" => "+91 9276589856",
            "logo" => "p-logo.png",
            "address" => "#50/5 , Street1, Usilampatti, Madurai - 625005"
        ]);
    }
}
