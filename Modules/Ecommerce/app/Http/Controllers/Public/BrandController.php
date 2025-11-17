<?php

namespace Modules\Ecommerce\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Sma\Product\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function featuredBrands()
    {
        $brands = Brand::withCount(['products' => function ($query) {
            $query->where('active', 1)
                ->where('hide_in_shop', 0)
                ->whereNull('deleted_at');
        }])
            ->whereHas('products', function ($query) {
                $query->where('active', 1)
                    ->where('hide_in_shop', 0)
                    ->whereNull('deleted_at');
            })
            ->orderByDesc('products_count')
            ->take(12)
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'logo' => $brand->logo ? asset('storage/' . $brand->logo) : null,
                    'products_count' => $brand->products_count,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Featured brands retrieved successfully',
            'data' => $brands
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('ecommerce::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('ecommerce::create');
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
        return view('ecommerce::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('ecommerce::edit');
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
