<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->index(['integration_id', 'account_id', 'shop_id']);

            $table->unsignedBigInteger('integration_id')->nullable();
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');

            $table->unsignedBigInteger('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            $table->unsignedBigInteger('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');

            $table->json('order_item_ids')->nullable();
            $table->json('cancelled_order_item_ids')->nullable();
            $table->json('returned_order_item_ids')->nullable();

            $table->index(['day', 'month', 'year', 'day_of_year']);

            $table->unsignedTinyInteger('day')->nullable();
            $table->unsignedTinyInteger('week')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedSmallInteger('day_of_year')->nullable();

            $table->string('currency')->nullable();

            $table->decimal('total_revenue', 18, 4)->default(0);

            $table->unsignedBigInteger('total_customers')->default(0);

            $table->decimal('basket_value', 18,4)->default(0);
            $table->decimal('basket_size', 18,4)->default(0);

            $table->decimal('total_returned_value', 18,4)->default(0);
            $table->decimal('total_cancelled_value', 18,4)->default(0);

            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('total_returned_orders')->default(0);
            $table->unsignedInteger('total_cancelled_orders')->default(0);

            $table->decimal('total_discount', 18,4)->default(0);
            $table->decimal('total_shipping_fees', 18,4)->default(0);
            $table->decimal('gross_profit', 18,4)->default(0);

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
        Schema::dropIfExists('reports');
    }
}
