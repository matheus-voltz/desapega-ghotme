<?php

namespace Tests\Feature;

use App\Models\AsaasPayment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AsaasPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('asaas.enabled', true);
        config()->set('asaas.environment', 'sandbox');
        config()->set('asaas.api_key', 'sandbox-key');
        config()->set('asaas.webhook_token', 'a-secure-webhook-token-with-more-than-32-characters');
    }

    public function test_it_sends_the_product_price_to_asaas_and_displays_the_dynamic_pix_code(): void
    {
        $product = $this->product();

        Http::fake([
            'https://api-sandbox.asaas.com/v3/customers' => Http::response(['id' => 'cus_123'], 200),
            'https://api-sandbox.asaas.com/v3/payments' => Http::response([
                'id' => 'pay_123',
                'status' => 'PENDING',
            ], 200),
            'https://api-sandbox.asaas.com/v3/payments/pay_123/pixQrCode' => Http::response([
                'encodedImage' => 'base64-png',
                'payload' => 'pix-copy-paste',
                'expirationDate' => now()->addHour()->toIso8601String(),
            ], 200),
        ]);

        $response = $this->post(route('purchase.product.pix.create', $product), [
            'customer_name' => 'Pessoa Teste',
            'customer_email' => 'pessoa@example.com',
            'customer_cpf_cnpj' => '12345678901',
            'customer_phone' => '11999999999',
            'postal_code' => '01001000',
            'address_number' => '100',
        ]);

        $payment = AsaasPayment::query()->sole();

        $response->assertRedirect(route('purchase.product.pix', [
            'product' => $product,
            'payment' => $payment->external_reference,
        ]));
        $this->assertSame('1850.00', $payment->amount);
        $this->assertSame('pay_123', $payment->asaas_payment_id);
        $this->assertSame('pix-copy-paste', $payment->pix_payload);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
                && $request['billingType'] === 'PIX'
                && $request['value'] === 1850.0;
        });

        $this->get(route('purchase.product.pix', [
            'product' => $product,
            'payment' => $payment->external_reference,
        ]))->assertOk()->assertSee('pix-copy-paste');
    }

    public function test_it_requires_an_email_and_cpf_or_cnpj_to_create_a_pix_payment(): void
    {
        $product = $this->product();

        $response = $this->from(route('purchase.product.pix', $product))
            ->post(route('purchase.product.pix.create', $product), [
                'customer_name' => 'Pessoa Teste',
            ]);

        $response->assertRedirect(route('purchase.product.pix', $product));
        $response->assertSessionHasErrors(['customer_email', 'customer_cpf_cnpj']);
        $this->assertDatabaseCount('asaas_payments', 0);
    }

    public function test_it_processes_a_credit_card_payment_in_installments_without_storing_card_data(): void
    {
        $product = $this->product();

        Http::fake([
            'https://api-sandbox.asaas.com/v3/customers' => Http::response(['id' => 'cus_card_123'], 200),
            'https://api-sandbox.asaas.com/v3/payments' => Http::response([
                'id' => 'pay_card_123',
                'status' => 'CONFIRMED',
            ], 200),
        ]);

        $response = $this->post(route('purchase.product.card.create', $product), $this->creditCardDetails([
            'installments' => 6,
        ]));

        $payment = AsaasPayment::query()->sole();

        $response->assertRedirect(route('purchase.product.card', [
            'product' => $product,
            'payment' => $payment->external_reference,
        ]));
        $this->assertSame('2122.04', $payment->amount);
        $this->assertSame('pay_card_123', $payment->asaas_payment_id);
        $this->assertSame('CONFIRMED', $payment->status);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
                && $request['billingType'] === 'CREDIT_CARD'
                && $request['installmentCount'] === 6
                && $request['totalValue'] === 2122.04
                && $request['creditCard']['number'] === '4111111111111111'
                && $request['remoteIp'] === '127.0.0.1';
        });

        $this->get(route('purchase.product.card', [
            'product' => $product,
            'payment' => $payment->external_reference,
        ]))->assertOk()
            ->assertSee('Pagamento aprovado!')
            ->assertSee('O cartão foi aprovado. Seu pedido segue para preparação.');
    }

    public function test_it_keeps_up_to_five_card_installments_without_fee(): void
    {
        $product = $this->product();

        Http::fake([
            'https://api-sandbox.asaas.com/v3/customers' => Http::response(['id' => 'cus_card_456'], 200),
            'https://api-sandbox.asaas.com/v3/payments' => Http::response(['id' => 'pay_card_456', 'status' => 'PENDING'], 200),
        ]);

        $this->post(route('purchase.product.card.create', $product), $this->creditCardDetails([
            'installments' => 5,
        ]));

        $this->assertDatabaseHas('asaas_payments', ['amount' => 2050]);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
            && $request['value'] === 2050.0);
    }

    public function test_it_limits_card_payments_up_to_one_hundred_reais_to_five_installments(): void
    {
        $product = $this->product();
        $product->update(['marketplace_price' => 100]);

        $response = $this->from(route('purchase.product.card', $product))
            ->post(route('purchase.product.card.create', $product), $this->creditCardDetails([
                'installments' => 6,
            ]));

        $response->assertRedirect(route('purchase.product.card', $product));
        $response->assertSessionHasErrors(['installments']);
        $response->assertSessionHasErrors([
            'installments' => 'Para este valor, escolha entre 1 e 5 parcelas.',
        ]);
        $this->assertDatabaseCount('asaas_payments', 0);

        $this->get(route('purchase.product.card', $product))
            ->assertOk()
            ->assertSee('5x de R$ 20,00 sem juros')
            ->assertDontSee('6x de R$');
    }

    public function test_it_does_not_offer_card_payment_without_a_marketplace_price(): void
    {
        $product = $this->product();
        $product->update(['marketplace_price' => null]);

        $this->get(route('purchase.product.card', $product))->assertNotFound();

        $this->post(route('purchase.product.card.create', $product), $this->creditCardDetails())
            ->assertNotFound();

        $this->assertDatabaseCount('asaas_payments', 0);
    }

    public function test_it_does_not_flash_credit_card_number_or_cvv_after_validation_fails(): void
    {
        $product = $this->product();
        $details = $this->creditCardDetails([
            'credit_card_number' => '4111111111111111',
            'credit_card_cvv' => '123',
            'installments' => 13,
        ]);

        $response = $this->from(route('purchase.product.card', $product))
            ->post(route('purchase.product.card.create', $product), $details);

        $response->assertRedirect(route('purchase.product.card', $product));
        $response->assertSessionHasErrors(['installments']);
        $this->assertNull(session()->getOldInput('credit_card_number'));
        $this->assertNull(session()->getOldInput('credit_card_cvv'));
        $this->assertDatabaseCount('asaas_payments', 0);
    }

    public function test_it_marks_a_product_sold_only_after_a_valid_received_webhook(): void
    {
        $product = $this->product();
        $payment = AsaasPayment::create([
            'product_id' => $product->id,
            'external_reference' => '656bf067-94a8-4f4f-aa31-021d0ee1a246',
            'asaas_payment_id' => 'pay_received',
            'amount' => 1850,
            'status' => 'PENDING',
        ]);

        $payload = [
            'id' => 'evt_received_123',
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => $payment->asaas_payment_id,
                'value' => 1850,
            ],
        ];

        $this->postJson(route('webhooks.asaas'), $payload, [
            'asaas-access-token' => 'a-secure-webhook-token-with-more-than-32-characters',
        ])->assertNoContent();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => 'sold',
            'sale_channel' => 'asaas',
        ]);
        $this->assertDatabaseHas('asaas_payments', [
            'id' => $payment->id,
            'status' => 'RECEIVED',
        ]);

        $this->postJson(route('webhooks.asaas'), $payload, [
            'asaas-access-token' => 'a-secure-webhook-token-with-more-than-32-characters',
        ])->assertNoContent();

        $this->assertDatabaseCount('asaas_webhook_events', 1);
    }

    public function test_it_rejects_a_webhook_without_the_configured_token(): void
    {
        $product = $this->product();
        $payment = AsaasPayment::create([
            'product_id' => $product->id,
            'external_reference' => '660f6f58-4d2b-4c7a-a01c-66b6f9187cab',
            'asaas_payment_id' => 'pay_unauthorized',
            'amount' => 1850,
            'status' => 'PENDING',
        ]);

        $this->postJson(route('webhooks.asaas'), [
            'id' => 'evt_unauthorized',
            'event' => 'PAYMENT_RECEIVED',
            'payment' => ['id' => $payment->asaas_payment_id, 'value' => 1850],
        ], [
            'asaas-access-token' => 'incorrect-token',
        ])->assertUnauthorized();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => 'available',
        ]);
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Notebook para teste',
            'slug' => 'notebook-para-teste',
            'pix_price' => 1850,
            'marketplace_price' => 2050,
            'status' => 'available',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function creditCardDetails(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Pessoa Teste',
            'customer_email' => 'pessoa@example.com',
            'customer_cpf_cnpj' => '12345678901',
            'customer_phone' => '11999999999',
            'postal_code' => '01001000',
            'address_number' => '100',
            'credit_card_holder_name' => 'Pessoa Teste',
            'credit_card_number' => '4111111111111111',
            'credit_card_expiry_month' => 12,
            'credit_card_expiry_year' => now()->year + 1,
            'credit_card_cvv' => '123',
            'installments' => 1,
        ], $overrides);
    }
}
