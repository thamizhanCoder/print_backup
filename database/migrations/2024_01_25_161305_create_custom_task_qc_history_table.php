<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomTaskQcHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_task_qc_history', function (Blueprint $table) {
            $table->bigIncrements('custom_task_qc_history_id');
            $table->integer('task_manager_id')->nullable();
            // $table->unsignedBigInteger('task_manager_id');
            // $table->foreign('task_manager_id')->references('task_manager_id')->on('task_manager');
            $table->text('attachment_image')->nullable();
            $table->text('qc_message')->nullable();
            $table->dateTime('qc_on')->nullable();
            $table->integer('qc_by')->nullable();
            $table->text('qc_reason')->nullable();
            $table->dateTime('qc_reason_on')->nullable();
            $table->tinyInteger('qc_status')->comment('1-approved: 2-rejected')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_task_qc_history');
    }
}
