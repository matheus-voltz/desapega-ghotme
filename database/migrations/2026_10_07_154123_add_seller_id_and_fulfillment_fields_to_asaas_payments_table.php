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
        Schema::table('asaas_payments', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->after('cart_id')->constrained('users')->nullOnDelete();
            $table->string('fulfillment_status', 40)->nullable()->after('status')->index();
            $table->string('delivery_method', 40)->nullable()->after('fulfillment_status');
            $table->string('tracking_code', 120)->nullable()->after('delivery_method');
            $table->text('seller_note')->nullable()->after('tracking_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asaas_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_id');
            $table->dropIndex(['fulfillment_status']);
            $table->dropColumn(['fulfillment_status', 'delivery_method', 'tracking_code', 'seller_note']);
        });
    }
};
