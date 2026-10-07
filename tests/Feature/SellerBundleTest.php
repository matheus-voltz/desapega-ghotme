<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerBundleTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_a_combo_using_only_their_items(): void
    {
        $seller = User::factory()->create(['account_type' => 'seller']);
        $firstProduct = Product::factory()->for($seller, 'seller')->create(['name' => 'Livro um']);
        $secondProduct = Product::factory()->for($seller, 'seller')->create(['name' => 'Livro dois']);

        $this->actingAs($seller)
            ->post(route('seller.bundles.store'), [
                'name' => 'Combo de leitura',
                'pix_price' => 50,
                'products' => [$firstProduct->id, $secondProduct->id],
                'active' => true,
            ])
            ->assertRedirect(route('seller.bundles.index'));

        $this->assertDatabaseHas('bundles', [
            'name' => 'Combo de leitura',
            'seller_id' => $seller->id,
        ]);
        $this->assertDatabaseCount('bundle_product', 2);
    }

    public function test_seller_cannot_add_another_sellers_item_to_a_combo(): void
    {
        $seller = User::factory()->create(['account_type' => 'seller']);
        $ownProduct = Product::factory()->for($seller, 'seller')->create();
        $otherProduct = Product::factory()->for(User::factory()->state(['account_type' => 'seller']), 'seller')->create();

        $this->actingAs($seller)
            ->post(route('seller.bundles.store'), [
                'name' => 'Combo inválido',
                'pix_price' => 50,
                'products' => [$ownProduct->id, $otherProduct->id],
            ])
            ->assertSessionHasErrors('products');

        $this->assertDatabaseCount('bundles', 0);
    }

    public function test_bundle_page_shows_component_items_as_individual_cards(): void
    {
        $seller = User::factory()->create(['account_type' => 'seller']);
        $firstProduct = Product::factory()->for($seller, 'seller')->create(['name' => 'Volume um']);
        $secondProduct = Product::factory()->for($seller, 'seller')->create(['name' => 'Volume dois']);
        $bundle = Bundle::create([
            'seller_id' => $seller->id,
            'name' => 'Combo de volumes',
            'slug' => 'combo-de-volumes',
            'pix_price' => 60,
            'active' => true,
        ]);
        $bundle->products()->attach([$firstProduct->id, $secondProduct->id]);

        $this->get(route('catalog.bundle', $bundle))
            ->assertOk()
            ->assertSee('Também à venda separadamente')
            ->assertSee('Volume um')
            ->assertSee('Volume dois');
    }
}
