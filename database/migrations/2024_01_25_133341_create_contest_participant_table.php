<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContestParticipantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contest_participant', function (Blueprint $table) {
            $table->bigIncrements('contest_participant_id');
            $table->integer('contest_id')->nullable();
            $table->integer('customer_id')->nullable();
            // $table->unsignedBigInteger('contest_id');
            // $table->foreign('contest_id')->references('contest_id')->on('contest');
            // $table->unsignedBigInteger('customer_id');
            // $table->foreign('customer_id')->references('customer_id')->on('customer');
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contest_participant');
    }
}
