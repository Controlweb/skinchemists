<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?string $heading = 'Dernières commandes';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->latest('id')->limit(8))
            ->paginated(false)
            ->columns([
                TextColumn::make('number')->label('N°')->weight('medium'),
                TextColumn::make('created_at')->label('Reçue')->since(),
                TextColumn::make('customer')
                    ->label('Client')
                    ->state(fn (Order $record) => $record->customerName())
                    ->description(fn (Order $record) => $record->phone),
                TextColumn::make('city')->label('Ville'),
                TextColumn::make('total_cents')->label('Total')
                    ->formatStateUsing(fn (int $state) => mad($state)),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'annulee' => 'danger',
                        'livree', 'expediee' => 'success',
                        'preparation' => 'warning',
                        default => 'info',
                    }),
            ])
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]));
    }
}
