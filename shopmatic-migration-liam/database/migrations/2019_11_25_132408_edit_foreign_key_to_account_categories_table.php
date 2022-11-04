<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditForeignKeyToAccountCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_categories', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_categories', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->foreign('account_id')->references('id')->on('integrations')->onDelete('CASCADE');
        });
    }
}
