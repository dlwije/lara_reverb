<?php

namespace App\Http\Controllers\Public;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index() {
        return Inertia::render('e-commerce/(public)/product/product-list', [
            ''
        ]);
    }

    public function show($product) {
        return Inertia::render('e-commerce/(public)/product/[productId]/page', [
            'productId' => $product, // pass as prop
        ]);
    }
}
