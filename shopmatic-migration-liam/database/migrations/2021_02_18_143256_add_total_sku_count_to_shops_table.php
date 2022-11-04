<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalSkuCountToShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->unsignedInteger('total_sku_count')->default(0)->after('e2e');
        });
        $shops = \App\Models\Shop::all();
        foreach ($shops as $shop) {
            $shop->total_sku_count = $shop->inventories()->count();
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
            $table->dropColumn('total_sku_count');
        });
    }
}
