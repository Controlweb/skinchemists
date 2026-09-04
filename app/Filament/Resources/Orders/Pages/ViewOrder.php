<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Commande '.$this->record->number;
    }

    /** Same actions as the table row menu: staff open an order to act on it. */
    protected function getHeaderActions(): array
    {
        return OrdersTable::actions();
    }
}
