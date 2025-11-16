<?php

namespace Modules\Wishlist\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sma\Product\Product;
use Illuminate\Http\Request;
use Modules\Cart\app\Facades\Cart;
use Modules\Wishlist\Facades\Wishlist;

class WishlistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $wishlist = Wishlist::instance('wishlist')
            ->associate(Product::class)
            ->apiContent();

        return response()->json($wishlist);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);

        Wishlist::instance('wishlist')
            ->associate(Product::class)
            ->add(
                $product->id,
                $product->name,
                $product->price,
                [
                    'image' => $product->image,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                ]
            );

        return response()->json([
            'message' => 'Product added to wishlist',
            'wishlist' => Wishlist::apiContent(),
            'count' => Wishlist::count()
        ]);
    }

    public function remove($rowId)
    {
        Wishlist::instance('wishlist')->remove($rowId);

        return response()->json([
            'message' => 'Item removed from wishlist',
            'wishlist' => Wishlist::apiContent(),
            'count' => Wishlist::count()
        ]);
    }

    public function clear()
    {
        Wishlist::instance('wishlist')->destroy();

        return response()->json([
            'message' => 'Wishlist cleared',
            'wishlist' => Wishlist::apiContent()
        ]);
    }

    public function moveToCart(Request $request)
    {
        $request->validate([
            'row_id' => 'required',
            'qty' => 'nullable|integer|min:1'
        ]);

        $cartItem = Wishlist::instance('wishlist')
            ->moveToCart($request->row_id, $request->qty ?? 1);

        return response()->json([
            'message' => 'Item moved to cart',
            'cart_item' => $cartItem,
            'wishlist' => Wishlist::apiContent(),
            'cart' => Cart::apiContent()
        ]);
    }

    public function save()
    {
        if (auth()->check()) {
            Wishlist::instance('wishlist')->store(auth()->id());

            return response()->json([
                'message' => 'Wishlist saved successfully'
            ]);
        }

        return response()->json([
            'message' => 'Please login to save your wishlist'
        ], 401);
    }

    public function restore()
    {
        if (auth()->check()) {
            Wishlist::instance('wishlist')->restore(auth()->id(), true);

            return response()->json([
                'message' => 'Wishlist restored',
                'wishlist' => Wishlist::apiContent()
            ]);
        }

        return response()->json([
            'message' => 'Please login to restore your wishlist'
        ], 401);
    }

    public function check($productId)
    {
        $exists = Wishlist::instance('wishlist')->exists($productId);

        return response()->json([
            'in_wishlist' => $exists
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('wishlist::create');
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
        return view('wishlist::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('wishlist::edit');
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
