<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponCodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupon_code', function (Blueprint $table) {
            $table->bigIncrements('coupon_code_id');
            $table->string('coupon_code', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('percentage', 255)->nullable();
            $table->decimal('set_min_amount', 20, 2)->nullable();
            $table->tinyInteger('is_limit_for_discount')->default('0')->comment('1.yes , 2.no');
            $table->integer('total_usage_limit_no_of_discount')->nullable();
            $table->tinyInteger('limit_to_use_per_customer')->default('0')->comment('1.yes , 2.no')->nullable();
            $table->tinyInteger('customer_eligibility')->default('0')->comment('1.new user , 2.for every one')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->time('start_time')->nullable();
            $table->dateTime('set_end_date')->nullable();
            $table->time('set_end_time')->nullable();
            $table->tinyInteger('status')->default('1')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupon_code');
    }
}
