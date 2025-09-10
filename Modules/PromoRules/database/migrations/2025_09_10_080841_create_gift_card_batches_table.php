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
        Schema::create('gift_card_batches', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('batch_code')->unique();
            $table->integer('quantity');
            $table->decimal('original_value', 12, 2);
            $table->foreignId('promo_rule_id')->nullable()->constrained('promo_rules')->onDelete('set null');
            $table->decimal('final_credit', 12, 2);
            $table->timestamp('expires_at');
            $table->enum('status', ['draft', 'active', 'expired', 'cancelled'])->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('batch_code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_card_batches');
    }
};
