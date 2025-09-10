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
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->onDelete('restrict');
            $table->decimal('amount', 12, 2);
            $table->decimal('wallet_applied', 12, 2)->default(0);
            $table->decimal('card_amount', 12, 2)->default(0);
            $table->enum('status', ['created', 'requires_action', 'confirmed', 'captured', 'failed'])->default('created');
            $table->integer('risk_score')->nullable();
            $table->string('device_id')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
