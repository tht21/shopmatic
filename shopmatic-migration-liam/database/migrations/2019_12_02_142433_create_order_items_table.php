<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('order_id')->index();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');

            $table->unsignedBigInteger('integration_id')->nullable()->index();
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');

            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');

            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('set null');

            $table->string('external_id')->index()->nullable();
            $table->string('external_product_id')->index()->nullable();

            $table->string('name')->nullable();
            $table->string('sku')->index()->nullable();

            $table->string('variation_name')->nullable();
            $table->string('variation_sku')->index()->nullable();

            $table->integer('quantity');

            $table->decimal('item_price', 18, 4)->nullable();
            $table->decimal('integration_discount', 18, 4)->default(0);
            $table->decimal('seller_discount', 18, 4)->default(0);
            $table->decimal('shipping_fee', 18, 4)->default(0);
            $table->decimal('tax', 18, 4)->nullable();
            $table->decimal('tax_2', 18, 4)->nullable();
            $table->decimal('grand_total', 18, 4);
            $table->decimal('buyer_paid', 18, 4)->nullable();

            $table->tinyInteger('fulfillment_status');
            $table->tinyInteger('return_status')->nullable();
            $table->tinyInteger('inventory_status');

            $table->timestamps();
        });
        DB::update("ALTER TABLE order_items AUTO_INCREMENT = 1100000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
