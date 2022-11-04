<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductListingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_listings', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');

            $table->unsignedBigInteger('account_id')->index();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            $table->unsignedBigInteger('integration_id')->index();
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');

            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');

            $table->unsignedBigInteger('integration_category_id')->index()->nullable();
            $table->foreign('integration_category_id')->references('id')->on('integration_categories')->onDelete('SET NULL');

            $table->unsignedBigInteger('account_category_id')->index()->nullable();
            $table->foreign('account_category_id')->references('id')->on('account_categories')->onDelete('SET NULL');

            $table->json('identifiers')->nullable();

            $table->string('name')->nullable();

            $table->integer('stock')->nullable();
            $table->boolean('sync_stock')->default(true);

            $table->integer('total_sold')->default(0);

            $table->string('product_url')->nullable();
            
            $table->tinyInteger('status')->default(\App\Constants\MarketplaceProductStatus::LIVE()->getValue());

            $table->timestamps();

            $table->softDeletes();
        });

        DB::update("ALTER TABLE product_listings AUTO_INCREMENT = 1100000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_listings');
    }
}
