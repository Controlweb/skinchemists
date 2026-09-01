<?php

namespace App\Filament\Resources\Articles\Tables;

use App\Models\Article;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
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
                TextColumn::make('title')->label('Titre')->searchable()->wrap()->limit(60),
                TextColumn::make('category')->label('Catégorie')->badge(),
                TextColumn::make('author')->label('Auteur')->toggleable(),
                IconColumn::make('is_featured')->label('À la une')->boolean(),

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
