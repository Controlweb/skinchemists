<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Actions\CancelOrder;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    /** Badge colours mirror the prototype's status palette. */
    private const STATUS_COLORS = [
        'nouvelle' => 'info',
        'confirmee' => 'info',
        'preparation' => 'warning',
        'expediee' => 'success',
        'livree' => 'success',
        'annulee' => 'danger',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('N°')
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('customer')
                    ->label('Client')
                    ->state(fn (Order $record) => $record->customerName())
                    ->description(fn (Order $record) => $record->phone)
                    ->searchable(['first_name', 'last_name', 'phone']),

                TextColumn::make('city')
                    ->label('Ville')
                    ->searchable(),

                TextColumn::make('items_count')
                    ->label('Articles')
                    ->counts('items'),

                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state) => mad($state))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => self::STATUS_COLORS[$state] ?? 'gray'),

                TextColumn::make('payment_status')
                    ->label('Paiement')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'paye' ? 'Payé' : 'En attente')
                    ->color(fn (string $state) => $state === 'paye' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(Order::STATUSES),

                Filter::make('unpaid_delivered')
                    ->label('Livrées non encaissées')
                    ->query(fn (Builder $query) => $query
                        ->where('status', 'livree')
                        ->where('payment_status', '!=', 'paye')),
            ])
            ->recordActions([
                ViewAction::make(),

                ActionGroup::make([
                    Action::make('advance')
                        ->label('Changer le statut')
                        ->icon('heroicon-m-arrow-right-circle')
                        ->visible(fn (Order $record) => ! $record->isCancelled())
                        ->schema([
                            Select::make('status')
                                ->label('Nouveau statut')
                                ->options(collect(Order::STATUSES)->except('annulee'))
                                ->required(),
                            TextInput::make('tracking')
                                ->label('N° de suivi (facultatif)'),
                        ])
                        ->action(function (Order $record, array $data) {
                            $record->update(array_filter([
                                'status' => $data['status'],
                                'tracking' => $data['tracking'] ?: null,
                            ], fn ($v) => $v !== null));

                            // Every status change leaves a timeline entry naming
                            // who did it — that is the whole point of the log.
                            $record->recordEvent(
                                'Statut : '.(Order::STATUSES[$data['status']] ?? $data['status']),
                                auth()->user()?->name ?? 'Administration',
                                auth()->id(),
                            );

                            Notification::make()->title('Statut mis à jour')->success()->send();
                        }),

                    Action::make('markPaid')
                        ->label('Marquer encaissée')
                        ->icon('heroicon-m-banknotes')
                        ->visible(fn (Order $record) => $record->payment_status !== 'paye' && ! $record->isCancelled())
                        ->requiresConfirmation()
                        ->action(function (Order $record) {
                            $record->update(['payment_status' => 'paye']);
                            $record->recordEvent(
                                'Paiement encaissé à la livraison',
                                auth()->user()?->name ?? 'Administration',
                                auth()->id(),
                            );

                            Notification::make()->title('Commande encaissée')->success()->send();
                        }),

                    Action::make('cancel')
                        ->label('Annuler et remettre en stock')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn (Order $record) => ! $record->isCancelled())
                        ->requiresConfirmation()
                        ->modalDescription('Les articles seront remis en stock et un mouvement sera enregistré.')
                        ->action(function (Order $record) {
                            app(CancelOrder::class)->handle(
                                $record,
                                auth()->user()?->name ?? 'Administration',
                                auth()->id(),
                            );

                            Notification::make()->title('Commande annulée, stock restitué')->success()->send();
                        }),
                ]),
            ])
            // Orders are financial records. They are cancelled, never deleted.
            ->toolbarActions([]);
    }
}
