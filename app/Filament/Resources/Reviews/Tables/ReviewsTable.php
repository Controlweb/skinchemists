<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produit')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('author')->label('Auteur')->searchable(),

                TextColumn::make('rating')
                    ->label('Note')
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state)),

                TextColumn::make('body')->label('Avis')->wrap()->limit(120),

                IconColumn::make('verified')->label('Vérifié')->boolean(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Review::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'approuve' => 'success',
                        'rejete' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('created_at')->label('Reçu le')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(Review::STATUSES),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (Review $record) => $record->status !== 'approuve')
                    ->action(function (Review $record) {
                        $record->update(['status' => 'approuve']);
                        self::recount($record);
                        Notification::make()->title('Avis publié')->success()->send();
                    }),

                Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (Review $record) => $record->status !== 'rejete')
                    ->requiresConfirmation()
                    ->action(function (Review $record) {
                        $record->update(['status' => 'rejete', 'featured' => false]);
                        self::recount($record);
                        Notification::make()->title('Avis rejeté')->send();
                    }),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label('Approuver la sélection')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function (Review $review) {
                                $review->update(['status' => 'approuve']);
                                self::recount($review);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    /**
     * The storefront shows a denormalised rating on the product card, so it has
     * to be recomputed from approved reviews whenever moderation changes.
     */
    private static function recount(Review $review): void
    {
        $product = $review->product;

        if (! $product) {
            return;
        }

        $approved = $product->reviews()->approved();

        $product->forceFill([
            'reviews_count' => $approved->count(),
            'rating_avg' => round($approved->avg('rating') ?? 0, 1),
        ])->save();
    }
}
