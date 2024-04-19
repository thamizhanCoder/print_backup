<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPhotoframeUploadHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_photoframe_upload_history', function (Blueprint $table) {
            $table->bigIncrements('order_photoframe_upload_history_id');
            $table->integer('order_photoframe_upload_id')->nullable();
            // $table->unsignedBigInteger('order_photoframe_upload_id');
            // $table->foreign('order_photoframe_upload_id')->references('order_photoframe_upload_id')->on('order_photoframe_upload');
            $table->text('image')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('status')->default('0')->nullable();
            $table->text('reject_reason')->nullable();
            $table->dateTime('rejected_on')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_photoframe_upload_history');
    }
}
