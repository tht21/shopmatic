<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->unsignedBigInteger('linked_location_id')->nullable()->index();
            $table->foreign('linked_location_id')->references('id')->on('locations')->onDelete('cascade');
            
            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');

            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            
            $table->string('external_id')->nullable()->index();
            
            $table->string('label');
            
            $table->string('contact_name')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('contact_email')->nullable();
            
            $table->string('name')->nullable();
            
            $table->text('full_address')->nullable();
            
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            
            $table->string('postcode', 20)->nullable();
            $table->string('country')->nullable();
            
            $table->boolean('has_inventory')->default(true);
            $table->tinyInteger('type');
            
            $table->mediumInteger('position')->default(0);
            
            $table->json('attributes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
}
