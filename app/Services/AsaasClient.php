<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AsaasClient
{
    /**
     * @param  array{name: string, email?: string|null, cpfCnpj?: string|null}  $customer
     */
    public function createCustomer(array $customer): string
    {
        $response = $this->request()->post($this->baseUrl().'/customers', array_filter($customer));
        $json = $this->decode($response->json(), $response->successful());
        $customerId = (string) ($json['id'] ?? '');

        if ($customerId === '') {
            throw new RuntimeException('O Asaas não retornou o identificador do cliente.');
        }

        return $customerId;
    }

    /**
     * @return array<string, mixed>
     */
    public function createPixPayment(
        string $customerId,
        string $amount,
        string $description,
        string $externalReference,
    ): array {
        $response = $this->request()->post($this->baseUrl().'/payments', [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => (float) $amount,
            'dueDate' => now()->addDays(max(1, (int) config('asaas.pix_due_days', 1)))->toDateString(),
            'description' => Str::limit($description, 500, ''),
            'externalReference' => $externalReference,
        ]);

        return $this->decode($response->json(), $response->successful());
    }

    /**
     * @param  array{name: string, email: string, cpf_cnpj: string, phone: string, postal_code: string, address_number: string, address_complement?: string|null}  $holder
     * @param  array{holder_name: string, number: string, expiry_month: int, expiry_year: int, ccv: string}  $card
     * @return array<string, mixed>
     */
    public function createCreditCardPayment(
        string $customerId,
        string $amount,
        string $description,
        string $externalReference,
        array $holder,
        array $card,
        int $installments,
        string $remoteIp,
    ): array {
        $body = [
            'customer' => $customerId,
            'billingType' => 'CREDIT_CARD',
            'value' => (float) $amount,
            'dueDate' => now()->toDateString(),
            'description' => Str::limit($description, 500, ''),
            'externalReference' => $externalReference,
            'creditCard' => [
                'holderName' => $card['holder_name'],
                'number' => $card['number'],
                'expiryMonth' => str_pad((string) $card['expiry_month'], 2, '0', STR_PAD_LEFT),
                'expiryYear' => (string) $card['expiry_year'],
                'ccv' => $card['ccv'],
            ],
            'creditCardHolderInfo' => [
                'name' => $holder['name'],
                'email' => $holder['email'],
                'cpfCnpj' => $holder['cpf_cnpj'],
                'postalCode' => $holder['postal_code'],
                'addressNumber' => $holder['address_number'],
                'addressComplement' => $holder['address_complement'] ?? null,
                'mobilePhone' => $holder['phone'],
            ],
            'remoteIp' => $remoteIp,
        ];

        if ($installments > 1) {
            $body['installmentCount'] = $installments;
            $body['totalValue'] = (float) $amount;
        }

        $response = $this->request()
            ->timeout(max(60, (int) config('asaas.credit_card_timeout', 60)))
            ->post($this->baseUrl().'/payments', $body);

        return $this->decode($response->json(), $response->successful());
    }

    /**
     * @return array<string, mixed>
     */
    public function getPixQrCode(string $asaasPaymentId): array
    {
        $response = $this->request()->retry(2, 300, throw: false)
            ->get($this->baseUrl().'/payments/'.$asaasPaymentId.'/pixQrCode');

        return $this->decode($response->json(), $response->successful());
    }

    /**
     * @return array<string, mixed>
     */
    public function registerWebhook(): array
    {
        $webhookUrl = trim((string) config('asaas.webhook_url'));
        $webhookToken = trim((string) config('asaas.webhook_token'));

        if (filter_var($webhookUrl, FILTER_VALIDATE_URL) === false || ! str_starts_with($webhookUrl, 'https://')) {
            throw new RuntimeException('ASAAS_WEBHOOK_URL deve ser uma URL HTTPS pública.');
        }

        if (mb_strlen($webhookToken) < 32) {
            throw new RuntimeException('ASAAS_WEBHOOK_TOKEN precisa ter ao menos 32 caracteres.');
        }

        $body = [
            'name' => 'Desapega.ghotme Pix',
            'url' => $webhookUrl,
            'enabled' => true,
            'authToken' => $webhookToken,
            'events' => ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'],
        ];

        $email = trim((string) config('asaas.webhook_email'));
        if ($email !== '') {
            $body['email'] = $email;
        }

        $response = $this->request()->post($this->baseUrl().'/webhooks', $body);

        return $this->decode($response->json(), $response->successful());
    }

    public function verifiesWebhookToken(?string $token): bool
    {
        $expectedToken = trim((string) config('asaas.webhook_token'));

        return $expectedToken !== ''
            && is_string($token)
            && hash_equals($expectedToken, $token);
    }

    private function request(): PendingRequest
    {
        $this->assertConfigured();

        return Http::asJson()
            ->acceptJson()
            ->withHeader('access_token', (string) config('asaas.api_key'))
            ->timeout(max(1, (int) config('asaas.timeout', 15)));
    }

    private function baseUrl(): string
    {
        $environment = (string) config('asaas.environment', 'production');
        $baseUrl = config("asaas.base_urls.{$environment}");

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new RuntimeException('ASAAS_ENV inválido. Use production ou sandbox.');
        }

        return $baseUrl;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(mixed $json, bool $successful): array
    {
        $json = is_array($json) ? $json : [];

        if (! $successful) {
            $message = data_get($json, 'errors.0.description')
                ?? data_get($json, 'message')
                ?? 'O Asaas não conseguiu processar esta solicitação.';

            throw new RuntimeException((string) $message);
        }

        return $json;
    }

    private function assertConfigured(): void
    {
        if (! config('asaas.enabled')) {
            throw new RuntimeException('A integração do Asaas está desativada.');
        }

        if (trim((string) config('asaas.api_key')) === '') {
            throw new RuntimeException('ASAAS_API_KEY não configurada.');
        }
    }
}
