<?php

namespace Modules\Ecommerce\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Collection;
use App\Models\Sma\Setting\CustomField;
use App\Models\Sma\Setting\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Modules\Product\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request) {
        $filters = $request->input('filters') ?? [];

        if (! ($filters['store'] ?? null) && session('selected_store_id', null) && Store::count() > 1) {
            $filters['store'] = session('selected_store_id');
        }

        $products = Product::with(
            'supplier:id,name,company', 'taxes:id,name', 'stocks',
            'brand:id,name', 'category:id,name,category_id', 'unit:id,code,name',
        )->filter($filters)->latest('id')->orderBy('name')->paginate();

        $data_array = [
            'custom_fields' => CustomField::ofModel('product')->get(),
            'stores'        => Store::active()->get(['id as value', 'name as label']),
            'products'      => $products->items(), // Just the products array
            'pagination'    => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
                'links'        => $products->linkCollection()->toArray(),
            ],
        ];
//        Log::info('product_data: ',['products' => $data_array['products'], 'pro_pagination' => $data_array['pagination']]);

        return Inertia::render('e-commerce/public/product/product-list', $data_array);
    }

    public function show(Request $request, $slug) {

        $product = Product::with([
            'unit', 'unit.subunits', 'unitPrices',
            'supplier:id,name,company', 'taxes:id,name',
            'products:id,code,name', 'stocks', 'stores', 'variations.stocks',
            'brand:id,name,slug,photo,description',
            'category:id,name,category_id,slug,photo,description',
            'subcategory:id,name,slug,photo,description',
            'unit:id,code,name',
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        if ($request->with == 'promotions') {
            $product->load(['validPromotions', 'category.validPromotions']);
        }

        return Inertia::render('e-commerce/public/product/page', [
            'single_product' => $product, // pass as prop
        ]);
    }
}
