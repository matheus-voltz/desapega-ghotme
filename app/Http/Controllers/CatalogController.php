<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Product::query()->orderBy('sort_order')->orderByDesc('id');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        $products = $query->paginate(12)->withQueryString();
        $categories = Product::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $bundles = Bundle::query()
            ->with('products')
            ->where('active', true)
            ->orderByDesc('id')
            ->get();

        if ($request->boolean('load_more')) {
            return response()->json([
                'html' => view('catalog._product-cards', compact('products'))->render(),
                'next_page_url' => $products->nextPageUrl(),
            ]);
        }

        return view('catalog.index', compact('products', 'categories', 'bundles'));
    }

    public function product(Product $product)
    {
        return view('catalog.product', compact('product'));
    }

    public function bundle(Bundle $bundle)
    {
        abort_unless($bundle->active, 404);
        $bundle->load('products');

        return view('catalog.bundle', compact('bundle'));
    }
}
