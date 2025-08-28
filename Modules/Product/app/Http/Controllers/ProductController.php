<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Collection;
use App\Models\Sma\Product\Brand;
use App\Models\Sma\Product\Category;
use App\Models\Sma\Product\Unit;
use App\Models\Sma\Setting\CustomField;
use App\Models\Sma\Setting\Store;
use App\Models\Sma\Setting\Tax;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Product\Actions\SaveProduct;
use Modules\Product\Http\Requests\ProductRequest;
use Modules\Product\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->input('filters');

        if (! ($filters['store'] ?? null) && session('selected_store_id', null)) {
            $filters['store'] = session('selected_store_id');
        }

        $dataArray = [
            'custom_fields' => CustomField::ofModel('product')->get(),
            'stores'        => Store::active()->get(['id as value', 'name as label']),

            'pagination' => new Collection(Product::with(
                'supplier:id,name,company', 'taxes:id,name', 'stocks',
                'brand:id,name', 'category:id,name,category_id', 'unit:id,code,name',
            )->filter($filters)->latest('id')->orderBy('name')->paginate()->withQueryString()),
        ];

        return Inertia::render('product/ProductsTable', $dataArray);
    }
    public function tableList(Request $request)
    {
        $filters = $request->input('filters');

        if (! ($filters['store'] ?? null) && session('selected_store_id', null)) {
            $filters['store'] = session('selected_store_id');
        }

        // Get per_page from filters or use the default from Paginatable trait
        $perPage = $filters['per_page'] ?? null;

        $query = Product::with(
            'supplier:id,name,company', 'taxes:id,name', 'stocks',
            'brand:id,name', 'category:id,name,category_id', 'unit:id,code,name',
        )->filter($filters)->latest('id')->orderBy('name');

        // If per_page is provided, use it, otherwise let the Paginatable trait handle it
        if ($perPage) {
            $pagination = $query->paginate($perPage)->withQueryString();
        } else {
            $pagination = $query->paginate()->withQueryString();
        }

        return [
            'stores' => Store::active()->get(['id as value', 'name as label']),
            'pagination' => $pagination
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $dataArray = [
            'stores'        => Store::all(['id', 'name']),
            'taxes'         => Tax::all(['id', 'name', 'rate']),
            'brands'        => Brand::active()->get(['id', 'name']),
            'custom_fields' => CustomField::ofModel('product')->get(),
            'units'         => Unit::onlyBase()->with('subunits')->get(['id', 'name', 'unit_id']),
            'categories'    => Category::onlyParent()->with('children')->active()->get(['id', 'name', 'category_id']),
        ];
//        return $dataArray;
        return Inertia::render('product/Form', $dataArray);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $product = (new SaveProduct)->execute($request->validated());

        return redirect()->route('products.index')
            ->with('message', __('{model} has been successfully {action}.', [
                'model'  => __('Product'),
                'action' => __('created'),
            ]));
    }

    /**
     * Show the specified resource.
     */
    public function show(Request $request, Product $product)
    {
        $product->load([
            'unit', 'unit.subunits', 'unitPrices',
            'supplier:id,name,company', 'taxes:id,name',
            'products:id,code,name', 'stocks', 'stores', 'variations.stocks',
            'brand:id,name', 'category:id,name,category_id', 'unit:id,code,name',
        ]);

        if ($request->with == 'promotions') {
            $product->load(['validPromotions', 'category.validPromotions']);
        }

        return $product;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $dataArray = [
            'current' => $product->loadMissing(['stores', 'taxes', 'products', 'unitPrices', 'variations']),

            'stores'        => Store::all(['id', 'name']),
            'taxes'         => Tax::all(['id', 'name', 'rate']),
            'brands'        => Brand::active()->get(['id', 'name']),
            'custom_fields' => CustomField::ofModel('product')->get(),
            'units'         => Unit::onlyBase()->with('subunits')->get(['id', 'name', 'unit_id']),
            'categories'    => Category::onlyParent()->with('children')->active()->get(['id', 'name', 'category_id']),
        ];
        return $dataArray;
        return Inertia::render('Sma/Product/Form', $dataArray);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product)
    {
        $product = (new SaveProduct)->execute($request->validated(), $product);

        return redirect()->route('products.index')
            ->with('message', __('{model} has been successfully {action}.', [
                'model'  => __('Product'),
                'action' => __('updated'),
            ]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->{$product->deleted_at ? 'forceDelete' : 'delete'}()) {
            return back()->with('message', __('{model} has been successfully {action}.', [
                'model'  => __('Product'),
                'action' => __('deleted'),
            ]));
        }

        return back()->with('error', __('{model} cannot be {action}. The record is being used for relationships.', [
            'model'  => __('Product'),
            'action' => __('deleted'),
        ]));
    }

    public function destroyMany(Request $request)
    {
        $count = 0;
        $failed = count($request->selection);
        foreach (Product::whereIn('id', $request->selection)->get() as $record) {
            $record->{$request->force ? 'forceDelete' : 'delete'}() ? $count++ : '';
        }

        return back()->with('message', __('The task has completed, {count} deleted and {failed} failed.', ['count' => $count, 'failed' => $failed - $count]));
    }

    public function restore(Product $purchase)
    {
        $purchase->restore();

        return back()->with('message', __('{record} has been {action}.', ['record' => 'Product', 'action' => 'restored']));
    }

    public function destroyPermanently(Product $product)
    {
        if ($product->forceDelete()) {
            return to_route('products.index')->with('message', __('{record} has been {action}.', ['record' => 'Product', 'action' => 'permanently deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }
}
