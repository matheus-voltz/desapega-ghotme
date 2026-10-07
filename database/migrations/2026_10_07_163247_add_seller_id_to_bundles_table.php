<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('bundles')->orderBy('id')->each(function (object $bundle): void {
            $sellerIds = DB::table('bundle_product')
                ->join('products', 'products.id', '=', 'bundle_product.product_id')
                ->where('bundle_product.bundle_id', $bundle->id)
                ->whereNotNull('products.seller_id')
                ->distinct()
                ->pluck('products.seller_id');

            if ($sellerIds->count() === 1) {
                DB::table('bundles')->where('id', $bundle->id)->update([
                    'seller_id' => $sellerIds->first(),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_id');
        });
    }
};
