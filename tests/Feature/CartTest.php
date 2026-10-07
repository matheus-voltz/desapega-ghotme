<?php

namespace Tests\Feature;

use App\Models\AsaasPayment;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_adding_an_item(): void
    {
        $product = Product::factory()->create();

        $this->post(route('cart.items.store', $product))
            ->assertRedirect(route('login'));
    }

    public function test_customer_can_add_unique_items_to_their_cart_and_see_the_total(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Mesa de madeira',
            'pix_price' => 1250,
        ]);

        $this->actingAs($customer)
            ->post(route('cart.items.store', $product))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success', 'Item adicionado à sua sacola.');

        $this->actingAs($customer)
            ->post(route('cart.items.store', $product))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success', 'Este item já está na sua sacola.');

        $this->assertDatabaseCount('cart_items', 1);

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Mesa de madeira')
            ->assertSee('R$ 1.250,00');
    }

    public function test_customer_cannot_add_an_unavailable_item_to_their_cart(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['status' => 'reserved']);

        $this->actingAs($customer)
            ->post(route('cart.items.store', $product))
            ->assertStatus(409);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_customer_cannot_mix_items_from_different_sellers_in_one_cart(): void
    {
        $customer = User::factory()->create();
        $firstSeller = User::factory()->create(['account_type' => 'seller']);
        $secondSeller = User::factory()->create(['account_type' => 'seller']);
        $firstProduct = Product::factory()->for($firstSeller, 'seller')->create();
        $secondProduct = Product::factory()->for($secondSeller, 'seller')->create();

        $this->actingAs($customer)->post(route('cart.items.store', $firstProduct));

        $this->actingAs($customer)
            ->post(route('cart.items.store', $secondProduct))
            ->assertStatus(422);

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_customer_cannot_remove_another_customers_cart_item(): void
    {
        $customer = User::factory()->create();
        $otherCartItem = CartItem::factory()->create();

        $this->actingAs($customer)
            ->delete(route('cart.items.destroy', $otherCartItem))
            ->assertNotFound();

        $this->assertDatabaseHas('cart_items', ['id' => $otherCartItem->id]);
    }

    public function test_cart_pix_payment_sends_the_sum_of_selected_items_to_asaas(): void
    {
        config()->set('asaas.enabled', true);
        config()->set('asaas.environment', 'sandbox');
        config()->set('asaas.api_key', 'sandbox-key');

        Http::fake([
            'https://api-sandbox.asaas.com/v3/customers' => Http::response(['id' => 'cus_cart_123'], 200),
            'https://api-sandbox.asaas.com/v3/payments' => Http::response(['id' => 'pay_cart_123', 'status' => 'PENDING'], 200),
            'https://api-sandbox.asaas.com/v3/payments/pay_cart_123/pixQrCode' => Http::response([
                'encodedImage' => 'base64-png',
                'payload' => 'pix-cart-copy-paste',
                'expirationDate' => now()->addHour()->toIso8601String(),
            ], 200),
        ]);

        $customer = User::factory()->create();
        $firstProduct = Product::factory()->create(['pix_price' => 100]);
        $secondProduct = Product::factory()->create(['pix_price' => 250]);

        $this->actingAs($customer)->post(route('cart.items.store', $firstProduct));
        $this->actingAs($customer)->post(route('cart.items.store', $secondProduct));

        $response = $this->actingAs($customer)->post(route('cart.pix.create'), [
            'customer_name' => 'Pessoa Teste',
            'customer_email' => 'pessoa@example.com',
            'customer_cpf_cnpj' => '12345678901',
            'customer_phone' => '11999999999',
            'postal_code' => '01001000',
            'address_number' => '100',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('asaas_payments', [
            'cart_id' => $customer->cart()->value('id'),
            'amount' => 350,
            'asaas_payment_id' => 'pay_cart_123',
        ]);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
            && $request['billingType'] === 'PIX'
            && $request['value'] === 350.0);
    }

    public function test_cart_credit_card_payment_sends_the_total_price_to_asaas(): void
    {
        config()->set('asaas.enabled', true);
        config()->set('asaas.environment', 'sandbox');
        config()->set('asaas.api_key', 'sandbox-key');

        Http::fake([
            'https://api-sandbox.asaas.com/v3/customers' => Http::response(['id' => 'cus_cart_card_123'], 200),
            'https://api-sandbox.asaas.com/v3/payments' => Http::response(['id' => 'pay_cart_card_123', 'status' => 'CONFIRMED'], 200),
        ]);

        $customer = User::factory()->create();
        $firstProduct = Product::factory()->create(['pix_price' => 100, 'marketplace_price' => 1100]);
        $secondProduct = Product::factory()->create(['pix_price' => 250, 'marketplace_price' => 900]);

        $this->actingAs($customer)->post(route('cart.items.store', $firstProduct));
        $this->actingAs($customer)->post(route('cart.items.store', $secondProduct));

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Pagar a sacola com cartão');

        $this->actingAs($customer)
            ->get(route('cart.card'))
            ->assertOk()
            ->assertSee('Pague com cartão')
            ->assertSee('Sua sacola · 2 item(ns)');

        $response = $this->actingAs($customer)->post(route('cart.card.create'), $this->creditCardDetails([
            'installments' => 6,
        ]));

        $payment = AsaasPayment::query()->sole();

        $response->assertRedirect(route('cart.card', ['payment' => $payment->external_reference]));
        $this->assertSame('2070.29', $payment->amount);
        $this->assertSame($customer->cart()->value('id'), $payment->cart_id);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
            && $request['billingType'] === 'CREDIT_CARD'
            && $request['installmentCount'] === 6
            && $request['totalValue'] === 2070.29);
    }

    public function test_received_cart_payment_marks_every_item_as_sold_and_empties_the_cart(): void
    {
        config()->set('asaas.webhook_token', 'a-secure-webhook-token-with-more-than-32-characters');

        $customer = User::factory()->create();
        $firstProduct = Product::factory()->create(['pix_price' => 100]);
        $secondProduct = Product::factory()->create(['pix_price' => 250]);
        $cart = $customer->cart()->create();
        $cart->items()->createMany([
            ['product_id' => $firstProduct->id],
            ['product_id' => $secondProduct->id],
        ]);
        $payment = AsaasPayment::create([
            'cart_id' => $cart->id,
            'external_reference' => '3975f419-5e3a-4a11-8609-5649f0dd0977',
            'asaas_payment_id' => 'pay_cart_received',
            'amount' => 350,
            'status' => 'PENDING',
        ]);

        $this->postJson(route('webhooks.asaas'), [
            'id' => 'evt_cart_received',
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => $payment->asaas_payment_id,
                'value' => 350,
            ],
        ], [
            'asaas-access-token' => 'a-secure-webhook-token-with-more-than-32-characters',
        ])->assertNoContent();

        $this->assertDatabaseHas('products', ['id' => $firstProduct->id, 'status' => 'sold']);
        $this->assertDatabaseHas('products', ['id' => $secondProduct->id, 'status' => 'sold']);
        $this->assertDatabaseCount('cart_items', 0);
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
