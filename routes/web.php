<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/boutique', [ShopController::class, 'index'])->name('shop');
Route::get('/produit/{product:slug}', [ProductController::class, 'show'])->name('product');

Route::get('/panier', [CartController::class, 'index'])->name('cart');
Route::post('/panier', [CartController::class, 'add'])->name('cart.add');

Route::get('/commande', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/commande', [CheckoutController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('checkout.store');

Route::get('/commande/{order:number}/confirmation', [CheckoutController::class, 'confirmation'])
    ->name('checkout.confirmation');

Route::get('/sitemap.xml', function () {
    $products = \App\Models\Product::active()->select('slug', 'updated_at')->get();

    return response()->view('sitemap', compact('products'))
        ->header('Content-Type', 'application/xml');
})->name('sitemap');
