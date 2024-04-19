<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommunicationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('communication', function (Blueprint $table) {
            $table->bigIncrements('communication_id');
            $table->string('communication_no', 500)->nullable();
            $table->integer('task_manager_id')->nullable();
            $table->integer('orderitem_stage_id')->nullable();
            // $table->unsignedBigInteger('task_manager_id');
            // $table->foreign('task_manager_id')->references('task_manager_id')->on('task_manager');
            // $table->unsignedBigInteger('orderitem_stage_id');
            // $table->foreign('orderitem_stage_id')->references('orderitem_stage_id')->on('orderitem_stage');
            $table->string('subject', 500)->nullable();
            $table->tinyInteger('status')->default('0')->comment('0-inprogress, 1-completed.');
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->dateTime('closed_on')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('communication');
    }
}
