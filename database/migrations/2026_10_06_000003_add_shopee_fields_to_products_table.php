<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('shopee_item_id', 32)->nullable()->index()->after('marketplace_url');
            $table->string('shopee_model_id', 32)->nullable()->index()->after('shopee_item_id');
            $table->string('shopee_order_sn', 64)->nullable()->index()->after('status');
            $table->string('shopee_order_status', 50)->nullable()->after('shopee_order_sn');
            $table->string('sale_channel', 30)->nullable()->after('shopee_order_status');
            $table->timestamp('shopee_synced_at')->nullable()->after('sale_channel');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['shopee_item_id']);
            $table->dropIndex(['shopee_model_id']);
            $table->dropIndex(['shopee_order_sn']);
            $table->dropColumn([
                'shopee_item_id',
                'shopee_model_id',
                'shopee_order_sn',
                'shopee_order_status',
                'sale_channel',
                'shopee_synced_at',
            ]);
        });
    }
};
