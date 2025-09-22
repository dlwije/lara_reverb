<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->decimal('bonus_value', 12, 2)->default(0);
            $table->unsignedBigInteger('gift_card_id')->nullable();
//            $table->foreignId('gift_card_id')->nullable()->constrained('st_gift_cards')->onDelete('set null');
            $table->unsignedBigInteger('promo_rule_id')->nullable();
            $table->decimal('base_value', 12, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('bonus_value');
            $table->dropColumn('gift_card_id');
            $table->dropColumn('promo_rule_id');
            $table->dropColumn('base_value');
        });
    }
};
