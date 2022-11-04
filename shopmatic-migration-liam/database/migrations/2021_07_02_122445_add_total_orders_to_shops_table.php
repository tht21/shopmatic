<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalOrdersToShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->unsignedInteger('total_orders_count')->default(0)->after('total_sku_count');
        });
        foreach (\App\Models\Shop::all() as $shop) {
            $shop->total_orders_count = $shop->orders()->where('type', '<>', \App\Constants\OrderType::SHADOW()->getValue())->count();
            $shop->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('total_orders_count');
        });
    }
}
