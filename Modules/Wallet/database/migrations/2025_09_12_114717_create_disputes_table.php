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
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('ec_customers')->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained('wallet_transactions')->onDelete('cascade');
            $table->text('reason');
            $table->enum('status', ['open', 'under_review', 'resolved', 'cancelled'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('resolution', ['refund', 'partial_refund', 'rejected', 'cancelled'])->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
//            $table->index('user_id');
//            $table->index('transaction_id');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
