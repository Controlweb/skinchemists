<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
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
                // Stacks into a card below md so nothing needs a sideways
                // swipe; behaves as a normal row from md up.
                Split::make([
                    // Nested Split keeps the thumbnail beside the name on a
                    // phone rather than stacking it above as its own block.
                    Split::make([
                        ImageColumn::make('primary_image')
                            ->label('')
                            ->state(fn (Product $record) => $record->primaryImage()?->url())
                            ->height(44)
                            ->grow(false),

                        Stack::make([
                            TextColumn::make('name')
                                ->label('Produit')
                                ->searchable(['name', 'sku'])
                                ->weight('medium')
                                ->wrap(),

                            TextColumn::make('sku')
                                ->color('gray')
                                ->size('xs')
                                ->description(fn (Product $record) => collect([$record->brand, $record->gamme])
                                    ->filter()->implode(' · ')),
                        ]),
                    ]),

                    TextColumn::make('category.name')
                        ->label('Catégorie')
                        ->sortable()
                        ->grow(false)
                        ->visibleFrom('lg'),

                    TextColumn::make('ingredient')
                        ->label('Actif')
                        ->grow(false)
                        ->visibleFrom('lg'),

                    TextColumn::make('price_cents')
                        ->label('Prix')
                        ->formatStateUsing(fn (int $state) => mad($state))
                        ->description(fn (Product $record) => $record->isOnSale()
                            ? 'Promo '.mad($record->sale_price_cents)
                            : null)
                        ->sortable()
                        ->grow(false),

                    TextColumn::make('stock')
                        ->label('Stock')
                        ->badge()
                        ->color(fn (Product $record) => match (true) {
                            $record->stock < 1 => 'danger',
                            $record->isLowStock() => 'warning',
                            default => 'success',
                        })
                        ->sortable()
                        ->grow(false),

                    ToggleColumn::make('is_active')->label('En ligne')->grow(false)->visibleFrom('md'),
                ])->from('md'),
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
