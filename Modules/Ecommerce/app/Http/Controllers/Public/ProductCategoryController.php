<?php

namespace Modules\Ecommerce\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Sma\Product\Category;
use App\Models\Sma\Setting\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductCategoryController extends Controller
{
    public function index() {}

    /**
     * Display category by slug
     */
    public function show(Request $request, $slug)
    {
        $category = Category::getBySlug($slug);

        $filters = $request->input('filters') ?? $request->only(['search', 'sort', 'price_min', 'price_max', 'brand']);

        // Get products with filters using similar structure to index
        $products = $category->products()
            ->with('supplier:id,name,company', 'taxes:id,name', 'stocks', 'brand:id,name', 'category:id,name,category_id', 'unit:id,code,name')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($filters['price_min'] ?? null, function ($query, $priceMin) {
                $query->where('price', '>=', $priceMin);
            })
            ->when($filters['price_max'] ?? null, function ($query, $priceMax) {
                $query->where('price', '<=', $priceMax);
            })
            ->when($filters['brand'] ?? null, function ($query, $brand) {
                $query->whereHas('brand', function ($q) use ($brand) {
                    $q->where('id', $brand);
                });
            })
            ->when($filters['sort'] ?? null, function ($query, $sort) {
                switch ($sort) {
                    case 'price_low':
                        $query->orderBy('price', 'asc');
                        break;
                    case 'price_high':
                        $query->orderBy('price', 'desc');
                        break;
                    case 'popular':
                        $query->withCount('saleItems') // Make sure this relationship exists
                        ->orderBy('sale_items_count', 'desc');
                        break;
                    case 'name':
                        $query->orderBy('name');
                        break;
                    case 'newest':
                    default:
                        $query->latest('id')->orderBy('name');
                        break;
                }
            }, function ($query) {
                // Default sorting if no sort specified
                $query->latest('id')->orderBy('name');
            })
            ->paginate(12);

        $data_array = [
            'category' => $category,
            'products' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
                'links'        => $products->linkCollection()->toArray(),
            ],
            'filters' => $filters,
            'breadcrumb' => $category->getBreadcrumb(),
            'childCategories' => $category->children,
            'parentCategory' => $category->category,
            // Add any additional data you need
            'stores' => Store::active()->get(['id as value', 'name as label']), // If needed
        ];


        return Inertia::render('e-commerce/public/category/CategoryShow', $data_array);
    }

    /**
     * API endpoint for category slider
     */
    public function apiCategories($type = 'popular')
    {
        switch ($type) {
            case 'trending':
                $categories = Category::trending(10)->get();
                break;
            case 'featured':
                $categories = Category::featured(8)->get();
                break;
            case 'most_products':
                $categories = Category::mostProducts(10)->get();
                break;
            case 'popular':
            default:
                // Use simple version to avoid the error
                $categories = Category::getSimplePopularForSlider(10);
                break;
        }

        return self::success($categories);
    }
}
