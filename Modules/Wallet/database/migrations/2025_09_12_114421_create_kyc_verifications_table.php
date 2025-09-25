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
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('tier')->default(0)->comment('0: Basic, 1: Verified, 2: Enhanced, 3: Corporate');
            $table->enum('status', ['pending', 'approved', 'rejected', 'draft'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('document_paths')->nullable()->comment('JSON array of document file paths');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // Indexes
//            $table->index('user_id');
            $table->index('status');
            $table->index('tier');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
