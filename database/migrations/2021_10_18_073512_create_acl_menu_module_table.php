<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAclMenuModuleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acl_menu_module', function (Blueprint $table) {
            $table->bigIncrements('acl_menu_module_id');
            $table->string('name')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->tinyInteger('view_type')->default('1')->comment('1-list-view,2-tabular-view');
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
        Schema::dropIfExists('acl_menu_module');
    }
}
