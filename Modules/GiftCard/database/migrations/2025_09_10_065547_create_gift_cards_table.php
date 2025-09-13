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
        Schema::create('st_gift_cards', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->decimal('face_value', 12, 2);
            $table->char('currency', 3)->default('AED');
            $table->string('batch_id')->nullable();
            $table->enum('status', ['created', 'active', 'redeemed', 'expired', 'void'])->default('created');
            $table->string('issued_to')->nullable(); // Email or name
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('batch_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('st_gift_cards');
    }
};
