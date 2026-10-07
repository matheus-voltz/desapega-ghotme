<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BundleController extends Controller
{
    public function index(Request $request): View
    {
        $bundles = $request->user()->bundles()
            ->with('products')
            ->latest()
            ->paginate(20);

        return view('seller.bundles.index', compact('bundles'));
    }

    public function create(Request $request): View
    {
        $products = $request->user()->products()->orderBy('name')->get();

        return view('seller.bundles.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $productIds = $data['products'];
        unset($data['products']);

        $this->productsOwnedBySeller($request, $productIds);
        $data['seller_id'] = $request->user()->id;
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['active'] = $request->boolean('active');

        if ($request->hasFile('cover_image')) {
            $data['cover_image_path'] = $request->file('cover_image')->store('bundles', 'public');
        }

        $bundle = Bundle::create($data);
        $bundle->products()->sync($productIds);

        return to_route('seller.bundles.index')->with('success', 'Combo criado.');
    }

    public function edit(Request $request, Bundle $bundle): View
    {
        $this->ensureOwner($request, $bundle);
        $products = $request->user()->products()->orderBy('name')->get();
        $bundle->load('products');

        return view('seller.bundles.edit', compact('bundle', 'products'));
    }

    public function update(Request $request, Bundle $bundle): RedirectResponse
    {
        $this->ensureOwner($request, $bundle);
        $data = $this->validated($request);
        $productIds = $data['products'];
        unset($data['products']);

        $this->productsOwnedBySeller($request, $productIds);
        $data['active'] = $request->boolean('active');

        if ($bundle->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $bundle->id);
        }

        if ($request->hasFile('cover_image')) {
            if ($bundle->cover_image_path) {
                Storage::disk('public')->delete($bundle->cover_image_path);
            }
            $data['cover_image_path'] = $request->file('cover_image')->store('bundles', 'public');
        }

        $bundle->update($data);
        $bundle->products()->sync($productIds);

        return to_route('seller.bundles.index')->with('success', 'Combo atualizado.');
    }

    public function destroy(Request $request, Bundle $bundle): RedirectResponse
    {
        $this->ensureOwner($request, $bundle);

        if ($bundle->cover_image_path) {
            Storage::disk('public')->delete($bundle->cover_image_path);
        }

        $bundle->delete();

        return to_route('seller.bundles.index')->with('success', 'Combo excluído.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'pix_price' => ['required', 'numeric', 'min:0'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'products' => ['required', 'array', 'min:2'],
            'products.*' => ['integer', 'distinct'],
        ]);
    }

    /**
     * @param  array<int, int|string>  $productIds
     */
    private function productsOwnedBySeller(Request $request, array $productIds): void
    {
        $productCount = $request->user()->products()
            ->whereIn('id', $productIds)
            ->count();

        if ($productCount !== count($productIds)) {
            throw ValidationException::withMessages([
                'products' => 'Selecione apenas itens do seu próprio catálogo.',
            ]);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $number = 2;

        while (Bundle::query()->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$number++;
        }

        return $slug;
    }

    private function ensureOwner(Request $request, Bundle $bundle): void
    {
        abort_unless($bundle->seller_id === $request->user()->id, 404);
    }
}
