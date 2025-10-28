<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            // ✅ Create table if it doesn't exist
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                // Add category directly in the new table
                $table->string('category')->nullable()->index();
            });
        } else {
            // ✅ If table exists, only add column if missing
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications', 'category')) {
                    $table->string('category')->nullable()->index()->after('type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (Schema::hasColumn('notifications', 'category')) {
                    $table->dropColumn('category');
                }
            });
        } else {
            Schema::dropIfExists('notifications');
        }
    }
};
