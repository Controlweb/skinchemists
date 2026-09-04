<?php

namespace App\Filament\Resources\Articles\Tables;

use App\Models\Article;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                Split::make([
                    // Which articles still have no visual is the thing you come
                    // to this list to find out; a thumbnail answers it at a
                    // glance where a filename would not.
                    ImageColumn::make('image_path')
                        ->label('Visuel')
                        ->getStateUsing(fn (Article $record) => $record->image_path
                            ? image_url($record->image_path)
                            : null)
                        ->height(40)
                        ->width(64)
                        ->grow(false)
                        ->visibleFrom('md'),

                    Stack::make([
                        TextColumn::make('title')->label('Titre')->searchable()->weight('medium')->wrap()->limit(60),
                        TextColumn::make('author')->color('gray')->size('xs'),
                    ]),

                    TextColumn::make('category')->label('Catégorie')->badge()->grow(false)->visibleFrom('md'),
                    IconColumn::make('is_featured')->label('À la une')->boolean()->grow(false)->visibleFrom('lg'),

                    TextColumn::make('published_at')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (Article $record) => match (true) {
                        $record->published_at === null => 'Brouillon',
                        $record->published_at->isFuture() => 'Programmé',
                        default => 'Publié',
                    })
                    ->color(fn (Article $record) => match (true) {
                        $record->published_at === null => 'gray',
                        $record->published_at->isFuture() => 'warning',
                        default => 'success',
                    })
                    ->description(fn (Article $record) => $record->published_at?->format('d/m/Y H:i'))
                        ->sortable(),
                ])->from('md'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(fn () => Article::query()->distinct()->pluck('category', 'category')->all()),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
