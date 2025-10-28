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
        if (!Schema::hasTable('payments')) {
            // ✅ Create table if it doesn't exist
//            Schema::create('payments', function (Blueprint $table) {
//                $table->decimal('wallet_applied', 12, 2)->default(0);
//            });
        } else {
            // ✅ If table exists, only add column if missing
            Schema::table('payments', function (Blueprint $table) {

                $table->boolean('use_wallet')->default(false);
                $table->decimal('wallet_applied', 12, 2)->default(0);
                $table->decimal('card_amount', 12, 2)->default(0);
                $table->string('failure_reason')->nullable();
                $table->json('wallet_lot_allocation')->nullable()->comment('Which wallet lots were used');
                $table->unsignedBigInteger('wallet_transaction_id')->nullable();
                $table->foreign('wallet_transaction_id')->references('id')->on('wallet_transactions')->onDelete('set null');

//                if (!Schema::hasColumn('payments', 'category')) {
//                    $table->string('category')->nullable()->index()->after('type');
//                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['wallet_transaction_id']);
                $table->dropColumn(['wallet_transaction_id', 'wallet_lot_allocation', 'failure_reason', 'card_amount', 'wallet_applied', 'use_wallet']);

//                if (Schema::hasColumn('payments', 'category')) {
//                    $table->dropColumn('category');
//                }
            });
        } else {
            Schema::dropIfExists('payments');
        }
    }
};
