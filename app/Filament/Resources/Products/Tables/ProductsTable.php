<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('primary_image')
                    ->label('')
                    ->state(fn (Product $record) => $record->primaryImage()?->url())
                    ->height(44),

                TextColumn::make('name')
                    ->label('Produit')
                    ->description(fn (Product $record) => $record->sku)
                    ->searchable(['name', 'sku'])
                    ->wrap(),

                TextColumn::make('brand')
                    ->label('Marque')
                    ->badge()
                    ->sortable(),

                TextColumn::make('gamme')
                    ->label('Gamme')
                    ->placeholder('—')
                    ->toggleable(),

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
                SelectFilter::make('brand')
                    ->label('Marque')
                    ->options(fn () => Product::query()
                        ->whereNotNull('brand')->distinct()->orderBy('brand')
                        ->pluck('brand', 'brand')->all()),

                SelectFilter::make('gamme')
                    ->label('Gamme')
                    ->options(fn () => Product::query()
                        ->whereNotNull('gamme')->distinct()->orderBy('gamme')
                        ->pluck('gamme', 'gamme')->all()),

                SelectFilter::make('category')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),

                Filter::make('low_stock')
                    ->label('Stock faible ou épuisé')
                    ->query(fn (Builder $query) => $query
                        ->whereColumn('stock', '<=', 'low_stock_threshold')),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Brand and gamme are usually corrected for a whole line at
                    // once, not product by product.
                    BulkAction::make('setBrandAndGamme')
                        ->label('Modifier marque / gamme')
                        ->icon('heroicon-m-tag')
                        ->schema([
                            TextInput::make('brand')
                                ->label('Marque')
                                ->datalist(fn () => Product::query()
                                    ->whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand')->all())
                                ->helperText('Laisser vide pour ne pas y toucher.'),

                            TextInput::make('gamme')
                                ->label('Gamme')
                                ->datalist(fn () => Product::query()
                                    ->whereNotNull('gamme')->distinct()->orderBy('gamme')->pluck('gamme')->all())
                                ->helperText('Laisser vide pour ne pas y toucher.'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            // Only the fields actually filled in are written, so
                            // setting a gamme cannot silently blank a brand.
                            $changes = array_filter([
                                'brand' => $data['brand'] ?: null,
                                'gamme' => $data['gamme'] ?: null,
                            ]);

                            if ($changes === []) {
                                Notification::make()->title('Rien à modifier')->warning()->send();

                                return;
                            }

                            $records->each->update($changes);

                            Notification::make()
                                ->title($records->count().' produit(s) mis à jour')
                                ->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
