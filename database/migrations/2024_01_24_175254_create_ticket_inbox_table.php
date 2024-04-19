<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketInboxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_inbox', function (Blueprint $table) {
            $table->bigIncrements('ticket_inbox_id');
            $table->integer('tickets_id')->nullable();
            // $table->unsignedBigInteger('tickets_id')->nullable();
            // $table->foreign('tickets_id')->references('tickets_id')->on('tickets');
            $table->text('messages')->nullable();
            $table->text('attachments')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('acl_user_id')->nullable();
            // $table->unsignedBigInteger('customer_id')->nullable();
            // $table->foreign('customer_id')->references('customer_id')->on('customer');
            // $table->unsignedBigInteger('acl_user_id')->nullable();
            // $table->foreign('acl_user_id')->references('acl_user_id')->on('acl_user');
            $table->dateTime('reply_on')->nullable();
            $table->decimal('ratings',4,2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_inbox');
    }
}
