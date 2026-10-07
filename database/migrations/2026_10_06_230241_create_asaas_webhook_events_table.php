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
        Schema::create('asaas_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asaas_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_id')->unique();
            $table->string('event', 60)->index();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asaas_webhook_events');
    }
};
