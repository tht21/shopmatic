<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExternalSourceToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('external_source')->nullable()->after('external_id');
            $table->dropColumn('shipment_provider');

            $table->decimal('actual_shipping_fee', 18, 4)->default(0)->after('shipping_fee');

            $table->timestamp('order_updated_at')->nullable()->after('order_placed_at');

            $table->unsignedBigInteger('parent_id')->nullable()->index()->after('customer_id');
            $table->foreign('parent_id')->references('id')->on('orders')->onDelete('set null');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->index()->after('shop_id');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            $table->json('cost_of_goods')->nullable()->after('buyer_paid');

            $table->unsignedBigInteger('product_inventory_id')->nullable()->index()->after('inventory_status');
            $table->foreign('product_inventory_id')->references('id')->on('product_inventories')->onDelete('set null');

            $table->json('data')->nullable()->after('product_inventory_id');

            $table->string('shipment_provider')->nullable()->after('fulfillment_status');
            $table->string('shipment_type')->nullable()->after('shipment_provider');
            $table->string('shipment_method')->nullable()->after('shipment_type');
            $table->decimal('actual_shipping_fee', 18, 4)->default(0)->after('shipping_fee');
            $table->string('tracking_number')->nullable()->after('shipment_method');

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->string('shipment_provider')->nullable()->after('fulfillment_type');
            $table->dropColumn(['external_source', 'parent_id', 'actual_shipping_fee', 'order_updated_at']);
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['account_id', 'product_inventory_id', 'cost_of_goods', 'data', 'shipment_provider', 'shipment_type',
                'shipment_method', 'tracking_number', 'actual_shipping_fee']);
            $table->dropSoftDeletes();
        });
    }
}
