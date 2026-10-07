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
        Schema::create('asaas_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bundle_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('external_reference')->unique();
            $table->string('asaas_payment_id')->nullable()->unique();
            $table->string('asaas_customer_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status', 40)->index();
            $table->longText('pix_payload')->nullable();
            $table->longText('pix_encoded_image')->nullable();
            $table->timestamp('pix_expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asaas_payments');
    }
};
