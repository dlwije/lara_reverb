<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OfStore implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();
        $store_id = session('selected_store_id', false);
        if ($store_id || ! ($user && ($user->hasRole('Super Admin') || $user->can('read-all')))) {
            $builder->where('store_id', session('selected_store_id'));
        }
    }
}
