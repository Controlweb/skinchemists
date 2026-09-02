<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BundleController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\OrderTrackingController;
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
    return response()->view('sitemap', [
        'products' => \App\Models\Product::active()->select('slug', 'updated_at')->get(),
        'ingredients' => \App\Models\Ingredient::published()->select('slug', 'updated_at')->get(),
        'articles' => \App\Models\Article::published()->select('slug', 'updated_at')->get(),
    ])->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::get('/coffrets', [BundleController::class, 'index'])->name('bundles');
Route::get('/actif/{ingredient:slug}', [IngredientController::class, 'show'])->name('ingredient');

Route::get('/le-lab', [ArticleController::class, 'index'])->name('lab');
Route::get('/le-lab/{article:slug}', [ArticleController::class, 'show'])->name('article');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::get('/suivi', [OrderTrackingController::class, 'show'])->name('tracking');
Route::post('/suivi', [OrderTrackingController::class, 'find'])
    ->middleware('throttle:8,1')
    ->name('tracking.find');
