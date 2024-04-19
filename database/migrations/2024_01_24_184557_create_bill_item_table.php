<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bill_item', function (Blueprint $table) {
            $table->bigIncrements('bill_item_id');
            $table->integer('bill_id')->nullable();
            $table->integer('order_items_id')->nullable();
            // $table->unsignedBigInteger('bill_id');
            // $table->foreign('bill_id')->references('bill_id')->on('bill');
            // $table->unsignedBigInteger('order_items_id');
            // $table->foreign('order_items_id')->references('order_items_id')->on('order_items');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bill_item');
    }
}
