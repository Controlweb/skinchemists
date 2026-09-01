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

    public function test_the_bundles_page_loads(): void
    {
        $this->get('/coffrets')->assertSuccessful()->assertSee('Coffrets');
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
