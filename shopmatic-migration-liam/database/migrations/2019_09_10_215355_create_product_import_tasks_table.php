<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductImportTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_import_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
    
            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
    
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
    
            $table->string('source_type');
            $table->string('source');
            $table->index(['source_type', 'source']);
    
            $table->json('messages')->nullable();
            $table->json('settings')->nullable();
            
            $table->integer('total_products')->default(0);
            $table->tinyInteger('status')->default(\App\Constants\JobStatus::PENDING());
            
            $table->timestamps();
        });
        DB::update("ALTER TABLE product_import_tasks AUTO_INCREMENT = 110000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('import_tasks');
    }
}
