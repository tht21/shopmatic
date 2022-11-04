<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductInventoryTrailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_inventory_trails', function (Blueprint $table) {
            $table->bigIncrements('id');
    
            $table->unsignedBigInteger('product_inventory_id')->nullable()->index();
            $table->foreign('product_inventory_id', 'product_inventory_trail_inv_id')->references('id')->on('product_inventories')->onDelete('cascade');
    
            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            
            $table->string('message')->nullable();
            
            $table->string('related_id')->nullable();
            $table->string('related_type')->nullable();
            
            $table->index(['related_id', 'related_type']);
            
            $table->integer('old');
            $table->integer('new');
            
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
        Schema::dropIfExists('product_inventory_trails');
    }
}
