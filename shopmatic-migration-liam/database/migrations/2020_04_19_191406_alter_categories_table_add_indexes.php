<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCategoriesTableAddIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->index(['breadcrumb']);
        });
        Schema::table('integration_categories', function (Blueprint $table) {
            $table->index(['breadcrumb']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['breadcrumb']);
        });
        Schema::table('integration_categories', function (Blueprint $table) {
            $table->dropColumn(['breadcrumb']);
        });
    }
}
