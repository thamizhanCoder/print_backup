<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotoPrintSettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photo_print_setting', function (Blueprint $table) {
            $table->bigIncrements('photo_print_settings_id');
            $table->string('width')->nullable();
            $table->string('height')->nullable();
            $table->string('min_resolution_width')->nullable();
            $table->string('min_resolution_height')->nullable();
            $table->string('max_resolution_width')->nullable();
            $table->string('max_resolution_height')->nullable();
            $table->tinyInteger('status')->default('1')->comment('1.Active,0.Inactive,2.Delete')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photo_print_setting');
    }
}
