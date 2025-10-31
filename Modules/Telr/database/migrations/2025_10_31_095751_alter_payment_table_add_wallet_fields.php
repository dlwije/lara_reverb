<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Wallet\Services\PaymentStatusEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency')->nullable();
            $table->string('charge_id')->nullable();
            $table->string('payment_channel')->nullable();
            $table->string('status')->nullable()->default(PaymentStatusEnum::PENDING);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('currency');
            $table->dropColumn('charge_id');
            $table->dropColumn('payment_channel');
            $table->dropColumn('status');
        });
    }
};
