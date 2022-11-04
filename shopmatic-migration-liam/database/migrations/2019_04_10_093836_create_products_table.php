<?php

use App\Constants\ProductStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            
            $table->string('slug')->index();
            $table->string('associated_sku')->nullable()->index();
            $table->string('name');
            $table->json('options')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('short_description')->nullable();
            $table->mediumText('html_description')->nullable();
            $table->string('main_image')->nullable();
            $table->tinyInteger('status')->default(ProductStatus::DRAFT());
    
            //Counters
            $table->integer('total_quantity_sold')->default(0);
            $table->integer('total_orders')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
        DB::update("ALTER TABLE products AUTO_INCREMENT = 1100000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
