<?php

namespace Tests\Feature;

use App\Filament\Pages\SeoSettings;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function product(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['name' => 'Test']);

        return Product::create(array_merge([
            'sku' => 'SC-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'Sérum de test',
            'slug' => 'serum-de-test',
            'category_id' => $category->id,
            'short' => 'Un sérum concentré pour le test.',
            'price_cents' => 50000,
            'stock' => 5,
        ], $attributes));
    }

    public function test_every_public_page_carries_a_full_set_of_meta(): void
    {
        $html = $this->get('/')->assertSuccessful()->getContent();

        foreach ([
            '<title>',
            '<meta name="description"',
            '<meta name="robots"',
            '<link rel="canonical"',
            '<meta property="og:title"',
            '<meta property="og:description"',
            '<meta property="og:image"',
            '<meta property="og:url"',
            '<meta name="twitter:card"',
            '"@type":"Organization"',
        ] as $tag) {
            $this->assertStringContainsString($tag, $html, "Balise absente : {$tag}");
        }
    }

    public function test_a_page_falls_back_to_the_site_defaults(): void
    {
        Setting::put('seo_default_description', 'La description de repli du site.');

        // /panier sets a title but no description of its own.
        $this->get('/panier')
            ->assertSuccessful()
            ->assertSee('La description de repli du site.', false);
    }

    public function test_a_row_can_override_its_own_title_and_description(): void
    {
        $this->product([
            'meta_title' => 'Titre choisi à la main',
            'meta_description' => 'Description choisie à la main.',
        ]);

        $head = $this->metaHead('/produit/serum-de-test');

        $this->assertStringContainsString('Titre choisi à la main', $head);
        $this->assertStringContainsString('Description choisie à la main.', $head);
        // Scoped to the head: the short copy still describes the product in the
        // page body, it just must not be what the meta description says.
        $this->assertStringNotContainsString('Un sérum concentré pour le test.', $head);
    }

    /** The rendered <head>, so meta assertions cannot be satisfied by body copy. */
    private function metaHead(string $url): string
    {
        preg_match('#<head>.*?</head>#s', $this->get($url)->assertSuccessful()->getContent(), $m);

        $this->assertNotEmpty($m, "Pas de <head> sur {$url}");

        return $m[0];
    }

    public function test_a_row_without_an_override_describes_itself(): void
    {
        $this->product();

        $head = $this->metaHead('/produit/serum-de-test');

        $this->assertStringContainsString('Sérum de test', $head);
        $this->assertStringContainsString('Un sérum concentré pour le test.', $head);
    }

    /** The brand belongs in the title once, not twice. */
    public function test_the_suffix_is_not_repeated_when_the_title_already_has_it(): void
    {
        $html = $this->get('/boutique')->assertSuccessful()->getContent();

        preg_match('#<title>(.*?)</title>#s', $html, $m);

        $this->assertSame(1, substr_count($m[1], 'SkinChemists Maroc'), "Titre : {$m[1]}");
    }

    /**
     * The switch that matters. A staging copy Google indexes competes with the
     * real shop for its own rankings, so it has to reach both the page and the
     * crawler — a meta tag alone only helps once the page has been fetched.
     */
    public function test_turning_indexing_off_blocks_both_the_page_and_the_crawler(): void
    {
        Setting::put('seo_indexable', 0);

        $this->get('/')->assertSee('noindex, nofollow', false);
        $this->get('/robots.txt')
            ->assertSuccessful()
            ->assertSee('Disallow: /')
            ->assertDontSee('Sitemap:');
    }

    public function test_indexing_on_points_crawlers_at_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertSuccessful()
            ->assertSee('Sitemap: '.route('sitemap'))
            ->assertSee('Disallow: /admin');
    }

    public function test_the_settings_screen_stores_the_site_wide_defaults(): void
    {
        Livewire::actingAs(User::factory()->create(['is_admin' => true]))
            ->test(SeoSettings::class)
            ->fillForm([
                'seo_site_name' => 'Boutique Test',
                'seo_title_suffix' => 'Boutique Test',
                'seo_default_description' => 'Une description par défaut.',
                'seo_indexable' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Boutique Test', Setting::get('seo_site_name'));
        $this->assertSame('0', (string) Setting::get('seo_indexable'));
    }

    /**
     * @section('x', $maybeNull) is a trap: Blade reads a null second argument
     * as the block form and calls ob_start() (ManagesLayouts::startSection),
     * so the page leaks an output buffer nobody ever closes. articles.image_path
     * is nullable, and passing it straight to @section did exactly that.
     */
    public function test_an_article_without_an_image_leaks_no_output_buffer(): void
    {
        Article::create([
            'title' => 'Article sans image',
            'slug' => 'article-sans-image',
            'category' => 'Routine',
            'author' => 'Le laboratoire',
            'excerpt' => 'Un article de test.',
            'body' => [['h' => 'Titre', 'p' => 'Corps.']],
            'image_path' => null,
            'published_at' => now()->subDay(),
        ]);

        $before = ob_get_level();

        $this->get('/le-lab/article-sans-image')->assertSuccessful();

        $this->assertSame($before, ob_get_level(), 'Un tampon de sortie est resté ouvert.');
    }

    public function test_the_settings_screen_loads(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/seo-settings')
            ->assertSuccessful()
            ->assertSee('Suffixe des titres');
    }
}
