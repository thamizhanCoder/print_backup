<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePasswordresetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('passwordreset', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->string('validity_hours')->nullable();
            $table->string('expired_time')->nullable();
            $table->integer('acl_user_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('employee_id')->nullable();
            // $table->unsignedBigInteger('acl_user_id')->nullable();
            // $table->foreign('acl_user_id')->references('acl_user_id')->on('acl_user');
            // $table->unsignedBigInteger('customer_id')->nullable();
            // $table->foreign('customer_id')->references('customer_id')->on('customer');
            // $table->unsignedBigInteger('employee_id')->nullable();
            // $table->foreign('employee_id')->references('employee_id')->on('employee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('passwordreset');
    }
}
