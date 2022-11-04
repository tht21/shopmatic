<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDownloadedStatusToExportExcelTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('export_excel_tasks', function (Blueprint $table) {
            $table->boolean('downloaded_status')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('export_excel_tasks', function (Blueprint $table) {
            $table->dropColumn('downloaded_status');
        });
    }
}
