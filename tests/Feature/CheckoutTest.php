<?php

namespace Tests\Feature;

use App\Actions\CancelOrder;
use App\Actions\PlaceOrder;
use App\Exceptions\OutOfStockException;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function product(int $priceCents, int $stock, ?int $saleCents = null): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'test'],
            ['name' => 'Test']
        );

        return Product::create([
            'sku' => 'SC-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'Sérum de test',
            'slug' => 'serum-'.uniqid(),
            'category_id' => $category->id,
            'price_cents' => $priceCents,
            'sale_price_cents' => $saleCents,
            'stock' => $stock,
        ]);
    }

    private function customer(): array
    {
        return [
            'first_name' => 'Salma',
            'last_name' => 'Benali',
            'phone' => '0661228410',
            'email' => 'salma@example.ma',
            'address' => '12 rue Ibn Batouta',
            'city' => 'Casablanca',
            'zip' => '20250',
        ];
    }

    public function test_it_places_an_order_and_decrements_stock(): void
    {
        $product = $this->product(priceCents: 64500, stock: 10);

        $order = app(PlaceOrder::class)->handle([$product->id => 2], $this->customer());

        $this->assertSame('SCM-'.(1042 + $order->id), $order->number);
        $this->assertSame('nouvelle', $order->status);
        $this->assertSame(129000, $order->subtotal_cents);
        $this->assertSame(8, $product->fresh()->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'delta' => -2,
            'stock_before' => 10,
            'stock_after' => 8,
        ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'label' => 'Commande reçue',
        ]);
    }

    public function test_it_charges_the_sale_price_when_one_is_set(): void
    {
        $product = $this->product(priceCents: 64500, stock: 5, saleCents: 48000);

        $order = app(PlaceOrder::class)->handle([$product->id => 1], $this->customer());

        $this->assertSame(48000, $order->subtotal_cents);
        $this->assertSame(48000, $order->items->first()->unit_price_cents);
    }

    public function test_it_snapshots_the_line_so_later_price_edits_do_not_rewrite_history(): void
    {
        $product = $this->product(priceCents: 30000, stock: 5);

        $order = app(PlaceOrder::class)->handle([$product->id => 1], $this->customer());

        $product->update(['price_cents' => 99900, 'name' => 'Nom changé']);

        $item = $order->fresh('items')->items->first();
        $this->assertSame(30000, $item->unit_price_cents);
        $this->assertSame('Sérum de test', $item->name);
    }

    public function test_it_rejects_an_order_that_exceeds_stock(): void
    {
        $product = $this->product(priceCents: 30000, stock: 1);

        $this->expectException(OutOfStockException::class);

        try {
            app(PlaceOrder::class)->handle([$product->id => 3], $this->customer());
        } finally {
            // The whole transaction must roll back: no order, no stock change.
            $this->assertSame(1, $product->fresh()->stock);
            $this->assertSame(0, Order::count());
        }
    }

    public function test_shipping_is_free_above_the_threshold(): void
    {
        $below = $this->product(priceCents: 50000, stock: 5);
        $order = app(PlaceOrder::class)->handle([$below->id => 1], $this->customer());
        $this->assertSame(3500, $order->shipping_cents);
        $this->assertSame(53500, $order->total_cents);

        $above = $this->product(priceCents: 60000, stock: 5);
        $order = app(PlaceOrder::class)->handle([$above->id => 1], $this->customer());
        $this->assertSame(0, $order->shipping_cents);
        $this->assertSame(60000, $order->total_cents);
    }

    public function test_express_shipping_is_flat_and_ignores_the_threshold(): void
    {
        $product = $this->product(priceCents: 100000, stock: 5);

        $order = app(PlaceOrder::class)->handle(
            [$product->id => 1], $this->customer(), shippingMethod: 'express'
        );

        $this->assertSame(6000, $order->shipping_cents);
        $this->assertSame(106000, $order->total_cents);
    }

    public function test_a_percentage_coupon_discounts_the_subtotal(): void
    {
        Promotion::create([
            'code' => 'MAROC10', 'name' => 'Bienvenue', 'type' => 'percent',
            'value' => 10, 'is_active' => true,
        ]);

        $product = $this->product(priceCents: 50000, stock: 5);

        $order = app(PlaceOrder::class)->handle(
            [$product->id => 1], $this->customer(), couponCode: 'maroc10'
        );

        $this->assertSame(50000, $order->subtotal_cents);
        $this->assertSame(5000, $order->discount_cents);
        // 450 MAD net is under the 600 threshold, so shipping is still charged.
        $this->assertSame(3500, $order->shipping_cents);
        $this->assertSame(48500, $order->total_cents);
        $this->assertSame('MAROC10', $order->coupon_code);
    }

    public function test_an_inactive_coupon_is_ignored(): void
    {
        Promotion::create([
            'code' => 'EXPIRE', 'name' => 'Expiré', 'type' => 'percent',
            'value' => 50, 'is_active' => false,
        ]);

        $product = $this->product(priceCents: 50000, stock: 5);

        $order = app(PlaceOrder::class)->handle(
            [$product->id => 1], $this->customer(), couponCode: 'EXPIRE'
        );

        $this->assertSame(0, $order->discount_cents);
        $this->assertNull($order->coupon_code);
    }

    public function test_cancelling_restocks_exactly_once(): void
    {
        $product = $this->product(priceCents: 30000, stock: 10);
        $order = app(PlaceOrder::class)->handle([$product->id => 3], $this->customer());
        $this->assertSame(7, $product->fresh()->stock);

        app(CancelOrder::class)->handle($order);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame('annulee', $order->fresh()->status);

        // Cancelling again must not invent inventory.
        app(CancelOrder::class)->handle($order->fresh());
        $this->assertSame(10, $product->fresh()->stock);
    }
}
