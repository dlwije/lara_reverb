<?php

namespace App\Http\Controllers\Sma\Search;

use App\Http\Controllers\Controller;
use App\Models\Sma\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SearchController extends Controller
{
    public $limit = 15;

    public function products(Request $request)
    {
        $products = Product::query()->select(['id', 'code', 'name', 'tax_included', 'type']);

        if($request->id) {
            $ids = is_array($request->id) ? $request->input('id') : Arr::wrap($request->input('id'));

            return Product::with([
                'unit:id,code,name',
                'unit.subunits:id,code,name,unit_id',
                'unitPrices',
                'supplier:id,name,company',
                'taxes:id,name',
                'products:id,code,name',
                'stocks',
                'stores:id,name',
                'variations.stocks',
                'brand:id,name',
                'category:id,name,category_id',
            ])->whereIn('id', $ids)->get();
        }

        if($request->barcode) {
            $products->selectRaw('price, barcode_symbology');
        }

        if ($request->type == 'combo'){
            $products->selectRaw('cost, price, min_price, max_price')->whereNotIn('type', ['Combo', 'Recipe']);
        }

        if(in_array($request->type, ['adjustment', 'purchase'])) {
            $products->ofType('Standard');
        }

        if($request->exact){
            $products->where('code', $request->input('search'));
        }elseif ($request->search){
            $products->search($request->input('search'));
        }

        $products = $products->take($this->limit)->get();
        return $products->load([
            'unit:id,code,name',
            'unit.subunits:id,code,name,unit_id',
            'unitPrices',
            'supplier:id,name,company',
            'taxes:id,name',
            'products:id,code,name',
            'stocks',
            'stores:id,name',
            'variations.stocks',
            'brand:id,name',
            'category:id,name,category_id',
        ]);
    }
}
