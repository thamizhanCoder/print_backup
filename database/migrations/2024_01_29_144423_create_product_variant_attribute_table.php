<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVariantAttributeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variant_attribute', function (Blueprint $table) {
            $table->bigIncrements('product_variant_attribute_id');
            $table->integer('product_variant_id')->nullable();
            $table->integer('variant_type_id')->nullable();
            // $table->unsignedBigInteger('product_variant_id')->nullable();
            // $table->foreign('product_variant_id')->references('product_variant_id')->on('product_variant');
            // $table->unsignedBigInteger('variant_type_id')->nullable();
            // $table->foreign('variant_type_id')->references('variant_type_id')->on('variant_type');
            $table->string('value')->nullable();
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
        Schema::dropIfExists('product_variant_attribute');
    }
}
