<?php

namespace App\Http\Controllers\Customer;

use App\Models\Cart;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index() {
        return Inertia::render('e-commerce/(public)/cart/page', []);
    }

    public function store(Product $product) {}

    public function destroy(Cart $cart) {}
}
