<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->string('external_id')->nullable();
    
            $table->unsignedBigInteger('source_account_id')->nullable()->index();
            $table->foreign('source_account_id')->references('id')->on('accounts')->onDelete('cascade');
    
            $table->unsignedBigInteger('product_id')->index();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
    
            $table->unsignedBigInteger('product_listing_id')->nullable()->index();
            $table->foreign('product_listing_id')->references('id')->on('product_listings')->onDelete('cascade');
    
            $table->string('source_url')->nullable();
            $table->string('image_url')->nullable();
            
            $table->unsignedMediumInteger('height')->nullable();
            $table->unsignedMediumInteger('width')->nullable();
            
            $table->integer('position')->default(0);
            $table->timestamps();
        });
        DB::update("ALTER TABLE product_images AUTO_INCREMENT = 2100000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_images');
    }
}
