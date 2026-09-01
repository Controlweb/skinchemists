<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrder;
use App\Exceptions\OutOfStockException;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Promotion;
use App\Services\Cart;
use App\Support\Pricing;

class CheckoutController extends Controller
{
    /** Cities the prototype ships to. */
    public const CITIES = [
        'Casablanca', 'Rabat', 'Marrakech', 'Tanger', 'Agadir',
        'Fès', 'Meknès', 'Oujda', 'Tétouan', 'Autre ville',
    ];

    public function show(Cart $cart)
    {
        if ($cart->isEmpty()) {
            return redirect()->route('cart')->with('status', 'Votre panier est vide.');
        }

        return view('checkout', [
            'lines' => $cart->lines(),
            'pricing' => Pricing::for($cart->lines(), null, 'standard'),
            'cities' => self::CITIES,
        ]);
    }

    public function store(CheckoutRequest $request, Cart $cart, PlaceOrder $placeOrder)
    {
        if ($cart->isEmpty()) {
            return redirect()->route('cart')->with('status', 'Votre panier est vide.');
        }

        $quantities = $cart->lines()
            ->mapWithKeys(fn ($line) => [$line['product']->id => $line['quantity']])
            ->all();

        try {
            $order = $placeOrder->handle(
                $quantities,
                $request->validated(),
                $request->string('shipping_method')->value(),
                $request->input('coupon_code'),
            );
        } catch (OutOfStockException $e) {
            return back()->withInput()->withErrors(['cart' => $e->getMessage()]);
        }

        $cart->clear();

        return redirect()->route('checkout.confirmation', $order);
    }

    public function confirmation(Order $order)
    {
        return view('confirmation', ['order' => $order->load('items')]);
    }
}
