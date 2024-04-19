<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityPortalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_portal', function (Blueprint $table) {
            $table->bigIncrements('activity_portal_id');
            $table->string('portal_name'); 
            $table->tinyInteger('status')->default('1');
            $table->dateTime('created_on');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_portal');
    }
}
