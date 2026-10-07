<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'https://desapega.ghotme.com.br/auth/google/callback',
        ]);
    }

    public function test_google_redirect_starts_an_authorization_request_with_a_state_value(): void
    {
        $response = $this->get(route('google.redirect'));

        $response->assertRedirectContains('accounts.google.com/o/oauth2/v2/auth')
            ->assertSessionHas('google_oauth_state');
    }

    public function test_google_callback_logs_in_a_user_already_registered_with_their_google_email(): void
    {
        $user = User::factory()->create(['email' => 'matheus@example.com']);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token']),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-123',
                'email' => 'matheus@example.com',
                'email_verified' => true,
                'name' => 'Matheus',
            ]),
        ]);

        $this->withSession(['google_oauth_state' => 'safe-state'])
            ->get(route('google.callback', ['state' => 'safe-state', 'code' => 'authorization-code']))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertDatabaseHas('users', ['id' => $user->id, 'google_id' => 'google-123']);
    }

    public function test_new_google_user_must_choose_an_account_type_and_accept_legal_terms(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token']),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-456',
                'email' => 'novo@example.com',
                'email_verified' => true,
                'name' => 'Novo usuário',
            ]),
        ]);

        $this->withSession(['google_oauth_state' => 'safe-state'])
            ->get(route('google.callback', ['state' => 'safe-state', 'code' => 'authorization-code']))
            ->assertRedirect(route('google.complete.create'));

        $this->post(route('google.complete.store'), [
            'account_type' => 'seller',
            'accept_legal' => true,
        ])->assertRedirect(route('seller.settings.edit'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'novo@example.com',
            'google_id' => 'google-456',
            'account_type' => 'seller',
        ]);
    }

    public function test_google_callback_rejects_an_invalid_state_without_calling_google(): void
    {
        Http::preventStrayRequests();

        $this->withSession(['google_oauth_state' => 'safe-state'])
            ->get(route('google.callback', ['state' => 'wrong-state', 'code' => 'authorization-code']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }
}
