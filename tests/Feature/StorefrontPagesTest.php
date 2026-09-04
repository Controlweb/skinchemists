<?php

namespace Tests\Feature;

use App\Actions\PlaceOrder;
use App\Models\Article;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function product(string $ingredient = 'Caviar'): Product
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['name' => 'Test']);

        return Product::create([
            'sku' => 'SC-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'Soin de test',
            'slug' => 'soin-'.uniqid(),
            'category_id' => $category->id,
            'ingredient' => $ingredient,
            'price_cents' => 50000,
            'stock' => 5,
        ]);
    }

    /**
     * .sc-mobile-only forces `display: block !important` at mobile widths.
     * Alpine's x-show hides an element with an inline `display: none`, which
     * !important beats — so combining them pins the panel permanently open.
     * That is exactly how the mobile menu shipped stuck open once already.
     * State-driven elements use .sc-mobile-panel, which only ever hides.
     */
    public function test_no_element_combines_x_show_with_a_forced_display_class(): void
    {
        $html = $this->get('/')->assertSuccessful()->getContent();

        preg_match_all('/<[^>]*\bx-show=[^>]*>/', $html, $matches);

        $this->assertNotEmpty($matches[0], 'Aucun élément x-show trouvé : le test ne vérifie rien.');

        foreach ($matches[0] as $tag) {
            $this->assertStringNotContainsString(
                'sc-mobile-only',
                $tag,
                'Un élément piloté par x-show force son affichage : '.substr($tag, 0, 120)
            );
        }
    }

    /**
     * Laravel's bundled paginator views are Tailwind-only and the storefront
     * loads no Tailwind, so falling back to one leaks the raw
     * "pagination.previous" key and an English "Showing … results" line.
     */
    public function test_the_shop_paginator_is_french(): void
    {
        for ($i = 0; $i < 13; $i++) {
            $this->product();
        }

        $this->get('/boutique')
            ->assertSuccessful()
            ->assertSee('Précédent')
            ->assertSee('Suivant')
            ->assertDontSee('pagination.previous')
            ->assertDontSee('pagination.next')
            ->assertDontSee('Showing')
            ->assertDontSee('results');
    }

    public function test_the_desktop_nav_reads_soins_to_contact(): void
    {
        $html = $this->get('/')->assertSuccessful()->getContent();

        preg_match('#<nav class="sc-desktop-only".*?</nav>#s', $html, $nav);
        $this->assertNotEmpty($nav, 'Le nav desktop est introuvable.');

        preg_match_all('#>([^<>]+)</a>#', $nav[0], $labels);
        $this->assertSame(
            ['Soins', 'Best-sellers', 'Coffrets', 'Le Lab', 'Contact'],
            array_map('trim', $labels[1])
        );
    }

    /**
     * The drawer mirrors the desktop nav: Soins expands into what the mega
     * panel holds, then the same four siblings. It used to carry its own
     * shape — a flat "Tous les soins" plus separate Actifs and Préoccupations
     * toggles — so the two navigations taught different maps of one catalogue.
     */
    public function test_the_mobile_drawer_mirrors_the_desktop_nav(): void
    {
        $html = $this->get('/')->assertSuccessful()->getContent();

        preg_match('#<aside[^>]*>.*?</aside>#s', $html, $drawer);
        $this->assertNotEmpty($drawer, 'Le tiroir mobile est introuvable.');

        foreach (['Soins', 'Best-sellers', 'Coffrets', 'Le Lab', 'Contact', 'Suivre ma commande'] as $label) {
            $this->assertStringContainsString('>'.$label.'<', $drawer[0]);
        }

        // The two lists now live under Soins, labelled as the mega panel does.
        $this->assertStringContainsString('Par actif', $drawer[0]);
        $this->assertStringContainsString('Par préoccupation', $drawer[0]);
        $this->assertStringNotContainsString('>Préoccupations<', $drawer[0]);
    }

    /**
     * Alpine binds a :style *string* with setAttribute('style', …), which
     * replaces the whole attribute rather than merging into it. On an element
     * that also carries a static style, the first evaluation therefore erases
     * it: that is how the Soins nav link lost its colour and fell back to the
     * global blue link rule. The object form sets one property and is safe.
     */
    public function test_no_element_binds_a_style_string_over_a_static_style(): void
    {
        $html = $this->get('/')->assertSuccessful()->getContent();

        preg_match_all('/<[^>]*:style=[^>]*>/', $html, $matches);

        $this->assertNotEmpty($matches[0], 'Aucun :style trouvé : le test ne vérifie rien.');

        foreach ($matches[0] as $tag) {
            if (! str_contains($tag, ' style="')) {
                continue; // Nothing static to clobber; the binding owns the element.
            }

            $this->assertMatchesRegularExpression(
                '/:style="\s*\{/',
                $tag,
                'Ce :style en chaîne écrase le style statique de l\'élément : '.substr($tag, 0, 160)
            );
        }
    }

    /**
     * Alpine reveals an element with style.removeProperty('display'), which
     * deletes the author's own inline display for good — one hide/show cycle
     * and a display:flex row silently becomes a stack. The express delivery
     * option on the checkout lost its layout exactly this way.
     *
     * Sibling of the .sc-mobile-only guard above: same collision between
     * x-show and an author-declared display, from the other direction.
     */
    public function test_no_x_show_element_declares_its_own_inline_display(): void
    {
        $product = $this->product();
        $this->post('/panier', ['product_id' => $product->id, 'quantity' => 1]);

        $pages = ['/', '/boutique', '/commande', '/produit/'.$product->slug];
        $checked = 0;

        foreach ($pages as $page) {
            $html = $this->get($page)->assertSuccessful()->getContent();

            preg_match_all('/<[^>]*\bx-show=[^>]*>/', $html, $matches);

            foreach ($matches[0] as $tag) {
                $checked++;
                $this->assertDoesNotMatchRegularExpression(
                    '/\sstyle="[^"]*\bdisplay\s*:/',
                    $tag,
                    "{$page} : cet élément x-show déclare son propre display, "
                    ."qu'Alpine supprimera en le réaffichant : ".substr($tag, 0, 140)
                );
            }
        }

        $this->assertGreaterThan(0, $checked, 'Aucun élément x-show trouvé : le test ne vérifie rien.');
    }

    public function test_the_bundles_page_loads(): void
    {
        $this->get('/coffrets')->assertSuccessful()->assertSee('Coffrets');
    }

    public function test_the_shop_filters_by_brand_and_by_gamme(): void
    {
        $chemists = $this->product();
        $chemists->update(['brand' => 'skinChemists', 'gamme' => 'Caviar', 'name' => 'Crème Caviar']);

        $drh = $this->product();
        $drh->update(['brand' => 'Dr H', 'gamme' => 'Vitamine C', 'name' => 'Sérum Dr H']);

        $this->get('/boutique?marque=Dr+H')
            ->assertSuccessful()
            ->assertSee('Sérum Dr H')
            ->assertDontSee('Crème Caviar');

        $this->get('/boutique?gamme=Caviar')
            ->assertSuccessful()
            ->assertSee('Crème Caviar')
            ->assertDontSee('Sérum Dr H');
    }

    public function test_the_brand_filter_is_hidden_when_everything_is_one_brand(): void
    {
        $this->product()->update(['brand' => 'skinChemists']);

        // A filter with a single option is noise, not navigation.
        $this->get('/boutique')->assertSuccessful()->assertDontSee('>Marque<', false);
    }

    public function test_a_card_shows_the_gamme_beside_the_brand(): void
    {
        $this->product()->update(['brand' => 'Dr H', 'gamme' => 'Vitamine C']);

        $this->get('/boutique')
            ->assertSuccessful()
            ->assertSee('Dr H')
            ->assertSee('Vitamine C');
    }

    public function test_a_low_stock_product_warns_on_its_card(): void
    {
        $product = $this->product();
        $product->update(['stock' => 3, 'low_stock_threshold' => 5]);

        $this->get('/boutique')
            ->assertSuccessful()
            ->assertSee('Plus que 3 en stock');
    }

    /**
     * The note renders below the button, so on a grid card it would push that
     * card's button out of line with the rest of the row. Cards draw it
     * themselves, under the rating; the button must stay silent by default.
     */
    public function test_the_add_to_cart_button_stays_silent_about_stock_by_default(): void
    {
        $product = $this->product();
        $product->update(['stock' => 2, 'low_stock_threshold' => 5]);

        Livewire::test('add-to-cart', ['product' => $product])
            ->assertDontSee('Plus que');

        Livewire::test('add-to-cart', ['product' => $product, 'withStockNote' => true])
            ->assertSee('Plus que');
    }

    /**
     * An Eloquent model's __toString() is its JSON, so passing one where a
     * string is expected produces a 200 with garbage baked into the markup
     * rather than an exception. That is exactly how the hero images broke:
     * the page looked fine to every status-code check.
     *
     * Checks image URLs specifically. Livewire legitimately serialises
     * component state into wire:snapshot, so scanning the whole document for
     * JSON would flag that instead.
     */
    public function test_no_image_url_contains_a_serialised_model(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);
        $this->seed(\Database\Seeders\EditorialSeeder::class);

        $product = \App\Models\Product::has('images')->first();

        $pages = [
            '/', '/boutique', '/coffrets', '/le-lab', '/panier', '/suivi',
            '/produit/'.$product->slug,
            '/actif/'.\App\Models\Ingredient::first()->slug,
            '/le-lab/'.\App\Models\Article::published()->first()->slug,
        ];

        foreach ($pages as $page) {
            $html = $this->get($page)->assertSuccessful()->getContent();

            preg_match_all("/(?:background-image:url\('|src=\")([^'\"]*)/", $html, $matches);

            foreach ($matches[1] as $url) {
                $this->assertStringNotContainsString(
                    '{',
                    $url,
                    "{$page} rend un modèle sérialisé dans une URL d'image"
                );
            }
        }
    }

    public function test_the_home_hero_points_at_real_image_files(): void
    {
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $html = $this->get('/')->assertSuccessful()->getContent();

        preg_match_all("/height:560px;background-image:url\('([^']+)'/", $html, $matches);

        $this->assertNotEmpty($matches[1], 'Aucune image de hero rendue');

        foreach ($matches[1] as $url) {
            $path = rawurldecode(parse_url($url, PHP_URL_PATH) ?? '');

            $this->assertTrue(
                is_file(public_path($path)),
                "L'image du hero n'existe pas sur le disque : {$path}"
            );
        }
    }

    public function test_an_ingredient_page_lists_its_products(): void
    {
        $product = $this->product('Caviar');

        Ingredient::create([
            'name' => 'Caviar',
            'slug' => 'caviar',
            'intro' => 'Un extrait riche en acides aminés.',
            'benefits' => ['Raffermit'],
        ]);

        $this->get('/actif/caviar')
            ->assertSuccessful()
            ->assertSee('Caviar')
            ->assertSee($product->name);
    }

    public function test_an_unpublished_ingredient_page_is_not_reachable(): void
    {
        Ingredient::create(['name' => 'Secret', 'slug' => 'secret', 'is_published' => false]);

        $this->get('/actif/secret')->assertNotFound();
    }

    public function test_the_lab_lists_published_articles_only(): void
    {
        $live = Article::create([
            'title' => 'Rétinol, par où commencer',
            'slug' => 'retinol-par-ou-commencer',
            'category' => 'Actifs', 'author' => 'Dr. L. Haddad',
            'excerpt' => 'Le protocole en quatre semaines.',
            'published_at' => now()->subDay(),
        ]);

        $scheduled = Article::create([
            'title' => 'Article programmé',
            'slug' => 'article-programme',
            'category' => 'Actifs', 'author' => 'Dr. L. Haddad',
            'excerpt' => 'Pas encore publié.',
            'published_at' => now()->addWeek(),
        ]);

        $this->get('/le-lab')
            ->assertSuccessful()
            ->assertSee($live->title)
            ->assertDontSee($scheduled->title);
    }

    public function test_a_scheduled_article_is_not_readable_yet(): void
    {
        Article::create([
            'title' => 'Article programmé', 'slug' => 'article-programme',
            'category' => 'Actifs', 'author' => 'Dr. L. Haddad',
            'published_at' => now()->addWeek(),
        ]);

        $this->get('/le-lab/article-programme')->assertNotFound();
    }

    /**
     * The Lab, the featured block and the article page have always rendered
     * this image, but nothing in the admin could set it — every article fell
     * back to the "[ visuel éditorial ]" placeholder. Asserted from the
     * storefront rather than the form, because that is where it was missing.
     */
    public function test_an_article_image_replaces_the_placeholder_on_the_lab(): void
    {
        $article = Article::create([
            'title' => 'Un article illustré', 'slug' => 'un-article-illustre',
            'category' => 'Actifs', 'author' => 'Le laboratoire',
            'excerpt' => 'Avec un visuel.',
            'body' => [['h' => 'Section', 'p' => 'Texte.']],
            'published_at' => now()->subDay(),
        ]);

        $this->get('/le-lab')->assertSuccessful()->assertSee('visuel éditorial', false);

        $article->update(['image_path' => 'uploads/articles/exemple.webp']);

        $lab = $this->get('/le-lab')->assertSuccessful()->getContent();
        $this->assertStringContainsString('uploads/articles/exemple.webp', $lab);

        // And it becomes the share image, rather than the site-wide default.
        $this->assertStringContainsString(
            'uploads/articles/exemple.webp',
            $this->get('/le-lab/un-article-illustre')->assertSuccessful()->getContent()
        );
    }

    public function test_an_article_shows_its_cited_products(): void
    {
        $product = $this->product();

        $article = Article::create([
            'title' => 'Rétinol, par où commencer', 'slug' => 'retinol-par-ou-commencer',
            'category' => 'Actifs', 'author' => 'Dr. L. Haddad',
            'lead' => 'Introduction progressive.',
            'body' => [['h' => 'Semaine 1', 'p' => 'Deux soirs par semaine.']],
            'published_at' => now()->subDay(),
        ]);

        $article->products()->sync([$product->id => ['position' => 0]]);

        $this->get('/le-lab/retinol-par-ou-commencer')
            ->assertSuccessful()
            ->assertSee('Produits cités')
            ->assertSee('Semaine 1')
            ->assertSee($product->name);
    }

    public function test_tracking_finds_an_order_with_the_matching_phone(): void
    {
        $order = app(PlaceOrder::class)->handle([$this->product()->id => 1], [
            'first_name' => 'Salma', 'last_name' => 'Benali',
            'phone' => '0661228410', 'address' => '12 rue Ibn Batouta',
            'city' => 'Casablanca',
        ]);

        $this->post('/suivi', ['number' => $order->number, 'phone' => '06 61 22 84 10'])
            ->assertSuccessful()
            ->assertSee($order->number)
            ->assertSee('Commande reçue');
    }

    public function test_tracking_refuses_a_valid_number_with_the_wrong_phone(): void
    {
        $order = app(PlaceOrder::class)->handle([$this->product()->id => 1], [
            'first_name' => 'Salma', 'last_name' => 'Benali',
            'phone' => '0661228410', 'address' => '12 rue Ibn Batouta',
            'city' => 'Casablanca',
        ]);

        // The order number alone must not be enough: they are sequential.
        $this->post('/suivi', ['number' => $order->number, 'phone' => '0600000000'])
            ->assertSessionHasErrors('number');
    }
}
