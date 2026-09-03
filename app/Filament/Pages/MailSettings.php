<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;
use UnitEnum;

/**
 * SMTP credentials for the order confirmations and contact-form notifications.
 *
 * These live in the database rather than .env because the person who needs to
 * change them cannot deploy. A blank host hands control back to .env, so an
 * environment that prefers MAIL_ variables keeps working untouched.
 */
class MailSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Contenu';

    protected static ?string $navigationLabel = 'Envoi des emails';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.mail-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function getTitle(): string
    {
        return 'Envoi des emails (SMTP)';
    }

    public function mount(): void
    {
        $this->form->fill([
            'mail_host' => Setting::get('mail_host'),
            'mail_port' => Setting::get('mail_port') ?: 587,
            'mail_scheme' => Setting::get('mail_scheme') ?: '',
            'mail_username' => Setting::get('mail_username'),
            'mail_password' => Setting::secret('mail_password'),
            'mail_from_address' => Setting::get('mail_from_address'),
            'mail_from_name' => Setting::get('mail_from_name'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Serveur')
                    ->description('Laissez l\'hôte vide pour utiliser la configuration du fichier .env.')
                    ->schema([
                        TextInput::make('mail_host')
                            ->label('Hôte SMTP')
                            ->placeholder('smtp.exemple.com')
                            ->maxLength(255),
                        TextInput::make('mail_port')
                            ->label('Port')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(65535)
                            ->default(587)
                            ->required(fn (callable $get) => filled($get('mail_host'))),
                        Select::make('mail_scheme')
                            ->label('Chiffrement')
                            ->options([
                                '' => 'Automatique (selon le port)',
                                'smtp' => 'STARTTLS — port 587',
                                'smtps' => 'SSL/TLS — port 465',
                            ])
                            ->default('')
                            ->selectablePlaceholder(false)
                            ->helperText('En automatique, le port 465 utilise SSL/TLS et tout autre port STARTTLS.'),
                        TextInput::make('mail_username')
                            ->label('Identifiant')
                            ->maxLength(255)
                            ->autocomplete('off'),
                        TextInput::make('mail_password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->maxLength(255)
                            ->helperText('Stocké chiffré. Videz le champ pour supprimer le mot de passe enregistré.'),
                    ])
                    ->columns(2),

                Section::make('Expéditeur')
                    ->schema([
                        TextInput::make('mail_from_address')
                            ->label('Adresse d\'expédition')
                            ->email()
                            ->placeholder('contact@skinchemists.ma')
                            ->maxLength(255),
                        TextInput::make('mail_from_name')
                            ->label('Nom affiché')
                            ->placeholder('skinChemists Maroc')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::put('mail_host', $data['mail_host'] ?: null);
        Setting::put('mail_port', $data['mail_port'] ?: null);
        Setting::put('mail_scheme', $data['mail_scheme'] ?: null);
        Setting::put('mail_username', $data['mail_username'] ?: null);
        Setting::putSecret('mail_password', $data['mail_password']);
        Setting::put('mail_from_address', $data['mail_from_address'] ?: null);
        Setting::put('mail_from_name', $data['mail_from_name'] ?: null);

        Notification::make()->title('Configuration enregistrée')->success()->send();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer')
                ->action('save'),

            // SMTP that is wrong fails at the worst moment — on a customer's
            // order confirmation, silently, hours later. One button that
            // actually opens the connection is worth more than the whole form.
            Action::make('sendTest')
                ->label('Envoyer un email de test')
                ->color('gray')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->form([
                    TextInput::make('to')
                        ->label('Destinataire')
                        ->email()
                        ->required()
                        ->default(fn () => auth()->user()?->email),
                ])
                ->action(fn (array $data) => $this->sendTest($data['to'])),
        ];
    }

    /** Saves first, so the button tests what is on screen, not what was stored. */
    protected function sendTest(string $to): void
    {
        $this->save();

        try {
            Mail::raw(
                "Cet email confirme que la configuration SMTP de skinChemists Maroc fonctionne.",
                fn ($message) => $message->to($to)->subject('Test de configuration SMTP')
            );
        } catch (Throwable $e) {
            Notification::make()
                ->title('Échec de l\'envoi')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Email de test envoyé à '.$to)
            ->success()
            ->send();
    }
}
