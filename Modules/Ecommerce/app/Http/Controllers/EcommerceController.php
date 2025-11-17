<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sma\Order\SaleItem;
use App\Models\Sma\Product\Brand;
use App\Models\Sma\Product\Category;
use App\Models\Sma\Product\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Product\Models\Product;

class EcommerceController extends Controller
{
    public function home()
    {
        return Inertia::render('e-commerce/public/home/home', []);
    }
    public function aboutUs()
    {
        return Inertia::render('e-commerce/public/about/page', []);
    }
    public function contactUs()
    {
        return Inertia::render('e-commerce/public/contact/page', []);
    }

    /**
     * Get top selling products
     */
    public function topSellingProducts()
    {
        try {
            $topSelling = SaleItem::select([
                'product_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue')
            ])
                ->with(['product' => function($query) {
                    $query->select(['id', 'name', 'slug', 'price', 'photo', 'secondary_name', 'brand_id', 'category_id'])
                        ->with(['brand:id,name', 'category:id,name'])
                        ->where('active', 1)
                        ->where('hide_in_shop', 0);
                }])
                ->whereHas('product', function($query) {
                    $query->where('active', 1)->where('hide_in_shop', 0);
                })
                ->groupBy('product_id')
                ->orderByDesc('total_quantity')
                ->take(8)
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Top selling products retrieved successfully',
                'data' => $topSelling
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve top selling products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active promotions with categories
     */
    public function activePromotions()
    {
        try {
            $promotions = Promotion::with(['categories' => function($query) {
                $query->select(['id', 'name', 'slug'])->where('active', 1);
            }])
                ->where('active', 1)
                ->where(function($query) {
                    $query->whereNull('start_date')
                        ->orWhere('start_date', '<=', now());
                })
                ->where(function($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })
                ->orderBy('created_at', 'desc')
                ->take(6)
                ->get(['id', 'name', 'type', 'start_date', 'end_date', 'active', 'discount', 'discount_method']);

            return response()->json([
                'status' => true,
                'message' => 'Active promotions retrieved successfully',
                'data' => $promotions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve promotions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured brands with product counts
     */
    public function featuredBrands()
    {
        try {
            $brands = Brand::withCount(['products' => function($query) {
                $query->where('active', 1)
                    ->where('hide_in_shop', 0)
                    ->whereNull('deleted_at');
            }])
                ->where('active', 1)
                ->whereHas('products', function($query) {
                    $query->where('active', 1)
                        ->where('hide_in_shop', 0)
                        ->whereNull('deleted_at');
                })
                ->orderBy('order')
                ->orderBy('name')
                ->take(12)
                ->get(['id', 'name', 'slug', 'photo', 'order', 'active']);

            return response()->json([
                'status' => true,
                'message' => 'Featured brands retrieved successfully',
                'data' => $brands
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve brands',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get homepage statistics
     */
    public function homepageStats()
    {
        try {
            $stats = [
                'total_products' => Product::where('active', 1)
                    ->where('hide_in_shop', 0)
                    ->whereNull('deleted_at')
                    ->count(),
                'total_categories' => Category::where('active', 1)
                    ->whereNull('deleted_at')
                    ->count(),
                'total_brands' => Brand::where('active', 1)
                    ->whereNull('deleted_at')
                    ->count(),
                'featured_products' => Product::where('featured', 1)
                    ->where('active', 1)
                    ->where('hide_in_shop', 0)
                    ->whereNull('deleted_at')
                    ->count(),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Homepage statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
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
