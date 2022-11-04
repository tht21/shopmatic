<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSellerIntegrationShippingFeeToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('seller_shipping_fee', 18, 4)->after('actual_shipping_fee')->default(0);
            $table->decimal('integration_shipping_fee', 18, 4)->after('actual_shipping_fee')->default(0);
            $table->decimal('transaction_fee', 18, 4)->after('commission_fee')->default(0);
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
            $table->dropColumn(['seller_shipping_fee', 'integration_shipping_fee', 'transaction_fee']);
        });
    }
}
