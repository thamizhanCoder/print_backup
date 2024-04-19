<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_user', function (Blueprint $table) {
            $table->bigIncrements('chat_user_id');
            $table->string('customer_name', 255)->nullable();
            $table->string('user_type', 255)->comment('customer, employee')->nullable();
            $table->text('profile_image')->nullable();
            $table->text('socketId')->nullable();
            $table->text('table_unique_id')->comment('refers customer table if user_type is customer else employee')->nullable();
            $table->string('online', 255)->nullable();
            $table->dateTime('last_active_at')->nullable();
            $table->text('token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_user');
    }
}
