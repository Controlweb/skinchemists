<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('primary_image')
                    ->label('')
                    ->state(fn (Product $record) => $record->primaryImage()
                        ? asset($record->primaryImage())
                        : null)
                    ->height(44),

                TextColumn::make('name')
                    ->label('Produit')
                    ->description(fn (Product $record) => $record->sku)
                    ->searchable(['name', 'sku'])
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),

                TextColumn::make('ingredient')
                    ->label('Actif')
                    ->toggleable(),

                TextColumn::make('price_cents')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state) => mad($state))
                    ->description(fn (Product $record) => $record->isOnSale()
                        ? 'Promo '.mad($record->sale_price_cents)
                        : null)
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (Product $record) => match (true) {
                        $record->stock < 1 => 'danger',
                        $record->isLowStock() => 'warning',
                        default => 'success',
                    })
                    ->sortable(),

                ToggleColumn::make('is_active')->label('En ligne'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),

                Filter::make('low_stock')
                    ->label('Stock faible ou épuisé')
                    ->query(fn (Builder $query) => $query
                        ->whereColumn('stock', '<=', 'low_stock_threshold')),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([])]);
    }
}
