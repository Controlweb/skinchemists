<?php

namespace Tests\Feature;

use App\Actions\PlaceOrder;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderAdminActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function order(): Order
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['name' => 'Test']);

        $product = Product::create([
            'sku' => 'SC-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'Soin de test',
            'slug' => 'soin-'.uniqid(),
            'category_id' => $category->id,
            'price_cents' => 50000,
            'stock' => 5,
        ]);

        return app(PlaceOrder::class)->handle([$product->id => 2], [
            'first_name' => 'Salma', 'last_name' => 'Benali',
            'phone' => '0661228410', 'email' => 'salma@example.ma',
            'address' => '12 rue Ibn Batouta', 'city' => 'Casablanca',
        ]);
    }

    public function test_the_order_page_carries_the_row_actions(): void
    {
        $order = $this->order();

        Livewire::actingAs(User::factory()->create(['is_admin' => true]))
            ->test(ViewOrder::class, ['record' => $order->number])
            ->assertActionExists('advance')
            ->assertActionExists('markPaid')
            ->assertActionExists('cancel')
            ->assertActionExists('delete');
    }

    public function test_deleting_an_order_restocks_it_first(): void
    {
        $order = $this->order();
        $product = $order->items->first()->product;

        $this->assertSame(3, $product->fresh()->stock);   // 5 − 2 sold

        Livewire::actingAs(User::factory()->create(['is_admin' => true]))
            ->test(ViewOrder::class, ['record' => $order->number])
            ->callAction('delete');

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
    }
}
