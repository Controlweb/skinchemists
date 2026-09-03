<?php

namespace App\Filament\Schemas;

use App\Support\Seo;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

/**
 * Per-page SEO overrides, shared by every resource with a public URL.
 *
 * Collapsed by default and empty by default, because the derived title and
 * description are right more often than a hand-written one — these are for the
 * pages where they are not, not a field to dutifully fill in on every row.
 */
class SeoSection
{
    public static function make(string $titlePlaceholder): Section
    {
        return Section::make('Référencement')
            ->description('Facultatif. Laissé vide, la page se décrit à partir de son propre contenu.')
            ->collapsed()
            ->schema([
                TextInput::make('meta_title')
                    ->label('Titre SEO')
                    ->placeholder($titlePlaceholder)
                    ->maxLength(120)
                    ->helperText('Google en affiche environ '.Seo::TITLE_LIMIT.' caractères. Le suffixe du site est ajouté automatiquement.'),

                Textarea::make('meta_description')
                    ->label('Description SEO')
                    ->rows(3)
                    ->maxLength(300)
                    ->helperText('Google en affiche environ '.Seo::DESCRIPTION_LIMIT.' caractères.'),
            ])
            ->columns(1);
    }
}
