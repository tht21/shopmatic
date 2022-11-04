<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Constants\ProductInventoryStatus;


class AddStatusToProductInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_inventories', function (Blueprint $table) {
            $table->tinyInteger('status')->default(ProductInventoryStatus::ENABLE())->after('enabled');;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_inventories', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
