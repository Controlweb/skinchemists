<?php

namespace App\Filament\Resources\Ingredients\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use App\Filament\Schemas\SeoSection;

class IngredientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Actif')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state)))
                        // The catalogue joins on this exact string.
                        ->helperText("Doit correspondre exactement au champ « Actif principal » des produits."),

                    TextInput::make('slug')->label('Slug (URL)')->required()->unique(ignoreRecord: true),

                    Textarea::make('intro')->label('Accroche')->rows(3)->columnSpanFull(),
                    Textarea::make('what')->label('Ce que c\'est')->rows(4)->columnSpanFull(),

                    Repeater::make('benefits')
                        ->label('Bienfaits')
                        ->simple(TextInput::make('benefit')->required())
                        ->columnSpanFull()
                        ->defaultItems(3),

                    Textarea::make('how')->label('Comment l\'utiliser')->rows(3),
                    Textarea::make('who')->label('Pour qui')->rows(3),

                    Toggle::make('is_published')->label('Page publiée')->default(true),
                ]),
            SeoSection::make("le nom de l'actif"),
        ]);
    }
}
