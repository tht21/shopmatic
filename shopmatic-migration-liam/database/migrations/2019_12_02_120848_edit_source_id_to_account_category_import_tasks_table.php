<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditSourceIdToAccountCategoryImportTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_category_import_tasks', function (Blueprint $table) {
            $table->renameColumn('source_id', 'source');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_category_import_tasks', function (Blueprint $table) {
            $table->renameColumn('source', 'source_id');
        });
    }
}
