<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingReviewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rating_review', function (Blueprint $table) {
            $table->bigIncrements('rating_review_id');
            $table->integer('product_id')->nullable();
            $table->integer('order_id')->nullable();
            $table->integer('customer_id')->nullable();
            // $table->unsignedBigInteger('product_id')->nullable();
            // $table->foreign('product_id')->references('product_id')->on('product');
            // $table->unsignedBigInteger('order_id')->nullable();
            // $table->foreign('order_id')->references('order_id')->on('orders');
            // $table->unsignedBigInteger('customer_id')->nullable();
            // $table->foreign('customer_id')->references('customer_id')->on('customer');
            $table->integer('rating')->nullable();
            $table->text('review')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('status')->default('1')->comment('1-pending, 2- approved')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rating_review');
    }
}
