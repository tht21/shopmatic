<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->json('region_ids')->nullable();
            $table->string('name');
            $table->string('thumbnail_image')->nullable();
            $table->json('sync_data')->nullable();
            $table->json('features')->nullable();
            $table->json('settings')->nullable();
            $table->tinyInteger('type');
            $table->tinyInteger('visibility')->default(0);
            $table->smallInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        DB::update("ALTER TABLE integrations AUTO_INCREMENT = 10000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('integrations');
    }
}
