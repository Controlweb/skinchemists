<?php

namespace Tests\Feature;

use App\Filament\Pages\MailSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\MailConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_the_screen_loads(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/mail-settings')
            ->assertSuccessful()
            ->assertSee('Hôte SMTP')
            ->assertSee('Chiffrement');
    }

    public function test_the_form_stores_the_smtp_settings(): void
    {
        Livewire::actingAs($this->admin())
            ->test(MailSettings::class)
            ->fillForm([
                'mail_host' => 'smtp.exemple.com',
                'mail_port' => 587,
                'mail_scheme' => 'smtp',
                'mail_username' => 'boutique@skinchemists.ma',
                'mail_password' => 'un-mot-de-passe',
                'mail_from_address' => 'contact@skinchemists.ma',
                'mail_from_name' => 'skinChemists Maroc',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('smtp.exemple.com', Setting::get('mail_host'));
        $this->assertSame('587', (string) Setting::get('mail_port'));
        $this->assertSame('un-mot-de-passe', Setting::secret('mail_password'));
    }

    /** A stolen database dump must not hand over the mailbox. */
    public function test_the_smtp_password_is_encrypted_at_rest(): void
    {
        Setting::putSecret('mail_password', 'un-mot-de-passe');

        $stored = DB::table('settings')->where('key', 'mail_password')->value('value');

        $this->assertNotSame('un-mot-de-passe', $stored);
        $this->assertStringNotContainsString('un-mot-de-passe', $stored);
        $this->assertSame('un-mot-de-passe', Setting::secret('mail_password'));
    }

    /**
     * A rotated APP_KEY leaves ciphertext that no longer decrypts. That must
     * read as "no password set" and send the admin back to this screen, not
     * throw out of every page that happens to touch mail config.
     */
    public function test_an_undecryptable_password_reads_as_unset(): void
    {
        Setting::put('mail_password', 'pas-du-tout-un-chiffrement-valide');

        $this->assertNull(Setting::secret('mail_password'));
    }

    public function test_the_stored_settings_override_the_mail_config(): void
    {
        Setting::put('mail_host', 'smtp.exemple.com');
        Setting::put('mail_port', 465);
        Setting::putSecret('mail_password', 'un-mot-de-passe');
        Setting::put('mail_from_address', 'contact@skinchemists.ma');

        MailConfig::apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.exemple.com', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('un-mot-de-passe', config('mail.mailers.smtp.password'));
        $this->assertSame('contact@skinchemists.ma', config('mail.from.address'));
    }

    /** Blank host = the admin never filled the form in; .env stays in charge. */
    public function test_no_stored_host_leaves_the_env_configuration_alone(): void
    {
        config(['mail.default' => 'log', 'mail.mailers.smtp.host' => 'valeur-du-env']);

        MailConfig::apply();

        $this->assertSame('log', config('mail.default'));
        $this->assertSame('valeur-du-env', config('mail.mailers.smtp.host'));
    }

    /**
     * Left empty, the scheme must stay null so Laravel infers smtps for 465
     * and smtp otherwise — writing '' would hand Symfony an invalid scheme.
     */
    public function test_an_empty_scheme_is_stored_as_null(): void
    {
        Setting::put('mail_host', 'smtp.exemple.com');
        Setting::put('mail_scheme', '');

        MailConfig::apply();

        $this->assertNull(config('mail.mailers.smtp.scheme'));
    }

    /**
     * The encrypted round-trip must be byte-faithful. An SMTP password is
     * rejected whole: a single character altered in storage is indistinguishable
     * from the wrong password, and sends you looking in the wrong place.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('awkwardPasswords')]
    public function test_a_password_survives_storage_unchanged(string $password): void
    {
        Setting::putSecret('mail_password', $password);

        $this->assertSame($password, Setting::secret('mail_password'));

        MailConfig::apply();
        Setting::put('mail_host', 'smtp.exemple.com');
        MailConfig::apply();

        $this->assertSame($password, config('mail.mailers.smtp.password'));
    }

    /** @return array<string, array{string}> */
    public static function awkwardPasswords(): array
    {
        return [
            'simple' => ['motdepasse'],
            'espaces au milieu' => ['mot de passe'],
            'caractères spéciaux' => ['p@ss"w0rd#$%&*()_+-=[]{}|;:,.<>?/~`'],
            'apostrophe' => ["l'apostrophe"],
            'antislash' => ['a\\b\\c'],
            'accents' => ['mötdepàssé'],
            'très long' => [str_repeat('x', 200)],
        ];
    }

    /**
     * Asserted against the array transport rather than Mail::fake(), because
     * MailFake::raw() is an empty method — a fake records nothing for a raw
     * send, so faking here would pass whether or not the button worked.
     *
     * No host is configured, so MailConfig leaves the test transport in place
     * and the button does not try to open a socket to a real server.
     */
    public function test_the_test_button_sends_to_the_given_address(): void
    {
        Livewire::actingAs($this->admin())
            ->test(MailSettings::class)
            ->callAction('sendTest', ['to' => 'patron@skinchemists.ma']);

        $messages = Mail::mailer()->getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);
        $this->assertSame(
            'patron@skinchemists.ma',
            $messages[0]->getOriginalMessage()->getTo()[0]->getAddress()
        );
    }
}
