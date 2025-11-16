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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->enum('direction', ['CR', 'DR']);
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('AED');
            $table->enum('type', ['redeem', 'purchase', 'refund_credit', 'admin_adjustment']);
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('completed');
            $table->string('ref_type')->nullable(); // e.g., 'App\\Models\\Order'
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->json('lot_allocation')->nullable(); // Array of {lot_id, amount}
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['ref_type', 'ref_id']);
            $table->index('type');
            $table->index('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
