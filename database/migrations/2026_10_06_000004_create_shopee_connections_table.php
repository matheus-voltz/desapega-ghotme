<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopee_connections', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id', 32)->unique();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('access_token_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopee_connections');
    }
};
