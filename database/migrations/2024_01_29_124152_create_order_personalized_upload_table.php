<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPersonalizedUploadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_personalized_upload', function (Blueprint $table) {
            $table->bigIncrements('order_personalized_upload_id');
            $table->integer('order_items_id')->nullable();
            // $table->unsignedBigInteger('order_items_id');
            // $table->foreign('order_items_id')->references('order_items_id')->on('order_items');
            $table->string('is_customized', 500)->nullable();
            $table->text('reference_image')->nullable();
            $table->text('image')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('status')->default('0')->comment('0-pending : 1- approved : 2-rejected');
            $table->text('reject_reason')->nullable();
            $table->dateTime('rejected_on')->nullable();
            $table->integer('is_chat')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_personalized_upload');
    }
}
