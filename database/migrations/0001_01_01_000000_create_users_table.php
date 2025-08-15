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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Two-factor authentication fields
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            // Jetstream-style profile fields
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->string('locale')->nullable();

            // Your original custom fields
            $table->string('avatar')->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('login_type', 55)->default('email');
            $table->tinyInteger('is_verified')->default(0);
            $table->timestamp('is_verified_at')->nullable();
            $table->string('device_type')->nullable();
            $table->enum('user_type', ['system', 'agency', 'dealer', 'customer']);
            $table->unsignedInteger('team_id')->nullable();

            // From existing DB
            $table->boolean('active')->nullable();
            $table->boolean('employee')->nullable();
            $table->boolean('view_all')->nullable();
            $table->boolean('edit_all')->nullable();
            $table->boolean('bulk_actions')->nullable();
            $table->boolean('can_be_impersonated')->nullable();

            // Foreign keys
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('store_id')->nullable()->index();

            // JSON attributes
            $table->json('extra_attributes')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
