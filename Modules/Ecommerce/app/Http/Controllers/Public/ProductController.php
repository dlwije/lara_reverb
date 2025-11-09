<?php

namespace Modules\Ecommerce\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\Product\Models\Product;

class ProductController extends Controller
{
    public function index() {

        return Inertia::render('e-commerce/public/product/product-list', [
            ''
        ]);
    }

    public function show(Product $product) {
        return Inertia::render('e-commerce/public/product/page', [
            'productId' => $product, // pass as prop
        ]);
    }
}
