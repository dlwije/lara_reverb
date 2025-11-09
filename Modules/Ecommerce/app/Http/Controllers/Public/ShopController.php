<?php

namespace App\Http\Controllers\Public;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index() {
        return Inertia::render('e-commerce/(public)/shop/page', [
            'filters' => request()->only('search'),
        ]);
    }

    public function show(Product $product) {}
}
