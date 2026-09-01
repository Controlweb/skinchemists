<?php

namespace App\Filament\Pages;

use App\Models\StockMovement;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Read-only inventory ledger.
 *
 * Every stock change lands here — sales, cancellations and manual edits alike —
 * so a discrepancy between the system and the shelf can be traced to the entry
 * that caused it. Nothing on this page writes.
 */
class StockLedger extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsUpDown;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Mouvements de stock';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.stock-ledger';

    public function getTitle(): string
    {
        return 'Mouvements de stock';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(StockMovement::query()->with(['product', 'order', 'user']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),

                TextColumn::make('product.name')
                    ->label('Produit')
                    ->description(fn (StockMovement $record) => $record->product?->sku)
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                TextColumn::make('delta')
                    ->label('Mouvement')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('stock_after')
                    ->label('Stock après')
                    ->description(fn (StockMovement $record) => 'avant : '.$record->stock_before),

                TextColumn::make('reason')->label('Motif')->searchable(),

                TextColumn::make('user.name')->label('Par')->placeholder('Système')->toggleable(),
            ])
            ->filters([
                Filter::make('manual')
                    ->label('Ajustements manuels seulement')
                    ->query(fn (Builder $query) => $query->where('reason', 'Ajustement manuel')),

                Filter::make('incoming')
                    ->label('Entrées seulement')
                    ->query(fn (Builder $query) => $query->where('delta', '>', 0)),
            ]);
    }
}
