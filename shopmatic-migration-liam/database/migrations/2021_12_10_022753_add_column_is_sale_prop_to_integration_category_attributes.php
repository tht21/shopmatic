<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnIsSalePropToIntegrationCategoryAttributes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('integration_category_attributes', function (Blueprint $table) {
            $table->boolean('is_sale_prop')->nullable();
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
            $table->dropColumn('is_sale_prop');
        });
    }
}
