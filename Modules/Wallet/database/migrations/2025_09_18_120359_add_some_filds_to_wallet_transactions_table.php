<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('wallet_transactions', 'base_value')) {
                $table->decimal('base_value', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('wallet_transactions', 'bonus_value')) {
                $table->decimal('bonus_value', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('wallet_transactions', 'promo_rule_id')) {
                $table->unsignedBigInteger('promo_rule_id')->nullable();
            }
            if (!Schema::hasColumn('wallet_transactions', 'gift_card_id')) {
                $table->unsignedBigInteger('gift_card_id')->nullable();
            }
        });
        DB::statement("
            ALTER TABLE wallet_transactions
            MODIFY COLUMN type ENUM(
                'redeem',
                'gift_card_redeem',
                'purchase',
                'refund_credit',
                'admin_adjustment'
            ) NOT NULL
        ");
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

        DB::statement("
            ALTER TABLE wallet_transactions
            MODIFY COLUMN type ENUM(
                'redeem',
                'purchase',
                'refund_credit',
                'admin_adjustment'
            ) NOT NULL
        ");
    }
};
