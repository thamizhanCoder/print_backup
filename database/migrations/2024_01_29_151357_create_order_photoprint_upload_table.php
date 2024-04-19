<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPhotoprintUploadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_photoprint_upload', function (Blueprint $table) {
            $table->bigIncrements('order_photoprint_upload_id');
            $table->integer('order_items_id')->nullable();
            // $table->unsignedBigInteger('order_items_id');
            // $table->foreign('order_items_id')->references('order_items_id')->on('order_items');
            $table->text('image')->nullable();
            $table->integer('quantity')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_photoprint_upload');
    }
}
