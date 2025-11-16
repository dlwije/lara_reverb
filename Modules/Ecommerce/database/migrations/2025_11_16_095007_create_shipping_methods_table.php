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
        if (!Schema::hasTable('shipping_methods')) {
            Schema::create('shipping_methods', function (Blueprint $table) {
                $table->id();
                $table->string('provider_name');
                $table->decimal('rate', 15, 4);
                $table->unsignedBigInteger('state_id')->nullable();
                $table->unsignedBigInteger('country_id')->nullable();
                $table->tinyInteger('charge_for_weight')->nullable();
                $table->tinyInteger('active')->nullable()->default(1);
                $table->decimal('free_order_amount', 15, 2);

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
