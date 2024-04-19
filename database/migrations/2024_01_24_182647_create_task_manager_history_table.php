<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskManagerHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_manager_history', function (Blueprint $table) {
            $table->bigIncrements('task_manager_history_id');
            $table->integer('task_manager_id')->nullable();
            // $table->unsignedBigInteger('task_manager_id')->nullable();
            // $table->foreign('task_manager_id')->references('task_manager_id')->on('task_manager');
            $table->string('employee_type')->nullable();
            $table->integer('employee_id')->nullable();
            $table->integer('orderitem_stage_id')->nullable();
            $table->integer('department_id')->nullable();
            // $table->unsignedBigInteger('employee_id')->nullable();
            // $table->foreign('employee_id')->references('employee_id')->on('employee');
            // $table->unsignedBigInteger('orderitem_stage_id')->nullable();
            // $table->foreign('orderitem_stage_id')->references('orderitem_stage_id')->on('orderitem_stage');
            // $table->unsignedBigInteger('department_id')->nullable();
            // $table->foreign('department_id')->references('department_id')->on('department');
            $table->date('expected_on')->nullable();
            $table->dateTime('assigned_on')->nullable();
            $table->integer('assigned_by')->nullable();
            $table->dateTime('approved_on')->nullable();
            $table->integer('approved_by')->nullable();
            $table->dateTime('taken_on')->nullable();
            $table->dateTime('completed_on')->nullable();
            $table->integer('completed_by')->nullable();
            $table->dateTime('rejected_on')->nullable();
            $table->integer('rejected_by')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->dateTime('revoked_on')->nullable();
            $table->integer('revoked_by')->nullable();
            $table->tinyInteger('production_status')->default('1')->comment('1:assign,2.revoke')->nullable();
            $table->tinyInteger('stage')->default('1')->comment('1.Operation : 2:Production : 3.Qc : 4.Delivery')->nullable();
            $table->tinyInteger('work_stage')->default('1')->comment('1.todo,2.inprogress,3.preview,4.completed	')->nullable();
            $table->date('extra_expected_on')->nullable();
            $table->dateTime('qc_approved_on')->nullable();
            $table->integer('qc_approved_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_manager_history');
    }
}
