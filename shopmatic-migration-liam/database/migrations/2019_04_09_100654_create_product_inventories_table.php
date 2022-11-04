<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_inventories', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            
            $table->string('sku')->index();
            $table->string('name')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('low_stock_notification')->default(5);
            $table->boolean('out_of_stock_notification')->default(true);
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
        DB::update("ALTER TABLE product_inventories AUTO_INCREMENT = 1100000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_inventories');
    }
}
