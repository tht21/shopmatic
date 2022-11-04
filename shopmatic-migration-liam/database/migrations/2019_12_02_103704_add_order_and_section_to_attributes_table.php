<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderAndSectionToAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('integration_category_attributes', function (Blueprint $table) {
            $table->tinyInteger('order')->default(0)->after('level');
            $table->tinyInteger('section')->default(0)->after('order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('integration_category_attributes', function (Blueprint $table) {
            $table->dropColumn(['order', 'section']);
        });
    }
}
