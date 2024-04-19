<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskManagerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_manager', function (Blueprint $table) {
            $table->bigIncrements('task_manager_id');   
            $table->string('task_code')->nullable();
            $table->integer('order_items_id')->nullable();
            $table->integer('orderitem_stage_id')->nullable();
            $table->integer('department_id')->nullable();
            // $table->unsignedBigInteger('order_items_id')->nullable();
            // $table->foreign('order_items_id')->references('order_items_id')->on('order_items');
            // $table->unsignedBigInteger('orderitem_stage_id')->nullable();
            // $table->foreign('orderitem_stage_id')->references('orderitem_stage_id')->on('orderitem_stage');
            // $table->unsignedBigInteger('department_id')->nullable();
            // $table->foreign('department_id')->references('department_id')->on('department');
            $table->tinyInteger('task_type')->comment('1-task : 2-order')->nullable();
            $table->string('task_name')->nullable();
            $table->text('description')->nullable();
            $table->text('attachment_image')->nullable();
            $table->tinyInteger('current_task_stage')->default('1')->comment('1.Operation : 2:Production : 3.Qc : 4.Delivery');
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('status')->default('1')->nullable();
            $table->string('qc_image')->nullable();
            $table->dateTime('qc_on')->nullable();
            $table->integer('qc_by')->nullable();
            $table->text('qc_reason')->nullable();
            $table->dateTime('qc_reason_on')->nullable();
            $table->tinyInteger('qc_status')->default('0')->comment('1-approved: 2-rejected')->nullable();
            $table->string('preview_image')->nullable();
            $table->dateTime('preview_on')->nullable();
            $table->integer('preview_by')->nullable();
            $table->text('preview_reason')->nullable();
            $table->dateTime('preview_reason_on')->nullable();
            $table->tinyInteger('preview_status')->default('0')->comment('1-approved: 2-rejected')->nullable();
            $table->string('qc_message')->nullable();
            $table->text('folder')->nullable();
            $table->integer('is_dispatch')->default('0')->nullable();


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_manager');
    }
}
