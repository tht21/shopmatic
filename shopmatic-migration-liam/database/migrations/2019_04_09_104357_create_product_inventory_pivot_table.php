<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductInventoryPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_inventory_pivot', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_inventory_id')->index();
            $table->foreign('product_inventory_id')->references('id')->on('product_inventories')->onDelete('cascade');
            $table->unsignedBigInteger('deduct_product_inventory_id')->index();
            $table->foreign('deduct_product_inventory_id')->references('id')->on('product_inventories')->onDelete('cascade');
            $table->unsignedInteger('deduct_amount')->default(1);
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
        Schema::dropIfExists('product_inventory_pivot');
    }
}
