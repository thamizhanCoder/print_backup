<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->bigIncrements('tickets_id');
            $table->string('ticket_no')->nullable();
            $table->integer('order_items_id')->nullable();
            // $table->unsignedBigInteger('order_items_id')->nullable();
            // $table->foreign('order_items_id')->references('order_items_id')->on('order_items');
            $table->string('subject')->nullable();
            $table->tinyInteger('priority')->comment('0-High, 1-Medium, 2-Low');
            $table->tinyInteger('status')->default('0')->comment('0-latest, 1-opened, 2.reply, 3-closed	');
            $table->dateTime('created_on')->nullable();
            $table->dateTime('closed_on')->nullable();
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
        Schema::dropIfExists('tickets');
    }
}
