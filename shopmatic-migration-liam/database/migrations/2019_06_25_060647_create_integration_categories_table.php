<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegrationCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integration_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('breadcrumb');
            $table->string('external_id')->index();
            $table->unsignedBigInteger('integration_id')->index();
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('CASCADE');
            $table->unsignedBigInteger('region_id')->index();
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('CASCADE');
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->foreign('parent_id')->references('id')->on('integration_categories')->onDelete('CASCADE');
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('SET NULL');
            $table->boolean('is_leaf');
            $table->boolean('visible')->default(true);
            $table->tinyInteger('flag')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('integration_categories');
    }
}
