<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product', function (Blueprint $table) {
            $table->bigIncrements('product_id');
            $table->integer('service_id')->nullable();
            $table->integer('category_id')->nullable();
            // $table->unsignedBigInteger('service_id')->nullable();
            // $table->foreign('service_id')->references('service_id')->on('service');
            // $table->unsignedBigInteger('category_id')->nullable();
            // $table->foreign('category_id')->references('category_id')->on('category');
            $table->string('product_code')->nullable();
            $table->string('product_name')->nullable();
            $table->string('no_of_images')->nullable();
            $table->string('print_size')->nullable();
            $table->text('frame_details')->nullable();
            $table->text('label_name_details')->nullable();
            $table->text('customer_description')->nullable();
            $table->text('designer_description')->nullable();
            $table->text('product_description')->nullable();
            $table->text('product_specification')->nullable();
            $table->text('help_url')->nullable();
            $table->decimal('mrp',20,2)->nullable();
            $table->decimal('selling_price',20,2)->nullable();
            $table->decimal('first_copy_selling_price',20,2)->nullable();
            $table->decimal('additional_copy_selling_price',20,2)->nullable();
            $table->integer('gst_percentage')->nullable();
            $table->tinyInteger('is_cod_available')->default('0');
            $table->text('product_image')->nullable();
            $table->text('thumbnail_image')->nullable();
            $table->text('selected_variants')->nullable();
            $table->text('primary_variant_details')->nullable();
            $table->tinyInteger('is_multivariant_available')->default('0')->comment('0.no ,1.yes')->nullable();
            $table->tinyInteger('is_related_product_available')->default(NULL)->comment('0.no ,1.yes')->nullable();
            $table->tinyInteger('is_customized')->default('0')->comment('0.no ,1.yes');
            $table->tinyInteger('is_notification')->default('0')->comment('	0.off, 1.on')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->tinyInteger('is_publish')->default('2')->comment('	2-un publish : 1-publish	');
            $table->tinyInteger('is_colour')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product');
    }
}
