<?php

namespace Modules\Cart\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sma\Product\Product;
use Illuminate\Http\Request;
use Modules\Cart\Facades\Cart;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $product = Product::find($request->product_id);

        Cart::instance('cart')->associate(Product::class)->add(
            $product->id,
            $product->name,
            $request->qty,
            $product->price,
            ['image' => $product->image]
        );

        return response()->json([
            'message' => 'Product added to cart',
            'cart' => Cart::apiContent()
        ]);
    }

    public function getCart()
    {
        return response()->json(Cart::instance('cart')->apiContent());
    }

    public function updateCart(Request $request)
    {
        Cart::instance('cart')->update($request->row_id, $request->qty);

        return response()->json([
            'message' => 'Cart updated',
            'cart' => Cart::apiContent()
        ]);
    }

    public function removeFromCart($rowId)
    {
        Cart::instance('cart')->remove($rowId);

        return response()->json([
            'message' => 'Item removed from cart',
            'cart' => Cart::apiContent()
        ]);
    }

    public function saveCart(Request $request)
    {
        $user = auth()->user();
        Cart::instance('cart')->store($user->id);

        return response()->json(['message' => 'Cart saved successfully']);
    }

    public function restoreCart()
    {
        $user = auth()->user();
        Cart::instance('cart')->restore($user->id, true);

        return response()->json([
            'message' => 'Cart restored',
            'cart' => Cart::apiContent()
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('cart::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cart::create');
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
        return view('cart::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('cart::edit');
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
