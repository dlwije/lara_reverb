<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('status', 60)->default('published');

                $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
                $table->decimal('total_available', 12, 2)->default(0);
                $table->decimal('total_pending', 12, 2)->default(0);
                $table->enum('wt_status', ['active', 'locked', 'suspended'])->default('active');
                $table->timestamps();

                $table->index('user_id');
                $table->index('wt_status');
            });
        }

        if (! Schema::hasTable('wallets_translations')) {
            Schema::create('wallets_translations', function (Blueprint $table) {
                $table->string('lang_code');
                $table->foreignId('wallets_id');
                $table->string('name', 255)->nullable();

                $table->primary(['lang_code', 'wallets_id'], 'wallets_translations_primary');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('wallets_translations');
    }
};
