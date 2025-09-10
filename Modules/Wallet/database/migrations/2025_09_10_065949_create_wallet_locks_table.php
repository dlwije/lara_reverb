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
        Schema::create('wallet_locks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('locked_by')->constrained('users')->onDelete('cascade');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('wallet_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_locks');
    }
};
