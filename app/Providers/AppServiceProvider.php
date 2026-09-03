<?php

namespace App\Providers;

use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\Cart;
use App\Support\MailConfig;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One cart per request: the session read and the resolved product
        // models are shared by the header badge, the drawer and the controller.
        $this->app->scoped(Cart::class);

        // Overlay the admin's SMTP settings onto config/mail.php. Hooked on the
        // mail manager rather than boot() so the settings reads only happen on
        // a request that actually sends something, and early enough that the
        // manager has not resolved a mailer from the .env values yet.
        $this->app->resolving('mail.manager', fn () => MailConfig::apply());
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
