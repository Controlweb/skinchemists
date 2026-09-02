<?php

namespace App\Filament\Resources\Bundles\Tables;

use App\Models\Bundle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BundlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('name')->label('Coffret')->searchable()->weight('medium'),
                        TextColumn::make('products_count')->counts('products')
                            ->formatStateUsing(fn (int $state) => $state.' produits')
                            ->color('gray')->size('xs'),
                    ]),

                    TextColumn::make('price')
                        ->label('Prix coffret')
                        ->state(fn (Bundle $record) => mad($record->priceCents()))
                        ->description(fn (Bundle $record) => 'au lieu de '.mad($record->fullPriceCents()))
                        ->grow(false),

                    TextColumn::make('saving')
                        ->label('Économie')
                        ->grow(false)
                        ->visibleFrom('lg')
                        ->state(fn (Bundle $record) => mad($record->savingCents()))
                        ->color('success'),

                    TextColumn::make('availability')
                        ->label('Disponible')
                        ->badge()
                        ->state(fn (Bundle $record) => $record->availableQuantity())
                        ->color(fn (Bundle $record) => $record->availableQuantity() > 0 ? 'success' : 'danger')
                        ->grow(false),

                    ToggleColumn::make('is_active')->label('Actif')->grow(false)->visibleFrom('md'),
                ])->from('md'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
