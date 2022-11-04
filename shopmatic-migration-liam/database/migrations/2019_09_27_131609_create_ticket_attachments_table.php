<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('title');

            $table->unsignedBigInteger('ticket_id')->index();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');

            $table->unsignedBigInteger('ticket_reply_id')->index();
            $table->foreign('ticket_reply_id')->references('id')->on('ticket_replies')->onDelete('cascade');

            $table->string('file_url');
            $table->string('file_type');

            $table->integer('file_size_in_kb');

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
