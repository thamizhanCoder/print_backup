<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee', function (Blueprint $table) {
            $table->bigIncrements('employee_id');
            $table->string('employee_code', 255)->nullable();
            $table->string('employee_name', 255)->nullable();
            $table->text('employee_image')->nullable();
            $table->integer('employee_type')->comment('1.Inhouse,2.Vendor')->nullable();
            $table->integer('department_id')->nullable();
            // $table->unsignedBigInteger('department_id');
            // $table->foreign('department_id')->references('department_id')->on('department');
            $table->string('mobile_no', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('password', 500)->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('status')->default('1')->comment('1.Active,0.Inactive,2.Delete');
            $table->text('fcm_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee');
    }
}
