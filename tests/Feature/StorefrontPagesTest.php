<?php

namespace Tests\Feature;

use App\Actions\PlaceOrder;
use App\Models\Article;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
