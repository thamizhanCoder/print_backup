<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAclMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acl_menu', function (Blueprint $table) {
            $table->bigIncrements('acl_menu_id');
            $table->unsignedBigInteger('acl_menu_module_id')->nullable();
            $table->foreign('acl_menu_module_id')->references('acl_menu_module_id')->on('acl_menu_module');
            $table->string('menu_name',255)->nullable();
            $table->string('icon_type',255)->nullable();
            $table->string('icon',255)->nullable();
            $table->string('url_type',255)->nullable();
            $table->string('url',255)->nullable();
            $table->string('url_target',255)->nullable();
            $table->string('menu_class')->nullable();
            $table->tinyInteger('active')->default('1')->nullable();
             $table->integer('parent')->nullable();
             $table->integer('sort')->nullable();
            $table->string('access_key',255)->nullable();
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('acl_menu');
    }
}
