<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /** Filters ride the query string: shareable, indexable, cacheable. */
    public function index(Request $request)
    {
        $products = Product::with('images')->active()
            ->when($request->filled('categorie'), fn ($q) => $q->whereHas(
                'category', fn ($c) => $c->where('slug', $request->string('categorie'))
            ))
            ->when($request->filled('marque'), fn ($q) => $q->where('brand', $request->string('marque')))
            ->when($request->filled('gamme'), fn ($q) => $q->where('gamme', $request->string('gamme')))
            ->when($request->filled('actif'), fn ($q) => $q->where('ingredient', $request->string('actif')))
            ->when($request->filled('besoin'), fn ($q) => $q->where('concern', $request->string('besoin')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('ingredient', 'like', $term)
                    ->orWhere('short', 'like', $term));
            })
            ->when($request->string('tri')->value() === 'prix-asc',
                fn ($q) => $q->orderByRaw('COALESCE(sale_price_cents, price_cents) asc'))
            ->when($request->string('tri')->value() === 'prix-desc',
                fn ($q) => $q->orderByRaw('COALESCE(sale_price_cents, price_cents) desc'))
            ->when($request->string('tri')->value() === 'nouveautes', fn ($q) => $q->latest())
            ->when(! $request->filled('tri'), fn ($q) => $q->orderByDesc('reviews_count'))
            ->paginate(12)
            ->withQueryString();

        return view('shop', [
            'products' => $products,
            'categories' => Category::orderBy('position')->get(),
            'brands' => Product::active()->whereNotNull('brand')
                ->distinct()->orderBy('brand')->pluck('brand'),
            'gammes' => Product::active()->whereNotNull('gamme')
                ->distinct()->orderBy('gamme')->pluck('gamme'),
            'ingredients' => Product::active()->whereNotNull('ingredient')
                ->distinct()->orderBy('ingredient')->pluck('ingredient'),
            'concerns' => Product::active()->whereNotNull('concern')
                ->distinct()->orderBy('concern')->pluck('concern'),
        ]);
    }
}
