<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imports the real catalogue exported from the prototype's products.js.
 * Idempotent: re-running updates in place rather than duplicating.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/products.json');

        if (! is_file($path)) {
            throw new RuntimeException("Catalogue export missing: {$path}");
        }

        $rows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $categories = [];
        foreach (array_unique(array_column($rows, 'cat')) as $position => $name) {
            $categories[$name] = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'position' => $position]
            );
        }

        foreach ($rows as $row) {
            $product = Product::updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'gtin' => $row['gtin'] ?? null,
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'brand' => $row['brand'] ?? 'skinChemists',
                    'category_id' => $categories[$row['cat']]->id,
                    'ingredient' => $row['ingredient'] ?? null,
                    'concern' => $row['concern'] ?? null,
                    'price_cents' => (int) round($row['price'] * 100),
                    'sale_price_cents' => isset($row['sale']) && $row['sale']
                        ? (int) round($row['sale'] * 100)
                        : null,
                    'short' => $row['short'] ?? null,
                    'bullets' => $row['bullets'] ?? [],
                    'actifs' => $row['actifs'] ?? [],
                    'stock' => (int) ($row['stock'] ?? 0),
                    'low_stock_threshold' => (int) ($row['low'] ?? 5),
                    'rating_avg' => $row['rating'] ?? 0,
                    'reviews_count' => (int) ($row['reviews'] ?? 0),
                    'is_active' => true,
                ]
            );

            $product->images()->delete();

            foreach (array_values($row['images'] ?? []) as $position => $path) {
                // A product page with a broken image is a bug we ship to customers.
                // Fail the import instead, while it is still cheap to fix.
                $file = public_path(rawurldecode($path));

                if (! is_file($file)) {
                    throw new RuntimeException(
                        "Missing image for {$row['sku']}: ".rawurldecode($path)
                    );
                }

                $product->images()->create(['path' => $path, 'position' => $position]);
            }
        }

        $this->command->info('Catalogue: '.Product::count().' products, '
            .Category::count().' categories.');
    }
}
