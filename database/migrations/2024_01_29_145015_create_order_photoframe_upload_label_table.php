<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPhotoframeUploadLabelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_photoframe_upload_label', function (Blueprint $table) {
            $table->bigIncrements('order_photoframe_upload_label_id');
            $table->integer('order_items_id')->nullable();
            // $table->unsignedBigInteger('order_items_id');
            // $table->foreign('order_items_id')->references('order_items_id')->on('order_items');
            $table->string('label_name', 500)->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('created_by')->nullable();
            $table->string('qc_image', 500)->nullable();
            $table->dateTime('qc_on')->nullable();
            $table->integer('qc_by')->nullable();
            $table->text('qc_reason')->nullable();
            $table->integer('qc_status')->default('0')->comment('1.approved:2.rejected')->nullable();
            $table->string('preview_image', 500)->nullable();
            $table->dateTime('preview_on')->nullable();
            $table->integer('preview_by')->nullable();
            $table->text('preview_reason')->nullable();
            $table->integer('preview_status')->default('0')->comment('1.approved:2.rejected')->nullable();
            $table->dateTime('qc_reason_on')->nullable();
            $table->dateTime('preview_reason_on')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_photoframe_upload_label');
    }
}
