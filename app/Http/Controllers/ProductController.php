<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $products = Product::with([
            'subCategory',
            'subCategory.category',
            'brand',
            'storage',
            'unit',
            'metric',
            'supplier',
        ])
            ->when($search, function ($query, $search) {
                return $query->where('product_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('product_code', 'LIKE', '%' . $search . '%');
            })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data produk',
            'products' => $products,
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
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'product_code' => 'required|string',
            'product_name' => 'required|string',
            'purchase_price' => 'required|double',
            'stock' => 'required|numeric',
            'measurement' => 'required|numeric',
            'image' => 'nullable|string',

            'sub_category_id' => 'required|numeric|exists:sub_categories,id',
            'brand_id' => 'required|numeric|exists:brands,id',
            'storage_id' => 'required|numeric|exists:storages,id',
            'unit_id' => 'required|numeric|exists:units,id',
            'metric_id' => 'required|numeric|exists:metrics,id',
            'supplier_id' => 'required|numeric|exists:suppliers,id',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {

            do {
                $year = date('Y', strtotime(Carbon::now()));
                $productCode = Product::generateProductCode($year);
            } while (Product::where('product_code', $productCode)->exists());

            $product = Product::where('product_name', ucwords($request->product_name))->withTrashed()->first();

            if ($product) {
                $product->restore();

                $updateProduct = $product->update([
                    'sub_category_id' => $request->sub_category_id,
                    'brand_id' => $request->brand_id,
                    'storage_id' => $request->storage_id,
                    'stock' => $request->stock,
                    'unit' => $request->unit_id,
                    'measurement' => $request->measurement,
                    'metric_id' => $request->metric_id,
                    'supplier_id' => $request->supplier_id,
                    'purchase_price' => $request->purchase_price,
                    'image' => $request->image,
                    'is_active' => 1,
                    'user_id' => auth()->user()->id,
                ]);

                if (!$updateProduct) {
                    throw new HttpException(400, 'Gagal mengubah data produk');
                }
            } else {
                $createProduct = Product::create([
                    'product_code' => $productCode,
                    'product_name' => ucwords($request->product_name),
                    'sub_category_id' => $request->sub_category_id,
                    'brand_id' => $request->brand_id,
                    'storage_id' => $request->storage_id,
                    'stock' => $request->stock,
                    'unit' => $request->unit_id,
                    'measurement' => $request->measurement,
                    'metric_id' => $request->metric_id,
                    'supplier_id' => $request->supplier_id,
                    'purchase_price' => $request->purchase_price,
                    'image' => $request->image,
                    'user_id' => auth()->user()->id,
                ]);

                if (!$createProduct) {
                    throw new HttpException(400, 'Gagal membuat data produk');
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil membuat data produk',
            ], 201);
        } catch (HttpException $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
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
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'product_code' => 'required|string',
            'product_name' => 'required|string',
            'purchase_price' => 'required|double',
            'stock' => 'required|numeric',
            'measurement' => 'required|numeric',
            'image' => 'nullable|string',

            'sub_category_id' => 'required|numeric|exists:sub_categories,id',
            'brand_id' => 'required|numeric|exists:brands,id',
            'storage_id' => 'required|numeric|exists:storages,id',
            'unit_id' => 'required|numeric|exists:units,id',
            'metric_id' => 'required|numeric|exists:metrics,id',
            'supplier_id' => 'required|numeric|exists:suppliers,id',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateProduct = $product->update([
            'sub_category_id' => $request->sub_category_id,
            'brand_id' => $request->brand_id,
            'storage_id' => $request->storage_id,
            'stock' => $request->stock,
            'unit' => $request->unit_id,
            'measurement' => $request->measurement,
            'metric_id' => $request->metric_id,
            'supplier_id' => $request->supplier_id,
            'purchase_price' => $request->purchase_price,
            'image' => $request->image,
            'is_active' => 1,
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateProduct) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data produk',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data produk',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($product->id);
        } else {

            if (!$product->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data produk',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data produk',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan produk' : 'Berhasil menghapus data produk',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateProduct = Product::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateProduct) {
            return response()->json([

                'status' => 'error',
                'message' => 'Gagal menonaktifkan produk',
            ], 400);
        }
    }
}
