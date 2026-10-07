<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\AsaasClient;
use App\Services\AsaasPaymentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsaasWebhookController extends Controller
{
    public function __invoke(Request $request, AsaasClient $client, AsaasPaymentService $payments): Response
    {
        if (! $client->verifiesWebhookToken($request->header('asaas-access-token'))) {
            return response('', Response::HTTP_UNAUTHORIZED);
        }

        $payload = $request->json()->all();
        if (! is_array($payload)) {
            return response('', Response::HTTP_BAD_REQUEST);
        }

        $payments->handleWebhook($payload);

        return response('', Response::HTTP_NO_CONTENT);
    }
}
