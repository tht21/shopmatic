<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('breadcrumb');
            $table->string('external_id');
            $table->unsignedBigInteger('account_id')->index();
            $table->foreign('account_id')->references('id')->on('integrations')->onDelete('CASCADE');
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->foreign('parent_id')->references('id')->on('account_categories')->onDelete('CASCADE');
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('SET NULL');
            $table->boolean('is_leaf');
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
        Schema::dropIfExists('account_categories');
    }
}
