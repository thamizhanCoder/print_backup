<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSelfieContestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('selfie_contest', function (Blueprint $table) {
            $table->bigIncrements('selfie_contest_id');
            $table->string('selfie_contest_name')->nullable();
            $table->integer('selfie_contest_mbl_no')->nullable();
            $table->string('selfie_contest_email')->nullable();
            $table->text('selfie_contest_address')->nullable();
            $table->text('selfie_contest_image')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('status')->default('0')->comment('1.Active,0.Inactive,2.Delete')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('selfie_contest');
    }
}
