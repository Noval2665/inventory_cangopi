<?php

namespace App\Http\Controllers;

use App\Models\ProductInfo;
use Illuminate\Http\Request;

class ProductInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        if ($search) {
            $page = 1;
        }

        $inventory_id = $request->inventory_id;

        $productsInfo = ProductInfo::with(['product', 'product.category', 'product.unit'])->when($search, function ($query, $search) {
            return $query->whereHas('product', function ($query) use ($search) {
                return $query->where('name', 'like', '%' . $search . '%');
            });
        })->when($inventory_id, function ($query, $inventory_id) {
            return $query->where('inventory_id', $inventory_id);
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data informasi produk',
            'products_info' => $productsInfo
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
