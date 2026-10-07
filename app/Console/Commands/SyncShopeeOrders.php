<?php

namespace App\Console\Commands;

use App\Services\ShopeeOrderSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncShopeeOrders extends Command
{
    protected $signature = 'shopee:sync-orders {--minutes=15 : Janela de segurança, em minutos}';

    protected $description = 'Sincroniza pedidos recentes da Shopee com os produtos locais';

    public function handle(ShopeeOrderSyncService $sync): int
    {
        try {
            $result = $sync->syncRecentOrders((int) $this->option('minutes'));
            $this->info("Shopee: {$result['orders']} pedido(s), {$result['products']} produto(s) atualizado(s).");

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
