<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_catalog_pagination_links_return_to_the_items_section(): void
    {
        $seller = User::factory()->create(['account_type' => 'seller']);
        Product::factory()->count(13)->for($seller, 'seller')->create();

        $this->get(route('seller.profile', $seller))
            ->assertOk()
            ->assertSee('page=2#itens')
            ->assertSee('Itens à venda')
            ->assertDontSee('Combos especiais');
    }
}
