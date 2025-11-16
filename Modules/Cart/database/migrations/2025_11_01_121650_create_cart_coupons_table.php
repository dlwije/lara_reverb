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
        Schema::create('cart_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->string('type'); // percentage, fixed, shipping
            $table->decimal('value', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->json('data')->nullable(); // Original coupon data snapshot
            $table->timestamps();

            $table->index(['cart_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_coupons');
    }
};
