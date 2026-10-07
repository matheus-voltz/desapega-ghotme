<?php

namespace App\Http\Controllers;

use App\Models\SavedCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $savedCatalogs = $request->user()
            ->savedCatalogs()
            ->with([
                'seller' => fn ($query) => $query->withCount([
                    'products as visible_products_count' => fn ($productQuery) => $productQuery
                        ->where('status', 'available')
                        ->where('is_visible', true),
                ]),
            ])
            ->latest()
            ->get();

        return view('saved-catalogs.index', compact('savedCatalogs'));
    }

    public function destroy(Request $request, SavedCatalog $savedCatalog): RedirectResponse
    {
        abort_unless($savedCatalog->user_id === $request->user()->id, 404);

        $savedCatalog->delete();

        return to_route('saved-catalogs.index')->with('success', 'Catálogo removido da sua lista.');
    }
}
