<?php

namespace Modules\Cart\Http\Controllers\Api;

use AllowDynamicProperties;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Cart\Classes\ApiCart;

#[AllowDynamicProperties]
class CartApiController extends Controller
{
    public function __construct(ApiCart $cart){
        $this->cart = $cart->instance('apicart');
    }

    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => $this->cart->apiContent()
        ]);
    }

    public function show()
    {
        return $this->index();
    }

    public function add(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'name' => 'required|string',
            'qty' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'options' => 'sometimes|array',
            'tax_rate' => 'sometimes|numeric|min:0'
        ]);

        $item = $this->cart->add(
            $request->id,
            $request->name,
            $request->qty,
            $request->price,
            $request->options ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'data' => $item,
            'cart' => $this->cart->apiContent()
        ]);
    }

    public function update($rowId, Request $request)
    {
        $request->validate([
            'qty' => 'required|integer|min:0'
        ]);

        if ($request->qty == 0) {
            $this->cart->remove($rowId);
            $message = 'Item removed from cart';
        } else {
            $this->cart->update($rowId, $request->qty);
            $message = 'Cart updated successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'cart' => $this->cart->apiContent()
        ]);
    }

    public function remove($rowId)
    {
        $this->cart->remove($rowId);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'cart' => $this->cart->apiContent()
        ]);
    }

    public function clear()
    {
        $this->cart->destroy();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully',
            'cart' => $this->cart->apiContent()
        ]);
    }

    public function setShipping(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0'
        ]);

        $this->cart->setShipping($request->amount);

        return response()->json([
            'success' => true,
            'message' => 'Shipping cost updated',
            'cart' => $this->cart->apiContent()
        ]);
    }

    public function setDiscount(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'coupon_code' => 'sometimes|string'
        ]);

        $this->cart->setDiscount($request->amount, $request->coupon_code);

        return response()->json([
            'success' => true,
            'message' => 'Discount applied',
            'cart' => $this->cart->apiContent()
        ]);
    }

    public function mergeWithUser()
    {
        if (Auth::check()) {
            $this->cart->mergeWithUserCart(Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Cart merged with user cart',
                'cart' => $this->cart->apiContent()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }
}
