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
        Schema::table('st_gift_cards', function (Blueprint $table) {
            // Remove the existing promo_multiplier column if it exists
            if (Schema::hasColumn('st_gift_cards', 'promo_multiplier')) {
                $table->dropColumn('promo_multiplier');
            }

            // Add new columns
            $table->decimal('base_value', 12, 2)->after('face_value');
            $table->decimal('bonus_value', 12, 2)->default(0)->after('base_value');
            $table->foreignId('promo_rule_id')->nullable()->constrained('promo_rules')->onDelete('set null')->after('bonus_value');
            $table->decimal('final_credit', 12, 2)->after('bonus_value');

            // Rename face_value to original_value for clarity
            $table->renameColumn('face_value', 'original_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('st_gift_cards', function (Blueprint $table) {
            $table->dropForeign(['promo_rule_id']);
            $table->renameColumn('original_value', 'face_value');
            $table->dropColumn(['base_value', 'bonus_value', 'promo_rule_id', 'final_credit']);;
        });
    }
};
