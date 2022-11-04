<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegrationCategoryAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integration_category_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('integration_category_id')->index();
            $table->foreign('integration_category_id')->references('id')->on('integration_categories')->onDelete('cascade');
            $table->unsignedBigInteger('integration_id')->index();
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');
            $table->string('name');
            $table->string('label');
            $table->boolean('required');
            $table->json('data')->nullable();
            $table->json('additional_data')->nullable();
            $table->text('html_hint')->nullable();
            $table->tinyInteger('type');
            $table->tinyInteger('level');
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
        Schema::dropIfExists('integration_category_attributes');
    }
}
