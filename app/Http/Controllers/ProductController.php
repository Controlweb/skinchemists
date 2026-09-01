<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load('images', 'category');

        return view('product', [
            'product' => $product,
            'reviews' => $product->reviews()->approved()->latest()->take(6)->get(),
            'related' => Product::with('images')->active()->inStock()
                ->where('id', '!=', $product->id)
                ->where(fn ($q) => $q->where('ingredient', $product->ingredient)
                    ->orWhere('category_id', $product->category_id))
                ->take(4)->get(),
        ]);
    }
}
