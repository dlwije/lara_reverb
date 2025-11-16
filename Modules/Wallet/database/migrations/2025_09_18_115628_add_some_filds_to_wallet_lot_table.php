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
        Schema::table('wallet_lots', function (Blueprint $table) {
            if (!Schema::hasColumn('wallet_lots', 'base_value')) {
                $table->decimal('base_value', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('wallet_lots', 'bonus_value')) {
                $table->decimal('bonus_value', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('wallet_lots', 'promo_rule_id')) {
                $table->unsignedBigInteger('promo_rule_id')->nullable();
            }
            if (!Schema::hasColumn('wallet_lots', 'gift_card_id')) {
                $table->unsignedBigInteger('gift_card_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_lots', function (Blueprint $table) {
            $table->dropColumn('base_value');
            $table->dropColumn('bonus_value');
            $table->dropColumn('promo_rule_id');
            $table->dropColumn('gift_card_id');
        });
    }
};
