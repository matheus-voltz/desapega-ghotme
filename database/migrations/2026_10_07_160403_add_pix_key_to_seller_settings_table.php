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
        Schema::table('seller_settings', function (Blueprint $table): void {
            $table->text('pix_key')->nullable()->after('asaas_webhook_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_settings', function (Blueprint $table): void {
            $table->dropColumn('pix_key');
        });
    }
};
