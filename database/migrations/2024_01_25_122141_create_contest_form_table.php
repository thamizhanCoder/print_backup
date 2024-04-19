<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContestFormTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contest_form', function (Blueprint $table) {
            $table->bigIncrements('contest_form_id');
            $table->integer('contest_id')->nullable();
            $table->integer('contest_field_type_id')->nullable();
            // $table->unsignedBigInteger('contest_id');
            // $table->foreign('contest_id')->references('contest_id')->on('contest');
            // $table->unsignedBigInteger('contest_field_type_id');
            // $table->foreign('contest_field_type_id')->references('contest_field_type_id')->on('contest_field_type');
            $table->string('label_name', 255)->nullable();
            $table->text('validation_rules')->nullable();
            $table->text('value')->nullable();
            $table->text('notes')->nullable();
            $table->tinyInteger('is_multiple')->nullable();
            $table->tinyInteger('is_required')->default('0')->nullable();
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
        Schema::dropIfExists('contest_form');
    }
}
