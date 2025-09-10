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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->decimal('base_value', 12, 2)->nullable()->after('amount');
            $table->decimal('bonus_value', 12, 2)->nullable()->after('base_value');
            $table->foreignId('gift_card_id')->nullable()->constrained('gift_cards')->onDelete('set null')->after('bonus_value');
            $table->foreignId('promo_rule_id')->nullable()->constrained('promo_rules')->onDelete('set null')->after('gift_card_id');

            // Add more specific type for gift card transactions
            $table->enum('type', [
                'redeem',
                'purchase',
                'refund_credit',
                'admin_adjustment',
                'gift_card_redeem', // New type for gift card redemptions
                'promo_credit'      // New type for promotional credits
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['gift_card_id', 'promo_rule_id']);
            $table->enum('type', ['redeem', 'purchase', 'refund_credit', 'admin_adjustment'])->change();
            $table->dropColumn(['base_value', 'bonus_value', 'gift_card_id', 'promo_rule_id']);
        });
    }
};
