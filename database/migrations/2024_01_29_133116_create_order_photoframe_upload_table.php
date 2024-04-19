<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPhotoframeUploadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_photoframe_upload', function (Blueprint $table) {
            $table->bigIncrements('order_photoframe_upload_id');
            $table->integer('order_items_id')->nullable();
            $table->integer('order_photoframe_upload_label_id')->nullable();
            // $table->unsignedBigInteger('order_items_id');
            // $table->foreign('order_items_id')->references('order_items_id')->on('order_items');
            // $table->unsignedBigInteger('order_photoframe_upload_label_id');
            // $table->foreign('order_photoframe_upload_label_id')->references('order_photoframe_upload_label_id')->on('order_photoframe_upload_label');
            $table->text('image')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();  
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('status')->default('0')->comment('0-pending : 1- approved : 2-rejected')->nullable();
            $table->text('reject_reason')->nullable();
            $table->dateTime('rejected_on')->nullable();
            $table->integer('is_chat')->default('0')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_photoframe_upload');
    }
}
