<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisitHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visit_history', function (Blueprint $table) {
            $table->bigIncrements('visit_history_id');
            $table->string('ip_address')->nullable();
            $table->string('page_type')->nullable();
            $table->string('user_agent')->nullable();
            $table->dateTime('visited_on')->nullable();
            $table->string('visited_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visit_history');
    }
}
