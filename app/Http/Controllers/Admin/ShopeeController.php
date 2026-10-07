<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopeeConnection;
use App\Services\ShopeeClient;
use App\Services\ShopeeOrderSyncService;
use Illuminate\Http\Request;
use Throwable;

class ShopeeController extends Controller
{
    public function index(ShopeeClient $client)
    {
        $connection = $client->connection();

        return view('admin.shopee.index', compact('connection'));
    }

    public function connect(ShopeeClient $client)
    {
        try {
            return redirect()->away($client->authorizationUrl());
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.shopee.index')
                ->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request, ShopeeClient $client)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'shop_id' => ['required'],
        ]);

        try {
            $client->exchangeCode($data['code'], (string) $data['shop_id']);

            $webhookUrl = (string) config('shopee.webhook_url');
            if ($webhookUrl !== '') {
                $client->setOrderStatusPush($webhookUrl);
            }

            return redirect()->route('admin.shopee.index')
                ->with('success', 'Loja Shopee conectada e webhook configurado.');
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.shopee.index')
                ->with('error', $e->getMessage());
        }
    }

    public function configurePush(ShopeeClient $client)
    {
        try {
            $url = (string) config('shopee.webhook_url');
            if ($url === '') {
                throw new \RuntimeException('Configure SHOPEE_WEBHOOK_URL antes de ativar o push.');
            }

            $client->setOrderStatusPush($url);

            return back()->with('success', 'Push de status dos pedidos ativado.');
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }
    }

    public function sync(ShopeeOrderSyncService $sync)
    {
        try {
            $result = $sync->syncRecentOrders(60);

            return back()->with(
                'success',
                "Sincronização concluída: {$result['orders']} pedido(s), {$result['products']} produto(s) atualizado(s)."
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }
    }

    public function disconnect()
    {
        ShopeeConnection::query()->delete();

        return back()->with('success', 'Conexão local com a Shopee removida.');
    }
}
