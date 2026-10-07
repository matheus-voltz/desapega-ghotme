<?php

namespace App\Http\Controllers;

use App\CreditCardInstallmentCalculator;
use App\Http\Requests\CreateAsaasCreditCardPaymentRequest;
use App\Http\Requests\CreateAsaasPixPaymentRequest;
use App\Models\AsaasPayment;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\AsaasPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $this->cartFor($request->user());
        $cart->load('items.product.seller.sellerSetting');

        $total = $cart->items->sum(fn (CartItem $item): float => (float) $item->product->pix_price);
        $canCheckout = $cart->items->isNotEmpty()
            && $cart->items->every(fn (CartItem $item): bool => $item->product->status === 'available');
        $canPayByCard = $canCheckout
            && $cart->items->every(fn (CartItem $item): bool => $item->product->marketplace_price !== null
                && ($item->product->seller_id === null || (bool) $item->product->seller?->sellerSetting?->asaas_api_key));

        return view('cart.index', compact('cart', 'total', 'canCheckout', 'canPayByCard'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'available', 409, 'Este produto não está mais disponível.');

        $cart = $this->cartFor($request->user());
        $this->ensureCartCanBeChanged($cart);

        $cart->load('items.product.seller.sellerSetting');
        $cartSellerIds = $cart->items
            ->pluck('product.seller_id')
            ->unique()
            ->values();

        abort_if(
            $cartSellerIds->isNotEmpty() && ! $cartSellerIds->contains($product->seller_id),
            422,
            'Finalize os itens deste vendedor antes de adicionar itens de outro catálogo.'
        );

        if ($product->seller_id !== null) {
            $request->user()->savedCatalogs()->firstOrCreate([
                'seller_id' => $product->seller_id,
            ]);
        }

        $cartItem = $cart->items()->firstOrCreate(['product_id' => $product->id]);

        return to_route('cart.index')->with(
            'success',
            $cartItem->wasRecentlyCreated ? 'Item adicionado à sua sacola.' : 'Este item já está na sua sacola.'
        );
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $cart = $this->cartFor($request->user());
        abort_unless($cartItem->cart_id === $cart->id, 404);

        $this->ensureCartCanBeChanged($cart);
        $cartItem->delete();

        return to_route('cart.index')->with('success', 'Item removido da sua sacola.');
    }

    public function pix(Request $request): View
    {
        $cart = $this->cartFor($request->user());
        $cart->load('items.product');
        $payment = $this->paymentForCart($request, $cart);

        abort_if($cart->items->isEmpty(), 422, 'Adicione pelo menos um item à sua sacola.');
        abort_if(
            ! $payment && $cart->items->contains(fn (CartItem $item): bool => $item->product->status !== 'available'),
            409,
            'Um ou mais itens da sua sacola não estão mais disponíveis.'
        );

        $price = $cart->items->sum(fn (CartItem $item): float => (float) $item->product->pix_price);

        return view('catalog.pix', [
            'title' => 'Sua sacola',
            'price' => $price,
            'backUrl' => route('cart.index'),
            'products' => $cart->items->pluck('product'),
            'payment' => $payment,
            'manualPixKey' => $this->manualPixKey($cart),
            'createPaymentUrl' => route('cart.pix.create'),
            'isCartPayment' => true,
        ]);
    }

    public function createPix(
        CreateAsaasPixPaymentRequest $request,
        AsaasPaymentService $payments,
    ): RedirectResponse {
        $cart = $this->cartFor($request->user());

        abort_if($this->manualPixKey($cart) !== null, 422, 'Este vendedor recebe Pix diretamente pela chave informada.');

        try {
            $payment = $payments->createForCart($cart, $request->validated());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('cart.pix', ['payment' => $payment->external_reference]);
    }

    public function card(Request $request, CreditCardInstallmentCalculator $installments): View
    {
        $cart = $this->cartFor($request->user());
        $cart->load('items.product.seller.sellerSetting');
        $payment = $this->paymentForCart($request, $cart);

        abort_if($cart->items->isEmpty(), 422, 'Adicione pelo menos um item à sua sacola.');
        abort_if(
            ! $payment && $cart->items->contains(fn (CartItem $item): bool => $item->product->status !== 'available'),
            409,
            'Um ou mais itens da sua sacola não estão mais disponíveis.'
        );
        abort_if(
            ! $payment && $cart->items->contains(fn (CartItem $item): bool => $item->product->marketplace_price === null),
            422,
            'Um ou mais itens da sua sacola não têm preço total para pagamento no cartão.'
        );
        abort_if(
            ! $payment && $cart->items->contains(fn (CartItem $item): bool => $item->product->seller_id !== null
                && ! $item->product->seller?->sellerSetting?->asaas_api_key),
            422,
            'Este vendedor ainda não aceita cartão.'
        );

        $price = $cart->items->sum(fn (CartItem $item): float => (float) $item->product->marketplace_price);

        return view('catalog.card', [
            'title' => 'Sua sacola · '.$cart->items->count().' item(ns)',
            'price' => $price,
            'backUrl' => route('cart.index'),
            'products' => $cart->items->pluck('product'),
            'payment' => $payment,
            'createPaymentUrl' => route('cart.card.create'),
            'installmentOptions' => $installments->options($price),
        ]);
    }

    public function createCard(
        CreateAsaasCreditCardPaymentRequest $request,
        AsaasPaymentService $payments,
    ): RedirectResponse {
        $cart = $this->cartFor($request->user());

        try {
            $payment = $payments->createCreditCardForCart($cart, $request->validated(), (string) $request->ip());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('cart.card', ['payment' => $payment->external_reference]);
    }

    private function cartFor(User $user): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $user->id]);
    }

    private function paymentForCart(Request $request, Cart $cart): ?AsaasPayment
    {
        $reference = trim((string) $request->query('payment'));

        if ($reference === '') {
            return null;
        }

        return $cart->asaasPayments()
            ->where('external_reference', $reference)
            ->firstOrFail();
    }

    private function ensureCartCanBeChanged(Cart $cart): void
    {
        $hasPaymentInProgress = $cart->asaasPayments()
            ->where(function ($query): void {
                $query->whereIn('status', ['CREATING', 'CONFIRMED'])
                    ->orWhere(function ($pendingQuery): void {
                        $pendingQuery->where('status', 'PENDING')
                            ->where('pix_expires_at', '>', now());
                    });
            })
            ->exists();

        abort_if($hasPaymentInProgress, 409, 'A sua sacola não pode ser alterada enquanto este pagamento estiver em andamento.');
    }

    private function manualPixKey(Cart $cart): ?string
    {
        $cart->loadMissing('items.product.seller.sellerSetting');
        $product = $cart->items->first()?->product;

        if (! $product || $product->seller_id === null || $product->seller?->sellerSetting?->asaas_api_key) {
            return null;
        }

        $pixKey = trim((string) $product->seller?->sellerSetting?->pix_key);

        return $pixKey === '' ? null : $pixKey;
    }
}
