<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sma\Product\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::index');
    }

    public function dropDownList(Request $request)
    {
        try {

            $page = $request->page;
            $res_count = $request->resCount;
            $search_term = $request->searchTerm;

            $paginator = Category::where('active', 1)
                ->where(function ($q) use ($search_term) {
                    $q->where('name', 'LIKE', "%$search_term%")
                        ->orWhere('description', 'LIKE', "%$search_term%");
                })
                ->paginate($res_count, ['*'], 'page', $page);

            // Convert to your format if needed:
            $data = $paginator->map(function ($item) {
                return ['id' => $item->id, 'text' => $item->name];
            });
            return [
                'status' => true,
                'results' => $data,
                'pagination' => ['more' => $paginator->hasMorePages()],
            ];
        }catch (\Exception $e){
            Log::error("select2Category: ".$e);
            return [
                'status' => false,
                'results' => [],
                'pagination' => ['more' => false],
            ];
        }
    }
    public function subDropDownList(Request $request)
    {
        try {

            $page = $request->page;
            $res_count = $request->resCount;
            $search_term = $request->searchTerm;

            $paginator = Category::where('active', 1)->where('category_id', $request->id)
                ->where(function ($q) use ($search_term) {
                    $q->where('name', 'LIKE', "%$search_term%")
                        ->orWhere('description', 'LIKE', "%$search_term%");
                })
                ->paginate($res_count, ['*'], 'page', $page);

            // Convert to your format if needed:
            $data = $paginator->map(function ($item) {
                return ['id' => $item->id, 'text' => $item->name];
            });
            return [
                'results' => $data,
                'pagination' => ['more' => $paginator->hasMorePages()],
            ];
        }catch (\Exception $e){
            Log::error("select2SubCategory: ".$e);
            return [
                'status' => false,
                'results' => [],
                'pagination' => ['more' => false],
            ];
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product::create');
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
        return view('product::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('product::edit');
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
