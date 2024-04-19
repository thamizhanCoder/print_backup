<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('order_items_id');
            $table->integer('product_variant_id')->nullable();
            $table->integer('order_id')->nullable();
            $table->integer('service_id')->nullable();
            // $table->unsignedBigInteger('product_variant_id');
            // $table->foreign('product_variant_id')->references('product_variant_id')->on('product_variant');
            // $table->unsignedBigInteger('order_id');
            // $table->foreign('order_id')->references('order_id')->on('orders');
            // $table->unsignedBigInteger('service_id');
            // $table->foreign('service_id')->references('service_id')->on('service');
            $table->string('image', 500)->comment('passport image')->nullable();
            $table->string('images', 500)->comment('selfie images')->nullable();
            $table->string('background_color', 500)->nullable();
            $table->text('variant_attributes')->nullable();
            $table->text('frames')->nullable();
            $table->integer('cart_type')->comment('1.add to cart 2.buynow')->nullable();
            $table->integer('product_id')->nullable();
            // $table->unsignedBigInteger('product_id');
            // $table->foreign('product_id')->references('product_id')->on('product');
            $table->integer('quantity')->nullable();
            $table->decimal('unit_price', 20, 2)->nullable();
            $table->decimal('additional_price', 20, 2)->nullable();
            $table->decimal('sub_total', 20, 2)->nullable();
            $table->decimal('cod_charge', 20, 2)->nullable();
            $table->integer('order_status')->default('1')->comment('1.pending 2.approved 3.dispatched 4.cancelled 5.delivered 6.disapproved 7.packed 8.cod disapproved 9.cod approved 10.approved task');
            $table->tinyInteger('cod_status')->default('0')->comment('1.cod pending 2.cod approved 3.cod disapproved 4 cod dispatched 5.cod delivered 6.cancelled 7.packed 8.approved task');
            $table->dateTime('approved_on')->nullable();
            $table->dateTime('disapproved_on')->nullable();
            $table->dateTime('shipped_on')->nullable();
            $table->dateTime('dispatched_on')->nullable();
            $table->dateTime('cancelled_on')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->dateTime('delivered_on')->nullable();
            $table->tinyInteger('is_cod')->default('2')->comment('1.cod : 2.others');
            $table->tinyInteger('is_receivedby')->nullable();
            $table->string('receivedby_name', 500)->nullable();
            $table->tinyInteger('is_refund')->default('0')->nullable();
            $table->string('refund_reason', 500)->nullable();
            $table->decimal('refund_amount', 20, 2)->default('0.00');
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('status')->default('1')->nullable();
            $table->text('photoprint_variant')->nullable();
            $table->decimal('delivery_charge', 20, 2)->nullable();
            $table->integer('is_customized')->nullable();
            $table->string('product_name', 500)->nullable();
            $table->string('product_code', 500)->nullable();
            $table->string('print_size', 500)->nullable();
            $table->text('customer_description')->nullable();
            $table->text('designer_description')->nullable();
            $table->text('product_description')->nullable();
            $table->text('product_specification')->nullable();
            $table->decimal('p_mrp', 20, 2)->nullable();
            $table->decimal('p_selling_price', 20, 2)->nullable();
            $table->decimal('first_copy_selling_price', 20, 2)->nullable();
            $table->decimal('additional_copy_selling_price', 20, 2)->nullable();
            $table->string('thumbnail_image', 500)->nullable();
            $table->integer('pv_is_customized')->nullable();
            $table->string('variant_code', 500)->nullable();
            $table->decimal('pv_mrp', 20, 2)->nullable();
            $table->decimal('pv_selling_price', 20, 2)->nullable();
            $table->text('pv_variant_attributes')->nullable();
            $table->decimal('customized_price', 20, 2)->nullable();
            $table->string('courier_no', 500)->nullable();
            $table->string('courier_name', 500)->nullable();
            $table->integer('shipped_vendor_details_id')->nullable();
            $table->string('delivered_transaction_id', 500)->nullable();
            $table->decimal('delivered_amount', 20, 2)->nullable();
            $table->dateTime('refunded_on')->nullable();
            $table->integer('refunded_by')->nullable();
            $table->string('photoprint_width', 500)->nullable();
            $table->string('photoprint_height', 500)->nullable();
            $table->text('gst_value')->nullable();
            $table->text('category_name')->nullable();
            $table->tinyInteger('production_status')->default('0');
            $table->tinyInteger('is_received_payment')->default('0');
            $table->integer('cancel_reason_id')->nullable();
            $table->text('bill_no')->nullable();
            $table->integer('payment_transcation_id')->nullable();
            $table->string('variant_type_name', 500)->nullable();
            $table->string('variant_label', 500)->nullable();
            $table->tinyInteger('payment_status')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
