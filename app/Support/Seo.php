<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Resolves the meta for the page being rendered.
 *
 * Three layers, most specific first: what the view passed, then the row's own
 * meta_title / meta_description override, then the site-wide defaults from the
 * settings screen. Every page therefore has a title and a description even if
 * nobody ever opens the SEO screen, and a single row can be corrected without
 * touching a template.
 */
class Seo
{
    /** Google truncates around here; longer is not penalised, just unread. */
    public const TITLE_LIMIT = 60;

    public const DESCRIPTION_LIMIT = 160;

    /**
     * @param  array<string, mixed>  $page  Values yielded by the view.
     * @return array<string, mixed>
     */
    public static function resolve(array $page = []): array
    {
        $siteName = Setting::get('seo_site_name') ?: Setting::get('store_name') ?: config('app.name');
        $suffix = Setting::get('seo_title_suffix') ?: $siteName;

        $title = static::clean($page['title'] ?? null) ?: (Setting::get('seo_default_title') ?: $siteName);

        // A page that already names the brand must not name it twice.
        if (filled($suffix) && ! Str::contains(Str::lower($title), Str::lower($suffix))) {
            $title = $title.' — '.$suffix;
        }

        $description = static::clean($page['description'] ?? null)
            ?: Setting::get('seo_default_description');

        $image = $page['image'] ?? null ?: Setting::get('seo_default_image');

        return [
            'title' => Str::limit($title, 70, ''),
            'description' => $description ? Str::limit($description, static::DESCRIPTION_LIMIT) : null,
            'image' => $image ? static::absolute($image) : null,
            'siteName' => $siteName,
            'type' => $page['type'] ?? 'website',
            'canonical' => $page['canonical'] ?? url()->current(),
            'robots' => static::robots($page),
            'twitterSite' => Setting::get('seo_twitter_handle'),
            'verification' => Setting::get('seo_google_verification'),
            'jsonLd' => $page['jsonLd'] ?? null,
        ];
    }

    /**
     * A staging copy that Google indexes competes with the real shop for its
     * own rankings, so the kill switch is site-wide and beats anything a view
     * asks for. Non-canonical pages (search results, filtered listings) opt out
     * individually.
     */
    public static function robots(array $page = []): string
    {
        if (! static::isIndexable()) {
            return 'noindex, nofollow';
        }

        return $page['robots'] ?? 'index, follow';
    }

    public static function isIndexable(): bool
    {
        return (bool) Setting::get('seo_indexable', true);
    }

    /** Strips tags and collapses whitespace: descriptions are often built from HTML. */
    public static function clean(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Str::squish(strip_tags($value)) ?: null;
    }

    public static function absolute(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://']) ? $path : asset($path);
    }

    /**
     * Organization schema, emitted on every page. It is what lets Google
     * associate the phone number, logo and social profiles with the brand
     * rather than treating each page as an unrelated document.
     *
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        $profiles = collect(preg_split('/\R+/', (string) Setting::get('seo_social_profiles')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => Setting::get('seo_site_name') ?: Setting::get('store_name'),
            'url' => url('/'),
            'logo' => static::absolute(Setting::get('seo_default_image')),
            'email' => Setting::get('store_email'),
            'telephone' => Setting::get('store_phone'),
            'sameAs' => $profiles ?: null,
        ]);
    }
}
