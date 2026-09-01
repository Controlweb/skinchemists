<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function product(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['name' => 'Test']);

        return Product::create(array_merge([
            'sku' => 'SC-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'Soin de test',
            'slug' => 'soin-'.uniqid(),
            'category_id' => $category->id,
            'brand' => 'skinChemists',
            'price_cents' => 64500,
            'stock' => 10,
        ], $attributes));
    }

    private function form(Product $product): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()]);
    }

    public function test_it_loads_prices_in_dirhams_and_saves_them_as_centimes(): void
    {
        $product = $this->product(['price_cents' => 64500, 'sale_price_cents' => 48000]);

        $this->form($product)
            ->assertFormSet(['price_cents' => 645, 'sale_price_cents' => 480])
            ->fillForm(['price_cents' => 700, 'sale_price_cents' => 550])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame(70000, $product->price_cents);
        $this->assertSame(55000, $product->sale_price_cents);
    }

    public function test_it_rejects_a_sale_price_above_the_list_price(): void
    {
        $product = $this->product(['price_cents' => 50000]);

        // Would advertise a negative discount on the storefront.
        $this->form($product)
            ->fillForm(['price_cents' => 500, 'sale_price_cents' => 600])
            ->call('save')
            ->assertHasFormErrors(['sale_price_cents']);

        $this->assertSame(50000, $product->fresh()->price_cents);
    }

    public function test_clearing_the_sale_price_removes_the_promotion(): void
    {
        $product = $this->product(['price_cents' => 50000, 'sale_price_cents' => 40000]);

        $this->form($product)
            ->fillForm(['sale_price_cents' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($product->fresh()->sale_price_cents);
        $this->assertFalse($product->fresh()->isOnSale());
    }

    public function test_bullets_and_actifs_survive_a_round_trip(): void
    {
        $product = $this->product([
            'bullets' => ['Hydrate intensément', 'Lisse le grain de peau'],
            'actifs' => [['t' => 'Caviar', 'd' => 'Raffermit la peau.']],
        ]);

        // JSON columns edited through repeaters. A simple() repeater holds its
        // state nested as [['bullet' => '…']] but must persist flat, because
        // the product page iterates the strings directly.
        $this->form($product)
            ->fillForm([
                'bullets' => [['bullet' => 'Un seul bénéfice']],
                'actifs' => [
                    ['t' => 'Rétinol', 'd' => 'Accélère le renouvellement.'],
                    ['t' => 'Niacinamide', 'd' => 'Apaise les rougeurs.'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame(['Un seul bénéfice'], array_values($product->bullets));
        $this->assertCount(2, $product->actifs);
        $this->assertSame('Rétinol', array_values($product->actifs)[0]['t']);
        $this->assertSame('Apaise les rougeurs.', array_values($product->actifs)[1]['d']);
    }

    public function test_the_saved_content_reaches_the_product_page(): void
    {
        $product = $this->product();

        $this->form($product)
            ->fillForm([
                'bullets' => [['bullet' => 'Bénéfice visible en boutique']],
                'actifs' => [['t' => 'Acide Hyaluronique', 'd' => 'Repulpe la peau.']],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/produit/'.$product->fresh()->slug)
            ->assertSuccessful()
            ->assertSee('Bénéfice visible en boutique')
            ->assertSee('Acide Hyaluronique')
            ->assertSee('Repulpe la peau.');
    }

    public function test_editing_a_product_does_not_rewrite_its_slug(): void
    {
        $product = $this->product(['slug' => 'ancien-slug']);

        // Live products keep their URL: the slug only autofills on create.
        $this->form($product)
            ->fillForm(['name' => 'Un nom entièrement différent'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('ancien-slug', $product->fresh()->slug);
    }

    public function test_a_stock_change_from_the_form_is_logged_as_a_movement(): void
    {
        $product = $this->product(['stock' => 10]);

        $this->form($product)
            ->fillForm(['stock' => 25])
            ->call('save')
            ->assertHasNoFormErrors();

        $movement = $product->stockMovements()->latest('id')->first();
        $this->assertSame(15, $movement->delta);
        $this->assertSame('Ajustement manuel', $movement->reason);
    }
}
