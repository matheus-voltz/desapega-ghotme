<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'account_type' => 'buyer',
            'accept_legal' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_sellers_are_sent_to_their_settings_after_registration(): void
    {
        $response = $this->post('/register', [
            'name' => 'Seller User',
            'email' => 'seller@example.com',
            'account_type' => 'seller',
            'accept_legal' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('seller.settings.edit', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'seller@example.com', 'account_type' => 'seller']);
    }

    public function test_registration_requires_acceptance_of_the_legal_documents(): void
    {
        $this->from(route('register'))
            ->post(route('register'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'account_type' => 'buyer',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('accept_legal');

        $this->assertGuest();
    }
}
