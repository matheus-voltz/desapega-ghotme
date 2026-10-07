<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_shows_twelve_items_then_offers_to_load_more(): void
    {
        $this->createProducts(13);

        $response = $this->get(route('catalog.index'));

        $response->assertSee('13 item(ns)')
            ->assertSee('Produto de teste 13')
            ->assertDontSee('Produto de teste 01')
            ->assertSee('Carregar mais itens');
    }

    public function test_catalog_returns_the_next_page_as_product_cards_for_load_more(): void
    {
        $this->createProducts(13);

        $response = $this->get(route('catalog.index', ['page' => 2, 'load_more' => 1]));

        $response->assertOk()
            ->assertJsonPath('next_page_url', null)
            ->assertJsonStructure(['html', 'next_page_url']);

        $this->assertStringContainsString('Produto de teste 01', (string) $response->json('html'));
    }

    public function test_catalog_keeps_the_category_filter_when_loading_more(): void
    {
        $this->createProducts(13, 'Livros');

        $response = $this->get(route('catalog.index', ['category' => 'Livros']));
        $nextPageUrl = (string) $response->viewData('products')->nextPageUrl();
        parse_str((string) parse_url($nextPageUrl, PHP_URL_QUERY), $query);

        $this->assertSame('Livros', $query['category']);
        $this->assertSame('2', $query['page']);
    }

    public function test_catalog_filters_available_items_by_search_and_maximum_pix_price(): void
    {
        Product::factory()->create(['name' => 'Livro de fantasia', 'description' => 'Aventura', 'pix_price' => 40]);
        Product::factory()->create(['name' => 'Livro raro', 'description' => 'Colecionável', 'pix_price' => 180]);
        Product::factory()->create(['name' => 'Console portátil', 'description' => 'Jogo eletrônico', 'pix_price' => 40]);
        Product::factory()->create(['name' => 'Livro indisponível', 'description' => 'Fora de estoque', 'pix_price' => 20, 'status' => 'sold']);

        $this->get(route('catalog.index', ['search' => 'Livro', 'max_price' => 100]))
            ->assertOk()
            ->assertSee('Livro de fantasia')
            ->assertDontSee('Livro raro')
            ->assertDontSee('Console portátil')
            ->assertDontSee('Livro indisponível');
    }

    public function test_catalog_hides_a_combo_when_one_of_its_products_is_not_available(): void
    {
        $availableProduct = Product::factory()->create(['status' => 'available']);
        $soldProduct = Product::factory()->create(['status' => 'sold']);
        $availableBundle = Bundle::create(['name' => 'Combo disponível', 'slug' => 'combo-disponivel', 'pix_price' => 100, 'active' => true]);
        $unavailableBundle = Bundle::create(['name' => 'Combo indisponível', 'slug' => 'combo-indisponivel', 'pix_price' => 100, 'active' => true]);
        $availableBundle->products()->attach($availableProduct);
        $unavailableBundle->products()->attach([$availableProduct->id, $soldProduct->id]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Combo disponível')
            ->assertDontSee('Combo indisponível');
    }

    private function createProducts(int $count, string $category = 'Eletrônicos'): void
    {
        foreach (range(1, $count) as $number) {
            $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

            Product::create([
                'name' => "Produto de teste {$suffix}",
                'slug' => "produto-de-teste-{$suffix}",
                'category' => $category,
                'pix_price' => 100,
                'status' => 'available',
            ]);
        }
    }
}
