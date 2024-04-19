<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVisitHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_visit_history', function (Blueprint $table) {
            $table->bigIncrements('product_visit_history_id');
            $table->integer('service_id')->nullable();
            // $table->unsignedBigInteger('service_id')->nullable();
            // $table->foreign('service_id')->references('service_id')->on('service');
            $table->dateTime('visited_on')->nullable();
            $table->text('ip_address')->nullable();
            $table->text('user_agent')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_visit_history');
    }
}
