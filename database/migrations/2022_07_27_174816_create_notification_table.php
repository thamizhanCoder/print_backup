<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->bigIncrements('notification_id');
            $table->string('title', 500)->nullable();
            $table->text('body')->nullable();
            $table->string('user_type', 255)->comment('1-Admin : 2-User : 3-Employee')->nullable();
            $table->integer('sender')->nullable();
            $table->integer('receiver')->nullable();
            $table->string('module_name', 255)->nullable();
            $table->text('page')->nullable();
            $table->text('portal')->nullable();
            $table->text('data')->nullable();
            $table->tinyInteger('msg_read')->default('0')->comment('0.unread,1.read')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->tinyInteger('status')->default('1')->nullable();
            $table->string('random_id', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification');
    }
}
