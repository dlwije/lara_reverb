<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Collection;
use App\Models\Sma\Product\Product;
use App\Models\Sma\Setting\CustomField;
use App\Models\Sma\Setting\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

        return [
            'custom_fields' => CustomField::ofModel('product')->get(),
            'stores'        => Store::active()->get(['id as value', 'name as label']),

            'pagination' => new Collection(Product::with(
                'supplier:id,name,company', 'taxes:id,name', 'stocks',
                'brand:id,name', 'category:id,name,category_id', 'unit:id,code,name',
            )->filter($filters)->latest('id')->orderBy('name')->paginate()->withQueryString()),
        ];

        return Inertia::render('Product/Index', [
            'custom_fields' => CustomField::ofModel('product')->get(),
            'stores'        => Store::active()->get(['id as value', 'name as label']),

            'pagination' => new Collection(Product::with(
                'supplier:id,name,company', 'taxes:id,name', 'stocks',
                'brand:id,name', 'category:id,name,category_id', 'unit:id,code,name',
            )->filter($filters)->latest('id')->orderBy('name')->paginate()->withQueryString()),
        ]);

//        return inertia('Product/Index', []);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('product::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('product::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
