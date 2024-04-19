<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContestParticipantFormTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contest_participant_form', function (Blueprint $table) {
            $table->bigIncrements('contest_participant_form_id');
            $table->integer('contest_participant_id')->nullable();
            $table->integer('contest_form_id')->nullable();
            // $table->unsignedBigInteger('contest_participant_id');
            // $table->foreign('contest_participant_id')->references('contest_participant_id')->on('contest_participant');
            // $table->unsignedBigInteger('contest_form_id');
            // $table->foreign('contest_form_id')->references('contest_form_id')->on('contest_form');
            $table->text('value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contest_participant_form');
    }
}
