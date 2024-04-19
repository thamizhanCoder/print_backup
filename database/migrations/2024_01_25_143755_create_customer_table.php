<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer', function (Blueprint $table) {
            $table->bigIncrements('customer_id');
            $table->string('customer_from', 2555)->nullable();
            $table->string('platform', 255)->nullable();
            $table->string('customer_code', 255)->nullable();
            $table->string('customer_first_name', 255)->nullable();
            $table->string('customer_last_name', 255)->nullable();
            $table->string('mobile_no', 15)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('password', 255)->nullable();
            $table->text('profile_image')->nullable();
            $table->string('address', 255)->nullable();
            $table->integer('district_id')->nullable();
            $table->integer('state_id')->nullable();
            // $table->unsignedBigInteger('district_id');
            // $table->foreign('district_id')->references('district_id')->on('district');
            // $table->unsignedBigInteger('state_id');
            // $table->foreign('state_id')->references('state_id')->on('state');
            $table->string('auth_provider', 255)->nullable();
            $table->text('auth_provider_token')->nullable();
            $table->string('apple_email', 255)->nullable();
            $table->string('apple_auth_provider_id', 255)->nullable();
            $table->tinyInteger('gender')->nullable();
            $table->integer('otp')->nullable();
            $table->string('otp_verify_date', 255)->nullable();
            $table->string('check_profile_image', 255)->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('status')->default('1')->nullable();
            $table->tinyInteger('is_agree')->default('0')->nullable();
            $table->string('token', 5000)->nullable();
            $table->string('mbl_token', 500)->nullable();
            $table->string('billing_customer_first_name', 255)->nullable();
            $table->string('billing_customer_last_name', 255)->nullable();
            $table->string('billing_email', 255)->nullable();
            $table->string('billing_mobile_number', 255)->nullable();
            $table->string('billing_alt_mobile_number', 255)->nullable();
            $table->integer('billing_country_id')->nullable();
            $table->integer('billing_state_id')->nullable();
            $table->integer('billing_city_id')->nullable();
            // $table->unsignedBigInteger('billing_country_id');
            // $table->foreign('billing_country_id')->references('country_id')->on('country');
            // $table->unsignedBigInteger('billing_state_id');
            // $table->foreign('billing_state_id')->references('state_id')->on('state');
            // $table->unsignedBigInteger('billing_city_id');
            // $table->foreign('billing_city_id')->references('district_id')->on('district');
            $table->string('billing_address_1', 500)->nullable();
            $table->text('billing_address_2')->nullable();
            $table->string('billing_place', 255)->nullable();
            $table->string('billing_landmark', 255)->nullable();
            $table->string('billing_pincode', 11)->nullable();
            $table->string('billing_gst_no', 50)->nullable();
            $table->string('other_district', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer');
    }
}
