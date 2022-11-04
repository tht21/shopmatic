<?php

use App\Constants\Dimension;
use App\Constants\ProductStatus;
use App\Constants\ShippingType;
use App\Constants\Weight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
    
            $table->unsignedBigInteger('product_id')->index();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    
            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            
            $table->string('name');
            
            $table->string('option_1')->nullable();
            $table->string('option_2')->nullable();
            $table->string('option_3')->nullable();
            
            $table->unsignedBigInteger('inventory_id')->nullable();
            $table->string('sku')->index()->nullable();
            $table->string('barcode')->index()->nullable();
            
            $table->string('main_image')->nullable();
            
            $table->integer('stock')->default(0);
            
            $table->string('currency', 3)->nullable();
            $table->decimal('price', 18, 4)->nullable();
            
            $table->tinyInteger('position')->default(0);
            $table->tinyInteger('status')->default(ProductStatus::DRAFT());
            $table->tinyInteger('shipping_type')->default(ShippingType::MARKETPLACE());
            
            $table->decimal('weight', 10, 2)->default(0);
            $table->tinyInteger('weight_unit')->default(Weight::GRAMS());
            
            $table->decimal('length', 10, 2)->default(0);
            $table->decimal('width', 10, 2)->default(0);
            $table->decimal('height', 10, 2)->default(0);
            
            $table->tinyInteger('dimension_unit')->default(Dimension::CM());
            
            $table->integer('total_quantity_sold')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
        DB::update("ALTER TABLE product_variants AUTO_INCREMENT = 1100000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variants');
    }
}
