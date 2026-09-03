<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Seo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Site-wide SEO defaults.
 *
 * Every public page falls back to these, so a page nobody has written meta for
 * still has a sensible title and description. Per-page overrides live on the
 * row itself, under the "Référencement" section of each resource's form.
 */
class SeoSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'Contenu';

    protected static ?string $navigationLabel = 'Référencement (SEO)';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.seo-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function getTitle(): string
    {
        return 'Référencement (SEO)';
    }

    public function mount(): void
    {
        $this->form->fill([
            'seo_site_name' => Setting::get('seo_site_name'),
            'seo_title_suffix' => Setting::get('seo_title_suffix'),
            'seo_default_title' => Setting::get('seo_default_title'),
            'seo_default_description' => Setting::get('seo_default_description'),
            'seo_default_image' => Setting::get('seo_default_image'),
            'seo_indexable' => (bool) Setting::get('seo_indexable', true),
            'seo_google_verification' => Setting::get('seo_google_verification'),
            'seo_twitter_handle' => Setting::get('seo_twitter_handle'),
            'seo_social_profiles' => Setting::get('seo_social_profiles'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Indexation')
                    ->schema([
                        Toggle::make('seo_indexable')
                            ->label('Autoriser l\'indexation par les moteurs de recherche')
                            ->helperText('À désactiver sur une copie de test : une préproduction indexée concurrence la vraie boutique sur ses propres mots-clés. Désactivé, tout le site passe en noindex et robots.txt interdit l\'exploration.'),
                    ]),

                Section::make('Titres et descriptions par défaut')
                    ->description('Utilisés quand une page ne définit rien de plus précis.')
                    ->schema([
                        TextInput::make('seo_site_name')
                            ->label('Nom du site')
                            ->maxLength(120),
                        TextInput::make('seo_title_suffix')
                            ->label('Suffixe des titres')
                            ->helperText('Ajouté après le titre de chaque page, sauf si le titre le contient déjà.')
                            ->maxLength(120),
                        TextInput::make('seo_default_title')
                            ->label('Titre par défaut')
                            ->maxLength(Seo::TITLE_LIMIT + 20)
                            ->helperText('Google en affiche environ '.Seo::TITLE_LIMIT.' caractères.'),
                        Textarea::make('seo_default_description')
                            ->label('Description par défaut')
                            ->rows(3)
                            ->maxLength(300)
                            ->helperText('Google en affiche environ '.Seo::DESCRIPTION_LIMIT.' caractères ; au-delà, le texte est coupé.'),
                        FileUpload::make('seo_default_image')
                            ->label('Image de partage')
                            ->helperText('Utilisée par Facebook, WhatsApp et X quand la page n\'a pas d\'image à elle. 1200 × 630 px : c\'est le format que ces réseaux recadrent le moins.')
                            ->image()
                            ->imageEditor()
                            ->imagePreviewHeight('140')
                            ->disk('public_files')
                            ->directory('uploads/seo')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            // Same reason as the gallery uploader: the disk builds
                            // preview URLs by concatenation, so paths with spaces
                            // or accents 404 in the editor unless built like the
                            // storefront builds them.
                            ->getUploadedFileUsing(function (string $file): ?array {
                                $absolute = public_path($file);

                                if (! is_file($absolute)) {
                                    return null;
                                }

                                return [
                                    'name' => basename($file),
                                    'size' => filesize($absolute) ?: 0,
                                    'type' => 'image/'.strtolower(pathinfo($file, PATHINFO_EXTENSION)),
                                    'url' => image_url($file),
                                ];
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Vérification et réseaux')
                    ->schema([
                        TextInput::make('seo_google_verification')
                            ->label('Code de vérification Google Search Console')
                            ->helperText('Seulement le code, pas la balise complète.')
                            ->maxLength(255),
                        TextInput::make('seo_twitter_handle')
                            ->label('Compte X / Twitter')
                            ->placeholder('@skinchemists')
                            ->maxLength(60),
                        Textarea::make('seo_social_profiles')
                            ->label('Profils sociaux')
                            ->rows(4)
                            ->helperText('Une URL par ligne. Transmises à Google pour rattacher ces comptes à la marque.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Setting::put($key, is_bool($value) ? (int) $value : ($value ?: null));
        }

        Notification::make()->title('Référencement enregistré')->success()->send();
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Enregistrer')->action('save'),
        ];
    }
}
