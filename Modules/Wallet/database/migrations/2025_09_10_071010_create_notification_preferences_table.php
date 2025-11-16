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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', [
                'transaction',
                'expiry_reminder',
                'promotional',
                'security',
                'system',
                'achievements'
            ]);
            $table->json('channels')->nullable()->comment('Available channels: mail, database, broadcast, sms');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            // Unique constraint to prevent duplicate preferences per user
            $table->unique(['customer_id', 'type']);

            // Index for faster queries
            $table->index(['customer_id', 'type', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
