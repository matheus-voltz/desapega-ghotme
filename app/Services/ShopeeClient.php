<?php

namespace App\Services;

use App\Models\ShopeeConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShopeeClient
{
    public function authorizationUrl(): string
    {
        $this->assertConfigured();

        $path = '/api/v2/shop/auth_partner';
        $timestamp = time();
        $redirect = (string) config('shopee.redirect_url');

        if ($redirect === '') {
            throw new RuntimeException('SHOPEE_REDIRECT_URL não configurada.');
        }

        return $this->baseUrl().$path.'?'.http_build_query([
            'partner_id' => (int) config('shopee.partner_id'),
            'timestamp' => $timestamp,
            'sign' => $this->publicSignature($path, $timestamp),
            'redirect' => $redirect,
        ]);
    }

    public function exchangeCode(string $code, string $shopId): ShopeeConnection
    {
        $path = '/api/v2/auth/token/get';

        $json = $this->publicPost($path, [
            'code' => $code,
            'shop_id' => (int) $shopId,
            'partner_id' => (int) config('shopee.partner_id'),
        ]);

        $accessToken = data_get($json, 'access_token') ?? data_get($json, 'response.access_token');
        $refreshToken = data_get($json, 'refresh_token') ?? data_get($json, 'response.refresh_token');
        $expiresIn = (int) (data_get($json, 'expire_in') ?? data_get($json, 'response.expire_in') ?? 14400);

        if (! $accessToken || ! $refreshToken) {
            throw new RuntimeException('A Shopee não retornou access_token/refresh_token.');
        }

        return ShopeeConnection::updateOrCreate(
            ['shop_id' => (string) $shopId],
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'access_token_expires_at' => now()->addSeconds(max(60, $expiresIn - 60)),
            ]
        );
    }

    public function getOrderDetail(string $orderSn, ?ShopeeConnection $connection = null): array
    {
        $connection = $this->freshConnection($connection);

        $json = $this->shopGet('/api/v2/order/get_order_detail', [
            'order_sn_list' => $orderSn,
            'response_optional_fields' => 'item_list',
        ], $connection);

        return (array) data_get($json, 'response.order_list.0', []);
    }

    public function getOrderList(
        int $timeFrom,
        int $timeTo,
        string $cursor = '',
        ?ShopeeConnection $connection = null
    ): array {
        $connection = $this->freshConnection($connection);

        $params = [
            'time_range_field' => 'update_time',
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'page_size' => 100,
            'response_optional_fields' => 'order_status',
        ];

        if ($cursor !== '') {
            $params['cursor'] = $cursor;
        }

        return $this->shopGet('/api/v2/order/get_order_list', $params, $connection);
    }

    public function setOrderStatusPush(string $callbackUrl): array
    {
        // Os endpoints de configuração de Push são APIs públicas da aplicação:
        // usam partner_id + path + timestamp, sem access_token/shop_id.
        return $this->publicPost('/api/v2/push/set_app_push_config', [
            'callback_url' => $callbackUrl,
            'set_push_config_on' => [3], // 3 = order_status_push
        ]);
    }

    public function getPushConfig(): array
    {
        return $this->publicGet('/api/v2/push/get_app_push_config');
    }

    public function verifyPushSignature(string $rawBody, ?string $authorization): bool
    {
        if (! config('shopee.verify_webhook_signature', true)) {
            return true;
        }

        $authorization = trim((string) $authorization);
        if ($authorization === '') {
            return false;
        }

        // Alguns clientes/proxies preservam um prefixo; a Shopee normalmente envia só o HMAC.
        $authorization = preg_replace('/^(sha256=|sha256\s+)/i', '', $authorization) ?? $authorization;

        $callbackUrl = (string) config('shopee.webhook_url');
        if ($callbackUrl === '') {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            $callbackUrl.'|'.$rawBody,
            (string) config('shopee.partner_key')
        );

        return hash_equals(strtolower($expected), strtolower($authorization));
    }

    public function connection(): ?ShopeeConnection
    {
        return ShopeeConnection::query()->orderBy('id')->first();
    }

    public function freshConnection(?ShopeeConnection $connection = null): ShopeeConnection
    {
        $this->assertConfigured();
        $connection ??= $this->connection();

        if (! $connection) {
            throw new RuntimeException('Nenhuma loja Shopee foi conectada ainda.');
        }

        if ($connection->access_token_expires_at?->isAfter(now()->addMinutes(5))) {
            return $connection;
        }

        return DB::transaction(function () use ($connection) {
            $locked = ShopeeConnection::query()->lockForUpdate()->findOrFail($connection->id);

            if ($locked->access_token_expires_at?->isAfter(now()->addMinutes(5))) {
                return $locked;
            }

            $json = $this->publicPost('/api/v2/auth/access_token/get', [
                'refresh_token' => $locked->refresh_token,
                'partner_id' => (int) config('shopee.partner_id'),
                'shop_id' => (int) $locked->shop_id,
            ]);

            $accessToken = data_get($json, 'access_token') ?? data_get($json, 'response.access_token');
            $refreshToken = data_get($json, 'refresh_token') ?? data_get($json, 'response.refresh_token');
            $expiresIn = (int) (data_get($json, 'expire_in') ?? data_get($json, 'response.expire_in') ?? 14400);

            if (! $accessToken || ! $refreshToken) {
                throw new RuntimeException('Não foi possível renovar o token da Shopee.');
            }

            $locked->update([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'access_token_expires_at' => now()->addSeconds(max(60, $expiresIn - 60)),
            ]);

            return $locked->fresh();
        });
    }

    private function shopGet(string $path, array $params, ShopeeConnection $connection): array
    {
        $timestamp = time();
        $common = [
            'partner_id' => (int) config('shopee.partner_id'),
            'timestamp' => $timestamp,
            'access_token' => $connection->access_token,
            'shop_id' => (int) $connection->shop_id,
            'sign' => $this->shopSignature($path, $timestamp, $connection),
        ];

        $response = $this->http(true)->get($this->baseUrl().$path, array_merge($common, $params));

        return $this->decode($response->json(), $response->successful(), $path);
    }

    private function publicGet(string $path, array $params = []): array
    {
        $this->assertConfigured();
        $timestamp = time();

        $response = $this->http(true)->get($this->baseUrl().$path, array_merge([
            'partner_id' => (int) config('shopee.partner_id'),
            'timestamp' => $timestamp,
            'sign' => $this->publicSignature($path, $timestamp),
        ], $params));

        return $this->decode($response->json(), $response->successful(), $path);
    }

    private function publicPost(string $path, array $body): array
    {
        $this->assertConfigured();
        $timestamp = time();

        $response = $this->http()->post($this->baseUrl().$path.'?'.http_build_query([
            'partner_id' => (int) config('shopee.partner_id'),
            'timestamp' => $timestamp,
            'sign' => $this->publicSignature($path, $timestamp),
        ]), $body);

        return $this->decode($response->json(), $response->successful(), $path);
    }

    private function decode(mixed $json, bool $httpOk, string $path): array
    {
        $json = is_array($json) ? $json : [];
        $error = trim((string) ($json['error'] ?? ''));

        if (! $httpOk || $error !== '') {
            $message = $json['message'] ?? 'Erro ao acessar a Shopee Open Platform.';
            $requestId = $json['request_id'] ?? null;

            throw new RuntimeException(sprintf(
                'Shopee %s: %s%s',
                $error !== '' ? $error : 'HTTP error',
                $message,
                $requestId ? " (request_id: {$requestId})" : ''
            ));
        }

        return $json;
    }

    private function publicSignature(string $path, int $timestamp): string
    {
        return hash_hmac(
            'sha256',
            (string) config('shopee.partner_id').$path.$timestamp,
            (string) config('shopee.partner_key')
        );
    }

    private function shopSignature(string $path, int $timestamp, ShopeeConnection $connection): string
    {
        return hash_hmac(
            'sha256',
            (string) config('shopee.partner_id').$path.$timestamp.$connection->access_token.$connection->shop_id,
            (string) config('shopee.partner_key')
        );
    }

    private function http(bool $retry = false): PendingRequest
    {
        $request = Http::asJson()
            ->acceptJson()
            ->timeout((int) config('shopee.timeout', 15));

        // Só GET recebe retry automático. Refresh token da Shopee é de uso único;
        // repetir POST após uma resposta perdida pode invalidar a sessão.
        return $retry ? $request->retry(2, 300, throw: false) : $request;
    }

    private function baseUrl(): string
    {
        $environment = (string) config('shopee.environment', 'production');

        return rtrim((string) config("shopee.base_urls.{$environment}", config('shopee.base_urls.production')), '/');
    }

    private function assertConfigured(): void
    {
        if (! config('shopee.enabled')) {
            throw new RuntimeException('Integração Shopee desativada. Defina SHOPEE_ENABLED=true.');
        }

        if (! config('shopee.partner_id') || ! config('shopee.partner_key')) {
            throw new RuntimeException('Configure SHOPEE_PARTNER_ID e SHOPEE_PARTNER_KEY.');
        }
    }
}
