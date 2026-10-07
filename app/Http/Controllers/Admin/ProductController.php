<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\SaleNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(private readonly SaleNotifier $saleNotifier) {}

    public function index()
    {
        $products = Product::orderBy('sort_order')->orderByDesc('id')->paginate(30);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.create', ['sellers' => User::where('account_type', 'seller')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $images = $data['cover_images'] ?? [];
        unset($data['cover_images']);
        $data['slug'] = $this->uniqueSlug($data['name']);

        if (($data['status'] ?? 'available') !== 'available') {
            $data['sale_channel'] = 'manual';
        }

        if ($images !== []) {
            $data['cover_image_path'] = $this->storeImages($images);
        }

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Produto criado.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', ['product' => $product, 'sellers' => User::where('account_type', 'seller')->orderBy('name')->get()]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request);
        $images = $data['cover_images'] ?? [];
        unset($data['cover_images']);
        $previousStatus = $product->status;

        if ($product->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        // Se você altera o status pelo painel, essa decisão passa a prevalecer sobre eventos antigos da Shopee.
        if ($product->status !== $data['status']) {
            $data['sale_channel'] = $data['status'] === 'available' ? null : 'manual';
            $data['shopee_order_sn'] = null;
            $data['shopee_order_status'] = null;
        }

        if ($images !== []) {
            $this->deleteImages($product);
            $data['cover_image_path'] = $this->storeImages($images);
        }

        $product->update($data);

        if ($previousStatus !== 'sold' && $product->status === 'sold') {
            $this->saleNotifier->productSold(
                $product,
                (float) $product->pix_price,
                'Pix / venda manual'
            );
        }

        return redirect()->route('admin.products.index')->with('success', 'Produto atualizado.');
    }

    public function destroy(Product $product)
    {
        $this->deleteImages($product);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produto excluído.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'seller_id' => ['nullable', 'integer', 'exists:users,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'pix_price' => ['required', 'numeric', 'min:0'],
            'marketplace_price' => ['nullable', 'numeric', 'min:0'],
            'marketplace_url' => ['nullable', 'url', 'max:1000'],
            'shopee_item_id' => ['nullable', 'regex:/^\d+$/', 'max:32'],
            'shopee_model_id' => ['nullable', 'regex:/^\d+$/', 'max:32'],
            'cover_images' => ['nullable', 'array', 'max:4'],
            'cover_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
            'status' => ['required', 'in:available,reserved,sold'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'cover_images.max' => 'Você pode enviar no máximo 4 fotos por item.',
            'cover_images.*.image' => 'Cada foto deve ser uma imagem válida em JPG, PNG ou WebP.',
            'cover_images.*.mimes' => 'As fotos devem estar em JPG, PNG ou WebP.',
            'cover_images.*.max' => 'Cada foto pode ter no máximo 12 MB.',
        ]);
    }

    /**
     * @param  list<UploadedFile>  $images
     */
    private function storeImages(array $images): string
    {
        $paths = array_map(
            fn (UploadedFile $image): string => $image->store('products', 'public'),
            $images
        );

        return json_encode($paths, JSON_THROW_ON_ERROR);
    }

    private function deleteImages(Product $product): void
    {
        Storage::disk('public')->delete($product->image_paths);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $i = 2;

        while (Product::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
