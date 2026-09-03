<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
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

        $imageSize = static::imageSize($image);

        return [
            // Not truncated. TITLE_LIMIT is what Google *displays*; cutting the
            // tag to it produced "… — SkinChemists Mar" in the share card, which
            // is worse than a title the search page trims itself.
            'title' => $title,
            'description' => $description ? Str::limit($description, static::DESCRIPTION_LIMIT) : null,
            'image' => $image ? static::absolute($image) : null,
            'imageSize' => $imageSize,
            'imageAlt' => $page['imageAlt'] ?? null ?: $title,
            'twitterCard' => static::cardType($image, $imageSize),
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

    /**
     * summary_large_image is a promise about the image. Claim it for an image
     * too small to fill the card and the network either letterboxes it or drops
     * the preview entirely, so a shop whose only share image is a 280px logo is
     * better served by the small card. Unmeasurable (remote) images are trusted.
     *
     * @param  array{width: int, height: int}|null  $size
     */
    public static function cardType(?string $image, ?array $size): string
    {
        if (blank($image)) {
            return 'summary';
        }

        // Twitter's documented floor for the large card is 300×157.
        return ($size === null || ($size['width'] >= 300 && $size['height'] >= 157))
            ? 'summary_large_image'
            : 'summary';
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

        // image_url(), not asset(): stored paths are decoded, and the upload
        // folders contain spaces and accents that asset() would leave raw.
        return Str::startsWith($path, ['http://', 'https://']) ? $path : image_url($path);
    }

    /**
     * Dimensions for the share image.
     *
     * Facebook, WhatsApp and LinkedIn all render a link better when the size is
     * declared: without it the first share shows no preview while the crawler
     * fetches the file, and only later shares get the card. Only local files can
     * be measured, so a remote URL simply omits the two tags.
     *
     * @return array{width: int, height: int}|null
     */
    public static function imageSize(?string $path): ?array
    {
        if (blank($path)) {
            return null;
        }

        // Product images arrive as absolute URLs from primaryImageUrl(). They
        // are still our own files, and product pages are the ones people share,
        // so map them back to disk instead of giving up on the dimensions.
        if (Str::startsWith($path, ['http://', 'https://'])) {
            $parts = parse_url($path);

            if (($parts['host'] ?? null) !== request()->getHost()) {
                return null;
            }

            $path = rawurldecode(ltrim($parts['path'] ?? '', '/'));
        }

        $absolute = public_path($path);

        if (! is_file($absolute)) {
            return null;
        }

        // Keyed on mtime so replacing the file re-measures it.
        return Cache::remember(
            'seo:image-size:'.md5($path).':'.filemtime($absolute),
            now()->addMonth(),
            function () use ($absolute): ?array {
                $size = @getimagesize($absolute);

                return $size ? ['width' => $size[0], 'height' => $size[1]] : null;
            }
        );
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
