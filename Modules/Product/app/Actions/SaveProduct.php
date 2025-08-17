<?php

namespace Modules\Product\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;

class SaveProduct
{
    public function execute(array $data, Product $product = new Product): Product
    {
        // logger()->info('Add product ', $data);
        $product = DB::transaction(function () use ($data, $product) {
            $creating = ! $product->id;
            $taxes = $data['taxes'] ?? [];
            $photos = $data['photos'] ?? [];
            $stores = $data['stores'] ?? null;
            $serials = $data['serials'] ?? null;
            $products = $data['products'] ?? null;
            $variations = $data['variations'] ?? null;
            $unit_prices = $data['unit_prices'] ?? null;
            unset($data['stores'], $data['taxes'], $data['photos'], $data['products'], $data['unit_prices'], $data['serials'], $data['variations']);

            $product->fill($data)->save();
            $product->taxes()->sync($taxes);

            if (! empty($variations)) {
                $uIds = [];
                $item_variations = $product->variations;
                foreach ($variations as $variation) {
                    if (isset($variation['meta']) && ! empty($variation['meta'])) {
                        $quantity = $variation['quantity'] ?? 0;
                        unset($variation['quantity']);
                        // $instance = $product->variations()->updateOrCreate(['sku' => $variation['sku']], $variation);
                        $instance = $item_variations->firstWhere('meta', $variation['meta']);
                        if ($instance) {
                            unset($variation['sku']);
                            $instance->update($variation);
                        } else {
                            $instance = $product->variations()->create($variation);
                        }

                        if ($quantity) {
                            $instance->stock->setBalance($quantity ?: 0, [
                                'description' => $creating ? 'Initial stock' : 'Stock adjustment by ' . auth()->user()->name,
                            ]);
                            // foreach ($variation['stock'] as $stock) {
                            //     $stock['avg_cost'] = $stock['cost'];
                            //     $instance->stock()->updateOrCreate(['location_id' => $stock['location_id']], $stock);
                            // }
                        }
                        $uIds[] = $instance->id;
                    }
                }

                $nonUsed = $product->variations()->whereNotIn('id', $uIds)->get();
                if ($nonUsed) {
                    foreach ($nonUsed as $value) {
                        $value->stock()->delete();
                        $value->delete();
                    }
                }
            } else {
                $product->variations->each->delete();
            }
            if (! empty($unit_prices)) {
                foreach ($unit_prices as $unit_id => $unit_price) {
                    if ($unit_price['cost'] || $unit_price['price']) {
                        $product->unitPrice()->create([
                            'unit_id' => $unit_id,
                            'cost'    => $unit_price['cost'],
                            'price'   => $unit_price['price'],
                        ]);
                    }
                }
            }
            if (! empty($serials)) {
                foreach ($serials as $serial) {
                    if ($serial['number'] && empty($serial['till'])) {
                        $product->serials()->create(['number' => $serial['number']]);
                    } elseif ($serial['number'] && ! empty($serial['till'])) {
                        for ($i = $serial['number']; $i <= $serial['till']; $i++) {
                            $product->serials()->create(['number' => $i]);
                        }
                    }
                }
                // $item->serials()->createMany($serials);
            }

            if ($products) {
                $combo_items = [];
                foreach ($products as $p) {
                    $combo_items[$p['id']] = ['quantity' => $p['quantity']];
                }
                $product->products()->sync($combo_items);
            }

            if ($stores) {
                $stores_array = [];
                foreach ($stores as $store) {
                    $stores_array[$store['store_id']] = [
                        'price'          => ($store['price'] ?? null) ?: null,
                        'taxes'          => $store['taxes'] ?? null,
                        'quantity'       => $store['quantity'] ?? 0,
                        'alert_quantity' => $store['alert_quantity'] ?? 0,
                    ];
                }
                $product->stores()->sync($stores_array);
            }

            $product->saveAttachments($photos);

            return $product;
        });

        return $product;
    }
}
