<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\AsaasPayment;
use App\Services\AsaasClient;
use App\Services\AsaasPaymentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsaasWebhookController extends Controller
{
    public function __invoke(Request $request, AsaasClient $client, AsaasPaymentService $payments): Response
    {
        $payload = $request->json()->all();
        if (! is_array($payload)) {
            return response('', Response::HTTP_BAD_REQUEST);
        }

        $asaasPaymentId = trim((string) data_get($payload, 'payment.id'));
        $payment = $asaasPaymentId === '' ? null : AsaasPayment::query()
            ->with('seller.sellerSetting')
            ->where('asaas_payment_id', $asaasPaymentId)
            ->first();

        if (! $client->verifiesWebhookToken($request->header('asaas-access-token'), $payment?->seller?->sellerSetting)) {
            return response('', Response::HTTP_UNAUTHORIZED);
        }

        $payments->handleWebhook($payload);

        return response('', Response::HTTP_NO_CONTENT);
    }
}
