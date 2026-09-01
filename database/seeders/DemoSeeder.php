<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;

/**
 * Demo content only — reviews nobody actually wrote.
 * Never called by DatabaseSeeder; run explicitly:
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $reviews = [
            ['SC-152', 'Salma B.', 5, true, 'en_attente', 'Ma peau est visiblement plus lisse après trois semaines, sans aucune irritation. Je vais commander la version nuit.'],
            ['SC-227', 'Youssef I.', 5, true, 'en_attente', 'Commandé un soir, livré le lendemain à Rabat, payé à la réception. Produit conforme et bien emballé.'],
            ['SC-222', 'Imane C.', 4, true, 'approuve', 'Les taches sur mes joues se sont nettement atténuées en deux mois. Il faut être patiente mais ça fonctionne.'],
            ['SC-215', 'Anonyme', 1, false, 'en_attente', 'Message promotionnel sans rapport avec le produit, contient un lien externe.'],
            ['SC-259', 'Sofia N.', 5, true, 'approuve', "Produit identique à celui acheté à Londres, et le conseil avant l'achat a été utile."],
            ['SC-230', 'Karim T.', 2, true, 'rejete', 'Trop asséchant pour ma peau, mais le service client a bien géré le retour.'],
        ];

        foreach ($reviews as [$sku, $author, $rating, $verified, $status, $body]) {
            $product = Product::where('sku', $sku)->first();

            if (! $product) {
                continue;
            }

            Review::updateOrCreate(
                ['product_id' => $product->id, 'author' => $author],
                compact('rating', 'verified', 'status', 'body'),
            );
        }

        // Keep the denormalised card rating consistent with what was seeded.
        Product::whereHas('reviews')->get()->each(function (Product $product) {
            $approved = $product->reviews()->approved();

            $product->forceFill([
                'reviews_count' => $approved->count(),
                'rating_avg' => round($approved->avg('rating') ?? 0, 1),
            ])->save();
        });

        $this->command->info('Demo reviews seeded: '.Review::count());
    }
}
