<?php

namespace App\Console\Commands;

use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

class TestTelegramNotification extends Command
{
    protected $signature = 'telegram:test';

    protected $description = 'Envia uma notificação de teste para o Telegram configurado';

    public function handle(TelegramNotifier $telegram): int
    {
        if (! $telegram->configured()) {
            $this->error('Telegram não configurado. Preencha TELEGRAM_BOT_TOKEN e TELEGRAM_CHAT_ID no .env.');

            return self::FAILURE;
        }

        if (! $telegram->send("✅ <b>Telegram configurado</b>\n\nO Desapego Canadá conseguiu enviar esta mensagem.")) {
            $this->error('Falha ao enviar. Consulte storage/logs/laravel.log.');

            return self::FAILURE;
        }

        $this->info('Notificação enviada com sucesso.');

        return self::SUCCESS;
    }
}
