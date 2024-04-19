<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('order_id');
            $table->string('order_code', 255)->nullable();
            $table->integer('customer_id')->nullable();
            // $table->unsignedBigInteger('customer_id');
            // $table->foreign('customer_id')->references('customer_id')->on('customer');
            $table->string('customer_code', 255)->nullable();
            $table->string('order_from', 255)->nullable();
            $table->string('order_time', 255)->nullable();
            $table->string('payment_mode', 255)->nullable();
            $table->string('paytm_payment_mode', 255)->nullable();
            $table->string('payment_transcation_id', 255)->nullable();
            $table->tinyInteger('payment_status')->default('0')->comment('0.Unpaid 1.Paid 2.Failure')->nullable();
            $table->tinyInteger('payment_delivery_status')->default('0')->nullable();
            $table->tinyInteger('paytm_payment_status')->nullable();
            $table->decimal('payment_amount', 20, 2)->nullable();
            $table->decimal('shipping_cost', 20, 2)->nullable();
            $table->integer('total_quantity')->nullable();
            $table->decimal('order_roundoff', 20, 2)->nullable();
            $table->decimal('order_totalamount', 20, 2)->nullable();
            $table->decimal('cancelled_order_totalamount', 20, 2)->nullable();
            $table->decimal('payment_service_charge', 20, 2)->nullable();
            $table->decimal('cod_total', 20, 2)->nullable();
            $table->dateTime('payment_transaction_date')->nullable();
            $table->dateTime('order_date')->nullable();
            $table->tinyInteger('is_cod')->default('2')->comment('1.cod : 2.others');
            $table->string('billing_customer_first_name', 255)->nullable();
            $table->string('billing_customer_last_name', 255)->nullable();
            $table->string('billing_email', 255)->nullable();
            $table->string('billing_mobile_number', 255)->nullable();
            $table->string('billing_alt_mobile_number', 255)->nullable();
            $table->integer('billing_country_id')->nullable();
            $table->integer('billing_state_id')->nullable();
            $table->integer('billing_city_id')->nullable();
            $table->string('billing_address_1', 255)->nullable();
            $table->string('billing_address_2', 255)->nullable();
            $table->string('billing_place', 255)->nullable();
            $table->string('billing_landmark', 255)->nullable();
            $table->integer('billing_pincode')->nullable();
            $table->string('coupon_code', 255)->nullable();
            $table->string('coupon_code_percentage', 255)->nullable();
            $table->decimal('coupon_amount', 20, 2)->nullable();
            $table->string('billing_gst_no', 255)->nullable();
            $table->string('billing_courier_type', 255)->nullable();
            $table->tinyInteger('is_received_payment')->default('0')->comment('1.yes : 2.no');
            $table->integer('delivery_total_amount')->nullable();
            $table->string('confirm_billno', 255)->nullable();
            $table->tinyInteger('order_billstatus')->default('0');
            $table->string('cancel_reason', 255)->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->string('paytm_response', 1500)->nullable();
            $table->string('enable_payment_mode', 1500)->nullable();
            $table->dateTime('waiting_cod_approved_on')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
