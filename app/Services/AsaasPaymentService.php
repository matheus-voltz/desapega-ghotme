<?php

namespace App\Services;

use App\CreditCardInstallmentCalculator;
use App\Models\AsaasPayment;
use App\Models\AsaasWebhookEvent;
use App\Models\Bundle;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AsaasPaymentService
{
    public function __construct(
        private readonly AsaasClient $client,
        private readonly SaleNotifier $saleNotifier,
        private readonly CreditCardInstallmentCalculator $installments,
    ) {}

    /**
     * @param  array{customer_name: string, customer_email: string, customer_cpf_cnpj: string, customer_phone: string, postal_code: string, address_number: string, address_complement?: string|null}  $customer
     */
    public function createForProduct(Product $product, array $customer): AsaasPayment
    {
        return $this->createForTarget($product, $customer);
    }

    /**
     * @param  array{customer_name: string, customer_email?: string|null, customer_cpf_cnpj?: string|null}  $customer
     */
    public function createForBundle(Bundle $bundle, array $customer): AsaasPayment
    {
        return $this->createForTarget($bundle, $customer);
    }

    /**
     * @param  array{customer_name: string, customer_email?: string|null, customer_cpf_cnpj?: string|null}  $customer
     */
    public function createForCart(Cart $cart, array $customer): AsaasPayment
    {
        return $this->createForTarget($cart, $customer);
    }

    /**
     * @param  array{customer_name: string, customer_email: string, customer_cpf_cnpj: string, customer_phone: string, postal_code: string, address_number: string, address_complement?: string|null, credit_card_holder_name: string, credit_card_number: string, credit_card_expiry_month: int, credit_card_expiry_year: int, credit_card_cvv: string, installments: int}  $details
     */
    public function createCreditCardForProduct(Product $product, array $details, string $remoteIp): AsaasPayment
    {
        return $this->createCreditCardForTarget($product, $details, $remoteIp);
    }

    /**
     * @param  array{customer_name: string, customer_email: string, customer_cpf_cnpj: string, customer_phone: string, postal_code: string, address_number: string, address_complement?: string|null, credit_card_holder_name: string, credit_card_number: string, credit_card_expiry_month: int, credit_card_expiry_year: int, credit_card_cvv: string, installments: int}  $details
     */
    public function createCreditCardForCart(Cart $cart, array $details, string $remoteIp): AsaasPayment
    {
        return $this->createCreditCardForTarget($cart, $details, $remoteIp);
    }

    /**
     * @param  array{customer_name: string, customer_email?: string|null, customer_cpf_cnpj?: string|null}  $customer
     */
    private function createForTarget(Product|Bundle|Cart $target, array $customer): AsaasPayment
    {
        $payment = DB::transaction(function () use ($target): AsaasPayment {
            if ($target instanceof Product) {
                $lockedTarget = Product::query()->lockForUpdate()->findOrFail($target->id);
                $this->ensureProductIsAvailable($lockedTarget);
                $activePayment = $this->activePayment($lockedTarget);

                if ($activePayment) {
                    return $activePayment;
                }

                return AsaasPayment::create([
                    'product_id' => $lockedTarget->id,
                    'external_reference' => (string) Str::uuid(),
                    'amount' => $lockedTarget->pix_price,
                    'status' => 'CREATING',
                ]);
            }

            if ($target instanceof Cart) {
                $lockedTarget = Cart::query()->lockForUpdate()->findOrFail($target->id);
                $cartItems = $lockedTarget->items()->lockForUpdate()->get();

                if ($cartItems->isEmpty()) {
                    throw new RuntimeException('Adicione pelo menos um item à sua sacola.');
                }

                $products = Product::query()
                    ->whereIn('id', $cartItems->pluck('product_id'))
                    ->lockForUpdate()
                    ->get();

                if ($products->count() !== $cartItems->count() || $products->contains(fn (Product $product): bool => $product->status !== 'available')) {
                    throw new RuntimeException('Um ou mais itens da sua sacola não estão mais disponíveis.');
                }

                $activePayment = $this->activePayment($lockedTarget);
                if ($activePayment) {
                    return $activePayment;
                }

                return AsaasPayment::create([
                    'cart_id' => $lockedTarget->id,
                    'external_reference' => (string) Str::uuid(),
                    'amount' => $products->sum(fn (Product $product): float => (float) $product->pix_price),
                    'status' => 'CREATING',
                ]);
            }

            $lockedTarget = Bundle::query()->lockForUpdate()->findOrFail($target->id);
            $products = $lockedTarget->products()->lockForUpdate()->get();

            if (! $lockedTarget->active || $products->contains(fn (Product $product): bool => $product->status !== 'available')) {
                throw new RuntimeException('Um ou mais itens deste combo não estão mais disponíveis.');
            }

            $activePayment = $this->activePayment($lockedTarget);
            if ($activePayment) {
                return $activePayment;
            }

            return AsaasPayment::create([
                'bundle_id' => $lockedTarget->id,
                'external_reference' => (string) Str::uuid(),
                'amount' => $lockedTarget->pix_price,
                'status' => 'CREATING',
            ]);
        });

        if ($payment->asaas_payment_id !== null) {
            return $this->loadQrCode($payment);
        }

        $payment->update($this->customerDetails($customer));

        try {
            $customerId = $this->client->createCustomer([
                'name' => $customer['customer_name'],
                'email' => $customer['customer_email'] ?? null,
                'cpfCnpj' => $customer['customer_cpf_cnpj'] ?? null,
            ]);

            $description = $this->paymentDescription($payment);

            $asaasPayment = $this->client->createPixPayment(
                $customerId,
                (string) $payment->amount,
                $description,
                $payment->external_reference,
            );

            $asaasPaymentId = (string) ($asaasPayment['id'] ?? '');
            if ($asaasPaymentId === '') {
                throw new RuntimeException('O Asaas não retornou o identificador da cobrança.');
            }

            $payment->update([
                'asaas_customer_id' => $customerId,
                'asaas_payment_id' => $asaasPaymentId,
                'status' => (string) ($asaasPayment['status'] ?? 'PENDING'),
            ]);

            return $this->loadQrCode($payment->fresh());
        } catch (Throwable $exception) {
            if ($payment->asaas_payment_id === null) {
                $payment->update(['status' => 'FAILED']);
            }

            report($exception);

            throw new RuntimeException('Não foi possível gerar o Pix agora. Tente novamente em alguns instantes.');
        }
    }

    /**
     * @param  array{customer_name: string, customer_email: string, customer_cpf_cnpj: string, customer_phone: string, postal_code: string, address_number: string, address_complement?: string|null, credit_card_holder_name: string, credit_card_number: string, credit_card_expiry_month: int, credit_card_expiry_year: int, credit_card_cvv: string, installments: int}  $details
     */
    private function createCreditCardForTarget(Product|Cart $target, array $details, string $remoteIp): AsaasPayment
    {
        $payment = DB::transaction(function () use ($target, $details): AsaasPayment {
            if ($target instanceof Product) {
                $lockedTarget = Product::query()->lockForUpdate()->findOrFail($target->id);
                $this->ensureProductIsAvailable($lockedTarget);

                if ($lockedTarget->marketplace_price === null) {
                    throw new RuntimeException('O preço para cartão ainda não foi configurado neste item.');
                }

                $price = (float) $lockedTarget->marketplace_price;
                $paymentAttributes = ['product_id' => $lockedTarget->id];
            } else {
                $lockedTarget = Cart::query()->lockForUpdate()->findOrFail($target->id);
                $cartItems = $lockedTarget->items()->lockForUpdate()->get();

                if ($cartItems->isEmpty()) {
                    throw new RuntimeException('Adicione pelo menos um item à sua sacola.');
                }

                $products = Product::query()
                    ->whereIn('id', $cartItems->pluck('product_id'))
                    ->lockForUpdate()
                    ->get();

                if ($products->count() !== $cartItems->count() || $products->contains(fn (Product $product): bool => $product->status !== 'available')) {
                    throw new RuntimeException('Um ou mais itens da sua sacola não estão mais disponíveis.');
                }

                if ($products->contains(fn (Product $product): bool => $product->marketplace_price === null)) {
                    throw new RuntimeException('Um ou mais itens da sua sacola não têm preço total para pagamento no cartão.');
                }

                $price = $products->sum(fn (Product $product): float => (float) $product->marketplace_price);
                $paymentAttributes = ['cart_id' => $lockedTarget->id];
            }

            $this->ensurePaymentIsNotBeingCreated($lockedTarget);
            $maximumInstallments = $this->installments->maximumFor($price);
            if ($details['installments'] > $maximumInstallments) {
                throw new RuntimeException("Este valor permite no máximo {$maximumInstallments} parcelas no cartão.");
            }

            return AsaasPayment::create(array_merge($paymentAttributes, [
                'external_reference' => (string) Str::uuid(),
                'amount' => $this->installments->for($price, $details['installments'])['total'],
                'status' => 'CREATING',
            ]));
        });

        $payment->update($this->customerDetails($details));

        try {
            $customerId = $this->client->createCustomer([
                'name' => $details['customer_name'],
                'email' => $details['customer_email'],
                'cpfCnpj' => $details['customer_cpf_cnpj'],
            ]);

            $description = $this->paymentDescription($payment);

            $asaasPayment = $this->client->createCreditCardPayment(
                $customerId,
                (string) $payment->amount,
                $description,
                $payment->external_reference,
                [
                    'name' => $details['customer_name'],
                    'email' => $details['customer_email'],
                    'cpf_cnpj' => $details['customer_cpf_cnpj'],
                    'phone' => $details['customer_phone'],
                    'postal_code' => $details['postal_code'],
                    'address_number' => $details['address_number'],
                    'address_complement' => $details['address_complement'] ?? null,
                ],
                [
                    'holder_name' => $details['credit_card_holder_name'],
                    'number' => $details['credit_card_number'],
                    'expiry_month' => $details['credit_card_expiry_month'],
                    'expiry_year' => $details['credit_card_expiry_year'],
                    'ccv' => $details['credit_card_cvv'],
                ],
                $details['installments'],
                $remoteIp,
            );

            $asaasPaymentId = (string) ($asaasPayment['id'] ?? '');
            if ($asaasPaymentId === '') {
                throw new RuntimeException('O Asaas não retornou o identificador da cobrança.');
            }

            $payment->update([
                'asaas_customer_id' => $customerId,
                'asaas_payment_id' => $asaasPaymentId,
                'status' => (string) ($asaasPayment['status'] ?? 'PENDING'),
            ]);

            return $payment->fresh();
        } catch (Throwable $exception) {
            $payment->update(['status' => 'FAILED']);

            report($exception);

            throw new RuntimeException('Não foi possível processar o cartão. Confira os dados e tente novamente.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): void
    {
        $eventId = trim((string) ($payload['id'] ?? ''));
        $event = trim((string) ($payload['event'] ?? ''));
        $asaasPaymentId = trim((string) data_get($payload, 'payment.id'));

        if ($eventId === '' || $asaasPaymentId === '' || ! in_array($event, ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'], true)) {
            return;
        }

        $notifications = DB::transaction(function () use ($eventId, $event, $asaasPaymentId, $payload): array {
            if (AsaasWebhookEvent::query()->where('event_id', $eventId)->exists()) {
                return [];
            }

            $payment = AsaasPayment::query()
                ->where('asaas_payment_id', $asaasPaymentId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                Log::warning('Webhook do Asaas para uma cobrança desconhecida.', [
                    'event_id' => $eventId,
                    'asaas_payment_id' => $asaasPaymentId,
                ]);

                return [];
            }

            $receivedAmount = data_get($payload, 'payment.value');
            if (! is_numeric($receivedAmount) || ! $this->amountMatches($payment->amount, (string) $receivedAmount)) {
                Log::critical('Valor recebido do Asaas difere do valor registrado.', [
                    'asaas_payment_id' => $asaasPaymentId,
                    'registered_amount' => $payment->amount,
                    'received_amount' => $receivedAmount,
                ]);

                AsaasWebhookEvent::create([
                    'asaas_payment_id' => $payment->id,
                    'event_id' => $eventId,
                    'event' => $event,
                    'payload' => $payload,
                    'processed_at' => now(),
                ]);

                return [];
            }

            $isPaid = $event === 'PAYMENT_RECEIVED';
            $payment->update([
                'status' => $isPaid ? 'RECEIVED' : 'CONFIRMED',
                'paid_at' => $isPaid ? ($payment->paid_at ?? now()) : $payment->paid_at,
            ]);

            $products = $this->updateProductsForPayment($payment, $isPaid);

            AsaasWebhookEvent::create([
                'asaas_payment_id' => $payment->id,
                'event_id' => $eventId,
                'event' => $event,
                'payload' => $payload,
                'processed_at' => now(),
            ]);

            return $isPaid ? [[
                'payment' => $payment->fresh(),
                'products' => collect($products),
            ]] : [];
        });

        foreach ($notifications as $notification) {
            $this->saleNotifier->paymentReceived($notification['payment'], $notification['products']);
        }
    }

    private function loadQrCode(AsaasPayment $payment): AsaasPayment
    {
        if ($payment->pix_payload !== null && $payment->pix_encoded_image !== null) {
            return $payment;
        }

        $qrCode = $this->client->getPixQrCode((string) $payment->asaas_payment_id);
        $payload = trim((string) ($qrCode['payload'] ?? ''));
        $encodedImage = trim((string) ($qrCode['encodedImage'] ?? ''));

        if ($payload === '' || $encodedImage === '') {
            throw new RuntimeException('O Asaas não retornou o QR Code Pix.');
        }

        $expirationDate = data_get($qrCode, 'expirationDate');

        $payment->update([
            'pix_payload' => $payload,
            'pix_encoded_image' => $encodedImage,
            'pix_expires_at' => $expirationDate ? Carbon::parse((string) $expirationDate) : null,
        ]);

        return $payment->fresh();
    }

    private function activePayment(Product|Bundle|Cart $target): ?AsaasPayment
    {
        $payment = $target->asaasPayments()
            ->whereIn('status', ['PENDING', 'CONFIRMED'])
            ->where('pix_expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($payment) {
            return $payment;
        }

        if ($target->asaasPayments()->where('status', 'CREATING')->exists()) {
            throw new RuntimeException('A geração deste Pix ainda está em andamento. Aguarde alguns segundos e tente novamente.');
        }

        return null;
    }

    private function ensurePaymentIsNotBeingCreated(Product|Bundle|Cart $target): void
    {
        if ($target->asaasPayments()->where('status', 'CREATING')->exists()) {
            throw new RuntimeException('Um pagamento já está sendo processado. Aguarde alguns segundos antes de tentar novamente.');
        }
    }

    /**
     * @return list<Product>
     */
    private function updateProductsForPayment(AsaasPayment $payment, bool $isPaid): array
    {
        if ($payment->product_id !== null) {
            $product = Product::query()->lockForUpdate()->find($payment->product_id);

            return $product ? $this->updateProductStatus($product, $isPaid) : [];
        }

        if ($payment->cart_id !== null) {
            $cart = Cart::query()->find($payment->cart_id);
            if (! $cart) {
                return [];
            }

            $productIds = $cart->items()->pluck('product_id');
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->flatMap(fn (Product $product): array => $this->updateProductStatus($product, $isPaid))
                ->all();

            if ($isPaid) {
                $cart->items()->delete();
            }

            return $products;
        }

        if ($payment->bundle_id === null) {
            return [];
        }

        $bundle = Bundle::query()->find($payment->bundle_id);
        if (! $bundle) {
            return [];
        }

        return $bundle->products()
            ->lockForUpdate()
            ->get()
            ->flatMap(fn (Product $product): array => $this->updateProductStatus($product, $isPaid))
            ->all();
    }

    /**
     * @return list<Product>
     */
    private function updateProductStatus(Product $product, bool $isPaid): array
    {
        if ($isPaid && $product->status !== 'sold') {
            $product->update([
                'status' => 'sold',
                'sale_channel' => 'asaas',
            ]);

            return [$product->fresh()];
        }

        if (! $isPaid && $product->status === 'available') {
            $product->update([
                'status' => 'reserved',
                'sale_channel' => 'asaas',
            ]);
        }

        return [];
    }

    private function ensureProductIsAvailable(Product $product): void
    {
        if ($product->status !== 'available') {
            throw new RuntimeException('Este produto não está mais disponível.');
        }
    }

    private function paymentDescription(AsaasPayment $payment): string
    {
        if ($payment->product) {
            return 'Compra de '.$payment->product->name;
        }

        if ($payment->bundle) {
            return 'Compra do combo '.$payment->bundle->name;
        }

        $itemsCount = $payment->cart?->items()->count() ?? 0;

        return $itemsCount > 0
            ? "Compra de {$itemsCount} itens via Desapega.ghotme"
            : 'Compra via Desapega.ghotme';
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, string|null>
     */
    private function customerDetails(array $customer): array
    {
        return [
            'customer_name' => (string) $customer['customer_name'],
            'customer_email' => (string) $customer['customer_email'],
            'customer_phone' => (string) $customer['customer_phone'],
            'customer_postal_code' => (string) $customer['postal_code'],
            'customer_address_number' => (string) $customer['address_number'],
            'customer_address_complement' => $customer['address_complement'] ?? null,
        ];
    }

    private function amountMatches(string $expected, string $received): bool
    {
        return number_format((float) $expected, 2, '.', '') === number_format((float) $received, 2, '.', '');
    }
}
