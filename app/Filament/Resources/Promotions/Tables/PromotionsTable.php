<?php

namespace App\Filament\Resources\Promotions\Tables;

use App\Models\Promotion;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PromotionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('code')->label('Code')->searchable()->weight('medium')->copyable(),
                        TextColumn::make('name')->searchable()->color('gray')->size('xs'),
                    ]),

                    TextColumn::make('value')
                        ->label('Remise')
                        ->state(fn (Promotion $record) => match ($record->type) {
                            'percent' => '−'.$record->value.'%',
                            'fixed' => '−'.mad($record->value),
                            'free_shipping' => 'Livraison offerte',
                            default => '—',
                        })
                        ->description(fn (Promotion $record) => $record->min_subtotal_cents > 0
                            ? 'dès '.mad($record->min_subtotal_cents)
                            : 'sans minimum')
                        ->grow(false),

                    TextColumn::make('period')
                        ->label('Période')
                        ->grow(false)
                        ->visibleFrom('lg')
                        ->state(fn (Promotion $record) => match (true) {
                            $record->starts_at && $record->ends_at => $record->starts_at->format('d/m').' → '.$record->ends_at->format('d/m'),
                            (bool) $record->ends_at => 'jusqu\'au '.$record->ends_at->format('d/m/Y'),
                            default => 'Permanent',
                        }),

                    TextColumn::make('uses')
                        ->label('Utilisé')
                        ->grow(false)
                        ->visibleFrom('md')
                        ->state(fn (Promotion $record) => $record->max_uses
                            ? $record->uses.' / '.$record->max_uses
                            : (string) $record->uses)
                        ->sortable(),

                    TextColumn::make('state')
                        ->label('État')
                        ->badge()
                        // The real question staff have is "does this work right now?",
                        // which the is_active flag alone does not answer.
                        ->state(fn (Promotion $record) => $record->isRedeemable() ? 'Utilisable' : 'Inactif')
                        ->color(fn (Promotion $record) => $record->isRedeemable() ? 'success' : 'gray')
                        ->grow(false),

                    ToggleColumn::make('is_active')->label('Activé')->grow(false)->visibleFrom('md'),
                ])->from('md'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
