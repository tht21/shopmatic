<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');

            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            $table->unsignedBigInteger('integration_id')->nullable()->index();
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');

            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');

            $table->string('external_id')->nullable()->index();
            $table->string('external_order_number')->nullable()->index();

            $table->string('customer_name')->index()->nullable();
            $table->string('customer_email')->index()->nullable();

            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();

            $table->dateTime('ship_by_date')->nullable();

            $table->string('currency', 3);
            $table->decimal('integration_discount', 18, 4)->default(0);
            $table->decimal('seller_discount', 18, 4)->default(0);
            $table->decimal('shipping_fee', 18, 4)->default(0);
            $table->decimal('tax', 18, 4)->default(0);
            $table->decimal('tax_2', 18, 4)->default(0);
            $table->decimal('commission_fee', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->decimal('buyer_paid', 18, 4)->default(0);
            $table->decimal('settlement_amount', 18, 4)->nullable();

            $table->tinyInteger('payment_status');
            $table->string('payment_method')->nullable();
            $table->tinyInteger('fulfillment_status');
            $table->tinyInteger('fulfillment_type');
            $table->string('shipment_provider')->nullable();
            $table->text('buyer_remarks')->nullable();
            $table->text('notes')->nullable();

            $table->tinyInteger('type');
            $table->json('data')->nullable();
            $table->json('internal_data')->nullable();

            $table->timestamp('order_placed_at');
            $table->timestamp('order_paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
        DB::update("ALTER TABLE orders AUTO_INCREMENT = 110000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
