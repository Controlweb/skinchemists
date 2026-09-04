<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\ShopOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Account settings: name, email and password, reached from the user
            // menu. Filament's page already gates every save behind the current
            // password, so there is nothing to hand-roll. isSimple: false keeps
            // it inside the panel shell rather than on a bare centred page.
            ->profile(isSimple: false)
            ->brandName('skinChemists Maroc')
            ->brandLogo(asset('uploads/black_Logo_1.webp'))
            ->darkModeBrandLogo(asset('uploads/SKINCHEMIST-LOGO-WHITE.webp'))
            ->brandLogoHeight('1.75rem')
            ->favicon(asset('uploads/favicon.png'))
            // Installable on a phone home screen: manifest, iOS meta tags and the
            // service worker registration. See the admin.pwa.* routes.
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): View => view('pwa.head'))
            ->colors([
                // Matches the storefront's near-black rather than Filament amber.
                'primary' => Color::Zinc,
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make('Ventes'),
                NavigationGroup::make('Catalogue'),
                NavigationGroup::make('Contenu'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                ShopOverview::class,
                LatestOrders::class,
                LowStockProducts::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
