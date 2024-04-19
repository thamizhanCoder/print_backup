<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeTaskHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_task_history', function (Blueprint $table) {
            $table->bigIncrements('employee_task_history_id');
            $table->integer('task_manager_history_id')->nullable();
            $table->integer('employee_id')->nullable();
            // $table->unsignedBigInteger('task_manager_history_id');
            // $table->foreign('task_manager_history_id')->references('task_manager_history_id')->on('task_manager_history');
            // $table->unsignedBigInteger('employee_id');
            // $table->foreign('employee_id')->references('employee_id')->on('employee');
            $table->dateTime('created_on')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->tinyInteger('status')->default('1')->comment('1.todo,2.inprogress,3.preview,4.completed');
            $table->tinyInteger('employee_status')->default('1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_task_history');
    }
}
