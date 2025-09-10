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
        Schema::create('promo_rule_user_segment', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promo_rule_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_segment_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['promo_rule_id', 'user_segment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_rule_user_segment');
    }
};
