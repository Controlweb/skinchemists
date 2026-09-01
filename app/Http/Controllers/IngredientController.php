<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;

class IngredientController extends Controller
{
    public function show(Ingredient $ingredient)
    {
        abort_unless($ingredient->is_published, 404);

        $products = $ingredient->products()
            ->with('images')
            ->active()
            ->orderByDesc('reviews_count')
            ->get();

        return view('ingredient', [
            'ingredient' => $ingredient,
            'products' => $products,
        ]);
    }
}
