<?php

namespace App\Console\Commands;

use App\Services\AsaasClient;
use Illuminate\Console\Command;
use Throwable;

class ConfigureAsaasWebhook extends Command
{
    protected $signature = 'asaas:configure-webhook';

    protected $description = 'Registra no Asaas o webhook seguro de confirmação de Pix';

    public function handle(AsaasClient $client): int
    {
        try {
            $webhook = $client->registerWebhook();
            $this->info('Webhook do Asaas configurado: '.($webhook['id'] ?? 'sem ID retornado'));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
