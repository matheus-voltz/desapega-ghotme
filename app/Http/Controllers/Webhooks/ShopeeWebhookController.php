<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessShopeeOrderUpdate;
use App\Models\ShopeeConnection;
use App\Services\ShopeeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ShopeeWebhookController extends Controller
{
    public function __invoke(Request $request, ShopeeClient $client): Response
    {
        $rawBody = $request->getContent();

        if (! $client->verifyPushSignature($rawBody, $request->header('Authorization'))) {
            Log::warning('Shopee webhook com assinatura inválida.');

            return response('', 401);
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            return response('', 400);
        }

        $code = (int) ($payload['code'] ?? 0);
        $shopId = isset($payload['shop_id']) ? (string) $payload['shop_id'] : null;

        if ($shopId && ! ShopeeConnection::where('shop_id', $shopId)->exists()) {
            Log::warning('Shopee webhook de loja não conectada.', ['shop_id' => $shopId]);

            return response('', 204);
        }

        // Push code 3 = mudança de status do pedido.
        if ($code === 3) {
            $data = (array) ($payload['data'] ?? []);
            $orderSn = (string) ($data['ordersn'] ?? $data['order_sn'] ?? '');
            $status = isset($data['status']) ? (string) $data['status'] : null;

            if ($orderSn !== '') {
                // A Shopee espera resposta rápida. O processamento ocorre depois da resposta HTTP.
                ProcessShopeeOrderUpdate::dispatchAfterResponse($orderSn, $status);
            }
        }

        return response('', 204);
    }
}
