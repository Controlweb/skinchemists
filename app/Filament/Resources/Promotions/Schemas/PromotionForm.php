<?php

namespace App\Filament\Resources\Promotions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Code promo')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        // Matching is case-insensitive at checkout; storing it
                        // uppercase keeps the admin list readable.
                        ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state)))
                        ->helperText('Saisi par le client au moment de la commande.'),

                    TextInput::make('name')->label('Nom interne')->required(),

                    Select::make('type')
                        ->label('Type de remise')
                        ->required()
                        ->live()
                        ->options([
                            'percent' => 'Pourcentage du sous-total',
                            'fixed' => 'Montant fixe',
                            'free_shipping' => 'Livraison offerte',
                        ]),

                    TextInput::make('value')
                        ->label(fn (Get $get) => match ($get('type')) {
                            'percent' => 'Remise (%)',
                            'fixed' => 'Remise (MAD)',
                            default => 'Valeur',
                        })
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(fn (Get $get) => $get('type') === 'percent' ? 90 : null)
                        ->visible(fn (Get $get) => in_array($get('type'), ['percent', 'fixed'], true))
                        ->required(fn (Get $get) => in_array($get('type'), ['percent', 'fixed'], true))
                        // Percent is stored as a whole number, fixed in centimes.
                        ->formatStateUsing(fn ($state, Get $get) => $get('type') === 'fixed' && $state !== null
                            ? $state / 100
                            : $state)
                        ->dehydrateStateUsing(fn ($state, Get $get) => $get('type') === 'fixed'
                            ? (int) round((float) $state * 100)
                            : (int) $state),

                    TextInput::make('min_subtotal_cents')
                        ->label('Sous-total minimum (MAD)')
                        ->numeric()->minValue(0)->default(0)
                        ->helperText('0 = sans minimum.')
                        ->formatStateUsing(fn (?int $state) => ($state ?? 0) / 100)
                        ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),
                ]),

            Section::make('Validité')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('starts_at')->label('Début')->seconds(false)
                        ->helperText('Vide = actif immédiatement.'),
                    DateTimePicker::make('ends_at')->label('Fin')->seconds(false)
                        ->helperText('Vide = sans date de fin.')
                        ->after('starts_at'),

                    TextInput::make('max_uses')
                        ->label('Utilisations maximum')
                        ->numeric()->minValue(1)
                        ->helperText('Vide = illimité.'),

                    TextInput::make('uses')
                        ->label('Utilisations')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Incrémenté automatiquement à chaque commande.'),

                    Toggle::make('is_active')->label('Actif')->default(true),
                ]),
        ]);
    }
}
