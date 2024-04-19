<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderSelfieuploadPreviewHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_selfieupload_preview_history', function (Blueprint $table) {
            $table->bigIncrements('order_selfieupload_preview_history_id');
            $table->integer('order_selfie_upload_id')->nullable();
            // $table->unsignedBigInteger('order_selfie_upload_id');
            // $table->foreign('order_selfie_upload_id')->references('order_selfie_upload_id')->on('order_selfie_upload');
            $table->text('preview_image')->nullable();
            $table->dateTime('preview_on')->nullable();
            $table->integer('preview_by')->nullable();
            $table->tinyInteger('preview_status')->default('0');
            $table->text('preview_reason')->nullable();
            $table->dateTime('preview_reason_on')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_selfieupload_preview_history');
    }
}
