<?php

namespace App\Filament\Resources\Bundles\Tables;

use App\Models\Bundle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                TextColumn::make('name')->label('Coffret')->searchable()->weight('medium'),
                TextColumn::make('products_count')->label('Produits')->counts('products')->visibleFrom('md'),

                TextColumn::make('price')
                    ->label('Prix coffret')
                    ->state(fn (Bundle $record) => mad($record->priceCents()))
                    ->description(fn (Bundle $record) => 'au lieu de '.mad($record->fullPriceCents())),

                TextColumn::make('saving')
                    ->label('Économie')
                    ->visibleFrom('lg')
                    ->state(fn (Bundle $record) => mad($record->savingCents()))
                    ->color('success'),

                TextColumn::make('availability')
                    ->label('Disponible')
                    ->badge()
                    ->state(fn (Bundle $record) => $record->availableQuantity())
                    ->color(fn (Bundle $record) => $record->availableQuantity() > 0 ? 'success' : 'danger'),

                ToggleColumn::make('is_active')->label('Actif')->visibleFrom('md'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
