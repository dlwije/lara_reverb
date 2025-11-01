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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('identifier'); // User ID or session ID
            $table->string('instance')->default('cart'); // 'cart', 'wishlist', etc.
            $table->text('content')->nullable(); // Serialized cart content
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('coupon_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();

            $table->unique(['identifier', 'instance']);
            $table->index(['user_id', 'instance']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
