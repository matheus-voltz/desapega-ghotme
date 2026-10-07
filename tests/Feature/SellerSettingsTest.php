<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_sellers_can_access_settings_and_values_are_encrypted(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['account_type' => 'seller']);

        $this->actingAs($buyer)->get(route('seller.settings.edit'))->assertRedirect(route('cart.index'));

        $this->actingAs($seller)->put(route('seller.settings.update'), [
            'asaas_api_key' => 'asaas-secret',
            'telegram_bot_token' => 'telegram-secret',
            'telegram_chat_id' => '8086091054',
        ])->assertRedirect();

        $this->assertDatabaseMissing('seller_settings', ['asaas_api_key' => 'asaas-secret']);
        $this->assertSame('asaas-secret', $seller->fresh()->sellerSetting->asaas_api_key);
    }

    public function test_administrator_can_preview_seller_settings(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get(route('seller.settings.edit'))
            ->assertOk()
            ->assertSee('Prévia administrativa');
    }

    public function test_blank_secret_fields_keep_the_previously_saved_values(): void
    {
        $seller = User::factory()->create(['account_type' => 'seller']);
        $seller->sellerSetting()->create([
            'asaas_api_key' => 'asaas-secret',
            'telegram_bot_token' => 'telegram-secret',
        ]);

        $this->actingAs($seller)
            ->put(route('seller.settings.update'), [])
            ->assertRedirect();

        $settings = $seller->fresh()->sellerSetting;
        $this->assertSame('asaas-secret', $settings->asaas_api_key);
        $this->assertSame('telegram-secret', $settings->telegram_bot_token);
    }
}
