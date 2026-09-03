<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

/**
 * Bridges the SMTP settings stored in the database into Laravel's mail config.
 *
 * The settings screen writes rows; nothing reads config/mail.php from the
 * database on its own, so without this the admin could fill the form in and
 * every mail would still go wherever MAIL_MAILER pointed. Applied when the mail
 * manager is first resolved (see AppServiceProvider) rather than on boot, so a
 * request that sends nothing pays no cache reads for it.
 */
class MailConfig
{
    /** Keys the settings screen owns, with their config paths. */
    public const KEYS = [
        'mail_host',
        'mail_port',
        'mail_scheme',
        'mail_username',
        'mail_password',
        'mail_from_address',
        'mail_from_name',
    ];

    public static function apply(): void
    {
        // No host configured means the admin has never filled the form in.
        // Leave .env in charge rather than half-overriding it.
        if (blank($host = Setting::get('mail_host'))) {
            return;
        }

        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => Setting::int('mail_port', 587),
            'mail.mailers.smtp.username' => Setting::get('mail_username') ?: null,
            'mail.mailers.smtp.password' => Setting::secret('mail_password'),
            // Null lets Laravel infer smtps for port 465 and smtp otherwise.
            'mail.mailers.smtp.scheme' => Setting::get('mail_scheme') ?: null,
        ]);

        if (filled($from = Setting::get('mail_from_address'))) {
            Config::set('mail.from.address', $from);
        }

        if (filled($name = Setting::get('mail_from_name'))) {
            Config::set('mail.from.name', $name);
        }
    }
}
