<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class SellerProfileController extends Controller
{
    public function show(User $seller): View
    {
        abort_unless($seller->isSeller(), 404);

        $products = $seller->products()
            ->where('status', 'available')
            ->where('is_visible', true)
            ->with('seller:id,name,public_slug')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(12);

        $bundles = $seller->bundles()
            ->with('products')
            ->where('active', true)
            ->has('products')
            ->whereDoesntHave('products', fn ($productQuery) => $productQuery
                ->where('status', '!=', 'available')
                ->orWhere('is_visible', false))
            ->latest()
            ->get();

        return view('seller.profile', compact('seller', 'products', 'bundles'));
    }
}
