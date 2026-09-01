<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['name' => 'Test']);

        return Product::create([
            'sku' => 'SC-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'Soin de test',
            'slug' => 'soin-'.uniqid(),
            'category_id' => $category->id,
            'price_cents' => 50000,
            'stock' => 5,
        ]);
    }

    public function test_a_path_with_spaces_and_accents_is_encoded_per_segment(): void
    {
        $image = new Image(['path' => 'uploads/products/CRÈME JOUR 50ML/photo une.webp']);

        // The source folders came with spaces and accents; an un-encoded space
        // silently breaks the URL, and asset() does not encode.
        $this->assertStringEndsWith(
            'uploads/products/CR%C3%88ME%20JOUR%2050ML/photo%20une.webp',
            $image->url()
        );
    }

    public function test_the_first_image_by_position_is_the_primary_one(): void
    {
        $product = $this->product();

        $product->images()->create(['path' => 'uploads/b.webp', 'position' => 1]);
        $product->images()->create(['path' => 'uploads/a.webp', 'position' => 0]);

        $this->assertSame('uploads/a.webp', $product->fresh()->primaryImage()->path);
    }

    public function test_a_product_without_images_yields_an_empty_url(): void
    {
        // Goes straight into a CSS url(); null would render the string "null".
        $this->assertSame('', $this->product()->primaryImageUrl());
    }

    public function test_products_and_bundles_own_images_independently(): void
    {
        $product = $this->product();
        $product->images()->create(['path' => 'uploads/product.webp', 'position' => 0]);

        $bundle = Bundle::create(['name' => 'Rituel', 'slug' => 'rituel-'.uniqid()]);
        $bundle->images()->create(['path' => 'uploads/bundle.webp', 'position' => 0]);

        $this->assertSame('uploads/product.webp', $product->fresh()->images->first()->path);
        $this->assertSame('uploads/bundle.webp', $bundle->fresh()->images->first()->path);
        $this->assertSame(1, $product->fresh()->images->count());
    }

    public function test_a_bundle_falls_back_to_its_product_images(): void
    {
        $a = $this->product();
        $a->images()->create(['path' => 'uploads/a.webp', 'position' => 0]);
        $b = $this->product();
        $b->images()->create(['path' => 'uploads/b.webp', 'position' => 0]);

        $bundle = Bundle::create(['name' => 'Rituel', 'slug' => 'rituel-'.uniqid()]);
        $bundle->products()->sync([$a->id => ['position' => 0], $b->id => ['position' => 1]]);

        $this->assertSame(
            ['uploads/a.webp', 'uploads/b.webp'],
            $bundle->fresh()->galleryImages()->pluck('path')->all()
        );
    }

    public function test_a_bundle_with_its_own_images_stops_composing_from_products(): void
    {
        $product = $this->product();
        $product->images()->create(['path' => 'uploads/product.webp', 'position' => 0]);

        $bundle = Bundle::create(['name' => 'Rituel', 'slug' => 'rituel-'.uniqid()]);
        $bundle->products()->sync([$product->id => ['position' => 0]]);
        $bundle->images()->create(['path' => 'uploads/coffret.webp', 'position' => 0]);

        $this->assertSame(
            ['uploads/coffret.webp'],
            $bundle->fresh()->galleryImages()->pluck('path')->all()
        );
    }

    public function test_deleting_a_product_removes_its_image_rows(): void
    {
        $product = $this->product();
        $product->images()->create(['path' => 'uploads/a.webp', 'position' => 0]);

        $product->delete();

        $this->assertSame(0, Image::count());
    }

    public function test_reseeding_the_catalogue_does_not_touch_existing_products(): void
    {
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $product = Product::first();
        $product->update(['price_cents' => 12345, 'stock' => 999]);
        $product->images()->delete();
        $product->images()->create(['path' => 'uploads/uploaded-by-staff.webp', 'position' => 0]);

        // DEPLOYMENT.md asks for db:seed on every deploy. It must not undo
        // prices, stock or images that staff have edited since launch.
        $this->seed(\Database\Seeders\CatalogSeeder::class);

        $product->refresh();
        $this->assertSame(12345, $product->price_cents);
        $this->assertSame(999, $product->stock);
        $this->assertSame('uploads/uploaded-by-staff.webp', $product->images->first()->path);
    }
}
