<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_redirected_away_from_the_administration_panel(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.products.index'))
            ->assertRedirect(route('cart.index'));
    }

    public function test_administrator_can_access_the_administration_panel(): void
    {
        $administrator = User::factory()->administrator()->create([
            'email' => 'admin@desapego.test',
        ]);

        $this->actingAs($administrator)
            ->get(route('admin.products.index'))
            ->assertOk();
    }

    public function test_another_admin_flagged_account_cannot_access_the_panel(): void
    {
        $administrator = User::factory()->administrator()->create([
            'email' => 'outro@example.com',
        ]);

        $this->actingAs($administrator)
            ->get(route('admin.products.index'))
            ->assertRedirect(route('cart.index'));
    }

    public function test_null_admin_flag_is_treated_as_a_regular_customer(): void
    {
        $customer = User::factory()->make(['is_admin' => null]);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertRedirect(route('cart.index'));
    }
}
