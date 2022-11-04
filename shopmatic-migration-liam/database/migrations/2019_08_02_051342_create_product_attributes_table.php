<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->unsignedBigInteger('product_id')->index();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            
            $table->unsignedBigInteger('product_listing_id')->nullable()->index();
            $table->foreign('product_listing_id')->references('id')->on('product_listings')->onDelete('cascade');
            
            $table->unsignedBigInteger('integration_id')->nullable()->index();
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');
            
            $table->string('name');
            $table->mediumText('value');
            $table->timestamps();
        });
        DB::update("ALTER TABLE product_attributes AUTO_INCREMENT = 11100000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_attributes');
    }
}
