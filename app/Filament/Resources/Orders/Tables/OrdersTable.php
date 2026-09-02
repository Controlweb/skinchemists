<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Actions\CancelOrder;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Throwable;

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
                // Nine columns needed ~890px on a 390px phone, so staff had to
                // swipe sideways to reach the total and the status - the two
                // things they open this screen for. Split stacks the row into a
                // card below md and behaves as a normal row from md up.
                Split::make([
                    TextColumn::make('number')
                        ->label('N°')
                        ->description(fn (Order $record) => $record->created_at->format('d/m/Y H:i'))
                        ->searchable()
                        ->sortable()
                        ->weight('medium')
                        ->grow(false),

                    TextColumn::make('customer')
                        ->label('Client')
                        ->state(fn (Order $record) => $record->customerName())
                        ->description(fn (Order $record) => $record->phone.' · '.$record->city)
                        ->searchable(['first_name', 'last_name', 'phone', 'city']),

                    TextColumn::make('items_count')
                        ->label('Articles')
                        ->counts('items')
                        ->grow(false)
                        ->visibleFrom('md'),

                    TextColumn::make('total_cents')
                        ->label('Total')
                        ->formatStateUsing(fn (int $state) => mad($state))
                        ->sortable()
                        ->weight('medium')
                        ->grow(false),

                    TextColumn::make('status')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                        ->color(fn (string $state) => self::STATUS_COLORS[$state] ?? 'gray')
                        ->grow(false),

                    TextColumn::make('payment_status')
                        ->label('Paiement')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => $state === 'paye' ? 'Payé' : 'En attente')
                        ->color(fn (string $state) => $state === 'paye' ? 'success' : 'gray')
                        ->grow(false)
                        ->visibleFrom('md'),
                ])->from('md'),
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

                    Action::make('resendConfirmation')
                        ->label('Renvoyer la confirmation')
                        ->icon('heroicon-m-envelope')
                        ->visible(fn (Order $record) => filled($record->email))
                        ->schema([
                            TextInput::make('email')
                                ->label('Adresse de destination')
                                ->email()
                                ->required()
                                ->default(fn (Order $record) => $record->email)
                                ->helperText('Corrigez ici si le client a mal saisi son email.'),
                        ])
                        ->action(function (Order $record, array $data) {
                            // Persist a corrected address, otherwise the next
                            // resend repeats the same typo.
                            if ($data['email'] !== $record->email) {
                                $record->update(['email' => $data['email']]);
                            }

                            try {
                                Mail::to($data['email'])->send(new OrderConfirmation($record));
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title("Envoi impossible : {$e->getMessage()}")
                                    ->danger()->send();

                                return;
                            }

                            $record->recordEvent(
                                'Confirmation renvoyée à '.$data['email'],
                                auth()->user()?->name ?? 'Administration',
                                auth()->id(),
                            );

                            Notification::make()->title('Confirmation renvoyée')->success()->send();
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
