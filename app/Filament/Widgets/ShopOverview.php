<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $today = Order::whereDate('created_at', today())->where('status', '!=', 'annulee');
        $month = Order::whereBetween('created_at', [now()->startOfMonth(), now()])
            ->where('status', '!=', 'annulee');

        $toCall = Order::where('status', 'nouvelle')->count();
        $lowStock = Product::whereColumn('stock', '<=', 'low_stock_threshold')->count();
        $pendingReviews = Review::where('status', 'en_attente')->count();

        return [
            Stat::make('Commandes aujourd\'hui', (clone $today)->count())
                ->description(mad((int) (clone $today)->sum('total_cents')).' encaissables')
                ->color('primary'),

            Stat::make('Chiffre du mois', mad((int) (clone $month)->sum('total_cents')))
                ->description((clone $month)->count().' commandes depuis le 1er')
                ->color('success'),

            Stat::make('À confirmer', $toCall)
                ->description($toCall > 0 ? 'Clients à appeler' : 'Rien en attente')
                ->color($toCall > 0 ? 'warning' : 'gray'),

            Stat::make('Alertes', $lowStock + $pendingReviews)
                ->description("{$lowStock} produit(s) en stock bas · {$pendingReviews} avis à modérer")
                ->color($lowStock + $pendingReviews > 0 ? 'danger' : 'gray'),
        ];
    }
}
