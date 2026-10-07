<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotifier
{
    public function configured(): bool
    {
        return (bool) config('telegram.enabled')
            && trim((string) config('telegram.bot_token')) !== ''
            && trim((string) config('telegram.chat_id')) !== '';
    }

    public function send(string $message): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $token = trim((string) config('telegram.bot_token'));
        $chatId = trim((string) config('telegram.chat_id'));

        try {
            $response = Http::asForm()
                ->timeout(max(1, (int) config('telegram.timeout', 5)))
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if (! $response->successful() || ! $response->json('ok')) {
                Log::warning('Falha ao enviar notificação para o Telegram.', [
                    'status' => $response->status(),
                    'telegram_description' => $response->json('description'),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            // Uma falha do Telegram nunca deve impedir o comprador de continuar.
            Log::warning('Erro ao enviar notificação para o Telegram.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
