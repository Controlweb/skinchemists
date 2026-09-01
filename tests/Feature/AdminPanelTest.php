<?php

namespace Tests\Feature;

use App\Actions\PlaceOrder;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function product(): Product
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['name' => 'Test']);

        return Product::create([
            'sku' => 'SC-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'Sérum de test',
            'slug' => 'serum-'.uniqid(),
            'category_id' => $category->id,
            'price_cents' => 50000,
            'stock' => 10,
        ]);
    }

    public function test_the_admin_panel_is_closed_to_guests(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_a_signed_in_user_without_the_admin_flag_is_refused(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_a_signed_in_user_reaches_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_the_orders_screen_lists_real_orders(): void
    {
        $product = $this->product();

        $order = app(PlaceOrder::class)->handle([$product->id => 1], [
            'first_name' => 'Salma', 'last_name' => 'Benali',
            'phone' => '0661228410', 'address' => '12 rue Ibn Batouta',
            'city' => 'Casablanca',
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/orders')
            ->assertSuccessful()
            ->assertSee($order->number);
    }

    public function test_the_products_screen_loads(): void
    {
        $this->product();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/products')
            ->assertSuccessful();
    }

    public function test_the_reviews_screen_loads(): void
    {
        Review::create([
            'product_id' => $this->product()->id,
            'author' => 'Salma B.',
            'rating' => 5,
            'body' => 'Très bon produit.',
            'status' => 'en_attente',
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/reviews')
            ->assertSuccessful();
    }
}
