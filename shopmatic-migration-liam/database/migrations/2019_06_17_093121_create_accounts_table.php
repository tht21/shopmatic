<?php

use App\Constants\AccountStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->unsignedBigInteger('shop_id')->index();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->unsignedBigInteger('integration_id')->index();
            $table->foreign('integration_id')->references('id')->on('integrations')->onDelete('cascade');
            $table->unsignedBigInteger('region_id')->index();
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            
            $table->string('name');
            $table->string('currency')->nullable();
            $table->json('credentials')->nullable();
            $table->json('sync_data')->nullable();
            $table->json('additional_data')->nullable();
            $table->json('settings')->nullable();
            
            $table->tinyInteger('status')->default(AccountStatus::ACTIVE());
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('shops', function(Blueprint $table) {
            $table->unsignedBigInteger('main_account_id')->nullable()->index()->after('currency');
            $table->foreign('main_account_id')->references('id')->on('accounts')->onDelete('set null');
    
        });
        DB::update("ALTER TABLE integrations AUTO_INCREMENT = 11000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
}
