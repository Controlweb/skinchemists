<?php

namespace App\Providers;

use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\Cart;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One cart per request: the session read and the resolved product
        // models are shared by the header badge, the drawer and the controller.
        $this->app->scoped(Cart::class);
    }

    public function boot(): void
    {
        Product::observe(ProductObserver::class);

        // Shared hosting terminates TLS at the proxy; without this every
        // asset() and route() would emit http:// on an https:// page.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
