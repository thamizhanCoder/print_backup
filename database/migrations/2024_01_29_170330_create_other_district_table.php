<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtherDistrictTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('other_district', function (Blueprint $table) {
            $table->bigIncrements('other_district_id');
            $table->string('district', 500)->nullable();
            $table->integer('state_id')->nullable();
            // $table->unsignedBigInteger('state_id');
            // $table->foreign('state_id')->references('state_id')->on('state');
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('status')->default('0')->comment('1.published,0.unpublished,2.delete')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('other_district');
    }
}
