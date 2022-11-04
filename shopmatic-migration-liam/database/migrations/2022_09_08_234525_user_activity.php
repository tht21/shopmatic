<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserActivity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_activity', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('ip_address')->nullable();

            $table->unsignedBigInteger('user_id')->index();
            
            $table->dateTime('login_timestamp')->nullable();

            $table->dateTime('logout_timestamp')->nullable();
            
            $table->string('session_id')->nullable();

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
        Schema::dropIfExists('user_activity');
    }
}
