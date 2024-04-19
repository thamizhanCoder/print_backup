<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRelatedProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('related_products', function (Blueprint $table) {
             $table->bigIncrements('related_product_id');
            $table->integer('product_id')->nullable();
            $table->integer('product_id_related')->nullable();
            $table->integer('service_id')->nullable();
            // $table->unsignedBigInteger('product_id')->nullable();
            // $table->foreign('product_id')->references('product_id')->on('product');
            // $table->unsignedBigInteger('product_id_related')->nullable();
            // $table->foreign('product_id_related')->references('product_id')->on('product');
            // $table->unsignedBigInteger('service_id')->nullable();
            // $table->foreign('service_id')->references('service_id')->on('service');
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('status')->default('1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('related_products');
    }
}
