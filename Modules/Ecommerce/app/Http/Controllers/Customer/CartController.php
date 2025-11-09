<?php

namespace Modules\Ecommerce\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Modules\Product\Models\Product;

class CartController extends Controller
{
    public function index() {
        return Inertia::render('e-commerce/(public)/cart/page', []);
    }

    public function store(Product $product) {}

//    public function destroy(Cart $cart) {}
}
