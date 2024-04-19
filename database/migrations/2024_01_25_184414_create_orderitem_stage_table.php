<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderitemStageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orderitem_stage', function (Blueprint $table) {
            $table->bigIncrements('orderitem_stage_id');
            $table->integer('order_items_id')->nullable();
            // $table->unsignedBigInteger('order_items_id');
            // $table->foreign('order_items_id')->references('order_items_id')->on('order_items');
            $table->text('stage')->nullable();
            $table->integer('department_id')->nullable();
            // $table->unsignedBigInteger('department_id');
            // $table->foreign('department_id')->references('department_id')->on('department');
            $table->integer('is_customer_preview')->nullable();
            $table->integer('is_qc')->nullable();
            $table->dateTime('completed_on')->nullable();
            $table->integer('completed_by')->nullable();
            $table->dateTime('qc_on')->nullable();
            $table->integer('qc_by')->nullable();
            $table->tinyInteger('status')->default('1')->comment('1-in-progress:2-completed')->nullable();
            $table->tinyInteger('is_status_check')->default('0');
            $table->text('qc_reason')->nullable();
            $table->dateTime('qc_reason_on')->nullable();
            $table->tinyInteger('qc_status')->default('0')->nullable();
            $table->text('completed_reason')->nullable();
            $table->integer('qc_reason_by')->nullable();
            $table->text('qc_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orderitem_stage');
    }
}
