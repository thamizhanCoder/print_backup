<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWishlistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wishlist', function (Blueprint $table) {
            $table->bigIncrements('wishlist_id');
            $table->integer('product_id')->nullable();
            $table->integer('customer_id')->nullable();
            // $table->unsignedBigInteger('product_id')->nullable();
            // $table->foreign('product_id')->references('product_id')->on('product');
            // $table->unsignedBigInteger('customer_id')->nullable();
            // $table->foreign('customer_id')->references('customer_id')->on('customer');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->tinyInteger('status')->default('1')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wishlist');
    }
}
