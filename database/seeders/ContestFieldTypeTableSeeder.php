<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContestFieldTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('contest_field_type')->insert([
            [
                "field_type" => "Text field",
            ],
            [
                "field_type" => "Selectbox",
            ],
            [
                "field_type" => "Upload",
            ],
            [
                "field_type" => "Radio button",
            ],
            [
                "field_type" => "Textarea",
            ],
            [
                "field_type" => "Checkbox",
            ],
            [
                "field_type" => "Alpha",
            ],
            [
                "field_type" => "Numeric",
            ],
            [
                "field_type" => "Alpha numeric",
            ]
        ]);
    }
}
