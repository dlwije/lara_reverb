<?php

namespace App\Traits;

trait HasStock
{
    public function adjustProductStock($type, $quantity, $data)
    {
        $data['store_id'] ??= session('selected_store_id', config('app.default_store_id'));
        unset($data['reference']);
        if ($quantity && $data['store_id'] && $data['description']) {
            $data['product_id'] = $this->id;
            $this->getProductStock($data['store_id'])->{$type . 'Balance'}($quantity, $data);
        } elseif (! ($data['description'] ?? null)) {
            throw new Exception('Please provide data description to adjustProductStock');
        }
    }

    public function adjustVariationStock($type, $quantity, $data)
    {
        $data['store_id'] ??= session('selected_store_id', null);
        if ($quantity && $data['store_id'] && $data['description']) {
            $data['variation_id'] = $this->id;
            $data['product_id'] = $this->product->id;
            $this->getVariationStock($data['store_id'])->{$type . 'Balance'}($quantity, $data);
        } elseif (! ($data['description'] ?? null)) {
            throw new Exception('Please provide data description to adjustVariationStock');
        }
    }

    public function getProductStock($store_id = null)
    {
        $store_id ??= session('selected_store_id', config('app.default_store_id'));
        $stock = $this->storeStock($store_id)->first();
        // $stock = $this->stocks()->ofStore($store_id)->first();
        if (! $stock) {
            $stock = $this->stocks()->create([
                'store_id'       => $store_id,
                'product_id'     => $this->id,
                'rack_location'  => $this->rack_location,
                'alert_quantity' => $this->alert_quantity,
            ]);
            $stock->setBalance(0, [
                'store_id'    => $store_id,
                'product_id'  => $this->id,
                'description' => 'Initial stock',
            ]);
        }

        return $stock;
    }

    public function getVariationStock($store_id = null)
    {
        $store_id ??= session('selected_store_id');
        $stock = $this->storeStock($store_id)->first();
        // $stock = $this->stocks()->ofStore($store_id)->first();
        if (! $stock) {
            $stock = $this->stocks()->create([
                'store_id'       => $store_id,
                'product_id'     => $this->product->id,
                'rack_location'  => $this->product->rack_location,
                'alert_quantity' => $this->product->alert_quantity,
            ]);
            $stock->setBalance(0, [
                'store_id'     => $store_id,
                'variation_id' => $this->id,
                'product_id'   => $this->product->id,
                'description'  => 'Initial stock',
            ]);
        }

        return $stock;
    }

    public function setProductStock()
    {
        foreach ($this->stores as $store) {
            $stock = $this->stocks->where('store_id', $store->id)->first();
            if (! $stock) {
                $stock = $this->stocks()->create([
                    'store_id'       => $store->id,
                    'rack_location'  => $this->rack_location,
                    'alert_quantity' => $this->alert_quantity ?? 0,
                ]);

                if ($this->has_variants) {
                    $quantity = $this->variations()->with(['stocks' => fn ($q) => $q->ofStore($store->id)])->get()->sum(fn ($v) => $v->stocks->sum('balance'));
                    $stock->setBalance($quantity, [
                        'store_id'    => $store->id,
                        'product_id'  => $this->id,
                        'description' => 'Initial stock',
                    ]);
                } else {
                    $stock->setBalance($store->pivot->quantity ?: 0, [
                        'store_id'    => $store->id,
                        'product_id'  => $this->id,
                        'description' => 'Initial stock',
                    ]);
                }
            } elseif ($this->has_variants) {
                $quantity = $this->variations()->with(['stocks' => fn ($q) => $q->ofStore($store->id)])->get()->sum(fn ($v) => $v->stocks->sum('balance'));
                $stock->setBalance($quantity, [
                    'store_id'    => $store->id,
                    'product_id'  => $this->id,
                    'description' => 'Update due to variations stock change',
                ]);
            }
        }
    }
}
