<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskManagerPreviewHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_manager_preview_history', function (Blueprint $table) {
            $table->bigIncrements('task_manager_preview_history_id');
            $table->integer('task_manager_id')->nullable();
            // $table->unsignedBigInteger('task_manager_id')->nullable();
            // $table->foreign('task_manager_id')->references('task_manager_id')->on('task_manager');
            $table->string('preview_image')->nullable();
            $table->dateTime('preview_on')->nullable();
            $table->integer('preview_by')->nullable();
            $table->text('preview_reason')->nullable();
            $table->tinyInteger('preview_status')->comment('1-approved: 2-rejected')->nullable();
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
        Schema::dropIfExists('task_manager_preview_history');
    }
}
