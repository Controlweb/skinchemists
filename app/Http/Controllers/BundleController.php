<?php

namespace App\Http\Controllers;

use App\Models\Bundle;

class BundleController extends Controller
{
    public function index()
    {
        return view('bundles', [
            'bundles' => Bundle::with('products.images')
                ->active()
                ->orderBy('position')
                ->get(),
        ]);
    }
}
