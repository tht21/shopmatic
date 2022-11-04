<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBraintreeColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('braintree_id')->nullable()->after('currency');
            $table->string('paypal_email')->nullable()->after('braintree_id');
            $table->string('card_brand')->nullable()->after('paypal_email');
            $table->string('card_last_four')->nullable()->after('card_brand');
            $table->timestamp('trial_ends_at')->nullable()->after('card_last_four');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['braintree_id', 'paypal_email', 'card_brand', 'card_last_four', 'trial_ends_at']);
        });
    }
}
