<?php

namespace App\Console\Commands;

use App\Services\ShopeeClient;
use Illuminate\Console\Command;
use Throwable;

class ConfigureShopeePush extends Command
{
    protected $signature = 'shopee:configure-push';

    protected $description = 'Configura o webhook de mudança de status de pedidos da Shopee';

    public function handle(ShopeeClient $client): int
    {
        $url = (string) config('shopee.webhook_url');

        if ($url === '') {
            $this->error('SHOPEE_WEBHOOK_URL não configurada.');

            return self::FAILURE;
        }

        try {
            $client->setOrderStatusPush($url);
            $this->info("Webhook Shopee configurado: {$url}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
