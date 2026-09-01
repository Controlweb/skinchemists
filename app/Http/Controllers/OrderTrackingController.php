<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Order tracking without customer accounts.
 *
 * Checkout is guest cash-on-delivery, so there is nothing to log in to. The
 * order number alone is guessable (they are sequential), so the phone number
 * used on the order acts as the second factor.
 */
class OrderTrackingController extends Controller
{
    public function show()
    {
        return view('tracking', ['order' => null]);
    }

    public function find(Request $request)
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $phone = preg_replace('/[\s.\-]/', '', $data['phone']);

        $order = Order::with('items', 'events')
            ->where('number', trim($data['number']))
            ->where('phone', $phone)
            ->first();

        if (! $order) {
            // Deliberately vague: never reveal whether the number exists.
            return back()
                ->withInput()
                ->withErrors(['number' => 'Aucune commande ne correspond à ce numéro et à ce téléphone.']);
        }

        return view('tracking', ['order' => $order]);
    }
}
