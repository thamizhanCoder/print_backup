<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommunicationInboxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('communication_inbox', function (Blueprint $table) {
            $table->bigIncrements('communication_inbox_id');
            $table->integer('communication_id')->nullable();
            // $table->unsignedBigInteger('communication_id');
            // $table->foreign('communication_id')->references('communication_id')->on('communication');
            $table->text('messages')->nullable();
            $table->text('attachments')->nullable();
            $table->integer('employee_id')->nullable();
            $table->integer('acl_user_id')->nullable();
            $table->dateTime('reply_on')->nullable();
            $table->text('folder')->nullable();
            $table->tinyInteger('is_read')->default('0')->nullable();
            $table->decimal('ratings', 4, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('communication_inbox');
    }
}
