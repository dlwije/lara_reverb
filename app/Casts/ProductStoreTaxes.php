<?php

namespace App\Casts;

use App\Models\Sma\Setting\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ProductStoreTaxes implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value) {
            $value = json_decode($value, true);
            // $value[] = ['taxes' => Tax::whereIn('id', $value)->get()];

            return ['value' => $value, 'taxes' => $value ? Tax::whereIn('id', $value)->get(['id', 'name', 'code', 'rate', 'state', 'same', 'compound', 'recoverable']) : []];
        }

        return [];
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return json_encode($value);
    }
}
