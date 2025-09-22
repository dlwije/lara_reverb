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
            $table->foreignId('gift_card_id')->nullable()->after('status')->constrained('st_gift_cards')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_lots', function (Blueprint $table) {
            $table->dropForeign(['gift_card_id']);
            $table->dropColumn('gift_card_id');
        });
    }
};
