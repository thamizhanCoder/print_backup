<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppUpdateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_update', function (Blueprint $table) {
            $table->bigIncrements('app_update_id');
            $table->decimal('version',10,1)->nullable();
            $table->decimal('appstore_version',10,1)->nullable();
            $table->string('mode')->nullable();
            $table->string('playstore_url',1000)->nullable();
            $table->string('appstore_url',1000)->nullable();
            $table->string('appstore_id',1000)->nullable();
            $table->string('description',1500)->nullable();
            $table->string('title',1000)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_update');
    }
}
