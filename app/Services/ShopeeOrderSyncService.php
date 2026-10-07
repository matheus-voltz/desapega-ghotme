<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ShopeeOrderSyncService
{
    public function __construct(
        private readonly ShopeeClient $client,
        private readonly SaleNotifier $saleNotifier
    ) {}

    public function syncOrder(string $orderSn, ?string $pushedStatus = null): int
    {
        $order = $this->client->getOrderDetail($orderSn);
        $status = strtoupper((string) ($order['order_status'] ?? $pushedStatus ?? ''));
        $items = (array) ($order['item_list'] ?? []);
        $updated = 0;

        if ($status === '') {
            Log::warning('Shopee: pedido sem status', ['order_sn' => $orderSn]);

            return 0;
        }

        foreach ($items as $item) {
            $itemId = isset($item['item_id']) ? (string) $item['item_id'] : null;
            $modelId = isset($item['model_id']) && (string) $item['model_id'] !== '0'
                ? (string) $item['model_id']
                : null;

            if (! $itemId) {
                continue;
            }

            $products = $this->productsForShopeeItem($itemId, $modelId);

            foreach ($products as $product) {
                $salePrice = (float) ($item['model_discounted_price'] ?? $item['model_original_price'] ?? $product->marketplace_price ?? $product->pix_price);

                if ($this->applyStatus($product, $orderSn, $status, $salePrice)) {
                    $updated++;
                }
            }
        }

        Log::info('Shopee: pedido sincronizado', [
            'order_sn' => $orderSn,
            'status' => $status,
            'products_updated' => $updated,
        ]);

        return $updated;
    }

    public function syncRecentOrders(int $minutes = 15): array
    {
        $connection = $this->client->freshConnection();
        $to = time();
        $from = max(
            $to - max(5, $minutes) * 60,
            $connection->last_synced_at?->copy()->subMinutes(2)->timestamp ?? 0
        );

        $cursor = '';
        $orders = 0;
        $products = 0;

        do {
            $json = $this->client->getOrderList($from, $to, $cursor, $connection);
            $response = (array) data_get($json, 'response', []);
            $list = (array) ($response['order_list'] ?? []);

            foreach ($list as $order) {
                $orderSn = (string) ($order['order_sn'] ?? '');
                if ($orderSn === '') {
                    continue;
                }

                $orders++;
                $products += $this->syncOrder($orderSn, $order['order_status'] ?? null);
            }

            $cursor = (string) ($response['next_cursor'] ?? '');
            $more = (bool) ($response['more'] ?? false);
        } while ($more && $cursor !== '');

        $connection->update(['last_synced_at' => now()]);

        return ['orders' => $orders, 'products' => $products];
    }

    private function productsForShopeeItem(string $itemId, ?string $modelId)
    {
        $query = Product::query()->where('shopee_item_id', $itemId);

        if ($modelId) {
            $exact = (clone $query)->where('shopee_model_id', $modelId)->get();
            if ($exact->isNotEmpty()) {
                return $exact;
            }
        }

        return $query->whereNull('shopee_model_id')->get();
    }

    private function applyStatus(Product $product, string $orderSn, string $shopeeStatus, float $salePrice): bool
    {
        $localStatus = match ($shopeeStatus) {
            'UNPAID', 'PENDING' => 'reserved',
            'READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'TO_CONFIRM_RECEIVE',
            'COMPLETED', 'INVOICE_PENDING', 'RETRY_SHIP' => 'sold',
            default => null,
        };

        if ($shopeeStatus === 'CANCELLED') {
            // Só reabre se este mesmo pedido da Shopee foi quem bloqueou/vendeu o item.
            if ($product->sale_channel !== 'shopee' || $product->shopee_order_sn !== $orderSn) {
                return false;
            }

            $product->update([
                'status' => 'available',
                'shopee_order_sn' => null,
                'shopee_order_status' => $shopeeStatus,
                'sale_channel' => null,
                'shopee_synced_at' => now(),
            ]);

            return true;
        }

        if (! $localStatus) {
            $product->update([
                'shopee_order_status' => $shopeeStatus,
                'shopee_synced_at' => now(),
            ]);

            return true;
        }

        // Não deixa a Shopee sobrescrever uma venda manual/Pix. Ainda registramos
        // o pedido para o conflito ficar visível no painel/log.
        if ($product->sale_channel && $product->sale_channel !== 'shopee' && $product->status === 'sold') {
            $product->update([
                'shopee_order_sn' => $orderSn,
                'shopee_order_status' => $shopeeStatus,
                'shopee_synced_at' => now(),
            ]);

            Log::critical('Shopee: possível venda duplicada. Produto já vendido manualmente/Pix.', [
                'product_id' => $product->id,
                'order_sn' => $orderSn,
                'shopee_status' => $shopeeStatus,
            ]);

            return true;
        }

        $becameSold = $localStatus === 'sold' && $product->status !== 'sold';

        $product->update([
            'status' => $localStatus,
            'shopee_order_sn' => $orderSn,
            'shopee_order_status' => $shopeeStatus,
            'sale_channel' => 'shopee',
            'shopee_synced_at' => now(),
        ]);

        if ($becameSold) {
            $this->saleNotifier->productSold($product, $salePrice, 'Shopee');
        }

        return true;
    }
}
