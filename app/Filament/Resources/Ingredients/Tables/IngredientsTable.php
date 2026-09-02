<?php

namespace App\Filament\Resources\Ingredients\Tables;

use App\Models\Ingredient;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class IngredientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Split::make([
                    TextColumn::make('name')->label('Actif')->searchable()->weight('medium'),

                    TextColumn::make('products')
                        ->label('Produits')
                        ->badge()
                        ->grow(false)
                        // Counted by name, since products store the active as a string.
                        ->state(fn (Ingredient $record) => Product::where('ingredient', $record->name)->count())
                        ->color(fn (Ingredient $record) => Product::where('ingredient', $record->name)->exists()
                            ? 'success'
                            : 'danger')
                        ->description(fn (Ingredient $record) => Product::where('ingredient', $record->name)->exists()
                            ? null
                            : 'Aucun produit ne porte ce nom'),

                    TextColumn::make('intro')->label('Accroche')->wrap()->limit(90)->visibleFrom('lg'),

                    ToggleColumn::make('is_published')->label('Publiée')->grow(false),
                ])->from('md'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
