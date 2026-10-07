<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FindTelegramChatId extends Command
{
    protected $signature = 'telegram:chat-id';

    protected $description = 'Mostra os chat IDs encontrados nas últimas mensagens recebidas pelo bot';

    public function handle(): int
    {
        $token = trim((string) config('telegram.bot_token'));

        if ($token === '') {
            $this->error('Preencha TELEGRAM_BOT_TOKEN no .env primeiro.');

            return self::FAILURE;
        }

        $response = Http::timeout(max(1, (int) config('telegram.timeout', 5)))
            ->get("https://api.telegram.org/bot{$token}/getUpdates");

        if (! $response->successful() || ! $response->json('ok')) {
            $this->error('Não foi possível consultar o Telegram: '.($response->json('description') ?: 'erro desconhecido'));

            return self::FAILURE;
        }

        $chats = collect($response->json('result', []))
            ->map(fn (array $update) => data_get($update, 'message.chat') ?? data_get($update, 'callback_query.message.chat'))
            ->filter()
            ->unique('id')
            ->values();

        if ($chats->isEmpty()) {
            $this->warn('Nenhum chat encontrado. Abra o bot no Telegram, envie /start e execute este comando novamente.');

            return self::SUCCESS;
        }

        $rows = $chats->map(fn (array $chat) => [
            (string) ($chat['id'] ?? ''),
            (string) ($chat['type'] ?? ''),
            trim((string) (($chat['first_name'] ?? '').' '.($chat['last_name'] ?? ''))),
            (string) ($chat['username'] ?? ''),
        ])->all();

        $this->table(['Chat ID', 'Tipo', 'Nome', 'Username'], $rows);
        $this->info('Use o Chat ID desejado em TELEGRAM_CHAT_ID.');

        return self::SUCCESS;
    }
}
