<?php

namespace App\Actions\Sma;

use App\Models\Sma\Order\Sale;
use App\Models\Sma\Product\Promotion;
use Illuminate\Support\Facades\DB;
use Modules\Cart\Models\CartItem;
use Modules\Ecommerce\Services\CartHelper;

class FreeItem
{
    public static function check($product, $freeItem)
    {
        $promotion = Promotion::valid()->where('product_id_to_buy', $product->product_id)->where('product_id_to_get', $freeItem->product_id)->where('type', 'BXGY')->first();

        if ($promotion) {
            return $promotion->quantity_to_buy <= $product->quantity && $freeItem->quantity == floor($product->quantity / $promotion->quantity_to_buy);
        }

        return false;
    }

    public static function update($productId, $quantity, $cartId = null)
    {
        if (! $cartId) {
            $cartId = session('cart_id');
        }
        $promotions = Promotion::valid()->where('product_id_to_buy', $productId)->where('type', 'BXGY')->get();
        if ($promotions->isNotEmpty()) {
            $freeItems = CartItem::where('cart_id', $cartId)->where('oId', $productId)->get();
            foreach ($promotions as $promotion) {
                if ($freeItems->isNotEmpty() && $freeItem = $freeItems->where('product_id', $promotion->product_id_to_get)->first()) {
                    if ($promotion->quantity_to_buy <= $quantity) {
                        $freeItem->update(['quantity' => floor($quantity / $promotion->quantity_to_buy)]);
                    } elseif ($promotion->product_id_to_get == $freeItem->product_id) {
                        $freeItem->delete();
                    }
                } elseif ($promotion->quantity_to_buy <= $quantity) {
                    $addCom = new CartHelper;
                    $addCom->add($promotion->product_id_to_get, floor($quantity / $promotion->quantity_to_buy), $productId)->submit();
                }
            }
        }
    }

}
