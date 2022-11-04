<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAccountCategoryImportTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_category_import_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->index(['source_type', 'source_id']);

            $table->json('messages')->nullable();
            $table->json('settings')->nullable();

            $table->integer('total_categories')->default(0);
            $table->tinyInteger('status')->default(\App\Constants\JobStatus::PENDING());

            $table->timestamps();
        });
        DB::update("ALTER TABLE account_category_import_tasks AUTO_INCREMENT = 110000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_category_import_tasks');
    }
}
