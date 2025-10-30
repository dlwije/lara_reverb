<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('wallet_lot_freezes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_lot_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable();
            $table->foreignId('payment_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['frozen', 'consumed', 'released'])->default('frozen');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['wallet_lot_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_lot_freezes');
    }
};
