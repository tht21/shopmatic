<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug')->index();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('currency', 3)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        DB::update("ALTER TABLE shops AUTO_INCREMENT = 1100;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shops');
    }
}
