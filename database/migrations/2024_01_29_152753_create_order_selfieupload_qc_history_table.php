<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderSelfieuploadQcHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_selfieupload_qc_history', function (Blueprint $table) {
            $table->bigIncrements('order_selfieupload_qc_history_id');
            $table->integer('order_selfie_upload_id')->nullable();
            // $table->unsignedBigInteger('order_selfie_upload_id');
            // $table->foreign('order_selfie_upload_id')->references('order_selfie_upload_id')->on('order_selfie_upload');
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
        Schema::dropIfExists('order_selfieupload_qc_history');
    }
}
