<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductInfo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductController extends Controller
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

        $type = $request->type;
        $supplierName = $request->supplier_name;
        $inventory_id = $request->inventory_id;

        $products = Product::with([
            'subCategory.category',
            'brand',
            'storage',
            'unit',
            'metric',
            'supplier',
            'inventoryProductInfo' => function ($query) use ($inventory_id) {
                $query->where('inventory_id', $inventory_id);
            }
        ])
            ->when($search, function ($query, $search) {
                return $query->where('product_name', 'LIKE', '%' . $search . '%')
                    ->orWhere('product_code', 'LIKE', '%' . $search . '%');
            })
            ->when($supplierName, function ($query, $supplierName) {
                return $query->whereHas('supplier', function ($query) use ($supplierName) {
                    $query->where('supplier_name', 'LIKE', '%' . $supplierName . '%');
                });
            })
            ->when($type, function ($query, $type) {
                return $query->whereIn('product_type', $type);
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
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string',
            'brand_id' => Rule::requiredIf($request->product_type == 'raw'),
            'sub_category_id' => Rule::requiredIf($request->product_type == 'raw'),
            'min_stock' => Rule::requiredIf($request->product_type == 'raw'),
            'automatic_use' => Rule::requiredIf($request->product_type == 'raw'),
            'purchase_price' => Rule::requiredIf($request->product_type == 'raw'),
            'selling_price' => Rule::requiredIf($request->product_type == 'finished'),
            'inventory_id' => 'nullable|numeric|exists:inventories,id',
            'initial_stock' => 'nullable|numeric',
            'unit_id' => Rule::requiredIf($request->product_type == 'raw'),
            'measurement' => Rule::requiredIf($request->product_type == 'raw'),
            'metric_id' => Rule::requiredIf($request->product_type == 'raw'),
            'image' => 'nullable',
            'storage_id' => Rule::requiredIf($request->product_type == 'raw'),
            'supplier_id' => Rule::requiredIf($request->product_type == 'raw'),
            'product_type' => 'required|string|in:raw,semi-finished,finished',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            if (!$request->product_code) {
                do {
                    $year = date('Y', strtotime(Carbon::now()));
                    $productCode = Product::generateProductCode($year, $request->product_type);
                } while (Product::where('product_code', $productCode)->exists());
            } else {
                $productCode = $request->product_code;
            }

            $createProduct = Product::create([
                'product_code' => $productCode,
                'product_name' => ucwords($request->product_name),
                'brand_id' => $request->brand_id,
                'sub_category_id' => $request->sub_category_id ?? NULL,
                'min_stock' => $request->min_stock,
                'stock' => $request->initial_stock ?? 0,
                'automatic_use' => $request->automatic_use ?? 0,
                'purchase_price' => $request->product_type == 'raw' ? $request->purchase_price : 0,
                'selling_price' => $request->product_type == 'finished' ? $request->selling_price : 0,
                'unit_id' => $request->unit_id,
                'measurement' => $request->measurement ?? 0,
                'metric_id' => $request->metric_id,
                'image' => $request->file('image')
                    ? $request->file('image')->store('images', 'public')
                    : null,
                'storage_id' => $request->storage_id,
                'supplier_id' => $request->supplier_id,
                'product_type' => $request->product_type,
                'user_id' => auth()->user()->id,
            ]);

            if (!$createProduct) {
                throw new HttpException(400, 'Gagal membuat data produk');
            }

            if ($request->inventory_id && $request->initial_stock > 0) {
                $createProductInfo = ProductInfo::create([
                    'product_id' => $createProduct->id,
                    'total_stock' => $request->initial_stock,
                    'total_stock_out' => 0,
                    'inventory_id' => $request->inventory_id,
                    'user_id' => auth()->user()->id,
                ]);

                if (!$createProductInfo) {
                    throw new HttpException(400, 'Gagal membuat data produk info');
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
            'product_name' => 'required|string',
            'brand_id' => Rule::requiredIf($request->product_type == 'raw'),
            'sub_category_id' => Rule::requiredIf($request->product_type == 'raw'),
            'min_stock' => Rule::requiredIf($request->product_type == 'raw'),
            'automatic_use' => Rule::requiredIf($request->product_type == 'raw'),
            'purchase_price' => Rule::requiredIf($request->product_type == 'raw'),
            'selling_price' => Rule::requiredIf($request->product_type == 'finished'),
            'inventory_id' => 'nullable|numeric|exists:inventories,id',
            'initial_stock' => 'nullable|numeric',
            'unit_id' => Rule::requiredIf($request->product_type == 'raw'),
            'measurement' => Rule::requiredIf($request->product_type == 'raw'),
            'metric_id' => Rule::requiredIf($request->product_type == 'raw'),
            'image' => 'nullable',
            'storage_id' => Rule::requiredIf($request->product_type == 'raw'),
            'supplier_id' => Rule::requiredIf($request->product_type == 'raw'),
            'product_type' => 'required|string|in:raw,semi-finished,finished',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $updateProduct = $product->update([
            'product_name' => ucwords($request->product_name),
            'brand_id' => $request->brand_id,
            'sub_category_id' => $request->sub_category_id,
            'min_stock' => $request->min_stock,
            'automatic_use' => $request->automatic_use,
            'purchase_price' => $request->product_type == 'raw' ? $request->purchase_price : 0,
            'selling_price' => $request->product_type == 'finished' ? $request->selling_price : 0,
            'unit_id' => $request->unit_id,
            'measurement' => $request->measurement,
            'metric_id' => $request->metric_id,
            'image' => $product->image && $request->file('image') && $request->file('image')->isValid()
                ? $request->file('image')->store('images', 'public')
                : NULL,
            'storage_id' => $request->storage_id,
            'supplier_id' => $request->supplier_id,
            'product_type' => $request->product_type,
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
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            if (!$product->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data produk',
                ], 400);
            }
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
