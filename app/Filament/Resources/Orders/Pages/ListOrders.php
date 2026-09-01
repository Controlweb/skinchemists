<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /** No create action: orders come from the storefront. */
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('Toutes')];

        foreach (Order::STATUSES as $value => $label) {
            $tabs[$value] = Tab::make($label)
                ->modifyQueryUsing(fn ($query) => $query->where('status', $value))
                ->badge(Order::where('status', $value)->count());
        }

        return $tabs;
    }
}
