<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrackHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('track_history', function (Blueprint $table) {
            $table->bigIncrements('track_history_id');
            $table->integer('order_items_id')->nullable();
            // $table->unsignedBigInteger('order_items_id')->nullable();
            // $table->foreign('order_items_id')->references('order_items_id')->on('order_items');
            $table->string('notes')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('order_status')->comment('1.pending 2.approved 3.dispatched 4.cancelled 5.delivered 6.disapproved 7.waiting cod delivery 8.cod disapproved 9.cod approved')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('track_history');
    }
}
