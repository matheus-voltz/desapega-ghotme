<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\AsaasPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->sales()
            ->with(['product', 'cart.items.product'])
            ->whereIn('status', ['CONFIRMED', 'RECEIVED'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('seller.orders.index', compact('orders'));
    }

    public function update(Request $request, AsaasPayment $order): RedirectResponse
    {
        abort_unless($order->seller_id === $request->user()->id, 404);
        abort_unless($order->isConfirmed(), 422, 'O pedido ainda não tem pagamento confirmado.');

        $data = $request->validate([
            'fulfillment_status' => ['required', 'in:payment_received,separating,packing,shipped,completed'],
            'delivery_method' => ['nullable', 'in:pickup,delivery,shipping'],
            'tracking_code' => ['nullable', 'string', 'max:120'],
            'seller_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->update($data);

        return back()->with('success', 'Status do pedido atualizado.');
    }
}
