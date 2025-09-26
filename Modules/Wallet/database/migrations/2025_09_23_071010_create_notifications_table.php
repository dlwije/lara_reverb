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
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

//            $table->index(['notifiable_type', 'notifiable_id']);
                $table->index('read_at');
                $table->index('created_at');
            });
        }else{
            Schema::table('notifications', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::dropIfExists('notifications');
        }else{
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }
    }
};
