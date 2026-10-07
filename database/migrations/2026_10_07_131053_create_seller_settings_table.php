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
        Schema::create('seller_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('asaas_api_key')->nullable();
            $table->text('asaas_webhook_token')->nullable();
            $table->text('telegram_bot_token')->nullable();
            $table->text('telegram_chat_id')->nullable();
            $table->text('shopee_partner_id')->nullable();
            $table->text('shopee_partner_key')->nullable();
            $table->text('shopee_shop_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_settings');
    }
};
