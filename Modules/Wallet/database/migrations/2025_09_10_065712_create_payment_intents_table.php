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

            // Botble Integration Fields
            $table->string('botble_order_id')->nullable()->comment('Botble order reference');
            $table->string('botble_order_code')->nullable()->comment('Botble order code');
            $table->string('botble_customer_id')->nullable()->comment('Botble customer ID');

            // Payment Information
            $table->decimal('amount', 12, 2);
            $table->decimal('wallet_applied', 12, 2)->default(0);
            $table->decimal('card_amount', 12, 2)->default(0);
            $table->decimal('cash_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('AED');

            // Wallet Integration
            $table->foreignId('user_id')->constrained('ec_customers')->onDelete('cascade');
            $table->boolean('use_wallet')->default(false);
            $table->json('wallet_lot_allocation')->nullable()->comment('Which wallet lots were used');

            // Payment Gateway Integration
            $table->string('gateway')->nullable()->comment('stripe,checkout,etc');
            $table->string('gateway_intent_id')->nullable()->comment('Gateway payment intent ID');
            $table->string('gateway_client_secret')->nullable();

            // Status Management
            $table->enum('status', [
                'created',
                'requires_payment_method',
                'requires_confirmation',
                'requires_action',
                'processing',
                'requires_capture',
                'cancelled',
                'succeeded',
                'failed',
                'confirmed',
                'captured'
            ])->default('created');

            // Risk & Fraud Detection
            $table->integer('risk_score')->nullable();
            $table->string('device_id')->nullable();
            $table->string('client_ip')->nullable();
            $table->string('user_agent')->nullable();

            // Timestamps
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
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
