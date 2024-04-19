<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPersonalizedUploadHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_personalized_upload_history', function (Blueprint $table) {
            $table->bigIncrements('order_personalized_upload_history_id');
            $table->integer('order_personalized_upload_id')->nullable();
            // $table->unsignedBigInteger('order_personalized_upload_id');
            // $table->foreign('order_personalized_upload_id')->references('order_personalized_upload_id')->on('order_personalized_upload');
            $table->text('image')->nullable();
            $table->string('is_customized')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->tinyInteger('status')->default('0');
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
        Schema::dropIfExists('order_personalized_upload_history');
    }
}
