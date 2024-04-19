<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotifymeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifyme', function (Blueprint $table) {
            $table->bigIncrements('notifyme_id');
            $table->integer('customer_id')->nullable();
            // $table->unsignedBigInteger('customer_id');
            // $table->foreign('customer_id')->references('customer_id')->on('customer');
            $table->string('email', 255)->nullable();
            $table->integer('product_id')->nullable();
            $table->integer('product_variant_id')->nullable();
            // $table->unsignedBigInteger('product_id');
            // $table->foreign('product_id')->references('product_id')->on('product');
            // $table->unsignedBigInteger('product_variant_id');
            // $table->foreign('product_variant_id')->references('product_variant_id')->on('product_variant');
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->string('customer_registered_from', 255)->nullable();
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
        Schema::dropIfExists('notifyme');
    }
}
