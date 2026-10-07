<?php

namespace App\Services;

use App\Models\SellerSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotifier
{
    public function configured(?SellerSetting $sellerSettings = null): bool
    {
        $botToken = trim((string) ($sellerSettings?->telegram_bot_token ?: config('telegram.bot_token')));
        $chatId = trim((string) ($sellerSettings?->telegram_chat_id ?: config('telegram.chat_id')));

        return ((bool) config('telegram.enabled') || $sellerSettings !== null)
            && $botToken !== ''
            && $chatId !== '';
    }

    public function send(string $message, ?SellerSetting $sellerSettings = null): bool
    {
        if (! $this->configured($sellerSettings)) {
            return false;
        }

        $token = trim((string) ($sellerSettings?->telegram_bot_token ?: config('telegram.bot_token')));
        $chatId = trim((string) ($sellerSettings?->telegram_chat_id ?: config('telegram.chat_id')));

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
