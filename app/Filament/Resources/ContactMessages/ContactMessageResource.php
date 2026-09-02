<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Resources\ContactMessages\Tables\ContactMessagesTable;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Messages';

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?string $modelLabel = 'message';

    protected static ?string $pluralModelLabel = 'messages';

    protected static ?int $navigationSort = 3;

    /** Messages arrive from the contact form; staff read and answer them. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $new = static::getModel()::where('status', 'nouveau')->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Expéditeur')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->label('Nom'),
                    TextEntry::make('email')->label('Email')->placeholder('—')->copyable(),
                    TextEntry::make('phone')->label('Téléphone')->placeholder('—')->copyable(),
                    TextEntry::make('subject')->label('Sujet')
                        ->state(fn (ContactMessage $record) => $record->subjectLabel()),
                    TextEntry::make('order_number')
                        ->label('Commande')
                        ->placeholder('—')
                        // Flags a number that matches nothing, which usually
                        // means a typo worth checking before replying.
                        ->hint(fn (ContactMessage $record) => $record->order_number && ! $record->order()
                            ? 'Introuvable'
                            : null)
                        ->hintColor('danger'),
                    TextEntry::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i'),
                ]),

            Section::make('Message')
                ->schema([
                    TextEntry::make('message')->hiddenLabel()->prose(),
                ]),

            Section::make('Traitement')
                ->columns(2)
                ->visible(fn (ContactMessage $record) => $record->isHandled())
                ->schema([
                    TextEntry::make('handler.name')->label('Traité par')->placeholder('—'),
                    TextEntry::make('handled_at')->label('Traité le')->dateTime('d/m/Y H:i'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return ContactMessagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}
