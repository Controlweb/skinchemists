<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Schemas\GallerySection;
use App\Models\Product;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(3)->schema([
                    // What you edit most often, in reading order.
                    Group::make()->columnSpan(2)->schema([
                        self::identity(),
                        self::pricing(),
                        self::content(),
                    ]),

                    // Reference and switches: consulted, rarely retyped.
                    Group::make()->columnSpan(1)->schema([
                        GallerySection::make('La première image sert de vignette dans la boutique, le panier et les emails.'),
                        self::stock(),
                        self::visibility(),
                        self::stats(),
                    ]),
                ]),
            ]);
    }

    private static function identity(): Section
    {
        return Section::make('Identité')
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->columnSpanFull()
                    ->live(onBlur: true)
                    // Only fill the slug for a new product: changing it on a
                    // live one breaks every link already shared and indexed.
                    ->afterStateUpdated(function ($state, $set, ?Product $record) {
                        if (! $record) {
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Slug (URL)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull()
                    ->helperText('Modifier ce champ casse les liens déjà partagés et le référencement.'),

                TextInput::make('sku')->label('SKU')->required()->unique(ignoreRecord: true),
                TextInput::make('gtin')->label('Code-barres (GTIN)'),

                TextInput::make('brand')
                    ->label('Marque')
                    ->required()
                    ->datalist(fn () => self::distinct('brand'))
                    ->helperText('Plusieurs marques sont distribuées ici.'),

                TextInput::make('gamme')
                    ->label('Gamme')
                    ->datalist(fn () => self::distinct('gamme'))
                    ->helperText('Ligne commerciale. Facultatif.'),

                Select::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name')
                    ->required(),

                TextInput::make('ingredient')
                    ->label('Actif principal')
                    ->datalist(fn () => self::distinct('ingredient'))
                    ->helperText('Doit correspondre au nom de la page Actif pour y être rattaché.'),

                TextInput::make('concern')
                    ->label('Préoccupation')
                    ->datalist(fn () => self::distinct('concern')),
            ]);
    }

    private static function pricing(): Section
    {
        return Section::make('Prix')
            ->description('Montants en dirhams. Stockés en centimes.')
            ->columns(2)
            ->schema([
                // The database is the source of truth in centimes; the form
                // works in dirhams so nobody multiplies by 100 by hand.
                TextInput::make('price_cents')
                    ->label('Prix courant (MAD)')
                    ->numeric()->required()->minValue(0)
                    ->formatStateUsing(fn (?int $state) => $state === null ? null : $state / 100)
                    ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),

                TextInput::make('sale_price_cents')
                    ->label('Prix promo (MAD)')
                    ->numeric()->minValue(0)
                    // A "sale" price above the list price would advertise a
                    // negative discount.
                    ->lt('price_cents')
                    ->helperText('Doit être inférieur au prix courant. Vide = pas de promotion.')
                    ->formatStateUsing(fn (?int $state) => $state === null ? null : $state / 100)
                    ->dehydrateStateUsing(fn ($state) => $state === null || $state === ''
                        ? null
                        : (int) round((float) $state * 100)),
            ]);
    }

    private static function content(): Section
    {
        return Section::make('Contenu de la fiche')
            ->description('Ce que le client lit sur la page produit.')
            ->schema([
                Textarea::make('short')->label('Description')->rows(5),

                Repeater::make('bullets')
                    ->label('Bénéfices')
                    ->simple(TextInput::make('bullet')->required()->maxLength(200))
                    ->addActionLabel('Ajouter un bénéfice')
                    ->reorderable()
                    ->defaultItems(0)
                    ->helperText('Liste à puces affichée sous le prix.'),

                Repeater::make('actifs')
                    ->label('Actifs clés')
                    ->schema([
                        TextInput::make('t')->label('Actif')->required(),
                        Textarea::make('d')->label('Ce qu\'il fait')->rows(3)->required(),
                    ])
                    ->itemLabel(fn (array $state) => $state['t'] ?? 'Actif')
                    ->addActionLabel('Ajouter un actif')
                    ->collapsed()
                    ->reorderable()
                    ->defaultItems(0),
            ]);
    }

    private static function stock(): Section
    {
        return Section::make('Stock')
            ->columns(2)
            ->schema([
                TextInput::make('stock')
                    ->label('Stock')
                    ->numeric()->required()->minValue(0)
                    ->helperText('Toute modification est enregistrée comme mouvement de stock.'),

                TextInput::make('low_stock_threshold')
                    ->label("Seuil d'alerte")
                    ->numeric()->required()->minValue(0),
            ]);
    }

    private static function visibility(): Section
    {
        return Section::make('Visibilité')
            ->schema([
                Toggle::make('is_active')
                    ->label('Visible sur le site')
                    ->default(true)
                    ->helperText('Désactivé : le produit disparaît de la boutique et des recherches.'),

                Toggle::make('is_featured')->label('Mis en avant'),
            ]);
    }

    private static function stats(): Section
    {
        return Section::make('Avis')
            ->columns(2)
            // Recomputed from approved reviews when one is moderated, so it is
            // shown but never typed.
            ->schema([
                Placeholder::make('rating_avg')
                    ->label('Note moyenne')
                    ->content(fn (?Product $record) => $record && $record->reviews_count > 0
                        ? $record->rating_avg.' / 5'
                        : '—'),

                Placeholder::make('reviews_count')
                    ->label('Avis publiés')
                    ->content(fn (?Product $record) => (string) ($record?->reviews_count ?? 0)),
            ])
            ->visibleOn('edit');
    }

    /** @return array<int, string> */
    private static function distinct(string $column): array
    {
        return Product::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }
}
