<?php

namespace App\Providers;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Navigation facets appear on every page. Querying them from the Blade
     * partial would mean two extra queries per request; cache them instead.
     * Cleared by ProductObserver whenever a product changes.
     */
    public function boot(): void
    {
        View::composer(['partials.header', 'home'], function ($view) {
            $view->with([
                // ->all(): cache plain arrays, never Collections. The database
                // cache store unserializes with allowed_classes restricted, so a
                // cached object returns as __PHP_Incomplete_Class on every hit
                // after the first — works locally, breaks once the cache warms.
                // name => slug, so the nav can link to the editorial page for
                // actives that have one and fall back to a shop filter for the rest.
                'navIngredients' => Cache::remember(
                    'nav:ingredients',
                    now()->addHour(),
                    function () {
                        $slugs = Ingredient::published()->pluck('slug', 'name');

                        return Product::active()->whereNotNull('ingredient')
                            ->distinct()->orderBy('ingredient')->pluck('ingredient')
                            ->mapWithKeys(fn ($name) => [$name => $slugs[$name] ?? null])
                            ->all();
                    }
                ),
                'navConcerns' => Cache::remember(
                    'nav:concerns',
                    now()->addHour(),
                    fn () => Product::active()->whereNotNull('concern')
                        ->distinct()->orderBy('concern')->pluck('concern')->all()
                ),
            ]);
        });

        // Used by the header bar, the drawer and the home page alike.
        // Resolved per-render, never in boot(): a provider that queries the
        // database at boot breaks every artisan command run before the tables
        // exist — migrate on a fresh server included.
        View::composer('*', function ($view) {
            $view->with('freeShippingThreshold', Setting::int('free_shipping_threshold_cents', 60000));
        });
    }
}
