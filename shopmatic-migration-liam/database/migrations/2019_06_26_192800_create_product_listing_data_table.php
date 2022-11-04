<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductListingDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_listing_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_listing_id');
            $table->foreign('product_listing_id')->references('id')->on('product_listings')->onDelete('cascade');
            $table->mediumText('raw_data')->nullable();
            $table->timestamps();
        });
        DB::update("ALTER TABLE product_listing_data AUTO_INCREMENT = 1100000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_listing_data');
    }
}
