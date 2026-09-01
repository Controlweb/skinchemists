<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Commandes';

    protected static string|\UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    protected static ?int $navigationSort = 1;

    /** Orders arrive from the storefront; staff read and progress them. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $new = static::getModel()::where('status', 'nouvelle')->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Client')
                ->columns(3)
                ->schema([
                    TextEntry::make('customer')->label('Nom')
                        ->state(fn (Order $record) => $record->customerName()),
                    TextEntry::make('phone')->label('Téléphone')->copyable(),
                    TextEntry::make('email')->label('Email')->placeholder('—'),
                    TextEntry::make('address')->label('Adresse')->columnSpan(2),
                    TextEntry::make('city')->label('Ville')
                        ->formatStateUsing(fn (Order $record) => trim($record->city.' '.$record->zip)),
                ]),

            Section::make('Articles')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('name')->label('Produit')->columnSpan(2),
                            TextEntry::make('quantity')->label('Qté'),
                            TextEntry::make('line_total_cents')->label('Total')
                                ->formatStateUsing(fn (int $state) => mad($state)),
                        ]),
                ]),

            Section::make('Montants')
                ->columns(4)
                ->schema([
                    TextEntry::make('subtotal_cents')->label('Sous-total')
                        ->formatStateUsing(fn (int $state) => mad($state)),
                    TextEntry::make('discount_cents')->label('Remise')
                        ->formatStateUsing(fn (int $state) => $state > 0 ? '−'.mad($state) : '—'),
                    TextEntry::make('shipping_cents')->label('Livraison')
                        ->formatStateUsing(fn (int $state) => $state > 0 ? mad($state) : 'Offerte'),
                    TextEntry::make('total_cents')->label('Total')
                        ->formatStateUsing(fn (int $state) => mad($state))
                        ->weight('bold'),
                ]),

            Section::make('Historique')
                ->schema([
                    RepeatableEntry::make('events')
                        ->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('label')->hiddenLabel(),
                            TextEntry::make('actor')->hiddenLabel(),
                            TextEntry::make('created_at')->hiddenLabel()->dateTime('d/m/Y H:i'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
