<?php

namespace App\Services;

use App\Models\AsaasPayment;
use App\Models\Product;
use Illuminate\Support\Collection;

class SaleNotifier
{
    public function __construct(private readonly TelegramNotifier $telegram) {}

    public function productSold(Product $product, float $price, string $channel): void
    {
        $message = implode("\n", [
            '✅ <b>Venda confirmada</b>',
            '',
            '<b>Produto:</b> '.e($product->name),
            '<b>Valor:</b> R$ '.number_format($price, 2, ',', '.'),
            '<b>Canal:</b> '.e($channel),
            '<b>Horário:</b> '.now()->format('d/m/Y H:i:s'),
            '',
            '<a href="'.e(route('catalog.product', $product)).'">Abrir produto</a>',
        ]);

        $this->telegram->send($message, $product->seller?->sellerSetting);
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    public function paymentReceived(AsaasPayment $payment, Collection $products): void
    {
        $address = trim(implode(', ', array_filter([
            $payment->customer_postal_code ? 'CEP '.$payment->customer_postal_code : null,
            $payment->customer_address_number ? 'nº '.$payment->customer_address_number : null,
            $payment->customer_address_complement,
        ])));

        $items = $products->map(fn (Product $product): string => '• '.e($product->name))->implode("\n");
        $message = implode("\n", [
            '✅ <b>Venda confirmada</b>',
            '',
            '<b>Cliente:</b> '.e((string) $payment->customer_name),
            '<b>Telefone:</b> '.e((string) $payment->customer_phone),
            '<b>Endereço:</b> '.e($address !== '' ? $address : 'Não informado'),
            '<b>E-mail:</b> '.e((string) $payment->customer_email),
            '<b>Valor:</b> R$ '.number_format((float) $payment->amount, 2, ',', '.'),
            '',
            '<b>Itens comprados:</b>',
            $items !== '' ? $items : '• Item não identificado',
            '',
            '<b>Horário:</b> '.now()->format('d/m/Y H:i:s'),
        ]);

        $this->telegram->send($message, $payment->seller?->sellerSetting);
    }
}
