<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = $request->user()->products()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20);

        return view('seller.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('seller.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $images = $data['cover_images'] ?? [];
        unset($data['cover_images']);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['seller_id'] = $request->user()->id;
        $data['status'] = 'available';

        if ($images !== []) {
            $data['cover_image_path'] = $this->storeImages($images);
        }

        Product::create($data);

        return to_route('seller.products.index')->with('success', 'Item publicado no seu catálogo.');
    }

    public function edit(Request $request, Product $product): View
    {
        $this->ensureOwner($request, $product);

        return view('seller.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->ensureOwner($request, $product);

        $data = $this->validated($request);
        $images = $data['cover_images'] ?? [];
        unset($data['cover_images']);

        if ($product->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        if ($images !== []) {
            $this->deleteImages($product);
            $data['cover_image_path'] = $this->storeImages($images);
        }

        $product->update($data);

        return to_route('seller.products.index')->with('success', 'Item atualizado.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->ensureOwner($request, $product);
        $this->deleteImages($product);
        $product->delete();

        return to_route('seller.products.index')->with('success', 'Item removido do seu catálogo.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'pix_price' => ['required', 'numeric', 'min:0'],
            'marketplace_price' => ['nullable', 'numeric', 'min:0'],
            'cover_images' => ['nullable', 'array', 'max:4'],
            'cover_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'cover_images.max' => 'Você pode enviar no máximo 4 fotos por item.',
            'cover_images.*.image' => 'Cada foto deve ser uma imagem válida em JPG, PNG ou WebP.',
            'cover_images.*.mimes' => 'As fotos devem estar em JPG, PNG ou WebP.',
            'cover_images.*.max' => 'Cada foto pode ter no máximo 12 MB.',
        ]);
    }

    /** @param list<UploadedFile> $images */
    private function storeImages(array $images): string
    {
        return json_encode(array_map(
            fn (UploadedFile $image): string => $image->store('products', 'public'),
            $images,
        ), JSON_THROW_ON_ERROR);
    }

    private function deleteImages(Product $product): void
    {
        Storage::disk('public')->delete($product->image_paths);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $number = 2;

        while (Product::query()->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$number++;
        }

        return $slug;
    }

    private function ensureOwner(Request $request, Product $product): void
    {
        abort_unless($product->seller_id === $request->user()->id, 404);
    }
}
