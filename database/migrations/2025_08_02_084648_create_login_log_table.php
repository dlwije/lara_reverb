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
        Schema::create('login_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('ip_address')->nullable();
            $table->string('device_type')->nullable();  // web, android, ios
            $table->string('device_model')->nullable(); // iPhone, Pixel, etc.
            $table->string('os')->nullable();           // iOS 17, Android 14
            $table->string('browser')->nullable();      // Chrome, Safari
            $table->string('login_type')->nullable();   // email, phone, google, etc.
            $table->string('location')->nullable();     // Optional (city/country)
            $table->boolean('successful')->default(true);
            $table->timestamp('logged_in_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_log');
    }
};
