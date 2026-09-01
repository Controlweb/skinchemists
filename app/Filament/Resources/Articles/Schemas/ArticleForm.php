<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Article')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Titre')
                        ->required()
                        ->columnSpanFull()
                        ->live(onBlur: true)
                        // Only fill the slug while drafting: changing it on a
                        // published article breaks every link already shared.
                        ->afterStateUpdated(fn ($state, $set, $get) => filled($get('published_at'))
                            ? null
                            : $set('slug', Str::slug($state))),

                    TextInput::make('slug')
                        ->label('Slug (URL)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Modifier ce champ casse les liens déjà partagés.'),

                    TextInput::make('category')
                        ->label('Catégorie')
                        ->required()
                        ->datalist(['Actifs', 'Routines', 'Climat', 'Conseils']),

                    TextInput::make('author')->label('Auteur')->required(),

                    TextInput::make('read_minutes')
                        ->label('Durée de lecture (min)')
                        ->numeric()->required()->minValue(1)->default(5),
                ]),

            Section::make('Contenu')
                ->schema([
                    Textarea::make('excerpt')->label('Accroche (listes)')->rows(2),
                    Textarea::make('lead')->label('Chapô')->rows(3),

                    Repeater::make('body')
                        ->label('Sections')
                        ->schema([
                            TextInput::make('h')->label('Titre de section')->required(),
                            Textarea::make('p')->label('Paragraphe')->rows(4)->required(),
                        ])
                        ->itemLabel(fn (array $state) => $state['h'] ?? 'Section')
                        ->collapsible()
                        ->reorderable()
                        ->defaultItems(1),
                ]),

            Section::make('Publication')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('published_at')
                        ->label('Publié le')
                        ->seconds(false)
                        ->helperText('Vide = brouillon. Une date future programme la publication.'),

                    Toggle::make('is_featured')->label('À la une'),

                    Select::make('products')
                        ->label('Produits cités')
                        ->relationship('products', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
