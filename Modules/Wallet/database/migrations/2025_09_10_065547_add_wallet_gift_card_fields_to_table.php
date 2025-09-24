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
        Schema::table('ec_discounts', function (Blueprint $table) {

            $table->decimal('original_value', 12, 2);
            $table->decimal('base_value', 12, 2)->default(0);
            $table->decimal('bonus_value', 12, 2)->default(0);
            $table->unsignedBigInteger('promo_rule_id')->nullable();
            $table->decimal('final_credit', 12, 2)->default(0);
            $table->char('currency', 3)->default('AED');
            $table->string('batch_id')->nullable();
            $table->enum('status', ['created', 'active', 'redeemed', 'expired', 'void'])->default('created');
            $table->string('issued_to')->nullable(); // Email or name
            $table->foreignId('redeemed_by')->nullable()->constrained('ec_customers')->onDelete('set null');
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();

            $table->index('status');
            $table->index('batch_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ec_discounts', function (Blueprint $table) {
            $table->dropColumn('face_value');
            $table->dropColumn('currency');
            $table->dropColumn('batch_id');
            $table->dropColumn('status');
            $table->dropColumn('issued_to');
            $table->dropColumn('redeemed_by');
            $table->dropForeign(['redeemed_by']);
            $table->dropColumn('redeemed_at');
            $table->dropColumn('expires_at');
            $table->dropColumn('metadata');
        });
    }
};
