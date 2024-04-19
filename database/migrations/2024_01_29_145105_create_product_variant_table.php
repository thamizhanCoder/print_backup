<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVariantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variant', function (Blueprint $table) {
            $table->bigIncrements('product_variant_id');
            $table->integer('product_id')->nullable();
            // $table->unsignedBigInteger('product_id')->nullable();
            // $table->foreign('product_id')->references('product_id')->on('product');
            $table->string('variant_code')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('mrp',20,2)->default('0.00')->nullable();
            $table->decimal('selling_price',20,2)->default('0.00')->nullable();
            $table->text('variant_attributes')->nullable();
            $table->decimal('customized_price',20,2)->default('0.00')->nullable();
            $table->tinyInteger('set_as_default')->default('0');
            $table->integer('variant_type_id')->nullable();
            // $table->unsignedBigInteger('variant_type_id')->nullable();
            // $table->foreign('variant_type_id')->references('variant_type_id')->on('variant_type');
            $table->string('image')->nullable();
            $table->string('label')->nullable();
            $table->string('internal_variant_id')->nullable();
            $table->text('variant_options')->nullable();
            $table->integer('status')->default('1')->nullable();
            $table->dateTime('updated_on')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variant');
    }
}
