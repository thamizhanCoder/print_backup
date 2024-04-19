<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAclPermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acl_permission', function (Blueprint $table) {
            $table->bigIncrements('acl_permission_id');
            $table->unsignedBigInteger('acl_role_id');
            $table->foreign('acl_role_id')->references('acl_role_id')->on('acl_role');
            $table->unsignedBigInteger('acl_menu_id');
            $table->foreign('acl_menu_id')->references('acl_menu_id')->on('acl_menu');
            $table->unsignedBigInteger('acl_menu_module_id');
            $table->foreign('acl_menu_module_id')->references('acl_menu_module_id')->on('acl_menu_module');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('acl_permission');
    }
}
