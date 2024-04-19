<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddToCartTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('add_to_cart', function (Blueprint $table) {
            $table->bigIncrements('add_to_cart_id');
            $table->integer('customer_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->integer('product_variant_id')->nullable();
            // $table->unsignedBigInteger('customer_id');
            // $table->foreign('customer_id')->references('customer_id')->on('customer');
            // $table->unsignedBigInteger('product_id');
            // $table->foreign('product_id')->references('product_id')->on('product');
            // $table->unsignedBigInteger('product_variant_id');
            // $table->foreign('product_variant_id')->references('product_variant_id')->on('product_variant');
            $table->integer('quantity')->nullable();
            $table->string('image', 500)->comment('passport single image : selfie multiple image')->nullable();
            $table->string('background_color')->nullable();
            $table->text('variant_attributes')->nullable();
            $table->text('frames')->nullable();
            $table->tinyInteger('cart_type')->default('1')->comment('1.add to cart 2.buynow');
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('status')->default('1')->nullable();
            $table->text('images')->nullable();
            $table->text('photoprint_variant')->nullable();
            $table->integer('service_id')->nullable();
            // $table->unsignedBigInteger('service_id');
            // $table->foreign('service_id')->references('service_id')->on('service');
            $table->tinyInteger('is_customized')->default('0')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('add_to_cart');
    }
}
