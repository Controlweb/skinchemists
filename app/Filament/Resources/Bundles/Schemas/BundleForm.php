<?php

namespace App\Filament\Resources\Bundles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use App\Filament\Schemas\GallerySection;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BundleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Coffret')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Nom')->required()->columnSpanFull()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                    TextInput::make('slug')->label('Slug (URL)')->required()->unique(ignoreRecord: true),
                    TextInput::make('tag')->label('Étiquette')->placeholder('Édition limitée'),

                    Textarea::make('blurb')->label('Description')->rows(3)->columnSpanFull(),

                    Select::make('products')
                        ->label('Produits du coffret')
                        ->relationship('products', 'name')
                        ->multiple()->preload()->searchable()->required()
                        ->columnSpanFull()
                        ->helperText('Le stock du coffret est celui de son composant le plus rare.'),

                    TextInput::make('discount_percent')
                        ->label('Remise (%)')
                        ->numeric()->required()->minValue(0)->maxValue(90)->default(15)
                        ->helperText('Réellement appliquée au panier quand tous les produits y sont.'),

                    Toggle::make('is_active')->label('Actif')->default(true),
                ]),

            GallerySection::make(
                'Facultatif. Sans image, la vignette est composée automatiquement à partir des produits du coffret.'
            ),
        ]);
    }
}
