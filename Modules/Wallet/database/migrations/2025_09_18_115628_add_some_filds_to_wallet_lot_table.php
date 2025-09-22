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
        Schema::table('wallet_lot', function (Blueprint $table) {
            $table->decimal('base_value', 12, 2)->default(0);
            $table->decimal('bonus_value', 12, 2)->default(0);
            $table->foreignId('promo_rule_id')->nullable()->constrained('promo_rules')->onDelete('set null');
            $table->json('metadata')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_lot', function (Blueprint $table) {
            $table->dropColumn('base_value');
            $table->dropColumn('bonus_value');
            $table->dropForeign(['promo_rule_id']);
            $table->dropColumn('promo_rule_id');
            $table->dropColumn('metadata');
        });
    }
};
