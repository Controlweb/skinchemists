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

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            // Import once, never clobber. After launch the database is the
            // source of truth: staff edit prices, stock and images in the
            // admin, and re-running this seeder must not undo that work.
            // DEPLOYMENT.md asks for `db:seed --force` on every deploy.
            if (Product::where('sku', $row['sku'])->exists()) {
                $skipped++;

                continue;
            }

            $imported++;

            $product = Product::create(
                [
                    'sku' => $row['sku'],
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

            foreach (array_values($row['images'] ?? []) as $position => $path) {
                // Stored decoded, matching what is on disk and what an upload
                // produces. image_url() encodes it at render time.
                $decoded = rawurldecode($path);

                // A product page with a broken image is a bug we ship to
                // customers. Fail the import instead, while it is cheap to fix.
                if (! is_file(public_path($decoded))) {
                    throw new RuntimeException("Missing image for {$row['sku']}: {$decoded}");
                }

                $product->images()->create(['path' => $decoded, 'position' => $position]);
            }
        }

        $this->command->info(
            "Catalogue : {$imported} produit(s) importé(s), {$skipped} déjà présent(s) et laissé(s) intact(s)."
        );
    }
}
