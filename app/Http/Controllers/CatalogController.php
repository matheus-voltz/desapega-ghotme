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
        $query = Product::query()
            ->where('status', 'available')
            ->with('seller:id,name,public_slug')
            ->orderBy('sort_order')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($productQuery) use ($search): void {
                $productQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('max_price') && is_numeric($request->input('max_price'))) {
            $query->where('pix_price', '<=', $request->input('max_price'));
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
        $product->load('seller.sellerSetting');

        return view('catalog.product', compact('product'));
    }

    public function bundle(Bundle $bundle)
    {
        abort_unless($bundle->active, 404);
        $bundle->load('products');

        return view('catalog.bundle', compact('bundle'));
    }
}
