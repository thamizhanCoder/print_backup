<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskManagerQcHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_manager_qc_history', function (Blueprint $table) {
            $table->bigIncrements('task_manager_qc_history_id');
            $table->integer('task_manager_id')->nullable();
            // $table->unsignedBigInteger('task_manager_id')->nullable();
            // $table->foreign('task_manager_id')->references('task_manager_id')->on('task_manager');
            $table->string('qc_image')->nullable();
            $table->dateTime('qc_on')->nullable();
            $table->integer('qc_by')->nullable();
            $table->text('qc_reason')->nullable();
            $table->tinyInteger('qc_status')->comment('1-approved: 2-rejected')->nullable();
            $table->dateTime('qc_reason_on')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_manager_qc_history');
    }
}
