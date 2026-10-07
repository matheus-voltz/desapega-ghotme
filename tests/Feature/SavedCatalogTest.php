<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_must_sign_in_before_starting_a_purchase(): void
    {
        $seller = User::factory()->create(['account_type' => 'seller']);
        $product = Product::factory()->for($seller, 'seller')->create();

        $this->get(route('purchase.product.pix', $product))
            ->assertRedirect(route('login'));
    }

    public function test_adding_an_item_saves_its_seller_catalog_for_the_buyer(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['account_type' => 'seller']);
        $product = Product::factory()->for($seller, 'seller')->create();

        $this->actingAs($buyer)
            ->post(route('cart.items.store', $product))
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('saved_catalogs', [
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($buyer)
            ->get(route('saved-catalogs.index'))
            ->assertOk()
            ->assertSee($seller->name)
            ->assertSee('Abrir catálogo');
    }

    public function test_buyer_can_only_remove_their_own_saved_catalog(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $seller = User::factory()->create(['account_type' => 'seller']);
        $savedCatalog = $otherBuyer->savedCatalogs()->create([
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($buyer)
            ->delete(route('saved-catalogs.destroy', $savedCatalog))
            ->assertNotFound();

        $this->assertDatabaseHas('saved_catalogs', ['id' => $savedCatalog->id]);
    }
}
