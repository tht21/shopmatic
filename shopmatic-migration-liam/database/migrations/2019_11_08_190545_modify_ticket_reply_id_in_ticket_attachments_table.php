<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyTicketReplyIdInTicketAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->dropForeign(['ticket_reply_id']);
            $table->unsignedBigInteger('ticket_reply_id')->nullable()->change();
            $table->foreign('ticket_reply_id')->references('id')->on('ticket_replies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_reply_id')->change();
        });
    }
}
