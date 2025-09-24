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
        Schema::create('wallet_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('ec_customers')->onDelete('restrict');
            $table->enum('source', ['gift_card', 'refund', 'adjustment', 'promo']);
            $table->decimal('amount', 12, 2);
            $table->decimal('remaining', 12, 2);
            $table->char('currency', 3)->default('AED');
            $table->timestamp('acquired_at');
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'expired', 'locked'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_lots');
    }
};
