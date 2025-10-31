<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('wallet_lots', function (Blueprint $table) {
            $table->decimal('base_value', 12, 2)->after('amount');
            $table->decimal('bonus_value', 12, 2)->default(0)->after('base_value');
//            $table->foreignId('gift_card_id')->nullable()->constrained('st_gift_cards')->onDelete('set null')->after('bonus_value');
            if (!Schema::hasColumn('wallet_lots', 'promo_rule_id')) {
                $table->foreignId('promo_rule_id')->nullable()->constrained('promo_rules')->onDelete('set null')->after('gift_card_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('wallet_lots', function (Blueprint $table) {
            $table->dropForeign(['gift_card_id', 'promo_rule_id']);

            $table->dropColumn('base_value');
            $table->dropColumn('bonus_value');
//            $table->dropForeign(['gift_card_id']);
            $table->dropColumn('gift_card_id');
//            $table->dropForeign(['promo_rule_id']);
            $table->dropColumn('promo_rule_id');
        });
    }
};
