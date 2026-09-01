<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'bestSellers' => Product::with('images')->active()->inStock()
                ->orderByDesc('reviews_count')->take(7)->get(),
            // $navIngredients (name => slug) comes from ViewServiceProvider.
            'reviews' => Review::with('product')->approved()->latest()->take(4)->get(),
            'ratingAvg' => round(Review::approved()->avg('rating') ?? 0, 1),
        ]);
    }
}
