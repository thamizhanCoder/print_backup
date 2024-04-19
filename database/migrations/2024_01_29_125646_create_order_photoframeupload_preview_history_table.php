<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPhotoframeuploadPreviewHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_photoframeupload_preview_history', function (Blueprint $table) {
            $table->bigIncrements('order_photoframeupload_preview_history_id');
            $table->integer('order_photoframe_upload_label_id')->nullable();
            // $table->unsignedBigInteger('order_photoframe_upload_label_id');
            // $table->foreign('order_photoframe_upload_label_id')->references('order_photoframe_upload_label_id')->on('order_photoframe_upload_label');
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
        Schema::dropIfExists('order_photoframeupload_preview_history');
    }
}
