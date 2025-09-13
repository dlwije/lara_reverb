<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('st_gift_cards', function (Blueprint $table) {

            // Modify the batch_id column to match the gift_card_batches.id type
            $table->unsignedBigInteger('batch_id')->nullable()->change();

            // Now add the foreign key constraint
            $table->foreign('batch_id')
                ->references('id')
                ->on('gift_card_batches')
                ->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('st_gift_cards', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['batch_id']);

            // Revert the column back to its original type (assuming it was regular integer)
            // If you know the original type, specify it here
            $table->string('batch_id')->nullable()->change();
        });
    }
};
