<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_upload_up_to_four_photos_for_a_product(): void
    {
        Storage::fake('public');
        $admin = User::factory()->administrator()->create();
        $photos = [
            UploadedFile::fake()->image('frente.jpg'),
            UploadedFile::fake()->image('lado.jpg'),
            UploadedFile::fake()->image('detalhe.jpg'),
            UploadedFile::fake()->image('verso.jpg'),
        ];

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Livro de teste',
            'pix_price' => '49.90',
            'status' => 'available',
            'cover_images' => $photos,
        ]);

        $product = Product::query()->sole();

        $response->assertRedirect(route('admin.products.index'));
        $this->assertCount(4, $product->image_paths);

        foreach ($product->image_paths as $imagePath) {
            Storage::disk('public')->assertExists($imagePath);
        }

        $this->get(route('catalog.product', $product))
            ->assertOk()
            ->assertSee('foto 4');
    }

    public function test_an_admin_cannot_upload_more_than_four_photos_for_a_product(): void
    {
        $admin = User::factory()->administrator()->create();
        $photos = array_fill(0, 5, UploadedFile::fake()->image('foto.jpg'));

        $response = $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'Livro de teste',
                'pix_price' => '49.90',
                'status' => 'available',
                'cover_images' => $photos,
            ]);

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors(['cover_images']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_an_admin_cannot_upload_a_photo_larger_than_twelve_megabytes(): void
    {
        $admin = User::factory()->administrator()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'Livro de teste',
                'pix_price' => '49.90',
                'status' => 'available',
                'cover_images' => [UploadedFile::fake()->image('muito-grande.png')->size(12289)],
            ]);

        $response->assertRedirect(route('admin.products.create'));
        $response->assertSessionHasErrors(['cover_images.0']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_a_product_with_one_existing_photo_remains_compatible(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/livro-existente.jpg', 'image');
        $product = Product::create([
            'name' => 'Livro existente',
            'slug' => 'livro-existente',
            'pix_price' => '39.90',
            'status' => 'available',
            'cover_image_path' => 'products/livro-existente.jpg',
        ]);

        $this->assertSame(['products/livro-existente.jpg'], $product->image_paths);
        $this->assertSame(
            Storage::disk('public')->url('products/livro-existente.jpg'),
            $product->primary_image_url
        );
    }
}
