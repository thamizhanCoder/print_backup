<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskstageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taskstage', function (Blueprint $table) {
            $table->bigIncrements('taskstage_id');
            $table->integer('service_id')->nullable();
            // $table->unsignedBigInteger('service_id')->nullable();
            // $table->foreign('service_id')->references('service_id')->on('service');
            $table->string('no_of_stage')->nullable();
            $table->text('stage_details')->nullable();
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
        Schema::dropIfExists('taskstage');
    }
}
