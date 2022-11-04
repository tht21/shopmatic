<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name');
            $table->string('external_id')->index();
            $table->unsignedBigInteger('integration_id')->index();
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('CASCADE');
            $table->unsignedBigInteger('region_id')->index();
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('CASCADE');
            
            $table->boolean('visible')->default(true);
            $table->tinyInteger('flag')->nullable();

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
        Schema::dropIfExists('brands');
    }
}
