<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BundleController extends Controller
{
    public function index()
    {
        $bundles = Bundle::with('products')->latest()->paginate(20);

        return view('admin.bundles.index', compact('bundles'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();

        return view('admin.bundles.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $productIds = $data['products'];
        unset($data['products']);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['active'] = $request->boolean('active');

        if ($request->hasFile('cover_image')) {
            $data['cover_image_path'] = $request->file('cover_image')->store('bundles', 'public');
        }

        $bundle = Bundle::create($data);
        $bundle->products()->sync($productIds);

        return redirect()->route('admin.bundles.index')->with('success', 'Combo criado.');
    }

    public function edit(Bundle $bundle)
    {
        $products = Product::orderBy('name')->get();
        $bundle->load('products');

        return view('admin.bundles.edit', compact('bundle', 'products'));
    }

    public function update(Request $request, Bundle $bundle)
    {
        $data = $this->validated($request);
        $productIds = $data['products'];
        unset($data['products']);
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

        return redirect()->route('admin.bundles.index')->with('success', 'Combo atualizado.');
    }

    public function destroy(Bundle $bundle)
    {
        if ($bundle->cover_image_path) {
            Storage::disk('public')->delete($bundle->cover_image_path);
        }
        $bundle->delete();

        return redirect()->route('admin.bundles.index')->with('success', 'Combo excluído.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'pix_price' => ['required', 'numeric', 'min:0'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'products' => ['required', 'array', 'min:2'],
            'products.*' => ['exists:products,id'],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $i = 2;

        while (Bundle::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
