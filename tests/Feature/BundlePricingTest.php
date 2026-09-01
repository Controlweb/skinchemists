<?php

namespace Tests\Feature;

use App\Actions\PlaceOrder;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Support\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundlePricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function product(int $priceCents, int $stock = 10): Product
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['name' => 'Test']);

        return Product::create([
            'sku' => 'SC-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'Soin de test',
            'slug' => 'soin-'.uniqid(),
            'category_id' => $category->id,
            'price_cents' => $priceCents,
            'stock' => $stock,
        ]);
    }

    /** @param  array<int, Product>  $products */
    private function bundle(array $products, int $discountPercent = 15): Bundle
    {
        $bundle = Bundle::create([
            'name' => 'Rituel de test',
            'slug' => 'rituel-'.uniqid(),
            'discount_percent' => $discountPercent,
            'is_active' => true,
        ]);

        $bundle->products()->sync(
            collect($products)->mapWithKeys(fn ($p, $i) => [$p->id => ['position' => $i]])->all()
        );

        return $bundle->load('products');
    }

    /** @param  array<int, array{0: Product, 1: int}>  $pairs */
    private function lines(array $pairs): array
    {
        return array_map(fn ($pair) => ['product' => $pair[0], 'quantity' => $pair[1]], $pairs);
    }

    public function test_the_bundle_price_is_rounded_to_the_nearest_five_dirhams(): void
    {
        $bundle = $this->bundle([$this->product(64500), $this->product(48000)]);

        // 1125 MAD full, minus 15% = 956.25 -> rounded to 955 MAD.
        $this->assertSame(112500, $bundle->fullPriceCents());
        $this->assertSame(95500, $bundle->priceCents());
        $this->assertSame(17000, $bundle->savingCents());
    }

    public function test_the_saving_is_applied_when_every_component_is_in_the_cart(): void
    {
        $a = $this->product(64500);
        $b = $this->product(48000);
        $bundle = $this->bundle([$a, $b]);

        $pricing = Pricing::for($this->lines([[$a, 1], [$b, 1]]), null, 'standard');

        $this->assertSame(112500, $pricing->subtotal);
        $this->assertSame($bundle->savingCents(), $pricing->discount);
        // Above the free-shipping threshold, so nothing is added for delivery.
        $this->assertSame(0, $pricing->shipping);
        $this->assertSame(95500, $pricing->total);
    }

    public function test_no_saving_when_a_component_is_missing(): void
    {
        $a = $this->product(64500);
        $b = $this->product(48000);
        $this->bundle([$a, $b]);

        $pricing = Pricing::for($this->lines([[$a, 1]]), null, 'standard');

        $this->assertSame(64500, $pricing->subtotal);
        $this->assertSame(0, $pricing->discount);
    }

    public function test_the_saving_scales_with_complete_sets(): void
    {
        $a = $this->product(64500);
        $b = $this->product(48000);
        $bundle = $this->bundle([$a, $b]);

        // Two of one, three of the other: only two complete sets.
        $pricing = Pricing::for($this->lines([[$a, 2], [$b, 3]]), null, 'standard');

        $this->assertSame($bundle->savingCents() * 2, $pricing->discount);
    }

    public function test_a_coupon_stacks_on_top_without_discounting_past_the_subtotal(): void
    {
        Promotion::create([
            'code' => 'MAROC10', 'name' => 'Bienvenue', 'type' => 'percent',
            'value' => 10, 'is_active' => true,
        ]);

        $a = $this->product(64500);
        $b = $this->product(48000);
        $bundle = $this->bundle([$a, $b]);

        $pricing = Pricing::for(
            $this->lines([[$a, 1], [$b, 1]]),
            Promotion::first(),
            'standard'
        );

        // Bundle first, then the coupon on what remains: 10% of 955 MAD.
        $expected = $bundle->savingCents() + (int) round(95500 * 0.10);

        $this->assertSame($expected, $pricing->discount);
        $this->assertLessThan($pricing->subtotal, $pricing->discount);
    }

    public function test_an_order_records_the_bundle_saving(): void
    {
        $a = $this->product(64500);
        $b = $this->product(48000);
        $bundle = $this->bundle([$a, $b]);

        $order = app(PlaceOrder::class)->handle(
            [$a->id => 1, $b->id => 1],
            [
                'first_name' => 'Salma', 'last_name' => 'Benali',
                'phone' => '0661228410', 'address' => '12 rue Ibn Batouta',
                'city' => 'Casablanca',
            ]
        );

        $this->assertSame($bundle->savingCents(), $order->discount_cents);
        $this->assertSame(95500, $order->total_cents);
    }

    public function test_an_inactive_bundle_grants_nothing(): void
    {
        $a = $this->product(64500);
        $b = $this->product(48000);
        $this->bundle([$a, $b])->update(['is_active' => false]);

        $pricing = Pricing::for($this->lines([[$a, 1], [$b, 1]]), null, 'standard');

        $this->assertSame(0, $pricing->discount);
    }

    public function test_bundle_availability_follows_the_scarcest_component(): void
    {
        $bundle = $this->bundle([
            $this->product(30000, stock: 9),
            $this->product(30000, stock: 2),
        ]);

        $this->assertSame(2, $bundle->availableQuantity());
    }
}
