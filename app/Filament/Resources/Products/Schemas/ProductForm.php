<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identité')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Nom')->required()->columnSpanFull(),
                    TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true)
                        ->helperText("Change l'URL publique du produit."),
                    TextInput::make('sku')->label('SKU')->required()->unique(ignoreRecord: true),
                    TextInput::make('gtin')->label('Code-barres (GTIN)'),
                    Select::make('category_id')->label('Catégorie')
                        ->relationship('category', 'name')->required(),
                    TextInput::make('ingredient')->label('Actif principal')
                        ->datalist(fn () => \App\Models\Product::query()
                            ->whereNotNull('ingredient')->distinct()->pluck('ingredient')->all()),
                    TextInput::make('concern')->label('Préoccupation')
                        ->datalist(fn () => \App\Models\Product::query()
                            ->whereNotNull('concern')->distinct()->pluck('concern')->all()),
                ]),

            Section::make('Prix')
                ->columns(2)
                ->description('Montants en dirhams. Ils sont stockés en centimes.')
                ->schema([
                    // The database is the source of truth in centimes; the form
                    // works in dirhams so nobody has to multiply by 100 by hand.
                    TextInput::make('price_cents')
                        ->label('Prix courant (MAD)')
                        ->numeric()->required()->minValue(0)
                        ->formatStateUsing(fn (?int $state) => $state === null ? null : $state / 100)
                        ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),

                    TextInput::make('sale_price_cents')
                        ->label('Prix promo (MAD)')
                        ->numeric()->minValue(0)
                        ->helperText('Laisser vide pour désactiver la promotion.')
                        ->formatStateUsing(fn (?int $state) => $state === null ? null : $state / 100)
                        ->dehydrateStateUsing(fn ($state) => $state === null || $state === ''
                            ? null
                            : (int) round((float) $state * 100)),
                ]),

            Section::make('Stock')
                ->columns(3)
                ->schema([
                    TextInput::make('stock')
                        ->label('Stock')
                        ->numeric()->required()->minValue(0)
                        ->helperText('Toute modification est enregistrée comme mouvement de stock.'),
                    TextInput::make('low_stock_threshold')
                        ->label('Seuil d\'alerte')->numeric()->required()->minValue(0),
                    Toggle::make('is_active')->label('Visible sur le site')->default(true),
                ]),

            Section::make('Contenu')
                ->schema([
                    Textarea::make('short')->label('Description')->rows(5),
                ]),
        ]);
    }
}
