<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('tickets', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('case_id');
            $table->string('subject');

            $table->text('description')->nullable();

            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');

            $table->unsignedBigInteger('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('ticket_categories_id')->index();
            $table->foreign('ticket_categories_id')->references('id')->on('ticket_categories')->onDelete('cascade');

            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('priority')->default(0);

            $table->json('tags')->nullable();

            $table->unsignedBigInteger('related_id')->nullable();

            $table->string('related_type')->nullable();

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
        Schema::dropIfExists('ticket_attachments');
    }
}
