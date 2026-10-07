<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->string('condition')->nullable();
            $table->text('description')->nullable();
            $table->decimal('pix_price', 10, 2);
            $table->decimal('marketplace_price', 10, 2)->nullable();
            $table->string('marketplace_url')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
