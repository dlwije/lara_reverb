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
        Schema::table('wallet_lots', function (Blueprint $table) {
            $table->string('ref_number')->unique()->nullable();
        });
        DB::statement("
            ALTER TABLE wallet_lots
            MODIFY COLUMN source ENUM(
                'gift_card',
                'refund',
                'adjustment',
                'promo',
                'credit_card',
                'loyalty_point'
            ) NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('ref_number');
        });

        DB::statement("
            ALTER TABLE wallet_lots
            MODIFY COLUMN source ENUM(
                'gift_card',
                'refund',
                'adjustment',
                'promo',
            ) NOT NULL
        ");
    }
};
