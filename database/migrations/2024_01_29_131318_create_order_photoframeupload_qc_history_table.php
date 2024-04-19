<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPhotoframeuploadQcHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_photoframeupload_qc_history', function (Blueprint $table) {
            $table->bigIncrements('order_photoframeupload_qc_history_id');
            $table->integer('order_photoframe_upload_label_id')->nullable();
            // $table->unsignedBigInteger('order_photoframe_upload_label_id');
            // $table->foreign('order_photoframe_upload_label_id')->references('order_photoframe_upload_label_id')->on('order_photoframe_upload_label');
            $table->text('qc_image')->nullable();
            $table->dateTime('qc_on')->nullable();
            $table->integer('qc_by')->nullable();
            $table->text('qc_reason')->nullable();
            $table->tinyInteger('qc_status')->comment('1.approved:2.rejected')->nullable();
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
        Schema::dropIfExists('order_photoframeupload_qc_history');
    }
}
