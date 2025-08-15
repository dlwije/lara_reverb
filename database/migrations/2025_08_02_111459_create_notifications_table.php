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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->nullable();
            $table->morphs('notifiable');
            $table->text('data')->nullable();
            $table->unsignedBigInteger('sent_from_id')->nullable();
            $table->unsignedBigInteger('sent_from_role_id')->nullable();
            $table->unsignedBigInteger('sent_to_id')->nullable();
            $table->unsignedBigInteger('sent_to_role_id')->nullable();
            $table->string('notify_title', 199)->nullable();
            $table->text('notify_body')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
