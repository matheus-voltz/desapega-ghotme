<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('shopee_connections');

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['shopee_item_id']);
            $table->dropIndex(['shopee_model_id']);
            $table->dropIndex(['shopee_order_sn']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'marketplace_url',
                'shopee_item_id',
                'shopee_model_id',
                'shopee_order_sn',
                'shopee_order_status',
                'shopee_synced_at',
            ]);
        });

        Schema::table('seller_settings', function (Blueprint $table) {
            $table->dropColumn(['shopee_partner_id', 'shopee_partner_key', 'shopee_shop_id']);
        });
    }

    public function down(): void
    {
        // Remoção definitiva da integração com a Shopee; não há reversão.
    }
};
