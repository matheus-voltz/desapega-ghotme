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
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(12);

        return view('seller.profile', compact('seller', 'products'));
    }
}
