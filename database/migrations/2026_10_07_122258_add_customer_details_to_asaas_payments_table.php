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
        Schema::table('asaas_payments', function (Blueprint $table): void {
            $table->string('customer_name')->nullable()->after('asaas_customer_id');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('customer_phone', 30)->nullable()->after('customer_email');
            $table->string('customer_postal_code', 20)->nullable()->after('customer_phone');
            $table->string('customer_address_number', 20)->nullable()->after('customer_postal_code');
            $table->string('customer_address_complement')->nullable()->after('customer_address_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asaas_payments', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_name',
                'customer_email',
                'customer_phone',
                'customer_postal_code',
                'customer_address_number',
                'customer_address_complement',
            ]);
        });
    }
};
