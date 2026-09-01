<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('author')
                    ->required(),
                TextInput::make('rating')
                    ->required()
                    ->numeric(),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('verified')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('en_attente'),
                Toggle::make('featured')
                    ->required(),
            ]);
    }
}
