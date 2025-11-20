<?php

namespace Modules\Ecommerce\Http\Controllers\Public;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Collection;
use App\Models\Sma\Product\Promotion;
use App\Models\Sma\Setting\CustomField;
use App\Models\Sma\Setting\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        Log::info('Product Show:', ['product' => $product]);
        return Inertia::render('e-commerce/public/product/page', [
            'single_product' => $product, // pass as prop
        ]);
    }

    /**
     * Get latest products for homepage
     */
    public function getLatestProducts(Request $request)
    {
        $limit = $request->get('limit', 6);

        $filters = $request->input('filters') ?? [];

        if (! ($filters['store'] ?? null) && session('selected_store_id', null) && Store::count() > 1) {
            $filters['store'] = session('selected_store_id');
        }

        $products = Product::with(
            'supplier:id,name,company', 'taxes:id,name', 'stocks',
            'brand:id,name', 'category:id,name,category_id', 'unit:id,code,name',
        )
            ->filter($filters)
            ->latest('created_at')
            ->take($limit)
            ->get();

        // Return JSON for API or Inertia response for frontend
        if ($request->expectsJson()) {
            return response()->json([
                'products' => $products
            ]);
        }

        return Inertia::render('e-commerce/public/components/LatestProducts', [
            'products' => $products
        ]);
    }

    /**
     * Get best selling products for homepage
     */
    public function getBestSellingProducts(Request $request)
    {
        $limit = $request->get('limit', 4);
        $period = $request->get('period', 'all');

        $filters = $request->input('filters') ?? [];

        if (! ($filters['store'] ?? null) && session('selected_store_id', null) && Store::count() > 1) {
            $filters['store'] = session('selected_store_id');
        }

        $products = Product::with([
            'supplier:id,name,company',
            'taxes:id,name',
            'stocks',
            'brand:id,name',
            'category:id,name,category_id',
            'unit:id,code,name',
        ])
            ->whereHas('saleItems')
            ->withCount(['saleItems as total_sold' => function($query) use ($period, $filters) {
                $query->whereHas('sale', function($saleQuery) use ($period, $filters) {
                    // Apply period filter
                    if ($period === 'monthly') {
                        $saleQuery->where('created_at', '>=', now()->subMonth());
                    } elseif ($period === 'weekly') {
                        $saleQuery->where('created_at', '>=', now()->subWeek());
                    } elseif ($period === 'yearly') {
                        $saleQuery->where('created_at', '>=', now()->subYear());
                    }

                    // Apply store filter
                    if (isset($filters['store'])) {
                        $saleQuery->where('store_id', $filters['store']);
                    }

                    // Only completed sales
//                    $saleQuery->where('status', 'completed');
                });

                $query->select(DB::raw('COALESCE(SUM(quantity), 0)'));
            }])
            ->orderBy('total_sold', 'desc')
            ->take($limit)
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'products' => $products
            ]);
        }

        return Inertia::render('e-commerce/public/components/BestSellingProducts', [
            'products' => $products
        ]);
    }

    public function featuredProducts()
    {
        $products = Product::with(['brand', 'category'])
            ->where('featured', 1)
//            ->where('active', 1)
            ->where('hide_in_shop', 0)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'currency' => default_currency(),
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'original_price' => $product->cost,
                    'on_sale' => $product->on_sale,
                    'photo' => ImageHelper::posImageUrl($product->photo) ?: null,
                    'secondary_name' => $product->secondary_name,
                    'brand' => $product->brand?->name,
                    'category' => $product->category?->name,
                    'featured' => $product->featured,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Featured products retrieved successfully',
            'data' => $products
        ]);
    }

    public function hotDeals()
    {
        $products = Product::with(['brand'])
            ->where('on_sale', 1)
//            ->where('active', 1)
            ->where('hide_in_shop', 0)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'original_price' => $product->cost,
                    'photo' => ImageHelper::posImageUrl($product->photo) ?: null,
                    'end_date' => $product->end_date,
                    // Add stock information if available
                    'stock_percentage' => 75, // Example
                    'sold_count' => 45, // Example
                    'stock_available' => 15, // Example
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Hot deals retrieved successfully',
            'data' => $products
        ]);
    }

    public function newArrivals()
    {
        $products = Product::with(['brand'])
//            ->where('active', 1)
            ->where('hide_in_shop', 0)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'photo' => ImageHelper::posImageUrl($product->photo) ?: null,
                    'created_at' => $product->created_at,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'New arrivals retrieved successfully',
            'data' => $products
        ]);
    }

    public function getPromotionProducts(Request $request)
    {
        $filters = $request->input('filters') ?? [];

        if (!($filters['store'] ?? null) && session('selected_store_id', null) && Store::count() > 1) {
            $filters['store'] = session('selected_store_id');
        }

        // Get active promotions with their products
        $promotions = Promotion::with([
            'products' => function($query) use ($filters) {
                $query->with([
                    'supplier:id,name,company',
                    'taxes:id,name',
                    'stocks' => function($q) use ($filters) {
                        if ($filters['store'] ?? null) {
                            $q->where('store_id', $filters['store']);
                        }
                    },
                    'brand:id,name',
                    'category:id,name,category_id',
                    'unit:id,code,name',
                    'variations.stocks'
                ]);
            },
            'productToBuy',
            'productToGet'
        ])
            ->valid()
            ->latest('id')
            ->get();

        // Transform promotions data for frontend
        $promotionData = $promotions->map(function($promotion) {
            return [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'type' => $promotion->type,
                'discount' => $promotion->discount,
                'discount_method' => $promotion->discount_method,
                'coupon_code' => $promotion->coupon_code,
                'start_date' => $promotion->start_date,
                'end_date' => $promotion->end_date,
                'details' => $promotion->details,
                'products' => $promotion->products->map(function($product) use ($promotion) {
                    return $this->transformProductWithPromotion($product, $promotion);
                }),
                'promotion_rules' => $this->getPromotionRules($promotion)
            ];
        });

        // Also get individual promoted products
        $promotedProducts = Product::with([
            'supplier:id,name,company',
            'taxes:id,name',
            'stocks' => function($q) use ($filters) {
                if ($filters['store'] ?? null) {
                    $q->where('store_id', $filters['store']);
                }
            },
            'brand:id,name',
            'category:id,name,category_id',
            'unit:id,code,name',
            'variations.stocks',
            'promotions' => function($q) {
                $q->active()->select('id', 'name', 'type', 'discount', 'discount_method');
            }
        ])
            ->whereHas('promotions', function($q) {
                $q->active();
            })
            ->filter($filters)
            ->latest('products.id')
            ->paginate(12);

        $data_array = [
            'promotions' => $promotionData,
            'promoted_products' => $promotedProducts->items(),
            'custom_fields' => CustomField::ofModel('product')->get(),
            'stores' => Store::active()->get(['id as value', 'name as label']),
            'pagination' => [
                'current_page' => $promotedProducts->currentPage(),
                'last_page' => $promotedProducts->lastPage(),
                'per_page' => $promotedProducts->perPage(),
                'total' => $promotedProducts->total(),
                'links' => $promotedProducts->linkCollection()->toArray(),
            ],
        ];

        return Inertia::render('e-commerce/public/promotions/promotion-list', $data_array);
    }

    private function transformProductWithPromotion($product, $promotion)
    {
        $originalPrice = floatval($product->price);
        $discountedPrice = $originalPrice;
        $discountAmount = 0;
        $discountPercentage = 0;

        // Calculate discounted price based on promotion type
        if ($promotion->discount_method === 'percentage' && $promotion->discount) {
            $discountAmount = ($originalPrice * floatval($promotion->discount)) / 100;
            $discountedPrice = $originalPrice - $discountAmount;
            $discountPercentage = $promotion->discount;
        } elseif ($promotion->discount_method === 'fixed' && $promotion->discount) {
            $discountAmount = floatval($promotion->discount);
            $discountedPrice = $originalPrice - $discountAmount;
            $discountPercentage = ($discountAmount / $originalPrice) * 100;
        }

        // Calculate stock
        $stockQuantity = $product->stocks->sum('balance') ?? 0;
        $inStock = $stockQuantity > 0;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'code' => $product->code,
            'description' => $product->description,
            'original_price' => $originalPrice,
            'discounted_price' => $discountedPrice,
            'discount_amount' => $discountAmount,
            'discount_percentage' => round($discountPercentage),
            'photo' => $product->photo,
            'brand' => $product->brand,
            'category' => $product->category,
            'in_stock' => $inStock,
            'stock_quantity' => $stockQuantity,
            'promotion_type' => $promotion->type,
            'promotion_name' => $promotion->name,
            'promotion_end_date' => $promotion->end_date,
        ];
    }

    private function getPromotionRules($promotion)
    {
        $rules = [];

        switch ($promotion->type) {
            case 'discount':
                if ($promotion->discount_method === 'percentage') {
                    $rules[] = "{$promotion->discount}% off";
                } else {
                    $rules[] = format_currency($promotion->discount) . " off";
                }
                break;

            case 'buy_x_get_y':
                if ($promotion->productToBuy && $promotion->productToGet) {
                    $rules[] = "Buy {$promotion->quantity_to_buy} {$promotion->productToBuy->name}, Get {$promotion->quantity_to_get} {$promotion->productToGet->name}";
                }
                break;

            case 'spend_and_save':
                if ($promotion->amount_to_spend && $promotion->discount) {
                    $rules[] = "Spend " . format_currency($promotion->amount_to_spend) . " and get " .
                        ($promotion->discount_method === 'percentage' ? "{$promotion->discount}% off" : format_currency($promotion->discount) . " off");
                }
                break;

            case 'coupon':
                if ($promotion->coupon_code) {
                    $rules[] = "Use code: {$promotion->coupon_code}";
                }
                break;
        }

        return $rules;
    }
}
