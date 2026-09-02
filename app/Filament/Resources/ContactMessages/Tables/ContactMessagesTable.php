<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                // Stacks into a card below md; a normal row from md up.
                Split::make([
                    Stack::make([
                        TextColumn::make('name')
                            ->label('Expéditeur')
                            ->weight('medium')
                            ->searchable(['name', 'email', 'phone']),

                        TextColumn::make('contact')
                            ->state(fn (ContactMessage $record) => $record->email ?: $record->phone)
                            ->color('gray')
                            ->size('xs'),

                        TextColumn::make('created_at')->since()->color('gray')->size('xs'),
                    ]),

                    TextColumn::make('subject')
                        ->label('Sujet')
                        ->badge()
                        ->formatStateUsing(fn (ContactMessage $record) => $record->subjectLabel())
                        ->grow(false),

                    TextColumn::make('order_number')
                        ->label('Commande')
                        ->placeholder('—')
                        ->searchable()
                        ->grow(false)
                        ->visibleFrom('lg'),

                    TextColumn::make('message')
                        ->label('Message')
                        ->wrap()
                        ->limit(90)
                        ->searchable()
                        ->visibleFrom('md'),

                    TextColumn::make('status')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ContactMessage::STATUSES[$state] ?? $state)
                        ->color(fn (string $state) => $state === 'traite' ? 'success' : 'warning')
                        ->grow(false),
                ])->from('md'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(ContactMessage::STATUSES),
                SelectFilter::make('subject')->label('Sujet')->options(ContactMessage::SUBJECTS),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('markHandled')
                    ->label('Marquer traité')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (ContactMessage $record) => ! $record->isHandled())
                    ->action(function (ContactMessage $record) {
                        $record->update([
                            'status' => 'traite',
                            'handled_at' => now(),
                            'handled_by' => auth()->id(),
                        ]);

                        Notification::make()->title('Message marqué traité')->success()->send();
                    }),

                Action::make('reopen')
                    ->label('Rouvrir')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->visible(fn (ContactMessage $record) => $record->isHandled())
                    ->action(fn (ContactMessage $record) => $record->update([
                        'status' => 'nouveau',
                        'handled_at' => null,
                        'handled_by' => null,
                    ])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markHandledBulk')
                        ->label('Marquer traités')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update([
                            'status' => 'traite',
                            'handled_at' => now(),
                            'handled_by' => auth()->id(),
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
