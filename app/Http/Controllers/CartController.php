<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
use App\Support\Pricing;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Cart $cart)
    {
        return view('cart', [
            'lines' => $cart->lines(),
            'pricing' => Pricing::for($cart->lines(), null, 'standard'),
        ]);
    }

    /** Non-JS fallback for the Livewire add-to-cart button. */
    public function add(Request $request, Cart $cart)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $cart->add(Product::findOrFail($data['product_id']), $data['quantity'] ?? 1);

        return back()->with('status', 'Produit ajouté au panier.');
    }
}
