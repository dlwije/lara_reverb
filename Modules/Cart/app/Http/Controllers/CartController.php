<?php

namespace Modules\Cart\Http\Controllers;

use AllowDynamicProperties;
use App\Http\Controllers\Controller;
use App\Models\Sma\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Modules\Cart\Classes\Cart;

#[AllowDynamicProperties]
class CartController extends Controller
{
    public function __construct(){
        $this->cart = app('cart');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('e-commerce/public/cart/page', [
            'cart' => $this->cart->apiContent()
        ]);
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1|max:9',
            'options' => 'sometimes|array'
        ]);

        $product = Product::findOrFail($request->product_id);

        $this->cart
            ->associate(Product::class)
            ->add(
                $product->id,
                $product->name,
                $request->qty,
                $product->price,
                $request->options ?? []
            );

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
            'data' => $this->cart->apiContent()
        ]);
    }

    public function getCart()
    {
        Log::info('All session data:', $this->cart->apiContent());
        return response()->json([
            'success' => true,
            'data' => $this->cart->apiContent()
        ]);
    }

    public function updateCart(Request $request, $rowId)
    {
        $request->validate([
            'qty' => 'required|integer|min:1|max:9'
        ]);

        if ($request->qty == 0) {
            $this->cart->remove($rowId);
        } else {
            $this->cart->update($rowId, (float) $request->qty);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully',
            'data' => $this->cart->apiContent()
        ]);
    }

    public function removeFromCart($rowId)
    {
        $this->cart->remove($rowId);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'data' => $this->cart->apiContent()
        ]);
    }

    public function clearCart()
    {
        $this->cart->destroy();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully',
            'data' => $this->cart->apiContent()
        ]);
    }

    public function storeCartToDatabase(Request $request)
    {
        DB::beginTransaction();
        try {

            $identifier = $request->input('identifier', null);

            $cart = $this->cart->store($identifier);

            DB::commit();
            return self::success($cart);
        }catch (\Exception $e) {

            DB::rollBack();
            Log::error($e);
            return self::error($e->getMessage(),500);
        }
    }

    public function restoreCart()
    {
        if (auth()->check()) {
            $this->cart->restore(auth()->id(), true);

            return response()->json([
                'success' => true,
                'message' => 'Cart restored successfully',
                'data' => $this->cart->apiContent()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Please login to restore your cart'
        ], 401);
    }

    public function getCartCount()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'count' => $this->cart->count()
            ]
        ]);
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
