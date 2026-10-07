<?php

use App\Http\Controllers\Admin\BundleController as AdminBundleController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ShopeeController as AdminShopeeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseClickController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\SettingsController;
use App\Http\Controllers\SellerProfileController;
use App\Http\Controllers\Webhooks\AsaasWebhookController;
use App\Http\Controllers\Webhooks\ShopeeWebhookController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', function (Request $request): RedirectResponse {
    return redirect()->route($request->user()->isAdmin() ? 'admin.products.index' : ($request->user()->isSeller() ? 'seller.products.index' : 'cart.index'));
})
    ->middleware('auth')
    ->name('dashboard');

Route::view('/', 'landing')->name('landing');
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/produto/{product:slug}', [CatalogController::class, 'product'])->name('catalog.product');
Route::get('/combo/{bundle:slug}', [CatalogController::class, 'bundle'])->name('catalog.bundle');
Route::get('/comprar/produto/{product:slug}/pix', [PurchaseClickController::class, 'productPix'])->name('purchase.product.pix');
Route::post('/comprar/produto/{product:slug}/pix', [PurchaseClickController::class, 'createProductPix'])->name('purchase.product.pix.create');
Route::get('/comprar/produto/{product:slug}/cartao', [PurchaseClickController::class, 'productCard'])->name('purchase.product.card');
Route::post('/comprar/produto/{product:slug}/cartao', [PurchaseClickController::class, 'createProductCard'])->name('purchase.product.card.create');
Route::post('/comprar/produto/{product:slug}/shopee', [PurchaseClickController::class, 'productShopee'])->name('purchase.product.shopee');
Route::get('/comprar/combo/{bundle:slug}/pix', [PurchaseClickController::class, 'bundlePix'])->name('purchase.bundle.pix');
Route::post('/comprar/combo/{bundle:slug}/pix', [PurchaseClickController::class, 'createBundlePix'])->name('purchase.bundle.pix.create');
Route::get('/pagamento/{asaasPayment:external_reference}/status', [PurchaseClickController::class, 'paymentStatus'])->name('purchase.payment.status');
Route::post('/webhooks/asaas', AsaasWebhookController::class)->withoutMiddleware([ValidateCsrfToken::class])->name('webhooks.asaas');
Route::post('/webhooks/shopee', ShopeeWebhookController::class)->withoutMiddleware([ValidateCsrfToken::class])->name('webhooks.shopee');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/sacola', [CartController::class, 'index'])->name('cart.index');
    Route::post('/sacola/itens/{product:slug}', [CartController::class, 'store'])->name('cart.items.store');
    Route::delete('/sacola/itens/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');
    Route::get('/sacola/pix', [CartController::class, 'pix'])->name('cart.pix');
    Route::post('/sacola/pix', [CartController::class, 'createPix'])->name('cart.pix.create');
    Route::get('/sacola/cartao', [CartController::class, 'card'])->name('cart.card');
    Route::post('/sacola/cartao', [CartController::class, 'createCard'])->name('cart.card.create');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('products', AdminProductController::class)->except('show');
    Route::resource('bundles', AdminBundleController::class)->except('show');
    Route::get('shopee', [AdminShopeeController::class, 'index'])->name('shopee.index');
    Route::get('shopee/connect', [AdminShopeeController::class, 'connect'])->name('shopee.connect');
    Route::get('shopee/callback', [AdminShopeeController::class, 'callback'])->name('shopee.callback');
    Route::post('shopee/configure-push', [AdminShopeeController::class, 'configurePush'])->name('shopee.configure-push');
    Route::post('shopee/sync', [AdminShopeeController::class, 'sync'])->name('shopee.sync');
    Route::delete('shopee/disconnect', [AdminShopeeController::class, 'disconnect'])->name('shopee.disconnect');
});

Route::middleware(['auth', 'seller'])->prefix('vendedor')->name('seller.')->group(function () {
    Route::resource('itens', SellerProductController::class)
        ->parameters(['itens' => 'product'])
        ->names('products')
        ->except('show');
    Route::get('/pedidos', [SellerOrderController::class, 'index'])->name('orders.index');
    Route::put('/pedidos/{order}', [SellerOrderController::class, 'update'])->name('orders.update');
    Route::get('/configuracoes', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/configuracoes', [SettingsController::class, 'update'])->name('settings.update');
});

Route::get('/vendedor/{seller:public_slug}', [SellerProfileController::class, 'show'])->name('seller.profile');

require __DIR__.'/auth.php';
