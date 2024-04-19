<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('description')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->string('created_by', 500)->nullable();
            $table->string('activity_type', 250)->nullable();
            // $table->unsignedBigInteger('activity_type');
            // $table->foreign('activity_type_id')->references('activity_type_id')->on('activity_type');
            $table->integer('activity_portal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
}
