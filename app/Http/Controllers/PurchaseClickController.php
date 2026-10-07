<?php

namespace App\Http\Controllers;

use App\CreditCardInstallmentCalculator;
use App\Http\Requests\CreateAsaasCreditCardPaymentRequest;
use App\Http\Requests\CreateAsaasPixPaymentRequest;
use App\Models\AsaasPayment;
use App\Models\Bundle;
use App\Models\Product;
use App\Services\AsaasPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use RuntimeException;

class PurchaseClickController extends Controller
{
    public function __construct(private readonly CreditCardInstallmentCalculator $installments) {}

    /**
     * Exibe o QR Code Pix. Esta ação não dispara Telegram: abrir a tela de
     * pagamento não significa que a venda foi concluída.
     */
    public function productPix(Request $request, Product $product): View
    {
        $product->loadMissing('seller.sellerSetting');
        $payment = $this->paymentForProduct($request, $product);
        abort_unless($payment || $product->status === 'available', 409, 'Este produto não está mais disponível.');

        $manualPixKey = $this->manualPixKey($product);

        return view('catalog.pix', [
            'title' => $product->name,
            'price' => (float) $product->pix_price,
            'backUrl' => route('catalog.product', $product),
            'products' => collect([$product]),
            'payment' => $payment,
            'manualPixKey' => $manualPixKey,
            'createPaymentUrl' => route('purchase.product.pix.create', $product),
        ]);
    }

    public function createProductPix(
        CreateAsaasPixPaymentRequest $request,
        Product $product,
        AsaasPaymentService $payments,
    ): RedirectResponse {
        abort_if($this->manualPixKey($product) !== null, 422, 'Este vendedor recebe Pix diretamente pela chave informada.');
        try {
            $payment = $payments->createForProduct($product, $request->validated());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('purchase.product.pix', [
            'product' => $product,
            'payment' => $payment->external_reference,
        ]);
    }

    public function productShopee(Product $product): RedirectResponse
    {
        abort_unless($product->status === 'available', 409, 'Este produto não está mais disponível.');
        abort_if(! $product->marketplace_url, 404, 'Link da Shopee não configurado.');

        return redirect()->away($product->marketplace_url);
    }

    public function productCard(Request $request, Product $product): View
    {
        abort_if($product->marketplace_price === null, 404, 'O preço para cartão ainda não foi configurado neste item.');
        $product->loadMissing('seller.sellerSetting');
        abort_if($product->seller_id !== null && ! $product->seller?->sellerSetting?->asaas_api_key, 404, 'Este vendedor ainda não aceita cartão.');

        $payment = $this->paymentForProduct($request, $product);
        abort_unless($payment || $product->status === 'available', 409, 'Este produto não está mais disponível.');

        return $this->cardView(
            $product->name,
            (float) $product->marketplace_price,
            route('catalog.product', $product),
            collect([$product]),
            $payment,
            route('purchase.product.card.create', $product),
        );
    }

    public function createProductCard(
        CreateAsaasCreditCardPaymentRequest $request,
        Product $product,
        AsaasPaymentService $payments,
    ): RedirectResponse {
        abort_if($product->marketplace_price === null, 404, 'O preço para cartão ainda não foi configurado neste item.');
        $product->loadMissing('seller.sellerSetting');
        abort_if($product->seller_id !== null && ! $product->seller?->sellerSetting?->asaas_api_key, 404, 'Este vendedor ainda não aceita cartão.');

        try {
            $payment = $payments->createCreditCardForProduct($product, $request->validated(), (string) $request->ip());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('purchase.product.card', [
            'product' => $product,
            'payment' => $payment->external_reference,
        ]);
    }

    public function bundlePix(Request $request, Bundle $bundle): View
    {
        $bundle->loadMissing('seller.sellerSetting');
        $payment = $this->paymentForBundle($request, $bundle);
        abort_unless($payment || $bundle->active, 404);

        $bundle->load('products');
        abort_if(
            ! $payment && $bundle->products->contains(fn (Product $product) => $product->status !== 'available'),
            409,
            'Um ou mais itens deste combo não estão mais disponíveis.'
        );

        return view('catalog.pix', [
            'title' => $bundle->name,
            'price' => (float) $bundle->pix_price,
            'backUrl' => route('catalog.bundle', $bundle),
            'products' => $bundle->products,
            'payment' => $payment,
            'manualPixKey' => $this->manualPixKey($bundle),
            'createPaymentUrl' => route('purchase.bundle.pix.create', $bundle),
        ]);
    }

    public function createBundlePix(
        CreateAsaasPixPaymentRequest $request,
        Bundle $bundle,
        AsaasPaymentService $payments,
    ): RedirectResponse {
        abort_if($this->manualPixKey($bundle) !== null, 422, 'Este vendedor recebe Pix diretamente pela chave informada.');

        try {
            $payment = $payments->createForBundle($bundle, $request->validated());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('purchase.bundle.pix', [
            'bundle' => $bundle,
            'payment' => $payment->external_reference,
        ]);
    }

    public function paymentStatus(AsaasPayment $asaasPayment): JsonResponse
    {
        return response()->json([
            'status' => $asaasPayment->status,
            'paid' => $asaasPayment->isPaid(),
        ]);
    }

    private function paymentForProduct(Request $request, Product $product): ?AsaasPayment
    {
        $reference = trim((string) $request->query('payment'));

        if ($reference === '') {
            return null;
        }

        return $product->asaasPayments()
            ->where('external_reference', $reference)
            ->firstOrFail();
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function cardView(
        string $title,
        float $price,
        string $backUrl,
        Collection $products,
        ?AsaasPayment $payment,
        string $createPaymentUrl,
    ): View {
        $installmentOptions = $this->installments->options($price);

        return view('catalog.card', compact('title', 'price', 'backUrl', 'products', 'payment', 'createPaymentUrl', 'installmentOptions'));
    }

    private function paymentForBundle(Request $request, Bundle $bundle): ?AsaasPayment
    {
        $reference = trim((string) $request->query('payment'));

        if ($reference === '') {
            return null;
        }

        return $bundle->asaasPayments()
            ->where('external_reference', $reference)
            ->firstOrFail();
    }

    private function manualPixKey(Product|Bundle $product): ?string
    {
        if ($product->seller_id === null || $product->seller?->sellerSetting?->asaas_api_key) {
            return null;
        }

        $pixKey = trim((string) $product->seller?->sellerSetting?->pix_key);

        return $pixKey === '' ? null : $pixKey;
    }
}
