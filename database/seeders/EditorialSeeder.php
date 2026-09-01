<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Bundle;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Ingredient pages, Lab articles and coffrets, imported from the prototype.
 * Idempotent — re-running updates in place.
 */
class EditorialSeeder extends Seeder
{
    public function run(): void
    {
        $this->ingredients();
        $this->articles();
        $this->bundles();
    }

    private function ingredients(): void
    {
        foreach ($this->load('ingredients') as $name => $meta) {
            Ingredient::updateOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'intro' => $meta['intro'] ?? null,
                    'what' => $meta['what'] ?? null,
                    'benefits' => $meta['benefits'] ?? [],
                    'how' => $meta['how'] ?? null,
                    'who' => $meta['who'] ?? null,
                    'is_published' => true,
                ]
            );
        }

        $this->command->info('Actifs : '.Ingredient::count());
    }

    private function articles(): void
    {
        foreach ($this->load('articles') as $index => $row) {
            $article = Article::updateOrCreate(
                ['slug' => Str::slug($row['title'])],
                [
                    'title' => $row['title'],
                    'category' => $row['cat'],
                    'author' => $row['author'],
                    'read_minutes' => (int) filter_var($row['read'] ?? '5', FILTER_SANITIZE_NUMBER_INT) ?: 5,
                    'excerpt' => $row['excerpt'] ?? null,
                    'lead' => $row['lead'] ?? null,
                    'body' => $row['body'] ?? [],
                    'is_featured' => $index === 0,
                    // The prototype's dates are display strings; stagger them
                    // instead so ordering is deterministic.
                    'published_at' => Carbon::now()->subDays($index * 4 + 1),
                ]
            );

            $ids = Product::whereIn('sku', array_map(
                fn ($id) => 'SC-'.$id,
                $row['ids'] ?? []
            ))->pluck('id')->all();

            $article->products()->sync(
                collect($ids)->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]])->all()
            );
        }

        $this->command->info('Articles : '.Article::count());
    }

    private function bundles(): void
    {
        foreach ($this->load('bundles') as $position => $row) {
            $products = Product::whereIn('sku', array_map(
                fn ($id) => 'SC-'.$id,
                $row['ids']
            ))->get();

            // A coffret missing a component would advertise a saving on a
            // protocol the customer cannot actually receive.
            if ($products->count() !== count($row['ids'])) {
                throw new RuntimeException(
                    "Coffret « {$row['name']} » : produit introuvable parmi ".implode(', ', $row['ids'])
                );
            }

            $bundle = Bundle::updateOrCreate(
                ['slug' => Str::slug($row['name'])],
                [
                    'name' => $row['name'],
                    'tag' => $row['tag'] ?? null,
                    'blurb' => $row['blurb'] ?? null,
                    'discount_percent' => 15,
                    'is_active' => true,
                    'position' => $position,
                ]
            );

            $bundle->products()->sync(
                $products->values()->mapWithKeys(fn ($p, $i) => [$p->id => ['position' => $i]])->all()
            );
        }

        $this->command->info('Coffrets : '.Bundle::count());
    }

    /** @return array<mixed> */
    private function load(string $name): array
    {
        $path = database_path("seeders/data/{$name}.json");

        if (! is_file($path)) {
            throw new RuntimeException("Export manquant : {$path}");
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
