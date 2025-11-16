<?php
namespace App\Services;

use App\Models\Sma\Product\Product;

class CheckOverSelling
{
    public function check($order_items, $store_id = null)
    {
        $error = [];
        $store_id ??= session('selected_store_id', null);
        $products = Product::whereIn('id', collect($order_items)->pluck('product_id'))->with(['stocks' => fn ($q) => $q->ofStore($store_id), 'unit.subunits', 'variations', 'variations.stocks' => fn ($q) => $q->ofStore($store_id)])->get();
        foreach ($order_items as $key => $order_item) {
            if (($order_item['type'] ?? null) == 'Gift Card') {
                continue;
            }
            if (isset($order_item['old_quantity'])) {
                $order_item['quantity'] -= $order_item['old_quantity'];
            }
            $product = $products->where('id', $order_item['product_id'])->first();
            if ($product->type == 'Standard') {
                $stock = $product->stocks->first();
                $base_quantity = convert_to_base_unit($product->unit, $order_item['unit_id'] ?? null, $order_item['quantity']);

                if ($product->has_variants) {
                    foreach ($order_item['variations'] as $vi => $va) {
                        if (isset($va['old_quantity'])) {
                            $va['quantity'] -= $va['old_quantity'];
                        }
                        $variation = $product->variations->where('id', $va['id'])->first();
                        $variation_stock = $variation->stocks->first();
                        $base_quantity = convert_to_base_unit($product->unit, $va['unit_id'] ?? null, $va['quantity']);
                        if (! $variation_stock) {
                            $error["items.{$key}.variations.{$vi}.quantity"] = __('{name} does not have {quantity} in stock, available quantity {available}.', ['name' => __('The') . ' ' . __('items') . '.' . $key . '.' . __('variations') . '.' . $vi . '.' . __('quantity'), 'quantity' => $va['quantity'], 'available' => '0']);
                        } elseif ($variation_stock->balance < $base_quantity) {
                            $error["items.{$key}.variations.{$vi}.quantity"] = __('{name} does not have {quantity} in stock, available quantity {available}.', ['name' => __('The') . ' ' . __('items') . '.' . $key . '.' . __('variations') . '.' . $vi . '.' . __('quantity'), 'quantity' => $base_quantity, 'available' => ((float) $variation_stock->balance)]);
                        }
                    }
                }

                if (! $stock) {
                    $error["items.{$key}.quantity"] = __('{name} does not have {quantity} in stock, available quantity {available}.', ['name' => __('The') . ' ' . __('items') . '.' . $key . '.' . __('quantity'), 'quantity' => $base_quantity, 'available' => '0']);
                } elseif ($stock->balance < $base_quantity) {
                    $error["items.{$key}.quantity"] = __('{name} does not have {quantity} in stock, available quantity {available}.', ['name' => __('The') . ' ' . __('items') . '.' . $key . '.' . __('quantity'), 'quantity' => $base_quantity, 'available' => ((float) $stock->balance)]);
                }
            } elseif (in_array($product->type, ['Combo', 'Recipe'])) {
                foreach ($product->products as $combo_product) {
                    if ($combo_product->type == 'Standard') {
                        $quantity = $order_item['quantity'] * $combo_product->pivot->quantity;
                        $combo_stock = $combo_product->stocks()->where('store_id', $store_id)->first();
                        if (! $combo_stock) {
                            $error["items.{$key}.quantity"] = __('{name} does not have {quantity} in stock, available quantity {available}.', ['name' => $combo_product->name, 'quantity' => $quantity, 'available' => '0']);
                        } elseif ($combo_stock->balance < $quantity) {
                            $error["items.{$key}.quantity"] = __('{name} does not have {quantity} in stock, available quantity {available}.', ['name' => $combo_product->name, 'quantity' => $quantity, 'available' => ((float) $combo_stock->balance)]);
                        }
                    }
                }
            }
        }

        return $error;
    }
}
