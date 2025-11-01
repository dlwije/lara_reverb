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
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->string('identifier');
            $table->string('instance')->default('default');
            $table->text('content')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['identifier', 'instance']);

            Schema::create('wishlist_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wishlist_id')->constrained()->onDelete('cascade');
                $table->string('row_id');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->decimal('price', 10, 2)->default(0);
                $table->json('options')->nullable();
                $table->timestamps();

                $table->unique(['wishlist_id', 'row_id']);
                $table->index(['wishlist_id', 'product_id']);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('wishlists');
    }
};
