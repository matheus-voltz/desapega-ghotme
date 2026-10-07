<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('seller.settings', [
            'settings' => $request->user()->sellerSetting,
            'shareUrl' => $request->user()->isSeller() ? route('seller.profile', $request->user()) : null,
            'isPreview' => $request->user()->isAdmin() && ! $request->user()->isSeller(),
            'productsCount' => $request->user()->isSeller() ? $request->user()->products()->count() : 0,
            'salesCount' => $request->user()->isSeller() ? $request->user()->sales()->where('status', 'RECEIVED')->count() : 0,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asaas_api_key' => ['nullable', 'string', 'max:500'],
            'asaas_webhook_token' => ['nullable', 'string', 'max:500'],
            'telegram_bot_token' => ['nullable', 'string', 'max:500'],
            'telegram_chat_id' => ['nullable', 'string', 'max:100'],
            'shopee_partner_id' => ['nullable', 'string', 'max:100'],
            'shopee_partner_key' => ['nullable', 'string', 'max:500'],
            'shopee_shop_id' => ['nullable', 'string', 'max:100'],
        ]);

        $data = array_filter($data, fn (?string $value): bool => $value !== null && trim($value) !== '');

        $request->user()->sellerSetting()->updateOrCreate([], $data);

        return back()->with('success', 'Configurações salvas com segurança.');
    }
}
