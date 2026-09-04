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
use App\Models\Article;
use App\Models\Ingredient;
use App\Models\Product;
use App\Support\Seo;
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

/**
 * robots.txt is generated rather than static so the SEO screen's indexing
 * toggle actually reaches crawlers. A meta robots tag only helps on pages a
 * crawler already fetched; this stops it at the door.
 */
Route::get('/robots.txt', function () {
    $lines = ['User-agent: *'];

    if (Seo::isIndexable()) {
        $lines[] = 'Disallow: /panier';
        $lines[] = 'Disallow: /commande';
        $lines[] = 'Disallow: /suivi';
        $lines[] = 'Disallow: /admin';
        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');
    } else {
        $lines[] = 'Disallow: /';
    }

    return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain');
})->name('robots');

Route::get('/sitemap.xml', function () {
    return response()->view('sitemap', [
        'products' => Product::active()->select('slug', 'updated_at')->get(),
        'ingredients' => Ingredient::published()->select('slug', 'updated_at')->get(),
        'articles' => Article::published()->select('slug', 'updated_at')->get(),
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

/**
 * Admin PWA: makes /admin installable on a phone home screen.
 *
 * These three live on Laravel routes rather than files in public/admin/, because
 * an actual public/admin directory would satisfy the `!-d` rewrite condition in
 * public/.htaccess and shadow the whole Filament panel. They stay outside the
 * panel's auth middleware: the browser fetches the manifest and the worker
 * without session credentials.
 */
Route::prefix('admin')->name('admin.pwa.')->group(function () {
    Route::get('manifest.webmanifest', function () {
        return response()->json([
            'name' => 'skinChemists Maroc — Administration',
            'short_name' => 'SC Admin',
            'lang' => 'fr',
            'start_url' => '/admin',
            'scope' => '/admin',
            'display' => 'standalone',
            'background_color' => '#18181b',
            'theme_color' => '#18181b',
            'icons' => collect([192, 512])->map(fn (int $size) => [
                'src' => asset("uploads/admin-icon-{$size}.png"),
                'sizes' => "{$size}x{$size}",
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ])->all(),
        ])->header('Content-Type', 'application/manifest+json');
    })->name('manifest');

    // Served from /admin/ so the worker's scope covers the panel and nothing else.
    Route::get('sw.js', function () {
        return response()
            ->view('pwa.sw', ['version' => substr(md5(config('app.key').filemtime(base_path('composer.lock'))), 0, 8)])
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache');
    })->name('sw');

    Route::get('hors-ligne', function () {
        // The worker caches this page on install, so it must reference nothing
        // it cannot load offline. Livewire injects its script into any response
        // containing </html> once a component has rendered; opt out explicitly
        // rather than rely on this route never touching one.
        config(['livewire.inject_assets' => false]);

        return view('pwa.offline');
    })->name('offline');
});
